<?php
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('aluno');

$user       = currentUser();
$cursoId    = (int)($_GET['id'] ?? 0);
$aulaIdAtual= (int)($_GET['aula'] ?? 0);

$cursoModel = new CursoModel();
$aulaModel  = new AulaModel();
$matModel   = new MaterialModel();
$matriModel = new MatriculaModel();
$avalModel  = new AvaliacaoModel();

$curso     = $cursoModel->findById($cursoId);
$matricula = $matriModel->buscar($user['id'], $cursoId);
if (!$curso || !$matricula || $matricula['status'] === 'cancelada') {
    setFlash('error','Você não tem acesso a este curso.'); redirect(APP_URL.'/aluno/dashboard.php');
}

$aulas     = $aulaModel->porCurso($cursoId);
$assistidas= $aulaModel->assistidas($user['id'], $cursoId);
$materiais = $matModel->porCurso($cursoId);
$avaliacao = $avalModel->porCurso($cursoId);

// Aula atual
$aulaAtual = null;
if ($aulaIdAtual) {
    foreach ($aulas as $a) { if ($a['id'] == $aulaIdAtual) { $aulaAtual = $a; break; } }
}
if (!$aulaAtual && $aulas) $aulaAtual = $aulas[0];

// Progresso
$totalAulas = count($aulas);
$doneAulas  = count($assistidas);
$progresso  = $totalAulas > 0 ? (int)(($doneAulas / $totalAulas) * 100) : 0;

// Cursos presenciais: progresso sempre 100% (não há aulas online)
if ($curso['tipo'] === 'presencial') {
    $progresso = 100;
}
$matriModel->atualizarProgresso($user['id'], $cursoId, $progresso);

// Auto-concluir apenas se não exige avaliação
if ($progresso >= 100 && $matricula['status'] === 'ativa' && !$curso['tem_avaliacao']) {
    $matriModel->concluir($user['id'], $cursoId);
    $matricula['status'] = 'concluida';
}

$pageTitle = e($curso['nome']);
$extraJs   = '<script>const appUrl = "' . APP_URL . '";</script>';
include __DIR__ . '/../app/views/layouts/aluno_header.php';
?>

<div class="mb-3 d-flex align-items-center gap-2">
  <a href="<?= APP_URL ?>/aluno/dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
  <h4 class="mb-0"><?= e($curso['nome']) ?></h4>
  <?php if ($matricula['status'] === 'concluida'): ?>
  <span class="badge bg-success ms-auto"><i class="bi bi-check-circle me-1"></i>Concluído</span>
  <?php endif; ?>
</div>

