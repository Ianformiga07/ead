<?php
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$userModel = new UsuarioModel();
$acao = $_GET['acao'] ?? 'listar';
$id   = (int)($_GET['id'] ?? 0);
$aluno = $id ? $userModel->findById($id) : null;

/* SALVAR */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $d = [
        'nome'      => sanitize($_POST['nome'] ?? ''),
        'email'     => sanitize($_POST['email'] ?? ''),
        'cpf'       => sanitize($_POST['cpf'] ?? ''),
        'telefone'  => sanitize($_POST['telefone'] ?? ''),
        'perfil'    => 'aluno',
        'status'    => (int)($_POST['status'] ?? 1),
        'senha'     => sanitize($_POST['senha'] ?? ''),
    ];

    // Validações
    $erros = [];
    if (strlen($d['nome']) < 3) $erros[] = 'Nome inválido.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if ($userModel->emailExiste($d['email'], $id)) $erros[] = 'E-mail já cadastrado.';
    if (!$id && strlen($d['senha']) < 6) $erros[] = 'Senha deve ter ao menos 6 caracteres.';

    if ($erros) {
        setFlash('error', implode(' | ', $erros));
        redirect(APP_URL . '/admin/alunos.php?acao=' . ($id ? "editar&id=$id" : 'novo'));
    }

    if ($id) {
        $userModel->atualizar($id, $d);
        logAction('aluno.atualizar', "Aluno ID $id atualizado");
        setFlash('success','Aluno atualizado!');
    } else {
        $newId = $userModel->criar($d);
        logAction('aluno.criar', "Aluno criado ID $newId");
        setFlash('success','Aluno cadastrado!');
    }
    redirect(APP_URL . '/admin/alunos.php');
}

/* DELETAR */
if ($acao === 'deletar' && $id) {
    $userModel->deletar($id);
    setFlash('success','Aluno desativado.');
    redirect(APP_URL . '/admin/alunos.php');
}

$busca  = sanitize($_GET['busca'] ?? '');
$page   = max(1,(int)($_GET['p'] ?? 1));
$pag    = paginate($userModel->totalAlunos($busca), 15, $page);
$alunos = $userModel->listar($pag['offset'], $pag['per_page'], $busca);

$pageTitle = 'Alunos';
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<?php if ($acao === 'novo' || $acao === 'editar'): ?>
<div class="page-header">
  <h1><?= $id ? 'Editar Aluno' : 'Novo Aluno' ?></h1>
  <a href="<?= APP_URL ?>/admin/alunos.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
</div>
<div class="form-card">
<form method="POST">
  <?= csrfField() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Nome Completo *</label>
      <input type="text" name="nome" class="form-control" required value="<?= e($aluno['nome'] ?? '') ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">E-mail *</label>
      <input type="email" name="email" class="form-control" required value="<?= e($aluno['email'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">CPF</label>
      <input type="text" name="cpf" class="form-control" placeholder="000.000.000-00" value="<?= e($aluno['cpf'] ?? '') ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Telefone</label>
      <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000" value="<?= e($aluno['telefone'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="1" <?= ($aluno['status'] ?? 1) == 1 ? 'selected':'' ?>>Ativo</option>
        <option value="0" <?= ($aluno['status'] ?? 1) == 0 ? 'selected':'' ?>>Inativo</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Senha <?= $id ? '(deixe em branco)' : '*' ?></label>
      <input type="password" name="senha" class="form-control" <?= !$id ? 'required' : '' ?> placeholder="••••••••">
    </div>
  </div>
  <hr class="my-3">
  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Salvar</button>
    <a href="<?= APP_URL ?>/admin/alunos.php" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>
</div>

<?php else: ?>
<div class="page-header">
  <div>
    <h1>Alunos</h1>
    <p class="page-subtitle"><?= $pag['total'] ?> aluno(s)</p>
  </div>
  <a href="?acao=novo" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo Aluno</a>
</div>
<div class="data-card">
  <div class="data-card-header">
    <form method="GET" class="d-flex gap-2">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" name="busca" class="form-control" placeholder="Buscar aluno..." value="<?= e($busca) ?>" style="min-width:240px">
      </div>
      <button class="btn btn-outline-primary">Buscar</button>
      <?php if ($busca): ?><a href="?" class="btn btn-outline-secondary">Limpar</a><?php endif; ?>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-ead">
      <thead><tr><th>Nome</th><th>E-mail</th><th>CPF</th><th>Telefone</th><th>Status</th><th>Cadastro</th><th>Ações</th></tr></thead>
      <tbody>
      <?php if ($alunos): foreach ($alunos as $a): ?>
      <tr>
        <td><strong><?= e($a['nome']) ?></strong></td>
        <td><?= e($a['email']) ?></td>
        <td><?= e($a['cpf'] ?: '—') ?></td>
        <td><?= e($a['telefone'] ?: '—') ?></td>
        <td><span class="badge-status badge-<?= $a['status'] ? 'ativo':'inativo' ?>"><?= $a['status'] ? 'Ativo':'Inativo' ?></span></td>
        <td><?= dataBR($a['criado_em']) ?></td>
        <td>
          <a href="<?= APP_URL ?>/admin/matriculas.php?aluno_id=<?= $a['id'] ?>" class="btn btn-icon btn-outline-success btn-sm" title="Matrículas"><i class="bi bi-journal-check"></i></a>
          <a href="?acao=editar&id=<?= $a['id'] ?>" class="btn btn-icon btn-outline-primary btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>
          <a href="?acao=deletar&id=<?= $a['id'] ?>" class="btn btn-icon btn-outline-danger btn-sm" title="Desativar"
             data-confirm="Desativar o aluno '<?= e($a['nome']) ?>'?"><i class="bi bi-person-x"></i></a>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="7"><div class="empty-state"><i class="bi bi-people"></i>Nenhum aluno encontrado.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if ($pag['pages'] > 1): ?>
<nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">
  <?php for ($i=1;$i<=$pag['pages'];$i++): ?>
  <li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="?busca=<?= urlencode($busca) ?>&p=<?= $i ?>"><?= $i ?></a></li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
