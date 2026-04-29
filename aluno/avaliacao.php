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

        $isQuestionario = ($avaliacao['tipo'] ?? 'prova') === 'questionario';

        if ($isQuestionario) {
            // ── QUESTIONÁRIO DE SATISFAÇÃO ────────────────────────────
            // Não calcula nota — aluno é aprovado ao concluir.
            // Salva cada resposta como o valor numérico (1–5) escolhido.
            $respostas = [];
            foreach ($perguntas as $p) {
                $valor = (int)($_POST["resp_{$p['id']}"] ?? 0);
                // Busca a alternativa correspondente ao valor escolhido
                $alts = $avalModel->alternativas($p['id']);
                $altId = 0;
                foreach ($alts as $alt) {
                    if ((int)$alt['valor'] === $valor) { $altId = $alt['id']; break; }
                }
                // Fallback: se não encontrar por valor, usa o índice direto
                if (!$altId && isset($alts[$valor - 1])) {
                    $altId = $alts[$valor - 1]['id'];
                }
                $respostas[] = ['pergunta_id' => $p['id'], 'alternativa_id' => $altId ?: 0, 'correta' => true];
            }

            $nota     = 100; // aprovado sempre
            $aprovado = true;

            $tentId = $avalModel->registrarTentativa($user['id'], $avaliacao['id'], $nota, $aprovado);
            if (!$tentId) throw new Exception('Falha ao registrar questionário.');

            foreach ($respostas as $r) {
                if ($r['alternativa_id']) {
                    $avalModel->registrarResposta($tentId, $r['pergunta_id'], $r['alternativa_id'], true);
                }
            }

            if ($mat['status'] !== 'concluida') {
                $matriModel->concluir($user['id'], $cursoId);
            }

            $db->commit();
            logAction('avaliacao.realizada', "Curso $cursoId — Questionário concluído");
            setFlash('success', 'Questionário enviado! Seu certificado já está disponível.');
            redirect(APP_URL . "/aluno/avaliacao.php?curso_id=$cursoId");

        } else {
            // ── PROVA COM NOTA ─────────────────────────────────────────
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
        }

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
$isQuestionario  = ($avaliacao['tipo'] ?? 'prova') === 'questionario';

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
<?php if ($isQuestionario): ?>
<div class="alert alert-success d-flex align-items-center gap-3 mb-4">
  <i class="bi bi-check-circle-fill fs-4"></i>
  <div class="flex-grow-1">
    <strong>Questionário já respondido</strong> — <?= dataBR($ultimaTentativa['realizado_em']) ?>
    <br><small>Você concluiu o questionário de satisfação deste curso.</small>
  </div>
  <a href="<?= APP_URL ?>/aluno/certificado.php?curso_id=<?= $cursoId ?>" class="btn btn-success btn-sm">
    <i class="bi bi-award me-1"></i>Ver Certificado
  </a>
</div>
<?php else: ?>
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

    <?php if ($isQuestionario): ?>
    <!-- ── QUESTIONÁRIO DE SATISFAÇÃO ─────────────────── -->
    <div class="alert alert-info mb-4 py-2 px-3" style="font-size:13px">
      <i class="bi bi-info-circle me-2"></i>
      Responda todas as questões abaixo. Não há nota — seu certificado será liberado ao concluir.
    </div>
    <form method="POST" id="formAvaliacao">
      <?= csrfField() ?>
      <?php foreach ($perguntas as $idx => $p): ?>
      <div class="bg-white border rounded-3 p-4 mb-3 shadow-sm">
        <div class="mb-3 d-flex align-items-start gap-2">
          <span class="badge bg-secondary rounded-pill"><?= $idx + 1 ?></span>
          <strong><?= e($p['enunciado']) ?></strong>
        </div>
        <!-- Escala Likert 1–5 -->
        <div class="d-flex gap-2 flex-wrap" id="grp_<?= $p['id'] ?>">
          <?php
          $opcoes = ['1' => 'Ruim', '2' => 'Regular', '3' => 'Bom', '4' => 'Muito bom', '5' => 'Excelente'];
          foreach ($opcoes as $val => $label):
          ?>
          <label class="likert-opt flex-fill text-center border rounded-3 p-2"
                 style="cursor:pointer;min-width:80px">
            <input type="radio" name="resp_<?= $p['id'] ?>" value="<?= $val ?>"
                   class="d-none likert-radio" data-group="<?= $p['id'] ?>" required>
            <div class="likert-num fw-bold" style="font-size:18px"><?= $val ?></div>
            <div style="font-size:11px;color:#6b7280"><?= $label ?></div>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

    <?php else: ?>
    <!-- ── PROVA COM NOTA ───────────────────────────────── -->
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
    <?php endif; ?>

      <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top">
        <a href="<?= APP_URL ?>/aluno/curso.php?id=<?= $cursoId ?>" class="btn btn-outline-secondary">
          <i class="bi bi-x me-1"></i>Cancelar
        </a>
        <button type="button" class="btn btn-primary px-5" id="btnRevisar" onclick="abrirRevisao()">
          <i class="bi bi-eye me-2"></i><?= $isQuestionario ? 'Revisar e Enviar' : 'Revisar e Enviar' ?>
        </button>
      </div>
    </form>

