<?php

declare(strict_types=1);

namespace App\Crm;

use App\Leads\Audit;
use App\Support\Db;

/** Руководителю: сотрудники и журнал действий. */
final class StaffController
{
    public static function staff(string $method): void
    {
        if ($method === 'POST') {
            self::save();
            View::redirect('/crm/staff');

            return;
        }

        View::render('staff', ['title' => 'Сотрудники', 'active' => 'staff', 'staff' => Staff::all()]);
    }

    public static function audit(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $where = '1 = 1';
        $params = [];

        if (ctype_digit((string) ($_GET['staff'] ?? ''))) {
            $where .= ' AND staff_id = ?';
            $params[] = (int) $_GET['staff'];
        }

        if (isset(Audit::LABELS[$_GET['action'] ?? ''])) {
            $where .= ' AND action = ?';
            $params[] = $_GET['action'];
        }

        if (ctype_digit((string) ($_GET['lead'] ?? ''))) {
            $where .= ' AND lead_id = ?';
            $params[] = (int) $_GET['lead'];
        }

        $stmt = Db::pdo()->prepare("SELECT * FROM audit_log WHERE $where ORDER BY id DESC LIMIT 100 OFFSET " . (($page - 1) * 100));
        $stmt->execute($params);

        View::render('audit', ['title' => 'Журнал действий', 'active' => 'audit', 'rows' => $stmt->fetchAll(), 'page' => $page, 'staff' => Staff::all()]);
    }

    private static function save(): void
    {
        $actor = Auth::actor();
        $id = (int) ($_POST['id'] ?? 0);
        $role = ($_POST['role'] ?? '') === 'manager' ? 'manager' : 'admin';
        $name = trim((string) ($_POST['name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($id === 0) {
            $login = (string) ($_POST['login'] ?? '');
            $error = Staff::validate($login, $name, $password, true);

            if ($error !== null) {
                View::flash($error, 'error');

                return;
            }

            $newId = Staff::create($login, $name, $role, $password);
            Audit::log($actor, 'staff', null, "Добавлен сотрудник №$newId $name (" . Staff::ROLES[$role] . ')');
            View::flash("Сотрудник «{$name}» добавлен. Передайте ему логин и пароль лично.");

            return;
        }

        $staff = Staff::find($id);

        if ($staff === null) {
            return;
        }

        $active = isset($_POST['active']) ? 1 : 0;
        $error = Staff::validate($staff['login'], $name, $password !== '' ? $password : null, false);

        // Не даём оставить систему без руководителя.
        if ($error === null && $staff['role'] === 'manager' && ($role !== 'manager' || !$active) && Staff::managersCount() <= 1) {
            $error = 'Должен остаться хотя бы один активный руководитель.';
        }

        if ($error !== null) {
            View::flash($error, 'error');

            return;
        }

        $fields = ['name' => $name, 'role' => $role, 'active' => $active];

        if ($password !== '') {
            $fields['password'] = $password;
        }

        Staff::update($id, $fields);

        if ($id === (int) Auth::user()['id'] && $password !== '') {
            Auth::refreshPasswordMark();
        }

        Audit::log($actor, 'staff', null, "Изменён сотрудник №$id $name" . ($password !== '' ? ', новый пароль' : '') . ($active ? '' : ', отключён'));
        View::flash('Сохранено');
    }
}