<?php if ($curso['tipo'] === 'presencial'): ?>
<!-- ════ LAYOUT PRESENCIAL ════ -->
<div class="row g-3 justify-content-center">
  <div class="col-md-7">
    <div class="bg-white border rounded-3 p-4 text-center mb-3">
      <div class="mb-3">
        <i class="bi bi-people-fill" style="font-size:56px;color:#003d7c;opacity:.8"></i>
      </div>
      <h5 class="fw-bold mb-1"><?= e($curso['nome']) ?></h5>
      <p class="text-muted mb-3">
        <span class="badge" style="background:#e0e9f8;color:#003d7c;font-size:12px">
          <i class="bi bi-people me-1"></i>Curso Presencial
        </span>
        &nbsp;
        <span class="badge" style="background:#f0f4f9;color:#475569;font-size:12px">
          <i class="bi bi-clock me-1"></i><?= $curso['carga_horaria'] ?>h
        </span>
      </p>
      <?php if ($curso['descricao']): ?>
      <p class="text-muted" style="font-size:14px"><?= nl2br(e($curso['descricao'])) ?></p>
      <?php endif; ?>
      <hr>
      <p class="text-muted mb-0" style="font-size:13px">
        <i class="bi bi-info-circle me-1"></i>
        Você participou deste curso presencialmente. Utilize os botões abaixo para responder à pesquisa de satisfação e emitir seu certificado.
      </p>
    </div>

    <!-- Ações presencial -->
    <div class="d-flex flex-column gap-3">
      <?php if ($curso['tem_avaliacao'] && $avaliacao): ?>
      <a href="<?= APP_URL ?>/aluno/avaliacao.php?curso_id=<?= $cursoId ?>" class="btn btn-warning btn-lg">
        <i class="bi bi-patch-question me-2"></i>Responder Pesquisa / Avaliação
      </a>
      <?php endif; ?>
      <?php if ($matricula['status'] === 'concluida'): ?>
      <a href="<?= APP_URL ?>/aluno/certificado.php?curso_id=<?= $cursoId ?>" class="btn btn-success btn-lg">
        <i class="bi bi-award me-2"></i>Emitir Meu Certificado
      </a>
      <?php else: ?>
      <div class="alert" style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:13px">
        <i class="bi bi-hourglass-split me-2 text-warning"></i>
        Seu certificado estará disponível assim que sua participação for confirmada pela administração.
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ════ LAYOUT EAD (original) ════ -->
<div class="row g-3">
  <!-- SIDEBAR AULAS -->
  <div class="col-md-4 col-xl-3">
    <!-- Progresso -->
    <div class="bg-white border rounded-3 p-3 mb-3">
      <div class="d-flex justify-content-between mb-2">
        <small class="fw-semibold">Progresso do Curso</small>
        <small class="fw-bold text-primary"><?= $progresso ?>%</small>
      </div>
      <div class="progress-ead" id="progressBar" style="height:8px">
        <div class="progress-bar-ead" style="width:<?= $progresso ?>%"></div>
      </div>
      <small class="text-muted mt-1 d-block"><?= $doneAulas ?>/<?= $totalAulas ?> aulas concluídas</small>
    </div>

    <!-- Lista de aulas -->
    <div class="bg-white border rounded-3 overflow-hidden mb-3">
      <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-list-ol me-2 text-primary"></i>Aulas</h6></div>
      <div class="p-2 d-flex flex-column gap-1">
        <?php foreach ($aulas as $a): ?>
        <a href="?id=<?= $cursoId ?>&aula=<?= $a['id'] ?>"
           class="lesson-card <?= ($aulaAtual && $aulaAtual['id'] == $a['id']) ? 'active' : '' ?> <?= in_array($a['id'], $assistidas) ? 'done' : '' ?>">
          <div class="lesson-icon">
            <?= in_array($a['id'], $assistidas) ? '<i class="bi bi-check-circle-fill"></i>' : '<i class="bi bi-play-circle"></i>' ?>
          </div>
          <div>
            <div class="lesson-title"><?= e($a['titulo']) ?></div>
            <div class="lesson-status"><?= in_array($a['id'], $assistidas) ? '✓ Assistida' : 'Aula ' . $a['ordem'] ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Materiais -->
    <?php if ($materiais): ?>
    <div class="bg-white border rounded-3 overflow-hidden mb-3">
      <div class="p-3 border-bottom"><h6 class="mb-0"><i class="bi bi-folder me-2 text-primary"></i>Materiais</h6></div>
      <div class="p-2 d-flex flex-column gap-1">
        <?php foreach ($materiais as $m): ?>
        <a href="<?= APP_URL ?>/public/uploads/materiais/<?= e($m['arquivo']) ?>" target="_blank" class="material-item">
          <span class="material-icon"><i class="bi bi-file-earmark-text"></i></span>
          <div>
            <div style="font-size:13px;font-weight:600"><?= e($m['titulo']) ?></div>
            <div style="font-size:11px;color:#64748b"><?= strtoupper($m['tipo']) ?></div>
          </div>
          <i class="bi bi-download ms-auto text-muted" style="font-size:14px"></i>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Links rápidos -->
    <div class="d-flex flex-column gap-2">
      <?php if ($curso['tem_avaliacao']): ?>
        <?php if ($progresso >= 100 && $avaliacao): ?>
        <a href="<?= APP_URL ?>/aluno/avaliacao.php?curso_id=<?= $cursoId ?>" class="btn btn-warning">
          <i class="bi bi-patch-question me-2"></i>Fazer Avaliação
        </a>
        <?php elseif ($progresso >= 100 && !$avaliacao): ?>
        <button class="btn btn-outline-secondary" disabled>
          <i class="bi bi-patch-question me-2"></i>Avaliação não configurada
        </button>
        <?php else: ?>
        <button class="btn btn-outline-secondary" disabled>
          <i class="bi bi-lock me-2"></i>Conclua as aulas (<?= $progresso ?>%)
        </button>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($matricula['status'] === 'concluida'): ?>
      <a href="<?= APP_URL ?>/aluno/certificado.php?curso_id=<?= $cursoId ?>" class="btn btn-outline-success">
        <i class="bi bi-award me-2"></i>Emitir Certificado
      </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- ÁREA DE VÍDEO -->
  <div class="col-md-8 col-xl-9">
    <?php if ($aulaAtual): ?>
    <div class="bg-white border rounded-3 overflow-hidden">
      <?php if ($aulaAtual['url_video']): ?>
      <div class="video-wrapper">
        <?php if (str_starts_with($aulaAtual['url_video'], 'local://')): ?>
          <?php $nomeVideo = str_replace('local://', '', $aulaAtual['url_video']); ?>
          <video controls style="position:absolute;top:0;left:0;width:100%;height:100%;background:#000"
                 preload="metadata">
            <source src="<?= APP_URL ?>/public/uploads/videos/<?= e($nomeVideo) ?>" type="video/mp4">
            Seu navegador não suporta o player de vídeo.
          </video>
        <?php else: ?>
          <iframe src="<?= embedVideo($aulaAtual['url_video']) ?>" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="d-flex align-items-center justify-content-center bg-dark rounded-top-3" style="height:360px">
        <div class="text-center text-white">
          <i class="bi bi-camera-video-off" style="font-size:48px;opacity:.4"></i>
          <p class="mt-2 opacity-50">Vídeo não disponível</p>
        </div>
      </div>
      <?php endif; ?>

      <div class="p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
          <div>
            <h5 class="mb-1"><?= e($aulaAtual['titulo']) ?></h5>
            <?php if ($aulaAtual['descricao']): ?>
            <p class="text-muted mb-0"><?= nl2br(e($aulaAtual['descricao'])) ?></p>
            <?php endif; ?>
          </div>
          <?php if (!in_array($aulaAtual['id'], $assistidas)): ?>
          <button id="markDoneBtn" data-aula="<?= $aulaAtual['id'] ?>" data-curso="<?= $cursoId ?>"
                  class="btn btn-outline-success flex-shrink-0">
            <i class="bi bi-check-circle me-1"></i>Marcar como concluída
          </button>
          <?php else: ?>
          <span class="btn btn-success flex-shrink-0 disabled"><i class="bi bi-check-circle-fill me-1"></i>Concluída</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="bg-white border rounded-3 d-flex align-items-center justify-content-center" style="min-height:400px">
      <div class="text-center text-muted">
        <i class="bi bi-play-circle" style="font-size:64px;opacity:.2;display:block;margin-bottom:16px"></i>
        <h5>Selecione uma aula para começar</h5>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/layouts/aluno_footer.php'; ?>