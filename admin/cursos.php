<?php
/**
 * admin/cursos.php — CRMV-TO EAD
 * Gerenciamento de cursos com layout em cards
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$model = new CursoModel();
$acao  = $_GET['acao'] ?? 'listar';
$id    = (int)($_GET['id'] ?? 0);
$curso = $id ? $model->findById($id) : null;

/* ── SALVAR ─────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $d = [
        'nome'                  => sanitize($_POST['nome'] ?? ''),
        'descricao'             => sanitize($_POST['descricao'] ?? ''),
        'tipo'                  => $_POST['tipo'] ?? 'ead',
        'carga_horaria'         => (int)($_POST['carga_horaria'] ?? 0),
        'instrutores'           => sanitize($_POST['instrutores'] ?? ''),
        'status'                => (int)($_POST['status'] ?? 1),
        'tem_avaliacao'         => (int)($_POST['tem_avaliacao'] ?? 0),
        'nota_minima'           => (float)($_POST['nota_minima'] ?? 60),
        'conteudo_programatico' => sanitize($_POST['conteudo_programatico'] ?? ''),
    ];
    if (!empty($_FILES['imagem']['name'])) {
        $img = uploadFile($_FILES['imagem'], UPLOAD_PATH . '/cursos', ALLOWED_IMAGE);
        if ($img) $d['imagem'] = $img;
    }
    if ($id) {
        $model->atualizar($id, $d);
        logAction('curso.atualizar', "Curso ID $id atualizado");
        setFlash('success', 'Curso atualizado com sucesso!');
    } else {
        $newId = $model->criar($d);
        logAction('curso.criar', "Curso criado ID $newId");
        setFlash('success', 'Curso criado com sucesso!');
    }
    redirect(APP_URL . '/admin/cursos.php');
}

/* ── DELETAR ─────────────────────────────────────── */
if ($acao === 'deletar' && $id) {
    $model->deletar($id);
    logAction('curso.deletar', "Curso ID $id deletado");
    setFlash('success', 'Curso removido com sucesso.');
    redirect(APP_URL . '/admin/cursos.php');
}

/* ── LISTAR ─────────────────────────────────────── */
$busca  = sanitize($_GET['busca'] ?? '');
$tipo   = sanitize($_GET['tipo'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$pag    = paginate($model->total($busca, $tipo), 12, $page);
$cursos = $model->listar($pag['offset'], $pag['per_page'], $busca, $tipo);

$pageTitle = ($acao === 'novo' || $acao === 'editar') ? ($id ? 'Editar Curso' : 'Novo Curso') : 'Gerenciar Cursos';
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<style>
/* ── Cards de cursos ──────────────────── */
.curso-admin-card {
    background: #fff;
    border: 1px solid #e9edf5;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    transition: transform .18s, box-shadow .18s;
    height: 100%;
    display: flex;
    flex-direction: column;
}
.curso-admin-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,60,120,.1); }

