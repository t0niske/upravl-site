<?php

declare(strict_types=1);

namespace App\Bot;

use DateTimeImmutable;

/**
 * Готовые тексты и кнопки бота. Факты — только с сайта bankrotstvo70.ru (src/data/site.ts, seo/BRANDCORE.md).
 */
final class Texts
{
    public const SITE = 'https://bankrotstvo70.ru';
    public const PHONE = '+7 (3822) 30-02-15';
    public const PHONE2 = '+7 (913) 889-35-36';
    public const ADDRESS = 'Томск, ул. Тверская, 14, оф. 1';
    public const HOURS = 'Пн–Пт с 9:00 до 18:00, по предварительной записи';
    public const CONSENT_URL = self::SITE . '/soglasie-na-obrabotku-personalnyh-dannyh/';

    public static function greeting(bool $short = false): string
    {
        if ($short) {
            return "Здравствуйте! Я ИИ-помощник арбитражного управляющего Юрия Антонца.\n"
                . 'Запишу вас на бесплатную консультацию к Юрию Анатольевичу — это займёт минуту.';
        }

        return "Здравствуйте! Я ИИ-помощник арбитражного управляющего Юрия Антонца, Томск.\n\n"
            . "Помогу понять, подходит ли вам банкротство, отвечу на общие вопросы и запишу на бесплатную консультацию к Юрию Анатольевичу лично.\n\n"
            . 'Я программа: мои ответы — общая информация, а не юридическое заключение по вашему делу.';
    }

    public static function menuPrompt(): string
    {
        return 'Чем помочь? Выберите кнопку или просто напишите вопрос.';
    }

    /** @return list<list<array>> */
    public static function menu(): array
    {
        return [
            [self::cb('Подходит ли мне банкротство', 'quiz')],
            [self::cb('Записаться на консультацию', 'book')],
            [self::cb('Задать вопрос', 'ask')],
            [self::cb('Сколько стоит', 'price'), self::cb('Позвонить', 'contacts')],
        ];
    }

    /** Кнопки под ответом: запись, ещё вопрос, меню. */
    public static function afterAnswer(): array
    {
        return [
            [self::cb('Записаться на консультацию', 'book')],
            [self::cb('Ещё вопрос', 'ask'), self::cb('Меню', 'menu')],
        ];
    }

    public static function askPrompt(): string
    {
        return 'Напишите вопрос одним сообщением. Например: «Заберут ли машину?» или «Что будет с ипотекой?»';
    }

    public static function price(): string
    {
        return "Сколько стоит банкротство гражданина:\n\n"
            . "• Первая консультация — бесплатно.\n"
            . "• Юридическое сопровождение под ключ — от 125 000 ₽.\n"
            . "• Рассрочка — от 10 000 ₽ в месяц.\n"
            . "• Отдельно по закону — 25 000 ₽ на депозит суда для вознаграждения финансового управляющего, а также публикации в ЕФРСБ и «Коммерсанте».\n\n"
            . 'Точную сумму под ваш случай Юрий Анатольевич назовёт на консультации. Цены на другие услуги — на сайте.';
    }

    public static function priceKeyboard(): array
    {
        return [
            [self::cb('Записаться на консультацию', 'book')],
            [self::link('Цены на сайте', self::SITE . '/yuridicheskie-uslugi/'), self::cb('Меню', 'menu')],
        ];
    }

    public static function contacts(): string
    {
        return "Телефоны: " . self::PHONE . ', ' . self::PHONE2 . "\n"
            . 'Офис: ' . self::ADDRESS . "\n"
            . self::HOURS . "\n\n"
            . 'Или оставьте номер — Юрий Анатольевич перезвонит сам.';
    }

    public static function contactsKeyboard(): array
    {
        return [
            [self::cb('Записаться на консультацию', 'book')],
            [self::link('Сайт', self::SITE . '/kontakty/'), self::cb('Меню', 'menu')],
        ];
    }

    /** Когда перезвонят: в рабочее время — «сегодня», иначе — ближайший рабочий день. */
    public static function whenCallback(DateTimeImmutable $now): string
    {
        $dow = (int) $now->format('N');
        $hour = (int) $now->format('G');

        if ($dow <= 5 && $hour >= 9 && $hour < 17) {
            return 'Юрий Анатольевич свяжется с вами сегодня в рабочее время.';
        }

        $next = $now->modify('+1 day');
        while ((int) $next->format('N') > 5) {
            $next = $next->modify('+1 day');
        }

        if ($dow <= 5 && $hour < 9) {
            $next = $now;
        }

        $days = [1 => 'в понедельник', 2 => 'во вторник', 3 => 'в среду', 4 => 'в четверг', 5 => 'в пятницу'];
        $word = $next->format('Y-m-d') === $now->format('Y-m-d') ? 'сегодня'
            : ($next->format('Y-m-d') === $now->modify('+1 day')->format('Y-m-d') ? 'завтра' : $days[(int) $next->format('N')]);

        return "Юрий Анатольевич свяжется с вами $word после 9:00.";
    }

    public static function cb(string $text, string $payload): array
    {
        return ['type' => 'callback', 'text' => $text, 'payload' => $payload];
    }

    public static function link(string $text, string $url): array
    {
        return ['type' => 'link', 'text' => $text, 'url' => $url];
    }
}
