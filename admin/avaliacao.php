<?php
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$cursoId   = (int)($_GET['curso_id'] ?? 0);
if (!$cursoId) { setFlash('error','Curso não informado.'); redirect(APP_URL.'/admin/cursos.php'); }

$cursoModel = new CursoModel();
$avalModel  = new AvaliacaoModel();
$curso = $cursoModel->findById($cursoId);
$aval  = $avalModel->porCurso($cursoId);
$acao  = $_GET['acao'] ?? 'index';

/* CRIAR / EDITAR AVALIAÇÃO */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'avaliacao') {
    csrfCheck();
    $d = ['curso_id'=>$cursoId, 'titulo'=>sanitize($_POST['titulo']), 'descricao'=>sanitize($_POST['descricao']), 'tentativas'=>(int)$_POST['tentativas']];
    if ($aval) $avalModel->atualizar($aval['id'], $d);
    else       $avalModel->criar($d);
    setFlash('success','Avaliação salva!');
    redirect(APP_URL . "/admin/avaliacao.php?curso_id=$cursoId");
}

/* CRIAR PERGUNTA + ALTERNATIVAS */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'pergunta') {
    csrfCheck();
    if (!$aval) { setFlash('error','Crie a avaliação primeiro.'); redirect(APP_URL . "/admin/avaliacao.php?curso_id=$cursoId"); }
    $pid = $avalModel->criarPergunta([
        'avaliacao_id' => $aval['id'],
        'enunciado'    => sanitize($_POST['enunciado']),
        'pontos'       => (float)($_POST['pontos'] ?? 1),
        'ordem'        => (int)($_POST['ordem'] ?? 1),
    ]);
    $alternativas = $_POST['alternativas'] ?? [];
    $correta      = (int)($_POST['correta'] ?? 0);
    foreach ($alternativas as $idx => $texto) {
        if (trim($texto)) $avalModel->criarAlternativa($pid, sanitize($texto), $idx == $correta);
    }
    setFlash('success','Pergunta adicionada!');
    redirect(APP_URL . "/admin/avaliacao.php?curso_id=$cursoId");
}

/* DELETAR PERGUNTA */
if ($acao === 'del_pergunta' && ($pid = (int)($_GET['pid'] ?? 0))) {
    $avalModel->deletarPergunta($pid);
    setFlash('success','Pergunta removida.');
    redirect(APP_URL . "/admin/avaliacao.php?curso_id=$cursoId");
}

$perguntas = $aval ? $avalModel->perguntas($aval['id']) : [];
foreach ($perguntas as &$p) {
    $p['alternativas'] = $avalModel->alternativas($p['id']);
}

$pageTitle = 'Avaliação — ' . ($curso['nome'] ?? '');
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<div class="page-header">
  <div>
    <h1>Avaliação do Curso</h1>
    <p class="page-subtitle"><?= e($curso['nome'] ?? '') ?></p>
  </div>
  <a href="<?= APP_URL ?>/admin/cursos.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
</div>

<div class="row g-3">
  <!-- CONFIG AVALIAÇÃO -->
  <div class="col-md-4">
    <div class="form-card">
      <h6 class="mb-3"><i class="bi bi-sliders me-2 text-primary"></i>Configurações</h6>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="form" value="avaliacao">
        <div class="mb-3">
          <label class="form-label">Título</label>
          <input type="text" name="titulo" class="form-control" value="<?= e($aval['titulo'] ?? 'Avaliação Final') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Descrição</label>
          <textarea name="descricao" class="form-control" rows="2"><?= e($aval['descricao'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Tentativas permitidas</label>
          <input type="number" name="tentativas" class="form-control" min="1" value="<?= $aval['tentativas'] ?? 1 ?>">
        </div>
        <button class="btn btn-primary w-100">Salvar Configurações</button>
      </form>
    </div>

    <!-- NOVA PERGUNTA -->
    <?php if ($aval): ?>
    <div class="form-card mt-3">
      <h6 class="mb-3"><i class="bi bi-plus-circle me-2 text-primary"></i>Nova Pergunta</h6>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="form" value="pergunta">
        <div class="mb-2">
          <label class="form-label">Enunciado *</label>
          <textarea name="enunciado" class="form-control" rows="2" required></textarea>
        </div>
        <div class="row g-2 mb-2">
          <div class="col-6"><label class="form-label">Pontos</label><input type="number" name="pontos" class="form-control" step="0.5" min="0.5" value="1"></div>
          <div class="col-6"><label class="form-label">Ordem</label><input type="number" name="ordem" class="form-control" min="1" value="<?= count($perguntas)+1 ?>"></div>
        </div>
        <label class="form-label">Alternativas (marque a correta)</label>
        <?php for ($i=0;$i<4;$i++): ?>
        <div class="input-group mb-1">
          <div class="input-group-text"><input type="radio" name="correta" value="<?= $i ?>" <?= $i===0?'checked':'' ?>></div>
          <input type="text" name="alternativas[]" class="form-control form-control-sm" placeholder="Alternativa <?= chr(65+$i) ?>">
        </div>
        <?php endfor; ?>
        <button class="btn btn-success w-100 mt-2"><i class="bi bi-plus-lg me-1"></i>Adicionar</button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <!-- LISTA DE PERGUNTAS -->
  <div class="col-md-8">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title">Perguntas (<?= count($perguntas) ?>)</h6>
        <?php if ($aval): ?>
        <span class="text-muted small">Nota mín.: <?= $curso['nota_minima'] ?>%</span>
        <?php endif; ?>
      </div>
      <div class="p-3">
        <?php if (!$aval): ?>
        <div class="empty-state"><i class="bi bi-patch-question"></i>Configure a avaliação primeiro.</div>
        <?php elseif ($perguntas): foreach ($perguntas as $idx => $p): ?>
        <div class="border rounded-3 p-3 mb-3">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <strong>Q<?= $idx+1 ?>. <?= e($p['enunciado']) ?></strong>
            <div class="d-flex gap-1 ms-2">
              <span class="badge bg-light text-dark"><?= $p['pontos'] ?> pt</span>
              <a href="?curso_id=<?= $cursoId ?>&acao=del_pergunta&pid=<?= $p['id'] ?>"
                 class="btn btn-icon btn-outline-danger btn-sm" data-confirm="Remover esta pergunta?"><i class="bi bi-trash"></i></a>
            </div>
          </div>
          <ul class="list-unstyled mb-0">
          <?php foreach ($p['alternativas'] as $alt): ?>
            <li class="d-flex align-items-center gap-2 py-1">
              <?php if ($alt['correta']): ?>
              <i class="bi bi-check-circle-fill text-success"></i>
              <?php else: ?>
              <i class="bi bi-circle text-muted"></i>
              <?php endif; ?>
              <span class="<?= $alt['correta'] ? 'fw-semibold text-success' : '' ?>"><?= e($alt['texto']) ?></span>
            </li>
          <?php endforeach; ?>
          </ul>
        </div>
        <?php endforeach; else: ?>
        <div class="empty-state"><i class="bi bi-list-check"></i>Nenhuma pergunta cadastrada.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
