<?php
/**
 * admin/matriculas.php — CRMV EAD
 * Gerenciamento de matrículas com busca dinâmica (autocomplete) de alunos
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$matModel   = new MatriculaModel();
$cursoModel = new CursoModel();
$userModel  = new UsuarioModel();

$cursoId = (int)($_GET['curso_id'] ?? 0);
$alunoId = (int)($_GET['aluno_id'] ?? 0);

/* ── MATRICULAR ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'matricular') {
    csrfCheck();
    $aId = (int)($_POST['aluno_id'] ?? 0);
    $cId = (int)($_POST['curso_id'] ?? 0);
    if ($aId && $cId) {
        $matModel->matricular($aId, $cId);
        logAction('matricula.criar', "Aluno $aId matriculado no curso $cId");
        setFlash('success', 'Matrícula realizada com sucesso!');
    } else {
        setFlash('error', 'Selecione um aluno e um curso para matricular.');
    }
    $redir = $cursoId ? "?curso_id=$cursoId" : ($alunoId ? "?aluno_id=$alunoId" : '');
    redirect(APP_URL . '/admin/matriculas.php' . $redir);
}

/* ── CANCELAR ───────────────────────────────────── */
if (($_GET['acao'] ?? '') === 'cancelar' && ($mid = (int)($_GET['id'] ?? 0))) {
    $matModel->cancelar($mid);
    setFlash('success', 'Matrícula cancelada.');
    $redir = $cursoId ? "?curso_id=$cursoId" : ($alunoId ? "?aluno_id=$alunoId" : '');
    redirect(APP_URL . '/admin/matriculas.php' . $redir);
}

$pageTitle = 'Matrículas';

/* ── CONTEXTO: POR CURSO ────────────────────────── */
if ($cursoId) {
    $curso        = $cursoModel->findById($cursoId);
    $alunosMatric = $matModel->alunosDoCurso($cursoId);
}
/* ── CONTEXTO: POR ALUNO ────────────────────────── */
elseif ($alunoId) {
    $aluno      = $userModel->findById($alunoId);
    $cursoAluno = $cursoModel->cursosDoAluno($alunoId);
    $cursosDisp = $matModel->cursosNaoMatriculado($alunoId);
}
/* ── LISTAGEM GERAL ─────────────────────────────── */
else {
    $db = getDB();
    $todas = $db->query(
        "SELECT m.*, u.nome as aluno, c.nome as curso, m.id as mid
         FROM matriculas m
         JOIN usuarios u ON m.aluno_id = u.id
         JOIN cursos c ON m.curso_id = c.id
         ORDER BY m.matriculado_em DESC LIMIT 100"
    )->fetchAll();
    $cursos = $cursoModel->cursosAtivos();
}

include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<div class="page-header">
  <div>
    <h1>Matrículas</h1>
    <p class="page-subtitle">
      <?php if ($cursoId && isset($curso)): ?>Alunos do curso: <strong><?= e($curso['nome']) ?></strong>
      <?php elseif ($alunoId && isset($aluno)): ?>Cursos do aluno: <strong><?= e($aluno['nome']) ?></strong>
      <?php else: ?>Gerenciar todas as matrículas<?php endif; ?>
    </p>
  </div>
  <?php if ($cursoId || $alunoId): ?>
  <a href="<?= APP_URL ?>/admin/matriculas.php" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Voltar
  </a>
  <?php endif; ?>
</div>

