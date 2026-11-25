<?php

declare(strict_types=1);

namespace App\Bot;

use App\Leads\Clock;
use App\Leads\Phone;
use App\Support\Env;

/**
 * Уведомление о заявке из бота: в группу MAX (MAX_LEADS_CHAT_ID) и письмом (LEADS_MAIL_TO) — как заявки с сайта.
 */
final class Notify
{
    public static function lead(int $leadId, array $book, string $details, ?string $quiz, string $source, bool $repeat): void
    {
        $num = $leadId > 0 ? " №$leadId" : '';
        $lines = [
            $repeat ? "Повторная запись по заявке$num из бота MAX" : "Новая заявка$num из бота MAX",
            'Имя: ' . (($book['name'] ?? '') !== '' ? $book['name'] : '—'),
            'Телефон: ' . Phone::format((string) $book['phone']) . (!empty($book['phone_verified']) ? ' (номер из MAX)' : ''),
            $details,
        ];

        if ($quiz !== null && $quiz !== '') {
            $lines[] = $quiz;
        }

        $lines[] = 'Источник: ' . $source;
        $lines[] = 'Время: ' . Clock::now()->format('d.m.Y H:i');

        if ($leadId > 0) {
            $lines[] = 'В CRM: ' . rtrim((string) Env::get('APP_URL', 'https://bankrotstvo70.ru'), '/') . '/crm/lead/' . $leadId;
        }

        $text = implode("\n", $lines);
        $chat = (string) Env::get('MAX_LEADS_CHAT_ID', '');

        if ($chat !== '') {
            MaxApi::sendToChat($chat, $text);
        }

        $to = (string) Env::get('LEADS_MAIL_TO', '');

        if ($to !== '' && !MaxApi::dryRun()) {
            $headers = [
                'From: =?UTF-8?B?' . base64_encode('Бот MAX bankrotstvo70.ru') . '?= <noreply@bankrotstvo70.ru>',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];
            $subject = '=?UTF-8?B?' . base64_encode(($repeat ? 'Повторная запись' : 'Заявка из бота MAX') . ' — ' . Phone::format((string) $book['phone'])) . '?=';
            @mail($to, $subject, $text, implode("\r\n", $headers));
        }
    }
}
