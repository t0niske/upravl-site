<?php

declare(strict_types=1);

namespace App\Bot;

use App\Leads\Clock;
use App\Leads\Log;
use App\Support\Db;
use App\Support\Env;
use DateTimeImmutable;

/**
 * Канал MAX: посты по расписанию. Бот — администратор канала с правом публиковать.
 * id канала: MAX_CHANNEL_ID в .env или storage/max-channel.json (пишется сам, когда бота добавляют в канал).
 */
final class Channel
{
    public const STATUSES = ['draft' => 'Черновик', 'approved' => 'Одобрен', 'published' => 'Опубликован', 'failed' => 'Ошибка'];
    public const MAX_TEXT = 4000;

    private static function file(): string
    {
        return APP_ROOT . '/storage/max-channel.json';
    }

    /** @return ?array{chat_id: string, title: string, added_at: string} */
    public static function info(): ?array
    {
        $env = (string) Env::get('MAX_CHANNEL_ID', '');

        if ($env !== '') {
            return ['chat_id' => $env, 'title' => '', 'added_at' => ''];
        }

        $data = is_file(self::file()) ? json_decode((string) file_get_contents(self::file()), true) : null;

        return is_array($data) && !empty($data['chat_id']) ? $data : null;
    }

    /** Бота добавили в канал — запоминаем id и название. */
    public static function remember(string $chatId): void
    {
        $chat = MaxApi::request('GET', '/chats/' . rawurlencode($chatId))['body'] ?? [];
        file_put_contents(self::file(), json_encode([
            'chat_id' => $chatId,
            'title' => (string) ($chat['title'] ?? ''),
            'added_at' => Clock::sql(),
        ], JSON_UNESCAPED_UNICODE));
    }

    public static function find(int $id): ?array
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM channel_posts WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** @return list<array> предстоящие — по времени, опубликованные — новые сверху */
    public static function upcoming(): array
    {
        return Db::pdo()->query("SELECT * FROM channel_posts WHERE status IN ('draft', 'approved', 'failed') ORDER BY publish_at, id")->fetchAll();
    }

    /** @return list<array> */
    public static function published(int $limit = 50): array
    {
        return Db::pdo()->query("SELECT * FROM channel_posts WHERE status = 'published' ORDER BY published_at DESC LIMIT $limit")->fetchAll();
    }

    public static function save(?int $id, array $fields, string $by): int
    {
        $fields['updated_at'] = Clock::sql();
        $fields['updated_by'] = mb_substr($by, 0, 100);

        if ($id === null) {
            $fields['created_at'] = $fields['updated_at'];
            $cols = array_keys($fields);
            Db::pdo()->prepare('INSERT INTO channel_posts (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')
                ->execute(array_values($fields));

            return (int) Db::pdo()->lastInsertId();
        }

        $sets = implode(', ', array_map(static fn (string $c): string => "$c = ?", array_keys($fields)));
        Db::pdo()->prepare("UPDATE channel_posts SET $sets WHERE id = ?")->execute([...array_values($fields), $id]);

        return $id;
    }

    public static function delete(int $id): void
    {
        Db::pdo()->prepare("DELETE FROM channel_posts WHERE id = ? AND status <> 'published'")->execute([$id]);
    }

    /** Опубликовать одобренные посты, время которых наступило (не больше 3 за запуск). */
    public static function publishDue(DateTimeImmutable $now): int
    {
        $stmt = Db::pdo()->prepare("SELECT * FROM channel_posts WHERE status = 'approved' AND publish_at <= ? ORDER BY publish_at, id LIMIT 3");
        $stmt->execute([Clock::sql($now)]);
        $count = 0;

        foreach ($stmt->fetchAll() as $post) {
            $count += self::publish($post) ? 1 : 0;
        }

        return $count;
    }

    /** Публикует пост сейчас. Ошибку записывает в пост (status = failed). */
    public static function publish(array $post): bool
    {
        $channel = self::info();

        if ($channel === null) {
            self::save((int) $post['id'], ['status' => 'failed', 'error' => 'Бот ещё не добавлен в канал администратором'], 'Бот');

            return false;
        }

        $res = MaxApi::request('POST', '/messages', ['chat_id' => $channel['chat_id']], [
            'text' => mb_substr((string) $post['text'], 0, self::MAX_TEXT),
            'notify' => true,
        ]);
        $mid = (string) ($res['body']['message']['body']['mid'] ?? '');

        if ($res['code'] !== 200 || $mid === '') {
            $error = 'MAX ответил ' . $res['code'] . ': ' . mb_substr((string) ($res['body']['message'] ?? $res['body']['code'] ?? 'нет ответа'), 0, 200);
            self::save((int) $post['id'], ['status' => 'failed', 'error' => $error], 'Бот');
            Log::write('bot.log', "Пост №{$post['id']} не опубликован: $error");

            return false;
        }

        self::save((int) $post['id'], ['status' => 'published', 'mid' => $mid, 'published_at' => Clock::sql(), 'error' => null], 'Бот');

        if ((int) $post['pin'] === 1) {
            $pin = MaxApi::request('PUT', '/chats/' . rawurlencode($channel['chat_id']) . '/pin', [], ['message_id' => $mid, 'notify' => false]);

            if ($pin['code'] !== 200) {
                self::save((int) $post['id'], ['error' => 'Опубликован, но не закреплён: закрепите вручную'], 'Бот');
            }
        }

        return true;
    }
}
