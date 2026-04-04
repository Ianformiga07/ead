<?php
/**
 * admin/alunos.php — CRMV EAD
 * Gerenciamento de alunos/veterinários
 * Admin e Operador podem acessar.
 */
require_once __DIR__ . '/../app/bootstrap.php';
// Operador também pode gerenciar alunos
if (!in_array($_SESSION['perfil'] ?? '', ['admin', 'operador'])) {
    redirect(APP_URL . '/login.php');
}

$userModel = new UsuarioModel();
$acao  = $_GET['acao'] ?? 'listar';
$id    = (int)($_GET['id'] ?? 0);
$aluno = $id ? $userModel->findById($id) : null;

/* ── SALVAR ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();

    // Formata data para banco (pode vir como dd/mm/yyyy ou yyyy-mm-dd)
    $dataNasc = '';
    $rawData  = trim($_POST['data_nascimento'] ?? '');
    if ($rawData) {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $rawData, $m)) {
            $dataNasc = "$m[3]-$m[2]-$m[1]";
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawData)) {
            $dataNasc = $rawData;
        }
    }

    $d = [
        'nome'            => sanitize($_POST['nome']            ?? ''),
        'email'           => sanitize($_POST['email']           ?? ''),
        'cpf'             => sanitize($_POST['cpf']             ?? ''),
        'crmv'            => sanitize($_POST['crmv']            ?? ''),
        'telefone'        => sanitize($_POST['telefone']        ?? ''),
        'data_nascimento' => $dataNasc ?: null,
        'sexo'            => sanitize($_POST['sexo']            ?? ''),
        'especialidade'   => sanitize($_POST['especialidade']   ?? ''),
        'cep'             => sanitize($_POST['cep']             ?? ''),
        'logradouro'      => sanitize($_POST['logradouro']      ?? ''),
        'numero'          => sanitize($_POST['numero']          ?? ''),
        'complemento'     => sanitize($_POST['complemento']     ?? ''),
        'bairro'          => sanitize($_POST['bairro']          ?? ''),
        'cidade'          => sanitize($_POST['cidade']          ?? ''),
        'estado'          => sanitize($_POST['estado']          ?? ''),
        'perfil'          => 'aluno',
        'status'          => (int)($_POST['status']             ?? 1),
        'senha'           => sanitize($_POST['senha']           ?? ''),
    ];

    $erros = [];
    if (strlen($d['nome'])  < 3)                         $erros[] = 'Nome inválido.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail inválido.';
    if ($userModel->emailExiste($d['email'], $id))       $erros[] = 'E-mail já cadastrado.';
    if (!$id && strlen($d['senha']) < 6)                 $erros[] = 'Senha deve ter ao menos 6 caracteres.';

    if ($erros) {
        setFlash('error', implode(' | ', $erros));
        redirect(APP_URL . '/admin/alunos.php?acao=' . ($id ? "editar&id=$id" : 'novo'));
    }

    if ($id) {
        $userModel->atualizar($id, $d);
        logAction('aluno.atualizar', "Aluno ID $id atualizado");
        setFlash('success', 'Aluno atualizado!');
    } else {
        $newId = $userModel->criar($d);
        logAction('aluno.criar', "Aluno criado ID $newId");
        setFlash('success', 'Aluno/Veterinário cadastrado com sucesso!');
    }
    redirect(APP_URL . '/admin/alunos.php');
}

/* ── DESATIVAR ── */
if ($acao === 'deletar' && $id) {
    $userModel->deletar($id);
    setFlash('success', 'Aluno desativado.');
    redirect(APP_URL . '/admin/alunos.php');
}

