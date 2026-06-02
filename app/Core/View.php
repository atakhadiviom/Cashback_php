<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__, 2) . '/resources/views/' . $view . '.php';
        ob_start();
        require $viewFile;
        $content = ob_get_clean();
        require dirname(__DIR__, 2) . '/resources/views/layouts/app.php';
    }
}
