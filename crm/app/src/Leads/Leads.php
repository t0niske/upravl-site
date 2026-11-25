<?php

declare(strict_types=1);

namespace App\Leads;

use App\Support\Db;
use App\Visits\Visits;
use DateTimeImmutable;

/** Заявки: запись с сайта (lead.php), чтение, статусы, комментарии, поиск, удаление по сроку. */
final class Leads
{
    /**
     * Новая заявка с формы сайта. Вызывает public/lead.php после проверки полей.
     * Источник берётся из захода на сайт (счётчик), если он есть.
     *
     * @param array{name?: string, phone: string, topic?: string, page?: string, message?: string, quiz?: string,
     *              ip?: string, user_agent?: string, visitor_id?: string, visit_id?: string} $data
     */
    public static function createFromSite(array $data): int
    {
        $now = Clock::sql();
        $phone = Phone::normalize($data['phone']) ?? mb_substr(preg_replace('/[^\d+]/', '', $data['phone']) ?? '', 0, 16);
        $vid = self::id($data['visitor_id'] ?? null);
        $sid = self::id($data['visit_id'] ?? null);
        $visit = $sid !== null ? Visits::findBySid($sid) : null;

        if ($visit !== null && $vid !== null && $visit['vid'] !== $vid) {
            $visit = null;
        }

        $id = self::insert([
            'created_at' => $now,
            'updated_at' => $now,
            'name' => mb_substr(trim($data['name'] ?? ''), 0, 100),
            'phone' => $phone,
            'phone_raw' => mb_substr($data['phone'], 0, 64),
            'topic' => self::nullable($data['topic'] ?? null, 150),
            'page_url' => self::nullable($data['page'] ?? null, 300),
            'message' => self::nullable($data['message'] ?? null, 2000),
            'quiz' => self::nullable($data['quiz'] ?? null, 600),
            'source' => $visit['source'] ?? 'Сайт',
            'ip' => self::nullable($data['ip'] ?? null, 45),
            'user_agent' => self::nullable($data['user_agent'] ?? null, 255),
            'status' => Status::NEW,
        ]);

        self::event($id, 'created', null, 'Заявка с сайта' . (($data['page'] ?? '') !== '' ? ': ' . $data['page'] : ''));
        Visits::linkLead($id, $vid, $visit !== null ? $sid : null);

        return $id;
    }

    public static function find(int $id): ?array
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM leads WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function insert(array $fields): int
    {
        $columns = array_keys($fields);
        Db::pdo()->prepare('INSERT INTO leads (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')')
            ->execute(array_values($fields));

        return (int) Db::pdo()->lastInsertId();
    }

    public static function update(int $id, array $fields): void
    {
        $fields['updated_at'] = Clock::sql();
        $sets = implode(', ', array_map(static fn (string $c): string => "$c = ?", array_keys($fields)));
        Db::pdo()->prepare("UPDATE leads SET $sets WHERE id = ?")->execute([...array_values($fields), $id]);
    }

    public static function setStatus(int $id, string $status, Actor $actor): void
    {
        $lead = self::find($id);

        if ($lead === null || !Status::valid($status) || $lead['status'] === $status) {
            return;
        }

        self::update($id, [
            'status' => $status,
            'closed_at' => in_array($status, Status::CLOSED, true) ? Clock::sql() : null,
        ]);
        self::event($id, 'status', $actor, Status::label($lead['status']) . ' → ' . Status::label($status));
        Audit::log($actor, 'lead_status', $id, Status::label($status));
    }

    public static function comment(int $id, string $text, Actor $actor): void
    {
        $text = trim($text);

        if ($text === '' || self::find($id) === null) {
            return;
        }

        self::event($id, 'comment', $actor, mb_substr($text, 0, 2000));
        self::update($id, []);
        Audit::log($actor, 'lead_comment', $id);
    }

    public static function delete(int $id, Actor $actor): void
    {
        Db::pdo()->prepare('UPDATE visits SET lead_id = NULL WHERE lead_id = ?')->execute([$id]);
        Db::pdo()->prepare('DELETE FROM leads WHERE id = ?')->execute([$id]);
        Audit::log($actor, 'lead_delete', $id);
    }

