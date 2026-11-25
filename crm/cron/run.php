<?php

declare(strict_types=1);

// Планировщик CRM. Cron на хостинге — раз в минуту:
//   * * * * * /opt/php/8.3/bin/php ~/bankrotstvo70-crm/cron/run.php >/dev/null 2>&1
// Закрывает заходы без данных дольше 30 минут (окончательно решает «человек / бот»),
// раз в сутки (в 03:xx) удаляет старые заходы, заявки по сроку хранения и счётчики лимитов.
// Ручная проверка с подменой времени: php cron/run.php --now="2026-11-02 03:05"

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Leads\Audit;
use App\Leads\Actor;
use App\Leads\Clock;
use App\Leads\Leads;
use App\Leads\Log;
use App\Leads\RateLimiter;
use App\Support\Env;
use App\Visits\Visits;

if (PHP_SAPI !== 'cli') {
    exit;
}

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--now=')) {
        Clock::set(substr($arg, 6));
    }
}

// Не запускаем второй экземпляр, если предыдущий ещё работает.
$lock = fopen(APP_ROOT . '/storage/cron.lock', 'c');

if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit(0);
}

try {
    $now = Clock::now();
    $closed = Visits::finalize($now);

    if ($closed > 0) {
        Log::write('cron.log', "Закрыто заходов: $closed");
    }

    // Раз в сутки, в третьем часу ночи по Томску.
    $marker = APP_ROOT . '/storage/cleanup.day';
    if ((int) $now->format('G') === 3 && @file_get_contents($marker) !== $now->format('Y-m-d')) {
        file_put_contents($marker, $now->format('Y-m-d'));
        $leads = Leads::cleanup($now, (int) Env::get('LEADS_RETENTION_MONTHS', '12'));
        $visits = Visits::cleanup($now);
        RateLimiter::cleanup();
        Log::write('cron.log', "Очистка: заявок $leads, заходов $visits");

        if ($leads > 0) {
            Audit::log(Actor::system(), 'retention', null, "Удалено заявок по сроку хранения: $leads");
        }
    }
} catch (Throwable $e) {
    Log::error('Ошибка планировщика', $e);
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
