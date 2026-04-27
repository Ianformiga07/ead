<?php
/**
 * admin/tentativas.php — Gerenciamento de Tentativas de Avaliação
 *
 * Funcionalidades:
 *   • Listar alunos com seus status em uma avaliação de um curso
 *   • Liberar nova tentativa extra para aluno específico
 *   • Invalidar tentativa específica (mantém histórico)
 *   • Visualizar histórico completo de tentativas de um aluno
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$admin      = currentUser();
$avalModel  = new AvaliacaoModel();
$cursoModel = new CursoModel();
$userModel  = new UsuarioModel();
$matModel   = new MatriculaModel();

$cursoId = (int)($_GET['curso_id'] ?? 0);
$alunoId = (int)($_GET['aluno_id'] ?? 0);
$acao    = $_GET['acao'] ?? 'listar';

if (!$cursoId) {
    setFlash('error', 'Curso não informado.');
    redirect(APP_URL . '/admin/cursos.php');
}

$curso     = $cursoModel->findById($cursoId);
$avaliacao = $avalModel->porCurso($cursoId);

if (!$curso) {
    setFlash('error', 'Curso não encontrado.');
    redirect(APP_URL . '/admin/cursos.php');
}

if (!$avaliacao) {
    setFlash('error', 'Este curso não possui avaliação configurada.');
    redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id={$cursoId}&tab=avaliacao");
}

// ── AÇÃO: Liberar tentativa extra ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'liberar_extra') {
    csrfCheck();

    $aId        = (int)($_POST['aluno_id'] ?? 0);
    $obs        = sanitize($_POST['observacao'] ?? '');

    if (!$aId) {
        setFlash('error', 'Aluno não informado.');
        redirect(APP_URL . "/admin/tentativas.php?curso_id={$cursoId}");
    }

    $ok = $avalModel->concederTentativaExtra($aId, $avaliacao['id'], $admin['id'], $obs);

    if ($ok) {
        $aluno = $userModel->findById($aId);
        logAction('tentativa.extra_concedida', "Aluno {$aluno['nome']} (ID:{$aId}) — Avaliação ID:{$avaliacao['id']} — Curso: {$curso['nome']}");
        setFlash('success', "Nova tentativa liberada com sucesso para {$aluno['nome']}.");
    } else {
        setFlash('error', 'Erro ao liberar tentativa. Tente novamente.');
    }

    redirect(APP_URL . "/admin/tentativas.php?curso_id={$cursoId}&aluno_id={$aId}&acao=historico");
}

// ── AÇÃO: Invalidar tentativa ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'invalidar') {
    csrfCheck();

    $tentativaId = (int)($_POST['tentativa_id'] ?? 0);
    $aId         = (int)($_POST['aluno_id'] ?? 0);
    $motivo      = sanitize($_POST['motivo'] ?? '');

    if (!$tentativaId) {
        setFlash('error', 'Tentativa não informada.');
        redirect(APP_URL . "/admin/tentativas.php?curso_id={$cursoId}&aluno_id={$aId}&acao=historico");
    }

    $ok = $avalModel->invalidarTentativa($tentativaId, $admin['id'], $motivo);

    if ($ok) {
        logAction('tentativa.invalidada', "Tentativa ID:{$tentativaId} — Aluno ID:{$aId} — Motivo: {$motivo}");
        setFlash('success', 'Tentativa invalidada. O aluno poderá refazer a avaliação.');
    } else {
        setFlash('error', 'Erro ao invalidar tentativa.');
    }

    redirect(APP_URL . "/admin/tentativas.php?curso_id={$cursoId}&aluno_id={$aId}&acao=historico");
}

// ── DADOS: Histórico de um aluno específico ───────────────────
$alunoSelecionado  = null;
$historico         = [];
$extrasAluno       = [];

if ($alunoId && $acao === 'historico') {
    $alunoSelecionado = $userModel->findById($alunoId);
    if ($alunoSelecionado) {
        $historico   = $avalModel->historicoTentativas($alunoId, $avaliacao['id']);
        $extrasAluno = $avalModel->listarExtras($alunoId, $avaliacao['id']);
    }
}

// ── DADOS: Lista de alunos do curso ──────────────────────────
$alunos = $matModel->alunosDoCurso($cursoId);

// Enriquece cada aluno com dados de tentativas
foreach ($alunos as &$a) {
    $a['tentativas_validas'] = $avalModel->tentativasAluno($a['id'], $avaliacao['id']);
    $a['extras_disponiveis'] = $avalModel->tentativasExtrasDisponiveis($a['id'], $avaliacao['id']);
    $a['ultima_tentativa']   = $avalModel->ultimaTentativa($a['id'], $avaliacao['id']);
    $a['pode_realizar']      = $avalModel->podeRealizar($a['id'], $avaliacao['id'], (int)$avaliacao['tentativas']);
}
unset($a);

$pageTitle = 'Tentativas — ' . $curso['nome'];
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-clipboard2-check me-2"></i>Tentativas de Avaliação</h1>
    <p class="page-subtitle">
      <a href="<?= APP_URL ?>/admin/cursos.php?acao=detalhe&id=<?= $cursoId ?>&tab=avaliacao"
         class="text-decoration-none text-muted">
        <?= e($curso['nome']) ?>
      </a>
      · <?= e($avaliacao['titulo']) ?>
    </p>
  </div>
  <a href="<?= APP_URL ?>/admin/cursos.php?acao=detalhe&id=<?= $cursoId ?>&tab=avaliacao"
     class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Voltar ao Curso
  </a>
</div>

<?php
$flash = getFlash();
if ($flash):
?>
<div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : ($flash['tipo'] === 'warning' ? 'warning' : 'danger') ?> alert-dismissible mb-4">
  <?= e($flash['msg']) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($alunoSelecionado && $acao === 'historico'): ?>
<!-- ═══════════════════════════════════════════════════════════
     PAINEL: HISTÓRICO DO ALUNO
═══════════════════════════════════════════════════════════════ -->
<div class="mb-3">
  <a href="<?= APP_URL ?>/admin/tentativas.php?curso_id=<?= $cursoId ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Voltar à lista
  </a>
</div>

<div class="row g-3">
  <!-- Coluna esquerda: info + ações -->
  <div class="col-md-4">

    <!-- Card: Info do aluno -->
    <div class="data-card mb-3">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-person me-2"></i>Aluno</h6>
      </div>
      <div style="padding: 16px 20px">
        <p class="mb-1"><strong><?= e($alunoSelecionado['nome']) ?></strong></p>
        <p class="mb-1 text-muted small"><?= e($alunoSelecionado['email']) ?></p>
        <?php
        $tentativasValidas = $avalModel->tentativasAluno($alunoId, $avaliacao['id']);
        $extrasDisp        = $avalModel->tentativasExtrasDisponiveis($alunoId, $avaliacao['id']);
        $pode              = $avalModel->podeRealizar($alunoId, $avaliacao['id'], (int)$avaliacao['tentativas']);
        ?>
        <hr class="my-2">
        <div class="d-flex justify-content-between small">
          <span class="text-muted">Tentativas usadas</span>
          <strong><?= $tentativasValidas ?> / <?= $avaliacao['tentativas'] ?></strong>
        </div>
        <div class="d-flex justify-content-between small mt-1">
          <span class="text-muted">Extras disponíveis</span>
          <strong class="text-primary"><?= $extrasDisp ?></strong>
        </div>
        <div class="d-flex justify-content-between small mt-1">
          <span class="text-muted">Pode realizar?</span>
          <span class="badge <?= $pode ? 'bg-success' : 'bg-danger' ?>">
            <?= $pode ? 'Sim' : 'Não' ?>
          </span>
        </div>
      </div>
    </div>

    <!-- Card: Liberar nova tentativa -->
    <div class="form-card mb-3">
      <h6 class="mb-3">
        <i class="bi bi-plus-circle text-success me-2"></i>Liberar Nova Tentativa
      </h6>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="form" value="liberar_extra">
        <input type="hidden" name="aluno_id" value="<?= $alunoId ?>">
        <div class="mb-3">
          <label class="form-label">Observação <small class="text-muted">(opcional)</small></label>
          <textarea name="observacao" class="form-control form-control-sm" rows="2"
                    placeholder="Ex: Aluno apresentou atestado médico..."></textarea>
        </div>
        <button type="submit" class="btn btn-success w-100 btn-sm"
                onclick="return confirm('Liberar nova tentativa para <?= e(addslashes($alunoSelecionado['nome'])) ?>?')">
          <i class="bi bi-unlock me-1"></i>Liberar Tentativa Extra
        </button>
      </form>
    </div>

    <!-- Card: Extras já concedidas -->
    <?php if ($extrasAluno): ?>
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-list-ul me-2"></i>Extras Concedidas</h6>
      </div>
      <?php foreach ($extrasAluno as $ex): ?>
      <div style="padding: 10px 16px; border-bottom: 1px solid var(--border); font-size: 13px">
        <div class="d-flex justify-content-between">
          <span>
            <span class="badge <?= $ex['utilizada'] ? 'bg-secondary' : 'bg-success' ?>">
              <?= $ex['utilizada'] ? 'Utilizada' : 'Disponível' ?>
            </span>
          </span>
          <small class="text-muted"><?= dataBR($ex['concedida_em']) ?></small>
        </div>
        <div class="text-muted mt-1">Por: <?= e($ex['concedida_por_nome']) ?></div>
        <?php if ($ex['observacao']): ?>
        <div class="mt-1 fst-italic">"<?= e($ex['observacao']) ?>"</div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>

  <!-- Coluna direita: Histórico de tentativas -->
  <div class="col-md-8">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title">
          <i class="bi bi-clock-history me-2"></i>Histórico de Tentativas
        </h6>
        <span class="badge bg-secondary"><?= count($historico) ?> tentativa(s)</span>
      </div>

      <?php if (empty($historico)): ?>
      <div class="text-center py-4 text-muted">
        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
        Nenhuma tentativa realizada ainda.
      </div>
      <?php else: ?>

      <?php foreach ($historico as $idx => $t): ?>
      <div class="p-3 <?= !$t['invalidada'] ? '' : 'bg-light' ?>"
           style="border-bottom: 1px solid var(--border);">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <span class="badge me-2 <?= $t['invalidada'] ? 'bg-secondary' : ($t['aprovado'] ? 'bg-success' : 'bg-warning text-dark') ?>">
              <?= $t['invalidada'] ? 'Invalidada' : ($t['aprovado'] ? 'Aprovado' : 'Reprovado') ?>
            </span>
            <strong>Nota: <?= $t['nota'] ?>%</strong>
            <small class="text-muted ms-2"><?= dataBR($t['realizado_em']) ?></small>
          </div>

          <?php if (!$t['invalidada']): ?>
          <!-- Botão para invalidar esta tentativa -->
          <button type="button" class="btn btn-sm btn-outline-danger"
                  data-bs-toggle="modal"
                  data-bs-target="#modalInvalidar"
                  data-id="<?= $t['id'] ?>"
                  data-idx="<?= $idx + 1 ?>"
                  title="Invalidar esta tentativa">
            <i class="bi bi-x-circle me-1"></i>Invalidar
          </button>
          <?php else: ?>
          <span class="text-muted small fst-italic">
            Invalidada por <?= e($t['invalidada_por_nome'] ?? 'Admin') ?> em <?= dataBR($t['invalidada_em']) ?>
          </span>
          <?php endif; ?>
        </div>

        <?php if ($t['invalidada'] && $t['motivo_invalidacao']): ?>
        <div class="mt-1 ms-1 small text-muted fst-italic">
          Motivo: "<?= e($t['motivo_invalidacao']) ?>"
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>

      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal de confirmação para invalidar tentativa -->
<div class="modal fade" id="modalInvalidar" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-exclamation-triangle text-danger me-2"></i>Invalidar Tentativa</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="form" value="invalidar">
        <input type="hidden" name="tentativa_id" id="modalTentativaId" value="">
        <input type="hidden" name="aluno_id" value="<?= $alunoId ?>">
        <div class="modal-body">
          <p class="small mb-2">
            Você está invalidando a <strong id="modalTentativaIdx"></strong> tentativa de
            <strong><?= e($alunoSelecionado['nome']) ?></strong>.
          </p>
          <p class="small text-muted mb-3">
            O histórico será preservado e o aluno poderá refazer a avaliação.
          </p>
          <label class="form-label small">Motivo <small class="text-muted">(opcional)</small></label>
          <input type="text" name="motivo" class="form-control form-control-sm"
                 placeholder="Ex: Conexão caiu durante a prova">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-danger">
            <i class="bi bi-x-circle me-1"></i>Invalidar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('modalInvalidar')?.addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    this.querySelector('#modalTentativaId').value = btn.dataset.id;
    this.querySelector('#modalTentativaIdx').textContent = btn.dataset.idx + 'ª';
});
</script>

<?php else: ?>
<!-- ═══════════════════════════════════════════════════════════
     PAINEL PRINCIPAL: LISTA DE ALUNOS
═══════════════════════════════════════════════════════════════ -->
<div class="data-card">
  <div class="data-card-header">
    <h6 class="data-card-title">
      <i class="bi bi-people me-2"></i>Alunos Matriculados
    </h6>
    <span class="badge bg-secondary"><?= count($alunos) ?> aluno(s)</span>
  </div>

  <?php if (empty($alunos)): ?>
  <div class="text-center py-4 text-muted">
    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
    Nenhum aluno matriculado neste curso.
  </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover mb-0" style="font-size:14px">
      <thead>
        <tr>
          <th>Aluno</th>
          <th class="text-center">Tentativas</th>
          <th class="text-center">Extras</th>
          <th class="text-center">Última Nota</th>
          <th class="text-center">Status</th>
          <th class="text-center">Pode Refazer?</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($alunos as $a): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($a['nome']) ?></div>
            <div class="text-muted small"><?= e($a['email']) ?></div>
          </td>
          <td class="text-center">
            <?= $a['tentativas_validas'] ?> / <?= $avaliacao['tentativas'] ?>
          </td>
          <td class="text-center">
            <?php if ($a['extras_disponiveis'] > 0): ?>
              <span class="badge bg-primary"><?= $a['extras_disponiveis'] ?> extra(s)</span>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <?php if ($a['ultima_tentativa']): ?>
              <strong><?= $a['ultima_tentativa']['nota'] ?>%</strong>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <?php if (!$a['ultima_tentativa']): ?>
              <span class="badge bg-secondary">Não realizada</span>
            <?php elseif ($a['ultima_tentativa']['aprovado']): ?>
              <span class="badge bg-success">Aprovado</span>
            <?php else: ?>
              <span class="badge bg-warning text-dark">Reprovado</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <?php if ($a['pode_realizar']): ?>
              <i class="bi bi-check-circle-fill text-success" title="Pode realizar"></i>
            <?php else: ?>
              <i class="bi bi-x-circle-fill text-danger" title="Sem tentativas disponíveis"></i>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <a href="<?= APP_URL ?>/admin/tentativas.php?curso_id=<?= $cursoId ?>&aluno_id=<?= $a['id'] ?>&acao=historico"
               class="btn btn-sm btn-outline-primary">
              <i class="bi bi-eye me-1"></i>Gerenciar
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php endif; ?>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
