<?php
/**
 * admin/usuarios.php — CRMV EAD
 * Gerenciamento de usuários do sistema (admin e operador)
 * Somente perfil 'admin' pode acessar esta página.
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$userModel = new UsuarioModel();
$acao  = $_GET['acao'] ?? 'listar';
$id    = (int)($_GET['id'] ?? 0);
$usuario = $id ? $userModel->findById($id) : null;

/* ── SALVAR ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    $perfil = $_POST['perfil'] ?? 'operador';
    // Segurança: somente admin pode criar outro admin
    if (!in_array($perfil, ['admin', 'operador'])) $perfil = 'operador';

    $d = [
        'nome'     => sanitize($_POST['nome']  ?? ''),
        'email'    => sanitize($_POST['email'] ?? ''),
        'perfil'   => $perfil,
        'status'   => (int)($_POST['status']   ?? 1),
        'senha'    => sanitize($_POST['senha'] ?? ''),
    ];

    $erros = [];
    if (strlen($d['nome']) < 3)                            $erros[] = 'Nome inválido.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL))   $erros[] = 'E-mail inválido.';
    if ($userModel->emailExiste($d['email'], $id))         $erros[] = 'E-mail já cadastrado.';
    if (!$id && strlen($d['senha']) < 6)                   $erros[] = 'Senha deve ter ao menos 6 caracteres.';

    if ($erros) {
        setFlash('error', implode(' | ', $erros));
        redirect(APP_URL . '/admin/usuarios.php?acao=' . ($id ? "editar&id=$id" : 'novo'));
    }

    if ($id) {
        $userModel->atualizar($id, $d);
        logAction('usuario.atualizar', "Usuário ID $id atualizado");
        setFlash('success', 'Usuário atualizado!');
    } else {
        $newId = $userModel->criar($d);
        logAction('usuario.criar', "Usuário criado ID $newId perfil=$perfil");
        setFlash('success', 'Usuário cadastrado com sucesso!');
    }
    redirect(APP_URL . '/admin/usuarios.php');
}

/* ── DESATIVAR ── */
if ($acao === 'deletar' && $id) {
    // Não permite desativar a si mesmo
    if ($id === (int)(currentUser()['id'] ?? 0)) {
        setFlash('error', 'Você não pode desativar seu próprio usuário.');
    } else {
        $userModel->deletar($id);
        logAction('usuario.desativar', "Usuário ID $id desativado");
        setFlash('success', 'Usuário desativado.');
    }
    redirect(APP_URL . '/admin/usuarios.php');
}

