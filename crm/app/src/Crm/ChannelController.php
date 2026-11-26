<?php

declare(strict_types=1);

namespace App\Crm;

use App\Bot\Channel;
use App\Leads\Audit;
use App\Leads\Clock;
use DateTimeImmutable;

/** Руководителю: посты канала MAX по расписанию — список, правка, одобрение, публикация сейчас. */
final class ChannelController
{
    public static function index(): void
    {
        View::render('channel', [
            'title' => 'Канал MAX',
            'active' => 'channel',
            'channel' => Channel::info(),
            'upcoming' => Channel::upcoming(),
            'published' => Channel::published(),
        ]);
    }

    public static function form(?int $id): void
    {
        $post = $id !== null ? Channel::find($id) : null;

        if ($id !== null && ($post === null || $post['status'] === 'published')) {
            View::redirect('/crm/channel');

            return;
        }

        View::render('channel_post', [
            'title' => $post ? 'Пост: ' . ($post['title'] ?: '№' . $post['id']) : 'Новый пост',
            'active' => 'channel',
            'post' => $post ?? ['id' => null, 'title' => '', 'text' => '', 'pin' => 0, 'status' => 'draft',
                'publish_at' => Clock::sql(Clock::now()->modify('+1 day')->setTime(10, 0))],
        ]);
    }

    public static function save(): void
    {
        $id = ctype_digit((string) ($_POST['id'] ?? '')) ? (int) $_POST['id'] : null;
        $old = $id !== null ? Channel::find($id) : null;

        if ($id !== null && ($old === null || $old['status'] === 'published')) {
            View::redirect('/crm/channel');

            return;
        }

        $text = str_replace("\r\n", "\n", trim((string) ($_POST['text'] ?? '')));
        $when = DateTimeImmutable::createFromFormat('Y-m-d H:i', trim(($_POST['date'] ?? '') . ' ' . ($_POST['time'] ?? '')));
        $error = match (true) {
            $text === '' => 'Пустой пост.',
            mb_strlen($text) > Channel::MAX_TEXT => 'Пост длиннее ' . Channel::MAX_TEXT . ' знаков — MAX его не примет.',
            $when === false => 'Укажите дату и время публикации.',
            default => null,
        };

        if ($error !== null) {
            View::flash($error, 'error');
            View::redirect($id !== null ? "/crm/channel/$id" : '/crm/channel/new');

            return;
        }

        $approved = isset($_POST['approve']);
        $user = Auth::user();
        $newId = Channel::save($id, [
            'title' => mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 150),
            'text' => $text,
            'publish_at' => $when->format('Y-m-d H:i:00'),
            'pin' => isset($_POST['pin']) ? 1 : 0,
            'status' => $approved ? 'approved' : 'draft',
            'error' => null,
        ], (string) $user['name']);

        Audit::log(Auth::actor(), 'channel', null, ($id === null ? 'Новый пост' : 'Изменён пост') . " №$newId" . ($approved ? ', одобрен' : ', черновик'));
        View::flash($approved ? 'Сохранено и одобрено: бот опубликует пост ' . View::date($when->format('Y-m-d H:i:00')) . '.' : 'Сохранено как черновик. Черновики бот не публикует.');
        View::redirect('/crm/channel');
    }

    public static function action(int $id, string $action): void
    {
        $post = Channel::find($id);

        if ($post === null || $post['status'] === 'published') {
            View::redirect('/crm/channel');

            return;
        }

        $user = (string) Auth::user()['name'];

        switch ($action) {
            case 'approve':
                Channel::save($id, ['status' => 'approved', 'error' => null], $user);
                View::flash('Одобрено: бот опубликует пост ' . View::date($post['publish_at']) . '.');
                break;
            case 'draft':
                Channel::save($id, ['status' => 'draft'], $user);
                View::flash('Пост снят с публикации и стал черновиком.');
                break;
            case 'delete':
                Channel::delete($id);
                View::flash('Пост удалён.');
                break;
            case 'publish':
                $ok = Channel::publish($post);
                View::flash($ok ? 'Опубликовано в канале.' : 'Не опубликовано: ' . (Channel::find($id)['error'] ?? 'ошибка'), $ok ? 'ok' : 'error');
                break;
            default:
                View::redirect('/crm/channel');

                return;
        }

        Audit::log(Auth::actor(), 'channel', null, "Пост №$id: $action");
        View::redirect('/crm/channel');
    }
}
