<?php

declare(strict_types=1);

namespace App\Leads;

use App\Support\Db;

/** Журнал действий: кто и когда открыл, изменил или удалил заявку, вход в админку, сотрудники. */
final class Audit
{
    public const LABELS = [
        'login' => 'Вход',
        'login_failed' => 'Неудачный вход',
        'logout' => 'Выход',
        'lead_view' => 'Открыл заявку',
        'visit_view' => 'Открыл заход на сайт',
        'lead_delete' => 'Удалил заявку',
        'lead_status' => 'Сменил статус',
        'lead_comment' => 'Комментарий',
        'export' => 'Выгрузка',
        'staff' => 'Сотрудники',
        'password' => 'Смена пароля',
        'retention' => 'Автоудаление',
        'channel' => 'Канал MAX',
    ];

    public static function log(?Actor $actor, string $action, ?int $leadId = null, ?string $details = null): void
    {
        Db::pdo()->prepare(
            'INSERT INTO audit_log (created_at, staff_id, actor, action, lead_id, details, ip) VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            Clock::sql(),
            $actor?->staffId,
            mb_substr($actor?->name ?? 'Система', 0, 100),
            $action,
            $leadId,
            $details === null ? null : mb_substr($details, 0, 1000),
            PHP_SAPI === 'cli' ? null : ($_SERVER['REMOTE_ADDR'] ?? null),
        ]);
    }
}