<?php if ($cursoId && isset($curso)): ?>
<!-- ════ POR CURSO ════ -->
<div class="row g-3">
  <div class="col-md-4">
    <div class="form-card">
      <h6 class="mb-3"><i class="bi bi-person-plus me-2 text-primary"></i>Matricular Aluno</h6>
      <form method="POST" id="formMatricularCurso">
        <?= csrfField() ?>
        <input type="hidden" name="acao" value="matricular">
        <input type="hidden" name="curso_id" value="<?= $cursoId ?>">

        <!-- Campo autocomplete de aluno -->
        <div class="mb-3">
          <label class="form-label">Buscar Aluno (veterinário)</label>
          <?php
            // Parâmetro para excluir já matriculados neste curso
            $endpointUrl = APP_URL . '/admin/buscar_aluno.php?excluir_curso=' . $cursoId;
          ?>
          <div class="ac-wrap" data-endpoint="<?= e($endpointUrl) ?>">
            <div class="ac-input-wrap">
              <i class="bi bi-search ac-icon"></i>
              <input type="text"
                     class="form-control ac-input"
                     placeholder="Digite o nome, CPF ou e-mail..."
                     autocomplete="off">
              <div class="ac-spinner d-none">
                <span class="spinner-border spinner-border-sm text-primary"></span>
              </div>
            </div>
            <div class="ac-dropdown d-none"></div>
            <div class="ac-selected d-none">
              <div class="ac-selected-card">
                <div class="d-flex align-items-center gap-2">
                  <div class="ac-selected-avatar"><i class="bi bi-person-check-fill"></i></div>
                  <div class="flex-grow-1">
                    <div class="ac-selected-nome fw-bold"></div>
                    <div class="ac-selected-info text-muted" style="font-size:12px"></div>
                  </div>
                  <button type="button" class="btn btn-sm btn-link text-danger ac-clear" title="Remover">
                    <i class="bi bi-x-circle"></i>
                  </button>
                </div>
              </div>
            </div>
            <!-- Campo hidden enviado no form -->
            <input type="hidden" name="aluno_id" class="ac-value" required>
          </div>
          <small class="text-muted">Mínimo 2 caracteres para buscar</small>
        </div>

        <button class="btn btn-primary w-100" type="submit">
          <i class="bi bi-plus-lg me-1"></i>Matricular
        </button>
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
            <td><strong><?= e($a['nome']) ?></strong></td>
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
                 class="btn btn-icon btn-outline-danger btn-sm"
                 data-confirm="Cancelar matrícula de <?= e($a['nome']) ?>?">
                <i class="bi bi-x-circle"></i>
              </a>
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
<!-- ════ POR ALUNO ════ -->
<div class="row g-3">
  <div class="col-md-4">
    <div class="form-card">
      <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded" style="background:var(--primary-xlight)">
        <div style="width:44px;height:44px;background:var(--primary-light);color:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">
          <i class="bi bi-person-fill"></i>
        </div>
        <div>
          <div class="fw-bold"><?= e($aluno['nome']) ?></div>
          <div style="font-size:12px;color:var(--text-muted)"><?= e($aluno['email']) ?></div>
          <?php if (!empty($aluno['cpf'])): ?>
          <div style="font-size:12px;color:var(--text-muted)">CPF: <?= e($aluno['cpf']) ?></div>
          <?php endif; ?>
        </div>
      </div>
      <h6 class="mb-3"><i class="bi bi-journal-plus me-2 text-primary"></i>Adicionar Curso</h6>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="acao" value="matricular">
        <input type="hidden" name="aluno_id" value="<?= $alunoId ?>">
        <div class="mb-3">
          <label class="form-label">Selecionar Curso</label>
          <select name="curso_id" class="form-select" required>
            <option value="">— Selecione um curso —</option>
            <?php foreach ($cursosDisp as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary w-100">
          <i class="bi bi-plus-lg me-1"></i>Matricular
        </button>
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
            <td>
              <a href="?aluno_id=<?= $alunoId ?>&acao=cancelar&id=<?= $c['matricula_id'] ?>"
                 class="btn btn-icon btn-outline-danger btn-sm"
                 data-confirm="Cancelar matrícula em '<?= e($c['nome']) ?>'?">
                <i class="bi bi-x-circle"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Nenhum curso matriculado.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ════ LISTAGEM GERAL ════ -->
<div class="row g-3 mb-4">
  <div class="col-lg-7">
    <div class="form-card">
      <h6 class="mb-3"><i class="bi bi-person-plus me-2 text-primary"></i>Nova Matrícula</h6>
      <form method="POST" id="formMatricularGeral">
        <?= csrfField() ?>
        <input type="hidden" name="acao" value="matricular">
        <div class="row g-3">
          <!-- Autocomplete de aluno -->
          <div class="col-md-6">
            <label class="form-label">Aluno (veterinário)</label>
            <div class="ac-wrap" data-endpoint="<?= e(APP_URL . '/admin/buscar_aluno.php') ?>">
              <div class="ac-input-wrap">
                <i class="bi bi-search ac-icon"></i>
                <input type="text" class="form-control ac-input"
                       placeholder="Nome, CPF ou e-mail..." autocomplete="off">
                <div class="ac-spinner d-none">
                  <span class="spinner-border spinner-border-sm text-primary"></span>
                </div>
              </div>
              <div class="ac-dropdown d-none"></div>
              <div class="ac-selected d-none">
                <div class="ac-selected-card">
                  <div class="d-flex align-items-center gap-2">
                    <div class="ac-selected-avatar"><i class="bi bi-person-check-fill"></i></div>
                    <div class="flex-grow-1">
                      <div class="ac-selected-nome fw-bold" style="font-size:13px"></div>
                      <div class="ac-selected-info text-muted" style="font-size:11px"></div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-danger ac-clear p-0">
                      <i class="bi bi-x-circle"></i>
                    </button>
                  </div>
                </div>
              </div>
              <input type="hidden" name="aluno_id" class="ac-value" required>
            </div>
          </div>
          <!-- Seleção de curso -->
          <div class="col-md-6">
            <label class="form-label">Curso</label>
            <select name="curso_id" class="form-select" required>
              <option value="">— Selecione —</option>
              <?php foreach ($cursos as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button class="btn btn-primary mt-3">
          <i class="bi bi-plus-lg me-1"></i>Realizar Matrícula
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Tabela geral -->
<div class="data-card">
  <div class="data-card-header">
    <h6 class="data-card-title">Últimas Matrículas</h6>
  </div>
  <div class="table-responsive">
    <table class="table table-ead">
      <thead><tr><th>Aluno</th><th>Curso</th><th>Status</th><th>Data</th><th></th></tr></thead>
      <tbody>
      <?php if ($todas): foreach ($todas as $m): ?>
      <tr>
        <td><strong><?= e($m['aluno']) ?></strong></td>
        <td><?= e($m['curso']) ?></td>
        <td><span class="badge-status badge-<?= $m['status'] ?>"><?= ucfirst($m['status']) ?></span></td>
        <td><?= dataBR($m['matriculado_em']) ?></td>
        <td>
          <?php if ($m['status'] === 'ativa'): ?>
          <a href="?acao=cancelar&id=<?= $m['mid'] ?>"
             class="btn btn-icon btn-outline-danger btn-sm"
             data-confirm="Cancelar esta matrícula?">
            <i class="bi bi-x-circle"></i>
          </a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma matrícula encontrada.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     ESTILOS E SCRIPT DO AUTOCOMPLETE
     ════════════════════════════════════════════════ -->
<style>
/* ── Autocomplete ──────────────────────────────── */
.ac-wrap { position: relative; }

.ac-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.ac-icon {
  position: absolute;
  left: 12px;
  color: var(--text-muted);
  font-size: 14px;
  pointer-events: none;
  z-index: 1;
}
.ac-input {
  padding-left: 36px !important;
  padding-right: 36px !important;
}
.ac-spinner {
  position: absolute;
  right: 12px;
}

/* Dropdown de resultados */
.ac-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0; right: 0;
  background: #fff;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,40,90,.12);
  z-index: 1050;
  overflow: hidden;
  max-height: 280px;
  overflow-y: auto;
}
.ac-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  cursor: pointer;
  border-bottom: 1px solid #f5f7fa;
  transition: background .12s;
}
.ac-item:last-child { border-bottom: none; }
.ac-item:hover, .ac-item.focused { background: var(--primary-xlight); }
.ac-item-avatar {
  width: 34px; height: 34px; flex-shrink: 0;
  background: var(--primary-light); color: var(--primary);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px;
}
.ac-item-nome { font-size: 13px; font-weight: 600; color: var(--text); }
.ac-item-info { font-size: 11px; color: var(--text-muted); }
.ac-empty {
  padding: 16px;
  text-align: center;
  color: var(--text-muted);
  font-size: 13px;
}
.ac-empty i { display: block; font-size: 24px; margin-bottom: 6px; opacity: .4; }