<!-- ══ MODAL DE REVISÃO E CONFIRMAÇÃO ══════════════════════════════════ -->
<div class="modal fade" id="modalRevisao" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content" style="border-radius:16px;border:0;overflow:hidden">

      <div class="modal-header" style="background:linear-gradient(135deg,#003d7c,#0066cc);border:0;padding:20px 24px">
        <div>
          <h5 class="modal-title text-white fw-bold mb-0">
            <i class="bi bi-eye me-2"></i>Revisão das Respostas
          </h5>
          <small class="text-white-50">Confira suas respostas antes de enviar</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4" id="corpoRevisao">
        <!-- preenchido via JS -->
      </div>

      <div class="modal-footer border-0" style="background:#f8fafc;padding:16px 24px">
        <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
          <div id="avisoIncompleto" class="text-danger fw-semibold d-none">
            <i class="bi bi-exclamation-triangle me-1"></i>Há questões sem resposta!
          </div>
          <div class="d-flex gap-2 ms-auto">
            <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
              <i class="bi bi-pencil me-1"></i>Corrigir Respostas
            </button>
            <button class="btn btn-success px-4 fw-bold" id="btnConfirmarEnvio" onclick="confirmarEnvio()">
              <i class="bi bi-send me-2"></i>Confirmar e Enviar
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
  </div>
</div>
<?php endif; ?>

