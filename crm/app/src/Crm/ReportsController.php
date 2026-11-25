<?php

declare(strict_types=1);

namespace App\Crm;

use App\Leads\Clock;
use App\Leads\Status;
use App\Support\Db;
use App\Visits\Visits;

/** Отчёты руководителя: заявки по статусам и источникам, сайт — люди, источники, страницы. */
final class ReportsController
{
    public static function index(): void
    {
        $period = (string) ($_GET['period'] ?? 'month');
        $fromValue = (string) ($_GET['from'] ?? '');
        $toValue = (string) ($_GET['to'] ?? '');
        [$from, $to] = LeadsController::period($period, $fromValue, $toValue, Clock::now());
        $from ??= Clock::now()->setTime(0, 0)->modify('first day of this month');
        $to ??= Clock::now()->setTime(0, 0)->modify('+1 day');

        $stmt = Db::pdo()->prepare('SELECT status, source FROM leads WHERE created_at >= ? AND created_at < ?');
        $stmt->execute([Clock::sql($from), Clock::sql($to)]);
        $statuses = array_fill_keys(array_keys(Status::LABELS), 0);
        $sources = [];
        $total = 0;

        foreach ($stmt->fetchAll() as $row) {
            $total++;
            $statuses[$row['status']] = ($statuses[$row['status']] ?? 0) + 1;
            $sources[$row['source']] ??= ['total' => 0, 'client' => 0];
            $sources[$row['source']]['total']++;
            if ($row['status'] === Status::CLIENT) {
                $sources[$row['source']]['client']++;
            }
        }

        uasort($sources, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        View::render('reports', [
            'title' => 'Отчёты',
            'active' => 'reports',
            'period' => $period,
            'fromValue' => $fromValue,
            'toValue' => $toValue,
            'from' => $from,
            'to' => $to,
            'total' => $total,
            'statuses' => $statuses,
            'sources' => $sources,
            'site' => Visits::summary($from, $to),
        ]);
    }

    public static function percent(int $part, int $whole): string
    {
        return $whole > 0 ? round($part * 100 / $whole) . ' %' : '—';
    }
}
