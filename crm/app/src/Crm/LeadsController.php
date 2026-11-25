<?php

declare(strict_types=1);

namespace App\Crm;

use App\Leads\Audit;
use App\Leads\Clock;
use App\Leads\Leads;
use App\Leads\Phone;
use App\Leads\Status;
use App\Visits\Visits;
use DateTimeImmutable;

/** Заявки: список с фильтрами, карточка (статус, комментарии, путь по сайту), выгрузка. */
final class LeadsController
{
    public static function index(): void
    {
        $f = self::filters();
        [$from, $to] = self::period($f['period'], $f['from'], $f['to'], Clock::now());
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Leads::search(['from' => $from, 'to' => $to] + $f, $page);

        View::render('leads', [
            'title' => 'Заявки',
            'active' => 'leads',
            'filters' => $f,
            'leads' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / 50)),
            'sources' => Leads::sources(),
            'newCount' => Leads::countNew(),
        ]);
    }

    public static function show(int $id): void
    {
        $lead = Leads::find($id);

        if ($lead === null) {
            View::render('error', ['title' => 'Не найдено', 'message' => 'Такой заявки нет — возможно, она удалена.'], 404);

            return;
        }

        // В заявке персональные данные — просмотр пишем в журнал, не чаще раза в 10 минут.
        $seen = $_SESSION['lead_viewed'][$id] ?? 0;
        if (time() - $seen > 600) {
            Audit::log(Auth::actor(), 'lead_view', $id);
            $_SESSION['lead_viewed'][$id] = time();
        }

        $visits = Visits::forLead($lead);
        $own = null;
        foreach ($visits as $v) {
            if ($v['sid'] === $lead['visit_id']) {
                $own = $v;
            }
        }

        View::render('lead', [
            'title' => 'Заявка №' . $id,
            'active' => 'leads',
            'lead' => $lead,
            'events' => Leads::events($id),
            'visits' => $visits,
            'timeline' => $own !== null ? Visits::timeline((int) $own['id']) : null,
            'ownVisit' => $own,
            'samePhone' => Leads::samePhone($lead),
        ]);
    }

    public static function action(int $id, string $action): void
    {
        $lead = Leads::find($id);

        if ($lead === null) {
            View::redirect('/crm');

            return;
        }

        $actor = Auth::actor();

        match ($action) {
            'status' => Leads::setStatus($id, (string) ($_POST['status'] ?? ''), $actor),
            'comment' => Leads::comment($id, (string) ($_POST['text'] ?? ''), $actor),
            'delete' => Leads::delete($id, $actor),
            default => null,
        };

        if ($action === 'delete') {
            View::flash("Заявка №$id удалена.");
            View::redirect('/crm');

            return;
        }

        View::flash(match ($action) {
            'status' => 'Статус сохранён.',
            'comment' => 'Комментарий добавлен.',
            default => 'Готово.',
        });
        View::redirect('/crm/lead/' . $id);
    }

    /** Выгрузка заявок по текущим фильтрам в CSV (открывается в Excel). Только руководитель. */
    public static function csv(): void
    {
        $f = self::filters();
        [$from, $to] = self::period($f['period'], $f['from'], $f['to'], Clock::now());
        $rows = Leads::all(['from' => $from, 'to' => $to] + $f);
        Audit::log(Auth::actor(), 'export', null, 'Заявки в CSV: ' . count($rows));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="zayavki-' . Clock::now()->format('Y-m-d') . '.csv"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['№', 'Поступила', 'Имя', 'Телефон', 'Тема', 'Страница', 'Источник', 'Статус', 'Тест', 'Сообщение'], ';', '"', '');

        // Текст из формы сайта не должен выполняться в Excel как формула (=, +, -, @ в начале ячейки).
        $text = static fn (?string $v): string => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v;

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'], $r['created_at'], $text($r['name']), Phone::format((string) $r['phone']), $text($r['topic']), $text($r['page_url']),
                $r['source'], Status::label($r['status']), $text($r['quiz']), $text($r['message']),
            ], ';', '"', '');
        }

        fclose($out);
    }

    /** Период фильтра → [с, до) в часовом поясе Томска. */
    public static function period(string $period, string $from, string $to, DateTimeImmutable $now): array
    {
        $today = $now->setTime(0, 0);

        return match ($period) {
            'today' => [$today, null],
            'days30' => [$today->modify('-29 days'), null],
            'week' => [$today->modify('monday this week'), null],
            'last_week' => [$today->modify('monday this week')->modify('-7 days'), $today->modify('monday this week')],
            'month' => [$today->modify('first day of this month'), null],
            'last_month' => [$today->modify('first day of last month'), $today->modify('first day of this month')],
            'custom' => [
                DateTimeImmutable::createFromFormat('!Y-m-d', $from) ?: null,
                ($d = DateTimeImmutable::createFromFormat('!Y-m-d', $to)) ? $d->modify('+1 day') : null,
            ],
            default => [null, null],
        };
    }

    /** @return array{q: string, status: string, source: string, period: string, from: string, to: string} */
    private static function filters(): array
    {
        return [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
            'source' => (string) ($_GET['source'] ?? ''),
            'period' => (string) ($_GET['period'] ?? 'all'),
            'from' => (string) ($_GET['from'] ?? ''),
            'to' => (string) ($_GET['to'] ?? ''),
        ];
    }
}
