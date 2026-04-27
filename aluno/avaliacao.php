<?php
/**
 * aluno/avaliacao.php — com controle de tentativas extras
 *
 * ALTERAÇÕES em relação ao original:
 *   • $podeRealizar agora usa $avalModel->podeRealizar() — considera extras
 *   • Exibe badge informativo quando o aluno tem tentativa extra disponível
 *   • Restante permanece IDÊNTICO ao original
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('aluno');

$user       = currentUser();
$cursoId    = (int)($_GET['curso_id'] ?? 0);
$cursoModel = new CursoModel();
$avalModel  = new AvaliacaoModel();
$matriModel = new MatriculaModel();

$curso = $cursoModel->findById($cursoId);
$mat   = $matriModel->buscar($user['id'], $cursoId);

if (!$curso || !$mat || $mat['status'] === 'cancelada') {
    setFlash('error', 'Acesso negado.');
    redirect(APP_URL . '/aluno/dashboard.php');
}

// Verificar se o aluno completou todas as aulas antes de acessar a avaliação
$aulaModel  = new AulaModel();
$totalAulas = $aulaModel->totalPorCurso($cursoId);
$assistidas = count($aulaModel->assistidas($user['id'], $cursoId));
if ($totalAulas > 0 && $assistidas < $totalAulas) {
    setFlash('error', 'Você precisa concluir todas as aulas antes de fazer a avaliação.');
    redirect(APP_URL . '/aluno/curso.php?id=' . $cursoId);
}

$avaliacao = $avalModel->porCurso($cursoId);
if (!$avaliacao) {
    setFlash('error', 'Avaliação ainda não foi configurada. Entre em contato com o administrador.');
    redirect(APP_URL . '/aluno/curso.php?id=' . $cursoId);
}

// ── VERIFICAÇÃO DE PERMISSÃO (agora considera tentativas extras) ──────────
$tentativas      = $avalModel->tentativasAluno($user['id'], $avaliacao['id']);
$ultimaTentativa = $avalModel->ultimaTentativa($user['id'], $avaliacao['id']);
$extrasDisp      = $avalModel->tentativasExtrasDisponiveis($user['id'], $avaliacao['id']);

// podeRealizar() encapsula: tentativas normais disponíveis OU extras disponíveis
$podeRealizar = $avalModel->podeRealizar($user['id'], $avaliacao['id'], (int)$avaliacao['tentativas']);

/* ── SUBMISSÃO DA AVALIAÇÃO ─────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $podeRealizar) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        setFlash('error', 'Requisição inválida. Tente novamente.');
        redirect(APP_URL . "/aluno/avaliacao.php?curso_id=$cursoId");
    }

    try {
        $db = getDB();
        $db->beginTransaction();

        $perguntas = $avalModel->perguntas($avaliacao['id']);
        if (empty($perguntas)) {
            throw new Exception('A avaliação não possui perguntas cadastradas.');
        }

        $totalPts  = array_sum(array_column($perguntas, 'pontos'));
        $acertos   = 0;
        $respostas = [];

        foreach ($perguntas as $p) {
            $altId   = (int)($_POST["resp_{$p['id']}"] ?? 0);
            $alts    = $avalModel->alternativas($p['id']);
            $correta = false;
            foreach ($alts as $alt) {
                if ($alt['id'] == $altId && $alt['correta']) {
                    $correta = true;
                    $acertos += $p['pontos'];
                    break;
                }
            }
            $respostas[] = ['pergunta_id' => $p['id'], 'alternativa_id' => $altId, 'correta' => $correta];
        }

        $nota     = $totalPts > 0 ? round(($acertos / $totalPts) * 100, 2) : 0;
        $aprovado = $nota >= (float)$curso['nota_minima'];

        // registrarTentativa() já cuida de marcar a extra como utilizada internamente
        $tentId = $avalModel->registrarTentativa($user['id'], $avaliacao['id'], $nota, $aprovado);
        if (!$tentId) throw new Exception('Falha ao registrar tentativa.');

        foreach ($respostas as $r) {
            $avalModel->registrarResposta($tentId, $r['pergunta_id'], $r['alternativa_id'], $r['correta']);
        }

        // Marcar matrícula como concluída se aprovado
        if ($aprovado && $mat['status'] !== 'concluida') {
            $matriModel->concluir($user['id'], $cursoId);
        }

        $db->commit();

        logAction('avaliacao.realizada', "Curso $cursoId — Nota: $nota% — " . ($aprovado ? 'Aprovado' : 'Reprovado'));

        $msg = $aprovado
            ? "Parabéns! Você foi aprovado com nota {$nota}%! Seu certificado já está disponível."
            : "Sua nota foi {$nota}%. A nota mínima é {$curso['nota_minima']}%.";
        setFlash($aprovado ? 'success' : 'warning', $msg);
        redirect(APP_URL . "/aluno/avaliacao.php?curso_id=$cursoId");

    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        logAction('avaliacao.erro', "Curso $cursoId — " . $e->getMessage());
        setFlash('error', 'Ocorreu um erro ao processar sua avaliação. Por favor, tente novamente.');
        redirect(APP_URL . "/aluno/avaliacao.php?curso_id=$cursoId");
    }
}

// Recarrega estado atualizado após possível POST
$perguntas = $avalModel->perguntas($avaliacao['id']);
foreach ($perguntas as &$p) {
    $p['alternativas'] = $avalModel->alternativas($p['id']);
}
unset($p);

$tentativas      = $avalModel->tentativasAluno($user['id'], $avaliacao['id']);
$ultimaTentativa = $avalModel->ultimaTentativa($user['id'], $avaliacao['id']);
$extrasDisp      = $avalModel->tentativasExtrasDisponiveis($user['id'], $avaliacao['id']);
$podeRealizar    = $avalModel->podeRealizar($user['id'], $avaliacao['id'], (int)$avaliacao['tentativas']);

$pageTitle = 'Avaliação — ' . $curso['nome'];
include __DIR__ . '/../app/views/layouts/aluno_header.php';
?>

<div class="mb-4 d-flex align-items-center gap-3">
  <a href="<?= APP_URL ?>/aluno/curso.php?id=<?= $cursoId ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h4 class="mb-0"><?= e($avaliacao['titulo']) ?></h4>
    <small class="text-muted"><?= e($curso['nome']) ?></small>
  </div>
</div>

<?php if ($ultimaTentativa): ?>
<div class="alert alert-<?= $ultimaTentativa['aprovado'] ? 'success' : 'warning' ?> d-flex align-items-center gap-3 mb-4">
  <i class="bi bi-<?= $ultimaTentativa['aprovado'] ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> fs-4"></i>
  <div class="flex-grow-1">
    <strong>Última tentativa:</strong> Nota <?= $ultimaTentativa['nota'] ?>%
    — <?= $ultimaTentativa['aprovado'] ? '<span class="fw-bold">Aprovado ✓</span>' : 'Não aprovado' ?>
    — <?= dataBR($ultimaTentativa['realizado_em']) ?>
    <br>
    <small>
      Tentativas utilizadas: <?= $tentativas ?> / <?= $avaliacao['tentativas'] ?>
      <?php if ($extrasDisp > 0): ?>
        · <span class="text-primary fw-semibold">
            <i class="bi bi-plus-circle me-1"></i><?= $extrasDisp ?> nova(s) tentativa(s) liberada(s) pelo administrador
          </span>
      <?php endif; ?>
    </small>
  </div>
  <?php if ($ultimaTentativa['aprovado']): ?>
  <a href="<?= APP_URL ?>/aluno/certificado.php?curso_id=<?= $cursoId ?>" class="btn btn-success btn-sm">
    <i class="bi bi-award me-1"></i>Ver Certificado
  </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!$podeRealizar): ?>
<div class="text-center py-5">
  <i class="bi bi-lock" style="font-size:56px;color:#cbd5e1;display:block;margin-bottom:20px"></i>
  <h5 class="text-muted">Número máximo de tentativas atingido.</h5>
  <p class="text-muted">Você utilizou todas as <?= $avaliacao['tentativas'] ?> tentativa(s) disponíveis.</p>
  <a href="<?= APP_URL ?>/aluno/dashboard.php" class="btn btn-primary mt-3">
    <i class="bi bi-grid me-2"></i>Voltar ao Dashboard
  </a>
</div>

<?php else: ?>
<div class="row justify-content-center">
  <div class="col-lg-8">

    <?php if ($extrasDisp > 0 && $tentativas >= $avaliacao['tentativas']): ?>
    <div class="alert alert-primary d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-unlock-fill fs-5"></i>
      <div>
        <strong>Nova tentativa liberada!</strong>
        O administrador concedeu <?= $extrasDisp === 1 ? 'uma nova tentativa' : "$extrasDisp novas tentativas" ?> para você refazer esta avaliação.
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($avaliacao['descricao'])): ?>
    <div class="alert alert-info mb-4">
      <i class="bi bi-info-circle me-2"></i><?= e($avaliacao['descricao']) ?>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between mb-3">
      <small class="text-muted"><?= count($perguntas) ?> questão(ões) · Mínimo: <?= $curso['nota_minima'] ?>%</small>
      <small class="text-muted">
        <?php
        $restantesNormais = max(0, $avaliacao['tentativas'] - $tentativas);
        $totalRestantes   = $restantesNormais + $extrasDisp;
        echo "Tentativas restantes: $totalRestantes";
        ?>
      </small>
    </div>

    <form method="POST" id="formAvaliacao">
      <?= csrfField() ?>
      <?php foreach ($perguntas as $idx => $p): ?>
      <div class="bg-white border rounded-3 p-4 mb-3 shadow-sm">
        <div class="mb-3 d-flex align-items-start gap-2">
          <span class="badge bg-primary rounded-pill"><?= $idx + 1 ?></span>
          <div>
            <strong><?= e($p['enunciado']) ?></strong>
            <small class="text-muted ms-2">(<?= $p['pontos'] ?> pt)</small>
          </div>
        </div>
        <input type="hidden" name="resp_<?= $p['id'] ?>" id="inp_<?= $p['id'] ?>" value="">
        <div class="d-flex flex-column gap-2">
          <?php foreach ($p['alternativas'] as $alt): ?>
          <div class="quiz-option border rounded-3 p-3"
               data-group="<?= $p['id'] ?>" data-value="<?= $alt['id'] ?>"
               onclick="selectOpt(this, '<?= $p['id'] ?>')">
            <?= e($alt['texto']) ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top">
        <a href="<?= APP_URL ?>/aluno/curso.php?id=<?= $cursoId ?>" class="btn btn-outline-secondary">
          <i class="bi bi-x me-1"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-primary px-5" id="btnEnviar">
          <i class="bi bi-send me-2"></i>Enviar Respostas
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function selectOpt(el, group) {
    document.querySelectorAll('.quiz-option[data-group="' + group + '"]').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('inp_' + group).value = el.dataset.value;
}
document.getElementById('formAvaliacao')?.addEventListener('submit', function(e) {
    const inputs = this.querySelectorAll('input[type="hidden"][id^="inp_"]');
    let ok = true;
    inputs.forEach(inp => { if (!inp.value) ok = false; });
    if (!ok) { e.preventDefault(); alert('Por favor, responda todas as questões antes de enviar.'); return; }
    const btn = document.getElementById('btnEnviar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
});
</script>

<?php include __DIR__ . '/../app/views/layouts/aluno_footer.php'; ?>