/* Card do selecionado */
.ac-selected-card {
  margin-top: 8px;
  background: var(--primary-xlight);
  border: 1.5px solid var(--primary-light);
  border-radius: 8px;
  padding: 10px 12px;
}
.ac-selected-avatar {
  width: 32px; height: 32px;
  background: var(--primary-light); color: var(--primary);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; flex-shrink: 0;
}

/* Highlight do termo buscado */
.ac-highlight { background: #fff3cd; border-radius: 2px; font-weight: 700; }
</style>

<script>
/**
 * Autocomplete de aluno — CRMV EAD
 * Funciona em múltiplos contextos (por curso, geral).
 * Cada instância é independente via .ac-wrap.
 */
(function () {
  'use strict';

  // Inicializa todos os blocos .ac-wrap presentes na página
  document.querySelectorAll('.ac-wrap').forEach(initAutocomplete);

  function initAutocomplete(wrap) {
    const endpoint   = wrap.dataset.endpoint;
    const inputText  = wrap.querySelector('.ac-input');
    const dropdown   = wrap.querySelector('.ac-dropdown');
    const spinner    = wrap.querySelector('.ac-spinner');
    const selectedEl = wrap.querySelector('.ac-selected');
    const hiddenVal  = wrap.querySelector('.ac-value');
    const clearBtn   = wrap.querySelector('.ac-clear');

    let debounceTimer = null;
    let currentFocus  = -1;
    let lastQuery     = '';

    // ── Digitação ──────────────────────────────
    inputText.addEventListener('input', function () {
      const q = this.value.trim();
      clearTimeout(debounceTimer);

      if (q.length < 2) {
        closeDropdown();
        return;
      }
      if (q === lastQuery) return;
      lastQuery = q;

      // Mostra spinner
      spinner.classList.remove('d-none');

      debounceTimer = setTimeout(() => buscar(q), 280);
    });

    // ── Navegação por teclado ───────────────────
    inputText.addEventListener('keydown', function (e) {
      const items = dropdown.querySelectorAll('.ac-item');
      if (!items.length) return;

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        currentFocus = Math.min(currentFocus + 1, items.length - 1);
        setFocus(items);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        currentFocus = Math.max(currentFocus - 1, 0);
        setFocus(items);
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (currentFocus >= 0 && items[currentFocus]) {
          items[currentFocus].click();
        }
      } else if (e.key === 'Escape') {
        closeDropdown();
      }
    });

    function setFocus(items) {
      items.forEach((el, i) => el.classList.toggle('focused', i === currentFocus));
      if (items[currentFocus]) {
        items[currentFocus].scrollIntoView({ block: 'nearest' });
      }
    }

    // ── Fechar ao clicar fora ───────────────────
    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) closeDropdown();
    });

    // ── Limpar seleção ──────────────────────────
    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        hiddenVal.value = '';
        inputText.value = '';
        selectedEl.classList.add('d-none');
        wrap.querySelector('.ac-input-wrap').classList.remove('d-none');
        inputText.focus();
        lastQuery = '';
      });
    }

    // ── Busca AJAX ──────────────────────────────
    function buscar(q) {
      const url = endpoint + (endpoint.includes('?') ? '&' : '?') + 'q=' + encodeURIComponent(q);

      fetch(url, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => {
        if (!r.ok) throw new Error('Erro na requisição');
        return r.json();
      })
      .then(data => {
        spinner.classList.add('d-none');
        renderDropdown(data, q);
      })
      .catch(() => {
        spinner.classList.add('d-none');
        renderDropdown([], q, true);
      });
    }

    // ── Renderizar resultados ───────────────────
    function renderDropdown(items, q, erro) {
      dropdown.innerHTML = '';
      currentFocus = -1;

      if (erro) {
        dropdown.innerHTML = `<div class="ac-empty"><i class="bi bi-wifi-off"></i>Erro ao buscar. Tente novamente.</div>`;
      } else if (!items.length) {
        dropdown.innerHTML = `<div class="ac-empty"><i class="bi bi-person-x"></i>Nenhum aluno encontrado para "<strong>${escHtml(q)}</strong>"</div>`;
      } else {
        items.forEach(aluno => {
          const el = document.createElement('div');
          el.className = 'ac-item';
          el.dataset.id   = aluno.id;
          el.dataset.nome = aluno.nome;
          el.dataset.info = aluno.info;
          el.innerHTML = `
            <div class="ac-item-avatar"><i class="bi bi-person-fill"></i></div>
            <div>
              <div class="ac-item-nome">${highlight(escHtml(aluno.nome), q)}</div>
              <div class="ac-item-info">${escHtml(aluno.info)}</div>
            </div>`;
          el.addEventListener('click', () => selecionar(aluno));
          dropdown.appendChild(el);
        });
      }

      dropdown.classList.remove('d-none');
    }

    // ── Selecionar aluno ────────────────────────
    function selecionar(aluno) {
      hiddenVal.value = aluno.id;

      // Preenche o card de selecionado
      selectedEl.querySelector('.ac-selected-nome').textContent = aluno.nome;
      selectedEl.querySelector('.ac-selected-info').textContent = aluno.info;
      selectedEl.classList.remove('d-none');

      // Oculta o campo de busca
      wrap.querySelector('.ac-input-wrap').classList.add('d-none');

      closeDropdown();
    }

    function closeDropdown() {
      dropdown.classList.add('d-none');
      spinner.classList.add('d-none');
      currentFocus = -1;
    }

    // ── Utilitários ─────────────────────────────
    function escHtml(str) {
      return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function highlight(text, q) {
      if (!q) return text;
      const regex = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
      return text.replace(regex, '<mark class="ac-highlight">$1</mark>');
    }
  }
})();
</script>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
