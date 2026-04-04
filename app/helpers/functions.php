<?php
// app/helpers/functions.php

/* ── Autenticação ── */
function authCheck(string $perfil = ''): void {
    if (empty($_SESSION['usuario_id'])) {
        redirect(APP_URL . '/login.php');
    }

    $perfilAtual = $_SESSION['perfil'] ?? '';

    // Página exclusiva de admin: operador NÃO pode acessar → manda pro dashboard admin dele
    if ($perfil === 'admin' && !in_array($perfilAtual, ['admin', 'operador'])) {
        // Não é admin nem operador → vai para o login
        redirect(APP_URL . '/login.php');
    }

    // Página exclusiva de aluno: operador/admin não pode acessar
    if ($perfil === 'aluno' && $perfilAtual !== 'aluno') {
        redirect(APP_URL . '/admin/dashboard.php');
    }
}

function isLoggedIn(): bool {
    return !empty($_SESSION['usuario_id']);
}

function currentUser(): array {
    return [
        'id'     => $_SESSION['usuario_id'] ?? null,
        'nome'   => $_SESSION['nome']        ?? '',
        'perfil' => $_SESSION['perfil']      ?? '',
        'email'  => $_SESSION['email']       ?? '',
    ];
}

/* ── Redirecionamento ── */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/* ── Flash Messages ── */
function setFlash(string $tipo, string $msg): void {
    $_SESSION['flash'] = ['tipo' => $tipo, 'msg' => $msg];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/* ── Sanitização ── */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function sanitize(string $str): string {
    return trim(strip_tags($str));
}

/**
 * Sanitiza HTML rico (vindo do CKEditor).
 * Permite tags seguras de formatação; remove scripts, iframes, etc.
 */
function sanitize_html(string $html): string
{
    $allowed = '<p><br><strong><b><em><i><u><s><ul><ol><li><h1><h2><h3><h4><blockquote><table><thead><tbody><tr><th><td><span><div><hr>';
    $clean = strip_tags($html, $allowed);
    $clean = preg_replace('/\s+on\w+\s*=\s*"[^"]*"/i', '', $clean);
    $clean = preg_replace('/\s+on\w+\s*=\s*\'[^\']*\'/i', '', $clean);
    $clean = preg_replace('/javascript\s*:/i', '', $clean);
    return trim($clean);
}

/* ── Upload de Arquivo ── */
function uploadFile(array $file, string $destDir, array $allowed): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return false;
    if ($file['size'] > MAX_UPLOAD_MB * 1024 * 1024) return false;

    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $nome = uniqid('', true) . '.' . $ext;
    $dest = $destDir . '/' . $nome;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
    return $nome;
}

/* ── CSRF ── */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfCheck(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Requisição inválida (CSRF).');
    }
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/* ── Paginação ── */
function paginate(int $total, int $perPage, int $current): array {
    $pages = (int)ceil($total / $perPage);
    return [
        'total'    => $total,
        'pages'    => $pages,
        'current'  => $current,
        'offset'   => ($current - 1) * $perPage,
        'per_page' => $perPage,
    ];
}

/* ── Logging ── */
function logAction(string $acao, string $detalhes = ''): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO logs (usuario_id, acao, detalhes, ip) VALUES (?,?,?,?)");
        $stmt->execute([
            $_SESSION['usuario_id'] ?? null,
            $acao,
            $detalhes,
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    } catch (Exception $e) { /* silencioso */ }
}

/* ── Código único certificado ── */
function gerarCodigoCertificado(): string {
    return strtoupper(bin2hex(random_bytes(16)));
}

/* ── Formata data PT-BR ── */
function dataBR(?string $date): string {
    if (!$date) return '—';
    return date('d/m/Y', strtotime($date));
}

/* ── Embed de vídeo ── */
function embedVideo(string $url): string {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{11})/', $url, $m)) {
        return "https://www.youtube.com/embed/{$m[1]}?enablejsapi=1";
    }
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
        return "https://player.vimeo.com/video/{$m[1]}?api=1";
    }
    return $url;
}