<?php

declare(strict_types=1);

namespace App\Bot;

use App\Leads\Clock;
use App\Support\Db;
use DateTimeImmutable;

/** Хранение собеседников бота, их переписки и обработанных событий. */
final class Store
{
    /** Собеседник по id пользователя MAX; создаётся при первом обращении. */
    public static function user(string $maxUserId, string $name = '', ?string $source = null): array
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM bot_users WHERE max_user_id = ?');
        $stmt->execute([$maxUserId]);
        $row = $stmt->fetch();

        if ($row) {
            return $row;
        }

        $now = Clock::sql();
        Db::pdo()->prepare('INSERT INTO bot_users (max_user_id, name, source, state, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$maxUserId, mb_substr($name, 0, 100), $source ?? 'MAX-бот', 'menu', $now, $now]);

        $stmt->execute([$maxUserId]);

        return $stmt->fetch();
    }

    public static function update(int $id, array $fields): void
    {
        $fields['updated_at'] = Clock::sql();
        $sets = implode(', ', array_map(static fn (string $c): string => "$c = ?", array_keys($fields)));
        Db::pdo()->prepare("UPDATE bot_users SET $sets WHERE id = ?")->execute([...array_values($fields), $id]);
    }

    /** @return array<string, mixed> */
    public static function data(array $user): array
    {
        $d = json_decode((string) ($user['data'] ?? ''), true);

        return is_array($d) ? $d : [];
    }

    public static function setData(int $id, array $data): void
    {
        self::update($id, ['data' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
    }

    public static function message(int $userId, string $dir, string $text): void
    {
        Db::pdo()->prepare('INSERT INTO bot_messages (bot_user_id, created_at, dir, text) VALUES (?, ?, ?, ?)')
            ->execute([$userId, Clock::sql(), $dir, mb_substr($text, 0, 4000)]);
    }

    /** @return list<array{dir: string, text: string, created_at: string}> последние реплики, по порядку */
    public static function messages(int $userId, int $limit = 20): array
    {
        $stmt = Db::pdo()->prepare("SELECT dir, text, created_at FROM bot_messages WHERE bot_user_id = ? ORDER BY id DESC LIMIT $limit");
        $stmt->execute([$userId]);

        return array_reverse($stmt->fetchAll());
    }

    /** Повтор того же события от MAX — true. */
    public static function seen(string $updateId): bool
    {
        try {
            Db::pdo()->prepare('INSERT INTO bot_updates (id, created_at) VALUES (?, ?)')->execute([$updateId, Clock::sql()]);

            return false;
        } catch (\PDOException) {
            return true;
        }
    }

    /**
     * Сроки хранения: собеседники без заявки — VISITS_RETENTION_MONTHS (как заходы на сайт),
     * с заявкой — пока жива заявка; события MAX — двое суток.
     */
    public static function cleanup(DateTimeImmutable $now, int $months): int
    {
        $pdo = Db::pdo();
        $pdo->prepare('DELETE FROM bot_updates WHERE created_at < ?')->execute([Clock::sql($now->modify('-2 days'))]);

        $stmt = $pdo->prepare('SELECT id FROM bot_users WHERE updated_at < ? AND (lead_id IS NULL OR lead_id NOT IN (SELECT id FROM leads))');
        $stmt->execute([Clock::sql($now->modify('-' . max(1, $months) . ' months'))]);
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($ids as $id) {
            $pdo->prepare('DELETE FROM bot_messages WHERE bot_user_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM bot_users WHERE id = ?')->execute([$id]);
        }

        return count($ids);
    }
}
