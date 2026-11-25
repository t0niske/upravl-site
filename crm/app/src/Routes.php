<?php

declare(strict_types=1);

namespace App;

use App\Crm\App as Crm;
use App\Leads\Migrator;
use App\Support\Env;
use App\Visits\Collector;

/**
 * Маршруты CRM: POST /api/visit (счётчик заходов) и /crm/* (админка). Заявки принимает public/lead.php сайта.
 * Возвращает true, если запрос обработан.
 */
final class Routes
{
    public static function handle(string $method, string $path): bool
    {
        $path = rtrim($path, '/') ?: '/';
        $route = match (true) {
            $path === '/api/visit' => 'visit',
            $path === '/crm', str_starts_with($path, '/crm/') => 'crm',
            default => null,
        };

        if ($route === null) {
            return false;
        }

        // Локально база создаётся сама; на хостинге — командой php database/migrate.php после выкладки.
        if (Env::get('APP_ENV') !== 'production') {
            Migrator::run();
        }

        if ($route === 'visit') {
            self::visit($method);
        } else {
            Crm::handle($method, $path);
        }

        return true;
    }

    /** POST /api/visit — счётчику всегда 204 без тела, что бы ни пришло. */
    private static function visit(string $method): void
    {
        http_response_code(204);
        header('Cache-Control: no-store');
        Collector::handle($method, (string) file_get_contents('php://input', false, null, 0, Collector::MAX_BYTES + 1), $_SERVER);
    }
}
