<?php

declare(strict_types=1);

// Посты из контент-плана (design/max-kanal/kontent-plan.md) — в расписание канала черновиками:
//   php bin/channel-import.php <файл.md> <понедельник первой недели, ГГГГ-ММ-ДД> [ЧЧ:ММ, по умолчанию 10:00]
// Закреплённый пост ставится на тот же понедельник на час раньше, с отметкой «закрепить».
// Посты с тем же названием второй раз не добавляются. Публикует бот только одобренные (раздел «Канал» в CRM).

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Bot\Channel;
use App\Support\Db;

if (PHP_SAPI !== 'cli' || !isset($argv[2]) || !is_file($argv[1])) {
    fwrite(STDERR, "Использование: php bin/channel-import.php <файл.md> <ГГГГ-ММ-ДД> [ЧЧ:ММ]\n");
    exit(1);
}

$md = str_replace("\r\n", "\n", (string) file_get_contents($argv[1]));
$start = DateTimeImmutable::createFromFormat('!Y-m-d', $argv[2]);
[$h, $i] = array_map('intval', explode(':', $argv[3] ?? '10:00'));

if ($start === false || $start->format('N') !== '1') {
    fwrite(STDERR, "Дата должна быть понедельником\n");
    exit(1);
}

$quote = static function (string $block): string {
    $lines = [];
    foreach (explode("\n", $block) as $line) {
        if (str_starts_with($line, '>')) {
            $lines[] = ltrim(substr($line, 1), ' ');
        }
    }

    return trim(implode("\n", $lines));
};

$posts = [];

if (preg_match('/\*\*Закреплённый пост:\*\*\n(.+?)(?=\n## )/su', $md, $m)) {
    $posts[] = ['title' => 'Закреплённый: знакомство', 'text' => $quote($m[1]), 'at' => $start->setTime(max(0, $h - 1), $i), 'pin' => 1];
}

$days = ['понедельник' => 0, 'вторник' => 1, 'среда' => 2, 'четверг' => 3, 'пятница' => 4, 'суббота' => 5, 'воскресенье' => 6];
preg_match_all('/^### Неделя (\d+), ([а-яё]+)\. ([^\n]+)\n(.+?)(?=\n### |\n## |\z)/msu', $md, $all, PREG_SET_ORDER);

foreach ($all as [, $week, $day, $title, $body]) {
    if (!isset($days[$day])) {
        continue;
    }
    $posts[] = [
        'title' => trim($title),
        'text' => $quote($body),
        'at' => $start->modify('+' . (((int) $week - 1) * 7 + $days[$day]) . ' days')->setTime($h, $i),
        'pin' => 0,
    ];
}

$exists = Db::pdo()->prepare('SELECT COUNT(*) FROM channel_posts WHERE title = ?');
$added = 0;

foreach ($posts as $p) {
    $exists->execute([$p['title']]);
    if ($p['text'] === '' || (int) $exists->fetchColumn() > 0) {
        continue;
    }

    Channel::save(null, [
        'title' => $p['title'],
        'text' => $p['text'],
        'publish_at' => $p['at']->format('Y-m-d H:i:00'),
        'pin' => $p['pin'],
        'status' => 'draft',
    ], 'Импорт');
    printf("%s  %s%s (%d знаков)\n", $p['at']->format('d.m H:i'), $p['title'], $p['pin'] ? ' [закрепить]' : '', mb_strlen($p['text']));
    $added++;
}

echo "Добавлено черновиков: $added из " . count($posts) . "\n";