    public static function event(int $leadId, string $type, ?Actor $actor, ?string $text): void
    {
        Db::pdo()->prepare('INSERT INTO lead_events (lead_id, created_at, type, staff_id, actor, text) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$leadId, Clock::sql(), $type, $actor?->staffId, $actor?->name ?? 'Сайт', $text]);
    }

    /** @return list<array> */
    public static function events(int $leadId): array
    {
        $stmt = Db::pdo()->prepare('SELECT * FROM lead_events WHERE lead_id = ? ORDER BY id DESC');
        $stmt->execute([$leadId]);

        return $stmt->fetchAll();
    }

    /** Другие заявки с того же номера. */
    public static function samePhone(array $lead): array
    {
        $stmt = Db::pdo()->prepare('SELECT id, created_at, status FROM leads WHERE phone = ? AND id <> ? ORDER BY id DESC LIMIT 20');
        $stmt->execute([$lead['phone'], $lead['id']]);

        return $stmt->fetchAll();
    }

    /**
     * Список с фильтрами: q (имя, телефон, №), status (new|…|active), source, from/to.
     *
     * @return array{rows: list<array>, total: int}
     */
    public static function search(array $f, int $page, int $perPage = 50): array
    {
        [$where, $params] = self::where($f);
        $pdo = Db::pdo();

        $count = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE $where");
        $count->execute($params);

        $stmt = $pdo->prepare("SELECT * FROM leads WHERE $where ORDER BY id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage));
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => (int) $count->fetchColumn()];
    }

    /** Все заявки по фильтрам — для выгрузки. @return list<array> */
    public static function all(array $f): array
    {
        [$where, $params] = self::where($f);
        $stmt = Db::pdo()->prepare("SELECT * FROM leads WHERE $where ORDER BY id DESC LIMIT 20000");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return list<string> */
    public static function sources(): array
    {
        return Db::pdo()->query('SELECT DISTINCT source FROM leads ORDER BY source')->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function countNew(): int
    {
        return (int) Db::pdo()->query("SELECT COUNT(*) FROM leads WHERE status = 'new'")->fetchColumn();
    }

    /**
     * 152-ФЗ: заявки, по которым человек не стал клиентом, удаляются через LEADS_RETENTION_MONTHS (по умолчанию 12)
     * после последнего изменения. Вместе с заявкой уходят её история и заходы посетителя (их чистит Visits::cleanup).
     */
    public static function cleanup(DateTimeImmutable $now, int $months): int
    {
        $stmt = Db::pdo()->prepare('SELECT id FROM leads WHERE status <> ? AND updated_at < ?');
        $stmt->execute([Status::CLIENT, Clock::sql($now->modify('-' . max(1, $months) . ' months'))]);
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($ids as $id) {
            self::delete((int) $id, Actor::system());
        }

        return count($ids);
    }

    /** @return array{0: string, 1: list<mixed>} */
    private static function where(array $f): array
    {
        $where = ['1 = 1'];
        $params = [];
        $q = trim((string) ($f['q'] ?? ''));

        if ($q !== '') {
            $digits = preg_replace('/\D+/', '', $q);

            if (ctype_digit($q) && strlen($q) <= 6) {
                $where[] = '(id = ? OR phone LIKE ?)';
                $params[] = (int) $q;
                $params[] = '%' . $q . '%';
            } elseif ($digits !== '' && strlen($digits) >= 4) {
                $where[] = 'phone LIKE ?';
                $params[] = '%' . substr($digits, -10) . '%';
            } else {
                $where[] = 'LOWER(name) LIKE ?';
                $params[] = '%' . mb_strtolower($q) . '%';
            }
        }

        $status = (string) ($f['status'] ?? '');

        if ($status === 'active') {
            $where[] = "status IN ('new', 'in_work')";
        } elseif (Status::valid($status)) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        if (($f['source'] ?? '') !== '') {
            $where[] = 'source = ?';
            $params[] = $f['source'];
        }

        if (($f['from'] ?? null) instanceof DateTimeImmutable) {
            $where[] = 'created_at >= ?';
            $params[] = Clock::sql($f['from']);
        }

        if (($f['to'] ?? null) instanceof DateTimeImmutable) {
            $where[] = 'created_at < ?';
            $params[] = Clock::sql($f['to']);
        }

        return [implode(' AND ', $where), $params];
    }

    private static function id(?string $value): ?string
    {
        return $value !== null && preg_match('/^[A-Za-z0-9_-]{8,40}$/', $value) ? $value : null;
    }

    private static function nullable(?string $value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
