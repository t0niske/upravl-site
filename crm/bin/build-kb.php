<?php

declare(strict_types=1);

// База знаний бота из страниц сайта: php bin/build-kb.php [https://bankrotstvo70.ru]
// Берёт адреса из sitemap, из каждой страницы — основной текст (без меню, подвала, скриптов),
// вопросы FAQ размечает «Вопрос: / Ответ:». Результат — kb/site.md. Запускать после выкладки новых страниц.

require dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit;
}

$base = rtrim($argv[1] ?? 'https://bankrotstvo70.ru', '/');
// Юридические документы и служебные страницы боту не нужны.
$skip = ['/politika-konfidencialnosti/', '/soglasie-na-obrabotku-personalnyh-dannyh/', '/stati/', '/spasibo/'];

$get = static function (string $url): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 20, CURLOPT_USERAGENT => 'bankrotstvo70-kb']);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code === 200 && is_string($body) ? $body : '';
};

$urls = [];
foreach (['/sitemap-index.xml'] as $index) {
    preg_match_all('#<loc>([^<]+)</loc>#', $get($base . $index), $m);
    foreach ($m[1] as $sitemap) {
        preg_match_all('#<loc>([^<]+)</loc>#', $get($sitemap), $p);
        array_push($urls, ...$p[1]);
    }
}

$out = "<!-- Собрано bin/build-kb.php " . date('Y-m-d H:i') . " из $base. Не править руками. -->\n\n";
$pages = 0;

foreach (array_unique($urls) as $url) {
    $path = parse_url($url, PHP_URL_PATH) ?: '/';
    if (in_array($path, $skip, true)) {
        continue;
    }

    $html = $get($url);
    if ($html === '') {
        fwrite(STDERR, "Не открылась: $url\n");
        continue;
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
    libxml_clear_errors();
    $xp = new DOMXPath($dom);

    $title = trim($xp->query('//h1')->item(0)?->textContent ?? $xp->query('//title')->item(0)?->textContent ?? $path);
    $main = $xp->query('//main')->item(0) ?? $xp->query('//body')->item(0);
    if ($main === null) {
        continue;
    }

    foreach (['.//script', './/style', './/noscript', './/svg', './/form', './/nav', './/header', './/footer', './/*[@aria-hidden="true"]', './/*[contains(@class,"sr-only")]'] as $q) {
        foreach (iterator_to_array($xp->query($q, $main)) as $n) {
            $n->parentNode?->removeChild($n);
        }
    }

    $lines = [];
    $walk = static function (DOMNode $node) use (&$walk, &$lines): void {
        foreach ($node->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            $text = static fn (DOMNode $n): string => trim(preg_replace('/\s+/u', ' ', $n->textContent) ?? '');

            if ($tag === 'details') {
                $summary = $child->getElementsByTagName('summary')->item(0);
                $q = $summary ? $text($summary) : '';
                if ($summary) {
                    $summary->parentNode->removeChild($summary);
                }
                $a = $text($child);
                if ($q !== '' && $a !== '') {
                    $lines[] = "\nВопрос: $q\nОтвет: $a\n";
                }
            } elseif (in_array($tag, ['h2', 'h3'], true)) {
                $lines[] = "\n" . ($tag === 'h2' ? '## ' : '### ') . $text($child) . "\n";
            } elseif (in_array($tag, ['p', 'li', 'dt', 'dd', 'td', 'th', 'blockquote', 'figcaption'], true)) {
                $t = $text($child);
                if ($t !== '') {
                    $lines[] = ($tag === 'li' ? '• ' : '') . $t;
                }
            } else {
                $walk($child);
            }
        }
    };
    $walk($main);

    // Повторы (одинаковые блоки на странице) — один раз.
    $seen = [];
    $body = [];
    foreach ($lines as $l) {
        $k = trim($l);
        if ($k === '' || (isset($seen[$k]) && !str_starts_with($k, '#'))) {
            continue;
        }
        $seen[$k] = true;
        $body[] = $l;
    }

    $out .= "# $title\nURL: $url\n\n" . trim(implode("\n", $body)) . "\n\n";
    $pages++;
}

@mkdir(APP_ROOT . '/kb', 0775, true);
file_put_contents(APP_ROOT . '/kb/site.md', $out);
echo "Страниц: $pages, символов: " . mb_strlen($out) . "\n";