.curso-admin-thumb {
    height: 150px;
    background: linear-gradient(135deg, #003d7c 0%, #0055aa 100%);
    position: relative;
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,.35); font-size: 44px;
    overflow: hidden;
}
.curso-admin-thumb img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.curso-admin-status {
    position: absolute; top: 10px; left: 10px;
    font-size: 11px; font-weight: 700; padding: 3px 9px;
    border-radius: 20px; text-transform: uppercase;
}
.status-ativo   { background: rgba(16,185,129,.9); color: #fff; }
.status-inativo { background: rgba(107,114,128,.85); color: #fff; }

.curso-admin-body { padding: 16px 18px; flex: 1; }
.curso-admin-title {
    font-weight: 700; font-size: 14px; color: #1a2035;
    margin-bottom: 8px; line-height: 1.3;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.curso-admin-meta { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
.curso-meta-tag {
    display: flex; align-items: center; gap: 4px;
    font-size: 11px; color: #6b7280; background: #f3f6fb;
    padding: 3px 9px; border-radius: 20px;
}

.curso-admin-actions {
    padding: 12px 18px;
    border-top: 1px solid #f0f4f9;
    display: flex; gap: 6px; align-items: center; flex-wrap: wrap;
}
.curso-admin-actions .btn { font-size: 12px; }
</style>

<?php if ($acao === 'novo' || $acao === 'editar'): ?>
<!-- ════════════════════════════════
     FORMULÁRIO DE CURSO
     ════════════════════════════════ -->
<div class="page-header">
  <div>
    <h1><?= $id ? 'Editar Curso' : 'Novo Curso' ?></h1>
    <p class="page-subtitle"><?= $id ? 'Atualize os dados do curso' : 'Preencha os dados do novo curso' ?></p>
  </div>
  <a href="<?= APP_URL ?>/admin/cursos.php" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Voltar
  </a>
</div>

<div class="form-card">
  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Nome do Curso *</label>
        <input type="text" name="nome" class="form-control" required value="<?= e($curso['nome'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Tipo *</label>
        <select name="tipo" class="form-select">
          <option value="ead"        <?= ($curso['tipo'] ?? 'ead') === 'ead'        ? 'selected' : '' ?>>EAD</option>
          <option value="presencial" <?= ($curso['tipo'] ?? '')     === 'presencial' ? 'selected' : '' ?>>Presencial</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Carga Horária (h) *</label>
        <input type="number" name="carga_horaria" class="form-control" min="1" required value="<?= e($curso['carga_horaria'] ?? '') ?>">
      </div>
      <div class="col-md-12">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control" rows="3"><?= e($curso['descricao'] ?? '') ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Instrutores</label>
        <input type="text" name="instrutores" class="form-control" placeholder="Ex: Prof. João Silva, Dra. Maria Souza" value="<?= e($curso['instrutores'] ?? '') ?>">
        <small class="text-muted">Separe múltiplos instrutores por vírgula.</small>
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="1" <?= ($curso['status'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
          <option value="0" <?= ($curso['status'] ?? 1) == 0 ? 'selected' : '' ?>>Inativo</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Imagem de Capa</label>
        <input type="file" name="imagem" class="form-control" accept="image/*">
        <?php if (!empty($curso['imagem'])): ?>
        <small class="text-muted">Imagem atual: <img src="<?= APP_URL ?>/public/uploads/cursos/<?= e($curso['imagem']) ?>" height="24" class="rounded ms-1"></small>
        <?php endif; ?>
      </div>
      <div class="col-md-12">
        <label class="form-label">Conteúdo Programático</label>
        <textarea name="conteudo_programatico" class="form-control" rows="6" placeholder="Digite cada tópico em uma linha separada..."><?= e($curso['conteudo_programatico'] ?? '') ?></textarea>
        <small class="text-muted">Um tópico por linha. Será exibido no verso do certificado.</small>
      </div>
      <div class="col-md-3">
        <label class="form-label">Avaliação</label>
        <select name="tem_avaliacao" class="form-select">
          <option value="0" <?= ($curso['tem_avaliacao'] ?? 0) == 0 ? 'selected' : '' ?>>Sem avaliação</option>
          <option value="1" <?= ($curso['tem_avaliacao'] ?? 0) == 1 ? 'selected' : '' ?>>Com avaliação</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Nota Mínima (%)</label>
        <input type="number" name="nota_minima" class="form-control" min="0" max="100" step="0.01" value="<?= e($curso['nota_minima'] ?? 60) ?>">
      </div>
    </div>
    <hr class="my-4">
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>Salvar Curso
      </button>
      <a href="<?= APP_URL ?>/admin/cursos.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php else: ?>
<!-- ════════════════════════════════
     LISTAGEM EM CARDS
     ════════════════════════════════ -->
<div class="page-header">
  <div>
    <h1>Gerenciar Cursos</h1>
    <p class="page-subtitle"><?= $pag['total'] ?> curso(s) cadastrado(s)</p>
  </div>
  <a href="?acao=novo" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Novo Curso
  </a>
</div>

<!-- Filtros -->
<div class="data-card mb-4">
  <div class="data-card-header">
    <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" name="busca" class="form-control" placeholder="Buscar curso..." value="<?= e($busca) ?>" style="min-width:200px">
      </div>
      <select name="tipo" class="form-select" style="width:140px">
        <option value="">Todos os tipos</option>
        <option value="ead"        <?= $tipo === 'ead'        ? 'selected' : '' ?>>EAD</option>
        <option value="presencial" <?= $tipo === 'presencial' ? 'selected' : '' ?>>Presencial</option>
      </select>
      <button class="btn btn-outline-primary">Filtrar</button>
      <?php if ($busca || $tipo): ?>
      <a href="<?= APP_URL ?>/admin/cursos.php" class="btn btn-outline-secondary">Limpar</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if ($cursos): ?>
<!-- Grid de cards -->
<div class="row g-4">
  <?php foreach ($cursos as $c): ?>
  <div class="col-md-6 col-xl-4">
    <div class="curso-admin-card">
      <!-- Thumbnail -->
      <div class="curso-admin-thumb">
        <?php if (!empty($c['imagem'])): ?>
        <img src="<?= APP_URL ?>/public/uploads/cursos/<?= e($c['imagem']) ?>" alt="<?= e($c['nome']) ?>">
        <?php else: ?>
        <i class="bi bi-journal-play"></i>
        <?php endif; ?>
        <span class="curso-admin-status status-<?= $c['status'] ? 'ativo' : 'inativo' ?>">
          <?= $c['status'] ? 'Ativo' : 'Inativo' ?>
        </span>
      </div>

      <!-- Corpo -->
      <div class="curso-admin-body">
        <div class="curso-admin-title"><?= e($c['nome']) ?></div>
        <div class="curso-admin-meta">
          <span class="curso-meta-tag">
            <i class="bi bi-laptop"></i><?= strtoupper($c['tipo']) ?>
          </span>
          <span class="curso-meta-tag">
            <i class="bi bi-clock"></i><?= $c['carga_horaria'] ?>h
          </span>
          <?php if ($c['tem_avaliacao']): ?>
          <span class="curso-meta-tag" style="background:#fef3c7;color:#b45309">
            <i class="bi bi-patch-question"></i>Avaliação
          </span>
          <?php endif; ?>
        </div>
        <?php if (!empty($c['instrutores'])): ?>
        <small class="text-muted">
          <i class="bi bi-person me-1"></i><?= e($c['instrutores']) ?>
        </small>
        <?php endif; ?>
      </div>

      <!-- Ações -->
      <div class="curso-admin-actions">
        <a href="<?= APP_URL ?>/admin/aulas.php?curso_id=<?= $c['id'] ?>"
           class="btn btn-sm btn-outline-info" title="Gerenciar Aulas">
          <i class="bi bi-play-circle me-1"></i>Aulas
        </a>
        <a href="<?= APP_URL ?>/admin/materiais.php?curso_id=<?= $c['id'] ?>"
           class="btn btn-sm btn-outline-secondary" title="Materiais">
          <i class="bi bi-file-earmark"></i>
        </a>
        <?php if ($c['tem_avaliacao']): ?>
        <a href="<?= APP_URL ?>/admin/avaliacao.php?curso_id=<?= $c['id'] ?>"
           class="btn btn-sm btn-outline-warning" title="Avaliação">
          <i class="bi bi-patch-question"></i>
        </a>
        <?php endif; ?>
        <a href="?acao=editar&id=<?= $c['id'] ?>"
           class="btn btn-sm btn-outline-primary ms-auto" title="Editar">
          <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <a href="?acao=deletar&id=<?= $c['id'] ?>"
           class="btn btn-sm btn-outline-danger" title="Excluir"
           data-confirm="Deseja excluir o curso '<?= e($c['nome']) ?>'? Isso removerá aulas, materiais e matrículas!">
          <i class="bi bi-trash"></i>
        </a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php else: ?>
<div class="empty-state">
  <i class="bi bi-journal-x"></i>
  Nenhum curso encontrado.
  <a href="?acao=novo" class="btn btn-primary btn-sm mt-3">
    <i class="bi bi-plus me-1"></i>Criar primeiro curso
  </a>
</div>
<?php endif; ?>

<!-- Paginação -->
<?php if ($pag['pages'] > 1): ?>
<nav class="mt-4">
  <ul class="pagination pagination-sm justify-content-center">
    <?php for ($i = 1; $i <= $pag['pages']; $i++): ?>
    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
      <a class="page-link" href="?busca=<?= urlencode($busca) ?>&tipo=<?= $tipo ?>&p=<?= $i ?>"><?= $i ?></a>
    </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
