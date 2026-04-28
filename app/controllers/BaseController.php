<?php
/**
 * app/controllers/BaseController.php
 *
 * Classe base para todos os controllers.
 * Fornece atalhos para view, redirect, json e verificação de auth.
 */

abstract class BaseController
{
    // ── Renderização ─────────────────────────────────────────

    protected function view(string $template, array $data = []): void
    {
        view($template, $data);
    }

    protected function partial(string $template, array $data = []): void
    {
        partial($template, $data);
    }

    // ── Resposta ─────────────────────────────────────────────

    protected function redirect(string $url): never
    {
        redirect($url);
    }

    protected function back(string $fallback = '/'): never
    {
        redirectBack($fallback);
    }

    protected function json(mixed $data, int $code = 200): never
    {
        jsonResponse($data, $code);
    }

    // ── Segurança ─────────────────────────────────────────────

    protected function authAdmin(): void
    {
        authCheck('admin');
    }

    protected function authSoAdmin(): void
    {
        authCheck('admin');
        soAdmin();
    }

    protected function authAluno(): void
    {
        authCheck('aluno');
    }

    protected function csrfVerify(): void
    {
        csrfCheck();
    }

    // ── Input helpers ─────────────────────────────────────────

    protected function input(string $key, mixed $default = ''): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function post(string $key, mixed $default = ''): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, mixed $default = ''): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function intPost(string $key, int $default = 0): int
    {
        return (int)($_POST[$key] ?? $default);
    }

    protected function intGet(string $key, int $default = 0): int
    {
        return (int)($_GET[$key] ?? $default);
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function isAjax(): bool
    {
        return isAjax();
    }

    // ── Flash ─────────────────────────────────────────────────

    protected function flash(string $tipo, string $msg): void
    {
        setFlash($tipo, $msg);
    }

    protected function success(string $msg): void
    {
        setFlash('success', $msg);
    }

    protected function error(string $msg): void
    {
        setFlash('error', $msg);
    }

    protected function warning(string $msg): void
    {
        setFlash('warning', $msg);
    }

    // ── Paginação ─────────────────────────────────────────────

    protected function paginate(int $total, int $perPage = PER_PAGE): array
    {
        $page = max(1, $this->intGet('page', 1));
        return paginate($total, $perPage, $page);
    }

    // ── Usuário logado ────────────────────────────────────────

    protected function user(): array
    {
        return currentUser();
    }

    protected function userId(): int
    {
        return $this->user()['id'];
    }
}