<style>
/* Likert scale visual feedback */
.likert-opt { transition: background .15s, border-color .15s; }
.likert-opt:hover { background: #f0f9ff; border-color: #3b82f6 !important; }
.likert-opt.likert-selected { background: #eff6ff; border-color: #2563eb !important; }
.likert-opt.likert-selected .likert-num { color: #2563eb; }
/* Revisão */
.revisao-item { border-radius:10px; padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; margin-bottom:10px; }
.revisao-item.ok    { border-left:4px solid #10b981; }
.revisao-item.vazio { border-left:4px solid #ef4444; background:#fff5f5; }
.revisao-resposta   { color:#003d7c; font-weight:600; font-size:13px; margin-top:4px; }
.revisao-sem        { color:#ef4444; font-size:13px; margin-top:4px; }
</style>

<script>
/* ── Dados das perguntas (PHP → JS) ──────────────────────────────── */
const isQuestionario = <?= $isQuestionario ? 'true' : 'false' ?>;

<?php if ($isQuestionario): ?>
const perguntas = <?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'enunciado' => $p['enunciado']], $perguntas)) ?>;
<?php else: ?>
const perguntas = <?= json_encode(array_map(fn($p) => [
    'id'          => $p['id'],
    'enunciado'   => $p['enunciado'],
    'pontos'      => $p['pontos'],
    'alternativas'=> array_map(fn($a) => ['id' => $a['id'], 'texto' => $a['texto']], $p['alternativas'])
], $perguntas)) ?>;
<?php endif; ?>

/* ── Questionário Likert ─────────────────────────────── */
document.querySelectorAll('.likert-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var group = this.dataset.group;
        document.querySelectorAll('[data-group="' + group + '"]').forEach(function(r) {
            r.closest('.likert-opt').classList.remove('likert-selected');
        });
        this.closest('.likert-opt').classList.add('likert-selected');
    });
});

/* ── Prova múltipla escolha ──────────────────────────────── */
function selectOpt(el, group) {
    document.querySelectorAll('.quiz-option[data-group="' + group + '"]').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('inp_' + group).value = el.dataset.value;
}

/* ── Coleta respostas ────────────────────────────────────── */
function coletarRespostas() {
    const respostas = {};
    if (isQuestionario) {
        document.querySelectorAll('.likert-radio:checked').forEach(r => {
            const gid = r.name.replace('resp_', '');
            respostas[gid] = { valor: r.value, texto: r.closest('.likert-opt')?.querySelector('div:last-child')?.textContent?.trim() };
        });
    } else {
        perguntas.forEach(p => {
            const inp = document.getElementById('inp_' + p.id);
            if (inp && inp.value) {
                const alt = p.alternativas.find(a => String(a.id) === String(inp.value));
                respostas[p.id] = { altId: inp.value, texto: alt ? alt.texto : '' };
            }
        });
    }
    return respostas;
}

/* ── Abre modal de revisão ─────────────────────────────── */
function abrirRevisao() {
    const respostas = coletarRespostas();
    let html = '';
    let temVazio = false;

    if (isQuestionario) {
        const labels = { '1':'Ruim','2':'Regular','3':'Bom','4':'Muito bom','5':'Excelente' };
        perguntas.forEach((p, idx) => {
            const resp = respostas[p.id];
            const ok = !!resp;
            if (!ok) temVazio = true;
            html += `<div class="revisao-item ${ok ? 'ok' : 'vazio'}">
                <div style="font-size:13px;color:#64748b">Questão ${idx+1}</div>
                <div style="font-size:14px;font-weight:600;color:#1a2035">${escHtml(p.enunciado)}</div>
                ${ok
                    ? `<div class="revisao-resposta"><i class="bi bi-check-circle me-1 text-success"></i>${resp.valor} — ${labels[resp.valor] ?? ''}</div>`
                    : `<div class="revisao-sem"><i class="bi bi-exclamation-circle me-1"></i>Não respondida</div>`}
            </div>`;
        });
    } else {
        perguntas.forEach((p, idx) => {
            const resp = respostas[p.id];
            const ok = !!resp;
            if (!ok) temVazio = true;
            html += `<div class="revisao-item ${ok ? 'ok' : 'vazio'}">
                <div class="d-flex justify-content-between align-items-center">
                  <div style="font-size:13px;color:#64748b">Questão ${idx+1}</div>
                  <span style="font-size:11px;color:#8898aa">${p.pontos} pt</span>
                </div>
                <div style="font-size:14px;font-weight:600;color:#1a2035">${escHtml(p.enunciado)}</div>
                ${ok
                    ? `<div class="revisao-resposta"><i class="bi bi-check-circle me-1 text-success"></i>${escHtml(resp.texto)}</div>`
                    : `<div class="revisao-sem"><i class="bi bi-exclamation-circle me-1"></i>Não respondida</div>`}
            </div>`;
        });
    }

    document.getElementById('corpoRevisao').innerHTML =
        `<div class="alert alert-info py-2 px-3 mb-3" style="font-size:13px">
            <i class="bi bi-info-circle me-2"></i>Revise todas as respostas. Após enviar, não será possível alterar.
         </div>` + html;

    const aviso = document.getElementById('avisoIncompleto');
    const btnConf = document.getElementById('btnConfirmarEnvio');
    aviso.classList.toggle('d-none', !temVazio);
    btnConf.disabled = temVazio;
    if (temVazio) {
        btnConf.title = 'Responda todas as questões antes de enviar';
    }

    new bootstrap.Modal(document.getElementById('modalRevisao')).show();
}

/* ── Confirma e envia ──────────────────────────────────── */
function confirmarEnvio() {
    const btn = document.getElementById('btnConfirmarEnvio');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
    document.getElementById('formAvaliacao').submit();
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php include __DIR__ . '/../app/views/layouts/aluno_footer.php'; ?>