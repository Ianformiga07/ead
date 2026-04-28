<?php
/**
 * app/helpers/view.php — Helper de renderização de views
 */

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);

    if (str_starts_with($template, 'admin/')) {
        $layout = 'admin';
    } elseif (str_starts_with($template, 'aluno/')) {
        $layout = 'aluno';
    } else {
        // auth/ e outras — sem layout (view auto-completa)
        $layout = null;
    }

    $viewFile = VIEWS_PATH . '/' . $template . '.php';

    if (!file_exists($viewFile)) {
        http_response_code(500);
        die("View não encontrada: {$viewFile}");
    }

    if ($layout) {
        $headerFile = VIEWS_PATH . '/layouts/' . $layout . '_header.php';
        $footerFile = VIEWS_PATH . '/layouts/' . $layout . '_footer.php';
        if (file_exists($headerFile)) include $headerFile;
        include $viewFile;
        if (file_exists($footerFile)) include $footerFile;
    } else {
        include $viewFile;
    }
}

function partial(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $file = VIEWS_PATH . '/' . $template . '.php';
    if (file_exists($file)) include $file;
}
