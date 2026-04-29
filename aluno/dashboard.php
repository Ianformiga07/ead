<?php
/**
 * aluno/dashboard.php — CRMV-TO EAD
 * Dashboard moderno com cards de cursos e estatísticas
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('aluno');

$user       = currentUser();
$cursoModel = new CursoModel();
$certModel  = new CertificadoModel();
$db         = getDB();

$cursos = $cursoModel->cursosDoAluno($user['id']);

$totalCerts = (int)$db->query(
    "SELECT COUNT(*) FROM certificados WHERE aluno_id=" . (int)$user['id']
)->fetchColumn();

$concluidos  = count(array_filter($cursos, fn($c) => $c['status_matricula'] === 'concluida'));
$andamento   = count(array_filter($cursos, fn($c) => $c['status_matricula'] === 'ativa'));

$pageTitle = 'Meus Cursos';
include __DIR__ . '/../app/views/layouts/aluno_header.php';
?>

<style>
/* ── Dashboard Stats ──────────────────── */
.stat-card {
    background: #fff;
    border: 1px solid #e9edf5;
    border-radius: 12px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
    transition: transform .15s, box-shadow .15s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.stat-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; flex-shrink: 0;
}
.stat-value { font-size: 28px; font-weight: 700; line-height: 1; color: #1a2035; }
.stat-label { font-size: 12px; color: #8898aa; margin-top: 2px; }

/* ── Curso Cards ──────────────────────── */
.curso-card {
    background: #fff;
    border: 1px solid #e9edf5;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
    transition: transform .2s, box-shadow .2s;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.curso-card:hover { transform: translateY(-4px); box-shadow: 0 8px 32px rgba(0,60,120,.10); }
.curso-card-thumb {
    height: 160px;
    background: linear-gradient(135deg, #003d7c 0%, #0066cc 100%);
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,.5); font-size: 48px;
    position: relative; overflow: hidden;
}
.curso-card-thumb img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.curso-card-badge {
    position: absolute; top: 10px; right: 10px;
    font-size: 11px; font-weight: 700; padding: 3px 10px;
    border-radius: 20px; text-transform: uppercase; letter-spacing: .5px;
}
.badge-concluida { background: #d1fae5; color: #065f46; }
.badge-ativa { background: #dbeafe; color: #1e40af; }

.curso-card-body { padding: 18px 20px; flex: 1; display: flex; flex-direction: column; }
.curso-card-title { font-weight: 700; font-size: 15px; color: #1a2035; margin-bottom: 6px; line-height: 1.3; }
.curso-card-meta { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
.curso-meta-item { display: flex; align-items: center; gap: 4px; font-size: 12px; color: #8898aa; }

.progress-wrap { margin-bottom: 16px; }
.progress-labels { display: flex; justify-content: space-between; margin-bottom: 5px; }
.progress-labels span { font-size: 12px; color: #8898aa; }
.progress-labels strong { font-size: 12px; color: #003d7c; }
.progress-bar-wrap { height: 6px; background: #f0f4f9; border-radius: 3px; overflow: hidden; }
.progress-bar-fill { height: 100%; background: linear-gradient(90deg, #003d7c, #0066cc); border-radius: 3px; transition: width .5s ease; }

.curso-card-footer { margin-top: auto; display: flex; gap: 8px; }
.curso-card-footer .btn { font-size: 13px; }

/* ── Empty state ──────────────────────── */
.empty-cursos { text-align: center; padding: 60px 20px; }
.empty-cursos-icon { font-size: 64px; color: #cbd5e1; margin-bottom: 16px; }
</style>

<!-- Saudação -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
  <div>
    <h2 class="mb-1" style="color:#1a2035;font-weight:700">
      Olá, <?= e(explode(' ', $user['nome'])[0]) ?>! 👋
    </h2>
    <p class="text-muted mb-0">Bem-vindo(a) à sua área de aprendizado.</p>
  </div>
  <div style="text-align:right">
    <small class="text-muted d-block">CRMV-TO — Educação Continuada</small>
    <small class="text-muted"><?= date('d/m/Y') ?></small>
  </div>
</div>

<!-- Estatísticas -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#e0e9f8;color:#003d7c">
        <i class="bi bi-journal-bookmark-fill"></i>
      </div>
      <div>
        <div class="stat-value"><?= count($cursos) ?></div>
        <div class="stat-label">Total de Cursos</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#d1fae5;color:#065f46">
        <i class="bi bi-check-circle-fill"></i>
      </div>
      <div>
        <div class="stat-value"><?= $concluidos ?></div>
        <div class="stat-label">Concluídos</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#dbeafe;color:#1e40af">
        <i class="bi bi-lightning-fill"></i>
      </div>
      <div>
        <div class="stat-value"><?= $andamento ?></div>
        <div class="stat-label">Em Andamento</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon" style="background:#fef3c7;color:#b45309">
        <i class="bi bi-award-fill"></i>
      </div>
      <div>
        <div class="stat-value"><?= $totalCerts ?></div>
        <div class="stat-label">Certificados</div>
      </div>
    </div>
  </div>
</div>

<!-- Título + Busca -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px">
  <div class="card-body p-3">
    <div class="d-flex flex-wrap align-items-center gap-3">
      <div class="flex-grow-1">
        <h5 class="mb-0" style="color:#1a2035;font-weight:700">
          <i class="bi bi-collection-play me-2 text-primary"></i>Meus Cursos
          <?php if ($cursos): ?>
          <span class="badge bg-primary ms-2" style="font-size:12px;font-weight:600"><?= count($cursos) ?></span>
          <?php endif; ?>
        </h5>
      </div>
      <?php if ($cursos): ?>
      <div class="d-flex gap-2 flex-wrap">
        <!-- Busca -->
        <div class="input-group" style="max-width:280px">
          <span class="input-group-text bg-white border-end-0" style="border-color:#dde6f0">
            <i class="bi bi-search text-muted" style="font-size:14px"></i>
          </span>
          <input type="text" id="buscaCurso" class="form-control border-start-0 ps-0"
                 placeholder="Buscar curso..." style="font-size:13px;border-color:#dde6f0"
                 oninput="filtrarCursos()">
        </div>
        <!-- Filtro status -->
        <select id="filtroCurso" class="form-select" style="max-width:150px;font-size:13px;border-color:#dde6f0" onchange="filtrarCursos()">
          <option value="">Todos</option>
          <option value="ativa">Em andamento</option>
          <option value="concluida">Concluídos</option>
        </select>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Mensagem sem resultados -->
<div id="semResultados" class="text-center py-4 d-none">
  <i class="bi bi-search" style="font-size:40px;color:#cbd5e1;display:block;margin-bottom:12px"></i>
  <p class="text-muted mb-0">Nenhum curso encontrado para a busca.</p>
</div>

<!-- Grid de cursos -->
<?php if ($cursos): ?>
<div class="row g-4" id="gridCursos">
  <?php foreach ($cursos as $c): ?>
  <div class="col-md-6 col-xl-4 curso-item"
       data-nome="<?= htmlspecialchars(strtolower($c['nome']), ENT_QUOTES) ?>"
       data-status="<?= $c['status_matricula'] ?>">
    <div class="curso-card">
      <!-- Thumbnail -->
      <div class="curso-card-thumb">
        <?php if (!empty($c['imagem'])): ?>
        <img src="<?= APP_URL ?>/public/uploads/cursos/<?= e($c['imagem']) ?>" alt="<?= e($c['nome']) ?>">
        <?php else: ?>
        <i class="bi bi-journal-play"></i>
        <?php endif; ?>
        <span class="curso-card-badge badge-<?= $c['status_matricula'] ?>">
          <?= $c['status_matricula'] === 'concluida' ? '✓ Concluído' : 'Em andamento' ?>
        </span>
      </div>

      <!-- Corpo -->
      <div class="curso-card-body">
        <div class="curso-card-title"><?= e($c['nome']) ?></div>
        <div class="curso-card-meta">
          <span class="curso-meta-item">
            <i class="bi bi-clock"></i><?= $c['carga_horaria'] ?>h
          </span>
          <span class="curso-meta-item">
            <i class="bi bi-laptop"></i><?= strtoupper($c['tipo']) ?>
          </span>
          <?php if (!empty($c['instrutores'])): ?>
          <span class="curso-meta-item">
            <i class="bi bi-person"></i><?= e(explode(',', $c['instrutores'])[0]) ?>
          </span>
          <?php endif; ?>
        </div>

        <!-- Progresso -->
        <div class="progress-wrap">
          <div class="progress-labels">
            <span>Progresso</span>
            <strong><?= $c['progresso'] ?>%</strong>
          </div>
          <div class="progress-bar-wrap">
            <div class="progress-bar-fill" style="width:<?= $c['progresso'] ?>%"></div>
          </div>
        </div>

        <!-- Ações -->
        <div class="curso-card-footer">
          <?php if ($c['tipo'] === 'ead'): ?>
          <a href="<?= APP_URL ?>/aluno/curso.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm flex-grow-1">
            <i class="bi bi-<?= $c['progresso'] > 0 ? 'play-fill' : 'play' ?> me-1"></i>
            <?= $c['progresso'] > 0 ? 'Continuar Curso' : 'Iniciar Curso' ?>
          </a>
          <?php else: ?>
          <a href="<?= APP_URL ?>/aluno/curso.php?id=<?= $c['id'] ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
            <i class="bi bi-people me-1"></i>Acessar Curso
          </a>
          <?php endif; ?>

          <?php if ($c['status_matricula'] === 'concluida'): ?>
          <a href="<?= APP_URL ?>/aluno/certificado.php?curso_id=<?= $c['id'] ?>"
             class="btn btn-outline-warning btn-sm" title="Emitir Certificado">
            <i class="bi bi-award"></i>
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php else: ?>
<div class="empty-cursos">
  <div class="empty-cursos-icon"><i class="bi bi-journal-x"></i></div>
  <h5 class="text-muted">Nenhum curso matriculado</h5>
  <p class="text-muted">Entre em contato com a administração do CRMV-TO para realizar sua matrícula em um curso.</p>
</div>
<?php endif; ?>

<script>
function filtrarCursos() {
    var busca = document.getElementById('buscaCurso')?.value.toLowerCase() ?? '';
    var filtro = document.getElementById('filtroCurso')?.value ?? '';
    var cards = document.querySelectorAll('#gridCursos .curso-item');
    var visiveis = 0;
    cards.forEach(function(card) {
        var nome = (card.dataset.nome || '').toLowerCase();
        var status = card.dataset.status || '';
        var matchBusca = busca === '' || nome.includes(busca);
        var matchFiltro = filtro === '' || status === filtro;
        if (matchBusca && matchFiltro) {
            card.style.display = '';
            visiveis++;
        } else {
            card.style.display = 'none';
        }
    });
    var semRes = document.getElementById('semResultados');
    if (semRes) semRes.classList.toggle('d-none', visiveis > 0);
}
</script>
<?php include __DIR__ . '/../app/views/layouts/aluno_footer.php'; ?>
