<?php
namespace App\Core;

/**
 * Sehr schlanker View-Renderer. Templates liegen unter app/View/ als .php.
 * Standardmäßig wird das Template in 'layout' eingebettet ($content).
 */
class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layout'): void
    {
        $content = self::capture($template, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        $data['content'] = $content;
        echo self::capture($layout, $data);
    }

    private static function capture(string $template, array $data): string
    {
        $file = BASE_PATH . '/app/View/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View fehlt: ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
