<?php
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('aluno');

$user      = currentUser();
$userModel = new UsuarioModel();
$aluno     = $userModel->findById($user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $d = [
        'nome'     => sanitize($_POST['nome'] ?? ''),
        'email'    => sanitize($_POST['email'] ?? ''),
        'cpf'      => sanitize($_POST['cpf'] ?? ''),
        'telefone' => sanitize($_POST['telefone'] ?? ''),
        'status'   => 1,
        'senha'    => sanitize($_POST['senha'] ?? ''),
    ];
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        setFlash('error','E-mail inválido.');
    } elseif ($userModel->emailExiste($d['email'], $user['id'])) {
        setFlash('error','E-mail já em uso.');
    } else {
        $userModel->atualizar($user['id'], $d);
        $_SESSION['nome'] = $d['nome'];
        setFlash('success','Perfil atualizado!');
    }
    redirect(APP_URL . '/aluno/perfil.php');
}

$pageTitle = 'Meu Perfil';
include __DIR__ . '/../app/views/layouts/aluno_header.php';
?>

<div class="page-header-aluno">
  <h1>Meu Perfil</h1>
</div>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="bg-white border rounded-3 p-4">
      <form method="POST">
        <?= csrfField() ?>
        <div class="mb-3">
          <label class="form-label fw-semibold">Nome Completo</label>
          <input type="text" name="nome" class="form-control" required value="<?= e($aluno['nome']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">E-mail</label>
          <input type="email" name="email" class="form-control" required value="<?= e($aluno['email']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">CPF</label>
          <input type="text" name="cpf" class="form-control" value="<?= e($aluno['cpf'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Telefone</label>
          <input type="text" name="telefone" class="form-control" value="<?= e($aluno['telefone'] ?? '') ?>">
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold">Nova Senha <small class="fw-normal text-muted">(deixe em branco para manter)</small></label>
          <input type="password" name="senha" class="form-control" placeholder="••••••••" minlength="6">
        </div>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Salvar</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../app/views/layouts/aluno_footer.php'; ?>
