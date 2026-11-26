<?php
/** Rendu des vues PHP avec layout */
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $content = self::partial($view, $data);
        $html = $layout === '' ? $content : self::partial('layouts/' . $layout, array_merge($data, ['content' => $content]));
        unset($_SESSION['_old']); // valeurs de formulaire consommées
        echo $html;
    }

    public static function partial(string $view, array $data = []): string
    {
        $file = APP . '/views/' . $view . '.php';
        if (!is_file($file)) throw new \RuntimeException("View not found: $view");
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string)ob_get_clean();
    }
}