$busca  = sanitize($_GET['busca'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$pag    = paginate($userModel->totalAlunos($busca), 15, $page);
$alunos = $userModel->listar($pag['offset'], $pag['per_page'], $busca);

$estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

$pageTitle = 'Alunos / Veterinários';
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<?php if ($acao === 'novo' || $acao === 'editar'): ?>
<!-- ════ FORMULÁRIO COMPLETO ════ -->
<div class="page-header">
  <div>
    <h1><?= $id ? 'Editar Veterinário' : 'Novo Veterinário / Aluno' ?></h1>
    <p class="page-subtitle">Dados completos do profissional</p>
  </div>
  <a href="<?= APP_URL ?>/admin/alunos.php" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Voltar
  </a>
</div>

<form method="POST">
  <?= csrfField() ?>

  <!-- ── IDENTIFICAÇÃO ── -->
  <div class="form-card mb-3">
    <h6 class="mb-3 text-primary"><i class="bi bi-person-vcard-fill me-2"></i>Identificação</h6>
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Nome Completo *</label>
        <input type="text" name="nome" class="form-control" required
               value="<?= e($aluno['nome'] ?? '') ?>" placeholder="Nome completo do veterinário">
      </div>
      <div class="col-md-4">
        <label class="form-label">CRMV</label>
        <input type="text" name="crmv" class="form-control"
               value="<?= e($aluno['crmv'] ?? '') ?>" placeholder="Ex: CRMV-TO 1234">
      </div>
      <div class="col-md-4">
        <label class="form-label">CPF</label>
        <input type="text" name="cpf" class="form-control" id="campoCpf"
               value="<?= e($aluno['cpf'] ?? '') ?>" placeholder="000.000.000-00" maxlength="14">
      </div>
      <div class="col-md-4">
        <label class="form-label">Data de Nascimento</label>
        <input type="text" name="data_nascimento" class="form-control" id="campoDataNasc"
               value="<?php
                 $dn = $aluno['data_nascimento'] ?? '';
                 if ($dn && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dn, $m)) echo "$m[3]/$m[2]/$m[1]";
                 else echo e($dn);
               ?>" placeholder="DD/MM/AAAA" maxlength="10">
      </div>
      <div class="col-md-4">
        <label class="form-label">Sexo</label>
        <select name="sexo" class="form-select">
          <option value="">— Selecione —</option>
          <option value="M" <?= ($aluno['sexo'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
          <option value="F" <?= ($aluno['sexo'] ?? '') === 'F' ? 'selected' : '' ?>>Feminino</option>
          <option value="O" <?= ($aluno['sexo'] ?? '') === 'O' ? 'selected' : '' ?>>Outro</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Especialidade / Área de Atuação</label>
        <input type="text" name="especialidade" class="form-control"
               value="<?= e($aluno['especialidade'] ?? '') ?>"
               placeholder="Ex: Clínica de Pequenos Animais, Zootecnia...">
      </div>
      <div class="col-md-3">
        <label class="form-label">Telefone / WhatsApp</label>
        <input type="text" name="telefone" class="form-control" id="campoTel"
               value="<?= e($aluno['telefone'] ?? '') ?>" placeholder="(00) 00000-0000" maxlength="15">
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="1" <?= ($aluno['status'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
          <option value="0" <?= ($aluno['status'] ?? 1) == 0 ? 'selected' : '' ?>>Inativo</option>
        </select>
      </div>
    </div>
  </div>

  <!-- ── CONTATO & ACESSO ── -->
  <div class="form-card mb-3">
    <h6 class="mb-3 text-primary"><i class="bi bi-envelope-at-fill me-2"></i>Contato e Acesso</h6>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">E-mail *</label>
        <input type="email" name="email" class="form-control" required
               value="<?= e($aluno['email'] ?? '') ?>" placeholder="email@exemplo.com">
        <small class="text-muted">Usado para login na plataforma.</small>
      </div>
      <div class="col-md-6">
        <label class="form-label">Senha <?= $id ? '(deixe em branco para manter)' : '*' ?></label>
        <input type="password" name="senha" class="form-control"
               <?= !$id ? 'required' : '' ?> placeholder="••••••••"
               autocomplete="new-password">
        <small class="text-muted">Mínimo 6 caracteres.</small>
      </div>
    </div>
  </div>

  <!-- ── ENDEREÇO ── -->
  <div class="form-card mb-3">
    <h6 class="mb-3 text-primary"><i class="bi bi-geo-alt-fill me-2"></i>Endereço</h6>
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">CEP</label>
        <input type="text" name="cep" class="form-control" id="campoCep"
               value="<?= e($aluno['cep'] ?? '') ?>" placeholder="00000-000" maxlength="9">
      </div>
      <div class="col-md-7">
        <label class="form-label">Logradouro</label>
        <input type="text" name="logradouro" class="form-control" id="campoLogradouro"
               value="<?= e($aluno['logradouro'] ?? '') ?>" placeholder="Rua, Avenida, etc.">
      </div>
      <div class="col-md-2">
        <label class="form-label">Número</label>
        <input type="text" name="numero" class="form-control"
               value="<?= e($aluno['numero'] ?? '') ?>" placeholder="Nº">
      </div>
      <div class="col-md-4">
        <label class="form-label">Complemento</label>
        <input type="text" name="complemento" class="form-control"
               value="<?= e($aluno['complemento'] ?? '') ?>" placeholder="Apto, Sala...">
      </div>
      <div class="col-md-4">
        <label class="form-label">Bairro</label>
        <input type="text" name="bairro" class="form-control" id="campoBairro"
               value="<?= e($aluno['bairro'] ?? '') ?>" placeholder="Bairro">
      </div>
      <div class="col-md-2">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-select" id="campoEstado">
          <option value="">—</option>
          <?php foreach ($estados as $uf): ?>
          <option value="<?= $uf ?>" <?= ($aluno['estado'] ?? '') === $uf ? 'selected' : '' ?>>
            <?= $uf ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Cidade</label>
        <input type="text" name="cidade" class="form-control" id="campoCidade"
               value="<?= e($aluno['cidade'] ?? '') ?>" placeholder="Cidade">
      </div>
    </div>
    <div class="mt-2">
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="buscarCep()">
        <i class="bi bi-search me-1"></i>Buscar CEP automaticamente
      </button>
      <span id="cepStatus" class="ms-2 text-muted" style="font-size:12px"></span>
    </div>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary px-4">
      <i class="bi bi-check-lg me-1"></i>Salvar
    </button>
    <a href="<?= APP_URL ?>/admin/alunos.php" class="btn btn-outline-secondary">Cancelar</a>
  </div>
</form>

<script>
/* Máscara CPF */
document.getElementById('campoCpf').addEventListener('input', function() {
  let v = this.value.replace(/\D/g,'').slice(0,11);
  if (v.length > 9) v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/,'$1.$2.$3-$4');
  else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{1,3})/,'$1.$2.$3');
  else if (v.length > 3) v = v.replace(/(\d{3})(\d{1,3})/,'$1.$2');
  this.value = v;
});

/* Máscara Telefone */
document.getElementById('campoTel').addEventListener('input', function() {
  let v = this.value.replace(/\D/g,'').slice(0,11);
  if (v.length > 10) v = v.replace(/(\d{2})(\d{5})(\d{4})/,'($1) $2-$3');
  else if (v.length > 6) v = v.replace(/(\d{2})(\d{4,5})(\d{0,4})/,'($1) $2-$3');
  else if (v.length > 2) v = v.replace(/(\d{2})(\d+)/,'($1) $2');
  this.value = v;
});

/* Máscara CEP */
document.getElementById('campoCep').addEventListener('input', function() {
  let v = this.value.replace(/\D/g,'').slice(0,8);
  if (v.length > 5) v = v.replace(/(\d{5})(\d{1,3})/,'$1-$2');
  this.value = v;
});

/* Máscara Data Nascimento DD/MM/AAAA */
document.getElementById('campoDataNasc').addEventListener('input', function() {
  let v = this.value.replace(/\D/g,'').slice(0,8);
  if (v.length > 4) v = v.replace(/(\d{2})(\d{2})(\d{1,4})/,'$1/$2/$3');
  else if (v.length > 2) v = v.replace(/(\d{2})(\d{1,2})/,'$1/$2');
  this.value = v;
});

/* Busca CEP via ViaCEP */
async function buscarCep() {
  const cep = document.getElementById('campoCep').value.replace(/\D/g,'');
  if (cep.length !== 8) { document.getElementById('cepStatus').textContent = 'CEP deve ter 8 dígitos.'; return; }
  document.getElementById('cepStatus').textContent = 'Buscando...';
  try {
    const r = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
    const d = await r.json();
    if (d.erro) { document.getElementById('cepStatus').textContent = 'CEP não encontrado.'; return; }
    document.getElementById('campoLogradouro').value = d.logradouro || '';
    document.getElementById('campoBairro').value     = d.bairro     || '';
    document.getElementById('campoCidade').value     = d.localidade || '';
    document.getElementById('campoEstado').value     = d.uf         || '';
    document.getElementById('cepStatus').textContent = '✓ Endereço preenchido!';
    setTimeout(() => document.getElementById('cepStatus').textContent = '', 3000);
  } catch(e) {
    document.getElementById('cepStatus').textContent = 'Erro ao buscar CEP.';
  }
}
</script>

<?php else: ?>
<!-- ════ LISTAGEM ════ -->
<div class="page-header">
  <div>
    <h1>Alunos / Veterinários</h1>
    <p class="page-subtitle"><?= $pag['total'] ?> cadastrado(s)</p>
  </div>
  <a href="?acao=novo" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Novo Veterinário
  </a>
</div>

<div class="data-card">
  <div class="data-card-header">
    <form method="GET" class="d-flex gap-2">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" name="busca" class="form-control" placeholder="Nome, e-mail, CPF ou CRMV..."
               value="<?= e($busca) ?>" style="min-width:260px">
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
          <th>CRMV</th>
          <th>E-mail</th>
          <th>CPF</th>
          <th>Telefone</th>
          <th>Cidade/UF</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($alunos): foreach ($alunos as $a): ?>
      <tr>
        <td><strong><?= e($a['nome']) ?></strong></td>
        <td><?= e($a['crmv'] ?: '—') ?></td>
        <td><?= e($a['email']) ?></td>
        <td><?= e($a['cpf'] ?: '—') ?></td>
        <td><?= e($a['telefone'] ?: '—') ?></td>
        <td>
          <?php
            $loc = array_filter([$a['cidade'] ?? '', $a['estado'] ?? '']);
            echo e(implode('/', $loc) ?: '—');
          ?>
        </td>
        <td>
          <span class="badge-status badge-<?= $a['status'] ? 'ativo' : 'inativo' ?>">
            <?= $a['status'] ? 'Ativo' : 'Inativo' ?>
          </span>
        </td>
        <td>
          <a href="<?= APP_URL ?>/admin/matriculas.php?aluno_id=<?= $a['id'] ?>"
             class="btn btn-icon btn-outline-success btn-sm" title="Matrículas">
            <i class="bi bi-journal-check"></i>
          </a>
          <a href="?acao=editar&id=<?= $a['id'] ?>"
             class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
            <i class="bi bi-pencil"></i>
          </a>
          <a href="?acao=deletar&id=<?= $a['id'] ?>"
             class="btn btn-icon btn-outline-danger btn-sm" title="Desativar"
             data-confirm="Desativar o aluno '<?= e($a['nome']) ?>'?">
            <i class="bi bi-person-x"></i>
          </a>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td colspan="8">
          <div class="empty-state">
            <i class="bi bi-people"></i>
            <p>Nenhum aluno encontrado.</p>
            <a href="?acao=novo" class="btn btn-primary btn-sm mt-2">
              <i class="bi bi-plus me-1"></i>Cadastrar primeiro veterinário
            </a>
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
