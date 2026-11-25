<?php

declare(strict_types=1);

namespace App\Bot;

/**
 * База знаний бота — тексты страниц сайта (kb/site.md, собирает bin/build-kb.php).
 * Делится на куски по заголовкам и вопросам FAQ; к вопросу подбираются самые похожие куски
 * (совпадение основ слов) — их и видит ИИ. Никаких внешних сервисов поиска.
 */
final class Kb
{
    /** @var ?list<array{title: string, url: string, text: string, faq: bool, stems: array<string, true>}> */
    private static ?array $chunks = null;

    private const STOP = ['банкр', 'котор', 'может', 'можно', 'нужно', 'если', 'когда', 'чтобы', 'этого', 'будет', 'есть',
        'меня', 'мной', 'мене', 'тоже', 'также', 'только', 'очень', 'после', 'перед', 'какие', 'какой', 'какая', 'сколь',
        'свой', 'своих', 'вашей', 'ваши', 'вашего', 'здрав', 'добры', 'подск', 'скажи', 'пожал'];

    public static function file(): string
    {
        return APP_ROOT . '/kb/site.md';
    }

    /**
     * @return list<array{title: string, url: string, text: string, faq: bool}>
     */
    public static function search(string $query, int $limit = 6): array
    {
        $chunks = self::chunks();
        $q = array_keys(self::stems($query));

        if ($q === [] || $chunks === []) {
            return [];
        }

        // Редкие слова важнее частых: вес = log(всего кусков / кусков со словом).
        $total = count($chunks);
        $scored = [];

        foreach ($chunks as $i => $c) {
            $score = 0.0;

            foreach ($q as $s) {
                if (isset($c['stems'][$s])) {
                    $df = self::df($s);
                    $score += log(1 + $total / max(1, $df));
                }
            }

            if ($score > 0) {
                $scored[$i] = $score * ($c['faq'] ? 1.3 : 1.0);
            }
        }

        arsort($scored);
        $out = [];

        foreach (array_slice($scored, 0, $limit, true) as $i => $score) {
            $c = $chunks[$i];
            $out[] = ['title' => $c['title'], 'url' => $c['url'], 'text' => $c['text'], 'faq' => $c['faq'], 'score' => $score];
        }

        return $out;
    }

    /** @return array<string, true> основы слов: нижний регистр, ё→е, первые 5 букв слов от 4 букв. */
    public static function stems(string $text): array
    {
        $text = str_replace('ё', 'е', mb_strtolower($text));
        preg_match_all('/[\p{L}\d]{4,}/u', $text, $m);
        $out = [];

        foreach ($m[0] as $w) {
            $s = mb_substr($w, 0, 5);

            if (!in_array($s, self::STOP, true)) {
                $out[$s] = true;
            }
        }

        return $out;
    }

    /** @var array<string, int> */
    private static array $df = [];

    private static function df(string $stem): int
    {
        if (self::$df === []) {
            foreach (self::chunks() as $c) {
                foreach ($c['stems'] as $s => $_) {
                    self::$df[$s] = (self::$df[$s] ?? 0) + 1;
                }
            }
        }

        return self::$df[$stem] ?? 0;
    }

    /** @return list<array{title: string, url: string, text: string, faq: bool, stems: array<string, true>}> */
    private static function chunks(): array
    {
        if (self::$chunks !== null) {
            return self::$chunks;
        }

        self::$chunks = [];
        $raw = is_file(self::file()) ? (string) file_get_contents(self::file()) : '';

        // Страницы начинаются с «# Заголовок» и строки «URL: …».
        foreach (preg_split('/^(?=# )/mu', $raw) ?: [] as $page) {
            if (!preg_match('/^# (.+)\R+URL: (\S+)/u', $page, $h)) {
                continue;
            }

            $body = trim(substr($page, strlen($h[0])));

            foreach (preg_split('/^(?=## |Вопрос: )/mu', $body) ?: [] as $part) {
                $part = trim($part);

                if (mb_strlen($part) < 40) {
                    continue;
                }

                foreach (self::split($part, 1400) as $text) {
                    self::$chunks[] = [
                        'title' => trim($h[1]),
                        'url' => $h[2],
                        'text' => $text,
                        'faq' => str_starts_with($part, 'Вопрос: '),
                        'stems' => self::stems($h[1] . ' ' . $text),
                    ];
                }
            }
        }

        return self::$chunks;
    }

    /** @return list<string> длинный раздел — по абзацам, не длиннее $max символов. */
    private static function split(string $text, int $max): array
    {
        if (mb_strlen($text) <= $max) {
            return [$text];
        }

        $out = [];
        $buf = '';

        foreach (preg_split('/\R{2,}/u', $text) ?: [] as $p) {
            if ($buf !== '' && mb_strlen($buf) + mb_strlen($p) > $max) {
                $out[] = $buf;
                $buf = '';
            }

            $buf = $buf === '' ? $p : $buf . "\n\n" . $p;
        }

        if ($buf !== '') {
            $out[] = mb_substr($buf, 0, $max * 2);
        }

        return $out;
    }
}