$busca  = sanitize($_GET['busca'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$pag    = paginate($userModel->totalUsuarios($busca), 15, $page);
$usuarios = $userModel->listarUsuarios($pag['offset'], $pag['per_page'], $busca);

$pageTitle = 'Usuários do Sistema';
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<?php if ($acao === 'novo' || $acao === 'editar'): ?>
<!-- ════ FORMULÁRIO ════ -->
<div class="page-header">
  <div>
    <h1><?= $id ? 'Editar Usuário' : 'Novo Usuário do Sistema' ?></h1>
    <p class="page-subtitle">Acesso ao painel administrativo</p>
  </div>
  <a href="<?= APP_URL ?>/admin/usuarios.php" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Voltar
  </a>
</div>

<div class="form-card">
  <form method="POST">
    <?= csrfField() ?>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Nome Completo *</label>
        <input type="text" name="nome" class="form-control" required
               value="<?= e($usuario['nome'] ?? '') ?>" placeholder="Nome completo">
      </div>
      <div class="col-md-6">
        <label class="form-label">E-mail *</label>
        <input type="email" name="email" class="form-control" required
               value="<?= e($usuario['email'] ?? '') ?>" placeholder="email@exemplo.com">
      </div>

      <div class="col-md-4">
        <label class="form-label">Perfil de Acesso *</label>
        <select name="perfil" class="form-select">
          <option value="operador" <?= ($usuario['perfil'] ?? 'operador') === 'operador' ? 'selected' : '' ?>>
            Operador — Gerencia alunos e cursos
          </option>
          <option value="admin" <?= ($usuario['perfil'] ?? '') === 'admin' ? 'selected' : '' ?>>
            Administrador — Acesso completo
          </option>
        </select>
        <small class="text-muted">
          <i class="bi bi-info-circle me-1"></i>
          O <strong>Operador</strong> pode cadastrar alunos e cursos, mas <strong>não</strong> pode gerenciar usuários do sistema.
        </small>
      </div>

      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="1" <?= ($usuario['status'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
          <option value="0" <?= ($usuario['status'] ?? 1) == 0 ? 'selected' : '' ?>>Inativo</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Senha <?= $id ? '(deixe em branco para manter)' : '*' ?></label>
        <input type="password" name="senha" class="form-control"
               <?= !$id ? 'required' : '' ?> placeholder="••••••••"
               autocomplete="new-password">
        <?php if (!$id): ?>
        <small class="text-muted">Mínimo 6 caracteres.</small>
        <?php endif; ?>
      </div>
    </div>

    <!-- Aviso sobre diferença de perfis -->
    <div class="alert mt-4 mb-0" style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:16px">
      <div class="d-flex gap-3">
        <div style="flex-shrink:0"><i class="bi bi-shield-lock-fill text-primary fs-4"></i></div>
        <div>
          <strong>Diferença entre perfis:</strong>
          <div class="mt-1" style="font-size:13px;color:#475569">
            <span class="badge bg-danger me-2">Admin</span> Acesso total: gerencia usuários, cursos, alunos, certificados e logs.<br>
            <span class="badge bg-info me-2">Operador</span> Acesso parcial: cadastra alunos e gerencia cursos, mas <strong>não vê</strong> a página de usuários do sistema.
          </div>
        </div>
      </div>
    </div>

    <hr class="my-3">
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>Salvar
      </button>
      <a href="<?= APP_URL ?>/admin/usuarios.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php else: ?>
<!-- ════ LISTAGEM ════ -->
<div class="page-header">
  <div>
    <h1><i class="bi bi-shield-person-fill me-2"></i>Usuários do Sistema</h1>
    <p class="page-subtitle">Administradores e operadores com acesso ao painel</p>
  </div>
  <a href="?acao=novo" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Novo Usuário
  </a>
</div>

<!-- Info card -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="stat-card">
      <div class="stat-icon" style="background:linear-gradient(135deg,#dc2626,#991b1b)">
        <i class="bi bi-shield-fill-check"></i>
      </div>
      <div>
        <div class="stat-value"><?= count(array_filter($usuarios, fn($u) => $u['perfil'] === 'admin')) ?></div>
        <div class="stat-label">Administradores</div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="stat-card">
      <div class="stat-icon" style="background:linear-gradient(135deg,#0ea5e9,#0369a1)">
        <i class="bi bi-person-badge-fill"></i>
      </div>
      <div>
        <div class="stat-value"><?= count(array_filter($usuarios, fn($u) => $u['perfil'] === 'operador')) ?></div>
        <div class="stat-label">Operadores</div>
      </div>
    </div>
  </div>
</div>

<div class="data-card">
  <div class="data-card-header">
    <form method="GET" class="d-flex gap-2">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" name="busca" class="form-control" placeholder="Buscar usuário..."
               value="<?= e($busca) ?>" style="min-width:240px">
      </div>
      <button class="btn btn-outline-primary">Buscar</button>
      <?php if ($busca): ?>
      <a href="?" class="btn btn-outline-secondary">Limpar</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-ead">
      <thead>
        <tr>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Perfil</th>
          <th>Status</th>
          <th>Cadastrado em</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($usuarios): foreach ($usuarios as $u): ?>
      <tr>
        <td>
          <strong><?= e($u['nome']) ?></strong>
          <?php if ($u['id'] == (currentUser()['id'] ?? 0)): ?>
          <span class="badge bg-secondary ms-1" style="font-size:10px">Você</span>
          <?php endif; ?>
        </td>
        <td><?= e($u['email']) ?></td>
        <td>
          <?php if ($u['perfil'] === 'admin'): ?>
          <span class="badge bg-danger"><i class="bi bi-shield-fill me-1"></i>Admin</span>
          <?php else: ?>
          <span class="badge bg-info"><i class="bi bi-person-badge me-1"></i>Operador</span>
          <?php endif; ?>
        </td>
        <td>
          <span class="badge-status badge-<?= $u['status'] ? 'ativo' : 'inativo' ?>">
            <?= $u['status'] ? 'Ativo' : 'Inativo' ?>
          </span>
        </td>
        <td><?= dataBR($u['criado_em']) ?></td>
        <td>
          <a href="?acao=editar&id=<?= $u['id'] ?>"
             class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
            <i class="bi bi-pencil"></i>
          </a>
          <?php if ($u['id'] != (currentUser()['id'] ?? 0)): ?>
          <a href="?acao=deletar&id=<?= $u['id'] ?>"
             class="btn btn-icon btn-outline-danger btn-sm" title="Desativar"
             data-confirm="Desativar o usuário '<?= e($u['nome']) ?>'?">
            <i class="bi bi-person-x"></i>
          </a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td colspan="6">
          <div class="empty-state">
            <i class="bi bi-people"></i>
            <p>Nenhum usuário encontrado.</p>
          </div>
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($pag['pages'] > 1): ?>
<nav class="mt-3">
  <ul class="pagination pagination-sm justify-content-center">
    <?php for ($i = 1; $i <= $pag['pages']; $i++): ?>
    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
      <a class="page-link" href="?busca=<?= urlencode($busca) ?>&p=<?= $i ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
