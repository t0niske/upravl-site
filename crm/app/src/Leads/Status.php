<?php

declare(strict_types=1);

namespace App\Leads;

/** Статусы заявки. Пока без менеджеров и очереди звонков: только итог работы с заявкой. */
final class Status
{
    public const NEW = 'new';
    public const IN_WORK = 'in_work';
    public const CLIENT = 'client';
    public const REFUSED = 'refused';
    public const JUNK = 'junk';

    public const LABELS = [
        self::NEW => 'Новая',
        self::IN_WORK => 'В работе',
        self::CLIENT => 'Стал клиентом',
        self::REFUSED => 'Отказ',
        self::JUNK => 'Спам',
    ];

    /** Заявка закрыта: дальше с ней не работают. */
    public const CLOSED = [self::CLIENT, self::REFUSED, self::JUNK];

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? $status;
    }

    public static function valid(string $status): bool
    {
        return isset(self::LABELS[$status]);
    }
}
