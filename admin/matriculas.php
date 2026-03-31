<?php
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$matModel    = new MatriculaModel();
$cursoModel  = new CursoModel();
$userModel   = new UsuarioModel();

$cursoId = (int)($_GET['curso_id'] ?? 0);
$alunoId = (int)($_GET['aluno_id'] ?? 0);

/* MATRICULAR */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'matricular') {
    csrfCheck();
    $aId = (int)($_POST['aluno_id'] ?? 0);
    $cId = (int)($_POST['curso_id'] ?? 0);
    if ($aId && $cId) {
        $matModel->matricular($aId, $cId);
        logAction('matricula.criar',"Aluno $aId matriculado no curso $cId");
        setFlash('success','Matrícula realizada!');
    }
    $redir = $cursoId ? "?curso_id=$cursoId" : ($alunoId ? "?aluno_id=$alunoId" : '');
    redirect(APP_URL . '/admin/matriculas.php' . $redir);
}

/* CANCELAR */
if (($_GET['acao'] ?? '') === 'cancelar' && ($mid = (int)($_GET['id'] ?? 0))) {
    $matModel->cancelar($mid);
    setFlash('success','Matrícula cancelada.');
    $redir = $cursoId ? "?curso_id=$cursoId" : ($alunoId ? "?aluno_id=$alunoId" : '');
    redirect(APP_URL . '/admin/matriculas.php' . $redir);
}

$pageTitle = 'Matrículas';

// Contexto: por curso
if ($cursoId) {
    $curso        = $cursoModel->findById($cursoId);
    $alunosMatric = $matModel->alunosDoCurso($cursoId);
    $naoMatric    = $matModel->cursosNaoMatriculado(0); // não usado aqui
    // Buscar alunos ativos não matriculados neste curso
    $db = getDB();
    $disponiveis = $db->prepare(
        "SELECT * FROM usuarios WHERE perfil='aluno' AND status=1
         AND id NOT IN (SELECT aluno_id FROM matriculas WHERE curso_id=? AND status != 'cancelada')
         ORDER BY nome"
    );
    $disponiveis->execute([$cursoId]);
    $alunosDisp = $disponiveis->fetchAll();
}
// Contexto: por aluno
elseif ($alunoId) {
    $aluno       = $userModel->findById($alunoId);
    $cursoAluno  = $cursoModel->cursosDoAluno($alunoId);
    $cursosDisp  = $matModel->cursosNaoMatriculado($alunoId);
}
// Listagem geral
else {
    $db = getDB();
    $todas = $db->query(
        "SELECT m.*, u.nome as aluno, c.nome as curso, m.id as mid
         FROM matriculas m
         JOIN usuarios u ON m.aluno_id=u.id
         JOIN cursos c ON m.curso_id=c.id
         ORDER BY m.matriculado_em DESC LIMIT 100"
    )->fetchAll();
    $cursos = $cursoModel->cursosAtivos();
    $alunos = $userModel->listar(0, 500);
}

include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<div class="page-header">
  <div>
    <h1>Matrículas</h1>
    <p class="page-subtitle">
      <?php if ($cursoId && isset($curso)): ?>Alunos do curso: <?= e($curso['nome']) ?>
      <?php elseif ($alunoId && isset($aluno)): ?>Cursos do aluno: <?= e($aluno['nome']) ?>
      <?php else: ?>Gerenciar todas as matrículas<?php endif; ?>
    </p>
  </div>
  <?php if ($cursoId || $alunoId): ?>
  <a href="<?= APP_URL ?>/admin/matriculas.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
  <?php endif; ?>
</div>

<?php if ($cursoId && isset($curso)): ?>
<!-- POR CURSO -->
<div class="row g-3">
  <div class="col-md-4">
    <div class="form-card">
      <h6 class="mb-3"><i class="bi bi-person-plus me-2 text-primary"></i>Matricular Aluno</h6>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="acao" value="matricular">
        <input type="hidden" name="curso_id" value="<?= $cursoId ?>">
        <div class="mb-3">
          <label class="form-label">Selecionar Aluno</label>
          <select name="aluno_id" class="form-select" required>
            <option value="">— Selecione —</option>
            <?php foreach ($alunosDisp as $a): ?>
            <option value="<?= $a['id'] ?>"><?= e($a['nome']) ?> (<?= e($a['email']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i>Matricular</button>
      </form>
    </div>
  </div>
  <div class="col-md-8">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title">Alunos Matriculados</h6>
        <span class="badge bg-primary"><?= count($alunosMatric) ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-ead">
          <thead><tr><th>Aluno</th><th>E-mail</th><th>Progresso</th><th>Status</th><th>Data</th><th></th></tr></thead>
          <tbody>
          <?php if ($alunosMatric): foreach ($alunosMatric as $a): ?>
          <tr>
            <td><?= e($a['nome']) ?></td>
            <td><?= e($a['email']) ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="progress-ead flex-grow-1" style="height:6px">
                  <div class="progress-bar" style="width:<?= $a['progresso'] ?>%"></div>
                </div>
                <small><?= $a['progresso'] ?>%</small>
              </div>
            </td>
            <td><span class="badge-status badge-<?= $a['status_matricula'] ?>"><?= ucfirst($a['status_matricula']) ?></span></td>
            <td><?= dataBR($a['matriculado_em']) ?></td>
            <td>
              <a href="?curso_id=<?= $cursoId ?>&acao=cancelar&id=<?= $a['matricula_id'] ?>"
                 class="btn btn-icon btn-outline-danger btn-sm" data-confirm="Cancelar matrícula de <?= e($a['nome']) ?>?"><i class="bi bi-x-circle"></i></a>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Nenhum aluno matriculado.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php elseif ($alunoId && isset($aluno)): ?>
<!-- POR ALUNO -->
<div class="row g-3">
  <div class="col-md-4">
    <div class="form-card">
      <h6 class="mb-3"><i class="bi bi-journal-plus me-2 text-primary"></i>Adicionar Curso</h6>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="acao" value="matricular">
        <input type="hidden" name="aluno_id" value="<?= $alunoId ?>">
        <div class="mb-3">
          <select name="curso_id" class="form-select" required>
            <option value="">— Selecione —</option>
            <?php foreach ($cursosDisp as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary w-100">Matricular</button>
      </form>
    </div>
  </div>
  <div class="col-md-8">
    <div class="data-card">
      <div class="data-card-header"><h6 class="data-card-title">Cursos do Aluno</h6></div>
      <div class="table-responsive">
        <table class="table table-ead">
          <thead><tr><th>Curso</th><th>Tipo</th><th>Progresso</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php if ($cursoAluno): foreach ($cursoAluno as $c): ?>
          <tr>
            <td><?= e($c['nome']) ?></td>
            <td><span class="badge-status badge-<?= $c['tipo'] ?>"><?= strtoupper($c['tipo']) ?></span></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="progress-ead flex-grow-1"><div class="progress-bar" style="width:<?= $c['progresso'] ?>%"></div></div>
                <small><?= $c['progresso'] ?>%</small>
              </div>
            </td>
            <td><span class="badge-status badge-<?= $c['status_matricula'] ?>"><?= ucfirst($c['status_matricula']) ?></span></td>
            <td><a href="?aluno_id=<?= $alunoId ?>&acao=cancelar&id=<?= $c['matricula_id'] ?>"
                   class="btn btn-icon btn-outline-danger btn-sm" data-confirm="Cancelar matrícula?"><i class="bi bi-x-circle"></i></a></td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Nenhum curso.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- LISTAGEM GERAL -->
<div class="row g-3 mb-3">
  <div class="col-md-6">
    <div class="form-card">
      <h6 class="mb-3"><i class="bi bi-person-plus me-2 text-primary"></i>Nova Matrícula</h6>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="acao" value="matricular">
        <div class="row g-2">
          <div class="col-6">
            <select name="aluno_id" class="form-select" required>
              <option value="">— Aluno —</option>
              <?php foreach ($alunos as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['nome']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <select name="curso_id" class="form-select" required>
              <option value="">— Curso —</option>
              <?php foreach ($cursos as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <button class="btn btn-primary mt-2"><i class="bi bi-plus-lg me-1"></i>Matricular</button>
      </form>
    </div>
  </div>
</div>
<div class="data-card">
  <div class="data-card-header"><h6 class="data-card-title">Últimas Matrículas</h6></div>
  <div class="table-responsive">
    <table class="table table-ead">
      <thead><tr><th>Aluno</th><th>Curso</th><th>Status</th><th>Data</th><th></th></tr></thead>
      <tbody>
      <?php if ($todas): foreach ($todas as $m): ?>
      <tr>
        <td><?= e($m['aluno']) ?></td>
        <td><?= e($m['curso']) ?></td>
        <td><span class="badge-status badge-<?= $m['status'] ?>"><?= ucfirst($m['status']) ?></span></td>
        <td><?= dataBR($m['matriculado_em']) ?></td>
        <td>
          <?php if ($m['status'] === 'ativa'): ?>
          <a href="?acao=cancelar&id=<?= $m['mid'] ?>" class="btn btn-icon btn-outline-danger btn-sm" data-confirm="Cancelar matrícula?"><i class="bi bi-x-circle"></i></a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma matrícula.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
