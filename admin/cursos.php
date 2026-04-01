<?php
/**
 * admin/cursos.php — CRMV EAD
 * Gerenciamento de cursos com página de detalhe em abas
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$cursoModel = new CursoModel();
$aulaModel  = new AulaModel();
$avalModel  = new AvaliacaoModel();
$matModel   = new MaterialModel();
$certModel  = new CertificadoModel();

$acao    = $_GET['acao'] ?? 'listar';
$id      = (int)($_GET['id'] ?? 0);
$tab     = $_GET['tab'] ?? 'config';
$curso   = $id ? $cursoModel->findById($id) : null;

/* ── SALVAR CURSO ──────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'curso') {
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
        $cursoModel->atualizar($id, $d);
        // Se ativou avaliação e ainda não existe registro, criar automaticamente
        if ($d['tem_avaliacao']) {
            $avalExiste = $avalModel->porCurso($id);
            if (!$avalExiste) {
                $avalModel->criar([
                    'curso_id'   => $id,
                    'titulo'     => 'Avaliação Final — ' . $d['nome'],
                    'descricao'  => '',
                    'tentativas' => 1,
                ]);
            }
        }
        logAction('curso.atualizar', "Curso ID $id");
        setFlash('success', 'Configurações salvas!');
        redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$id&tab=config");
    } else {
        $newId = $cursoModel->criar($d);
        // Se já criou com avaliação, criar o registro automaticamente
        if ($d['tem_avaliacao']) {
            $avalModel->criar([
                'curso_id'   => $newId,
                'titulo'     => 'Avaliação Final — ' . $d['nome'],
                'descricao'  => '',
                'tentativas' => 1,
            ]);
        }
        logAction('curso.criar', "Curso ID $newId");
        setFlash('success', 'Curso criado! Configure as aulas e a avaliação.');
        redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$newId&tab=config");
    }
}

/* ── SALVAR AULA ───────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'aula') {
    csrfCheck();
    $aulaId = (int)($_POST['aula_id'] ?? 0);
    $tipoAula = $_POST['tipo_aula'] ?? 'link';

    $d = [
        'curso_id'  => $id,
        'titulo'    => sanitize($_POST['titulo'] ?? ''),
        'descricao' => sanitize($_POST['descricao'] ?? ''),
        'url_video' => '',
        'ordem'     => (int)($_POST['ordem'] ?? 1),
        'status'    => (int)($_POST['status'] ?? 1),
    ];

    if ($tipoAula === 'link') {
        $d['url_video'] = sanitize($_POST['url_video'] ?? '');
    } elseif ($tipoAula === 'upload') {
        // Criar pasta de vídeos se não existir
        $videoDir = UPLOAD_PATH . '/videos';
        if (!is_dir($videoDir)) mkdir($videoDir, 0755, true);

        if (!empty($_FILES['video_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ALLOWED_VIDEO) && $_FILES['video_file']['size'] <= 200 * 1024 * 1024) {
                $nomeVideo = uniqid('vid_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $videoDir . '/' . $nomeVideo)) {
                    // Marcar URL com prefixo local para distinguir
                    $d['url_video'] = 'local://' . $nomeVideo;
                }
            } else {
                setFlash('error', 'Arquivo inválido. Use MP4, WebM ou OGG. Máx 200MB.');
                redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$id&tab=aulas");
            }
        } elseif ($aulaId && !empty($_POST['manter_video'])) {
            $aulaAtual = $aulaModel->findById($aulaId);
            $d['url_video'] = $aulaAtual['url_video'] ?? '';
        }
    }

    if ($aulaId) {
        $aulaModel->atualizar($aulaId, $d);
        setFlash('success', 'Aula atualizada!');
    } else {
        $aulaModel->criar($d);
        setFlash('success', 'Aula criada!');
    }
    redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$id&tab=aulas");
}

/* ── DELETAR AULA ──────────────────────────── */
if ($acao === 'del_aula' && $id && ($aid = (int)($_GET['aid'] ?? 0))) {
    $aulaModel->deletar($aid);
    setFlash('success', 'Aula removida.');
    redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$id&tab=aulas");
}

/* ── SALVAR AVALIAÇÃO ──────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'avaliacao') {
    csrfCheck();
    $aval = $avalModel->porCurso($id);
    $d = ['curso_id' => $id, 'titulo' => sanitize($_POST['titulo']), 'descricao' => sanitize($_POST['descricao']), 'tentativas' => (int)$_POST['tentativas']];
    if ($aval) $avalModel->atualizar($aval['id'], $d);
    else       $avalModel->criar($d);
    setFlash('success', 'Avaliação salva!');
    redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$id&tab=avaliacao");
}

/* ── CRIAR PERGUNTA ────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'pergunta') {
    csrfCheck();
    $aval = $avalModel->porCurso($id);
    if (!$aval) { setFlash('error', 'Crie a avaliação primeiro.'); redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$id&tab=avaliacao"); }
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
    setFlash('success', 'Pergunta adicionada!');
    redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$id&tab=avaliacao");
}

/* ── DELETAR PERGUNTA ──────────────────────── */
if ($acao === 'del_pergunta' && $id && ($pid = (int)($_GET['pid'] ?? 0))) {
    $avalModel->deletarPergunta($pid);
    setFlash('success', 'Pergunta removida.');
    redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$id&tab=avaliacao");
}

/* ── UPLOAD MATERIAL ───────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'material') {
    csrfCheck();
    if (!empty($_FILES['arquivo']['name'])) {
        $nome = uploadFile($_FILES['arquivo'], MAT_PATH, ALLOWED_MATERIAL);
        if ($nome) {
            $matModel->criar(['curso_id' => $id, 'titulo' => sanitize($_POST['titulo'] ?? $_FILES['arquivo']['name']), 'arquivo' => $nome, 'tipo' => strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION)), 'tamanho' => $_FILES['arquivo']['size']]);
            setFlash('success', 'Material enviado!');
        } else { setFlash('error', 'Erro no upload. Verifique tipo e tamanho.'); }
    }
    redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$id&tab=certificado");
}

/* ── DELETAR MATERIAL ──────────────────────── */
if ($acao === 'del_material' && $id && ($mid = (int)($_GET['mid'] ?? 0))) {
    $mat = $matModel->findById($mid);
    if ($mat) { @unlink(MAT_PATH . '/' . $mat['arquivo']); $matModel->deletar($mid); }
    setFlash('success', 'Material removido.');
    redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$id&tab=certificado");
}

/* ── DELETAR CURSO ─────────────────────────── */
if ($acao === 'deletar' && $id) {
    $cursoModel->deletar($id);
    logAction('curso.deletar', "Curso ID $id");
    setFlash('success', 'Curso removido.');
    redirect(APP_URL . '/admin/cursos.php');
}

/* ── LISTAR ────────────────────────────────── */
$busca  = sanitize($_GET['busca'] ?? '');
$tipo   = sanitize($_GET['tipo']  ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$pag    = paginate($cursoModel->total($busca, $tipo), 12, $page);
$cursos = $cursoModel->listar($pag['offset'], $pag['per_page'], $busca, $tipo);

// Dados para detalhe
$aulas     = $id ? $aulaModel->porCurso($id) : [];
$aval      = $id ? $avalModel->porCurso($id) : null;
$perguntas = $aval ? $avalModel->perguntas($aval['id']) : [];
foreach ($perguntas as &$p) { $p['alternativas'] = $avalModel->alternativas($p['id']); }
unset($p);
$materiais = $id ? $matModel->porCurso($id) : [];

// Para edição de aula
$editAulaId = (int)($_GET['edit_aula'] ?? 0);
$editAula   = $editAulaId ? $aulaModel->findById($editAulaId) : null;

$pageTitle = $acao === 'detalhe' && $curso ? $curso['nome'] : ($acao === 'novo' ? 'Novo Curso' : 'Gerenciar Cursos');
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<?php if ($acao === 'novo'): ?>
<!-- ════ NOVO CURSO (formulário rápido) ════ -->
<div class="page-header">
  <div>
    <h1>Novo Curso</h1>
    <p class="page-subtitle">Preencha as informações básicas</p>
  </div>
  <a href="<?= APP_URL ?>/admin/cursos.php" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Voltar
  </a>
</div>
<div class="form-card">
  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <input type="hidden" name="form" value="curso">
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Nome do Curso *</label>
        <input type="text" name="nome" class="form-control" required placeholder="Ex: Bem-estar Animal — Módulo I">
      </div>
      <div class="col-md-2">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select">
          <option value="ead">EAD</option>
          <option value="presencial">Presencial</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Carga Horária (h)</label>
        <input type="number" name="carga_horaria" class="form-control" min="1" value="8">
      </div>
      <div class="col-12">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control" rows="3" placeholder="Descreva o objetivo do curso..."></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Instrutores</label>
        <input type="text" name="instrutores" class="form-control" placeholder="Ex: Dr. João Silva, Dra. Maria Souza">
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select"><option value="1">Ativo</option><option value="0">Inativo</option></select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Imagem de Capa</label>
        <input type="file" name="imagem" class="form-control" accept="image/*">
      </div>
    </div>
    <hr class="my-3">
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Criar Curso</button>
      <a href="<?= APP_URL ?>/admin/cursos.php" class="btn btn-outline-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php elseif ($acao === 'detalhe' && $curso): ?>
<!-- ════ DETALHE DO CURSO COM ABAS ════ -->
<div class="page-header">
  <div class="d-flex align-items-center gap-3">
    <a href="<?= APP_URL ?>/admin/cursos.php" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h1><?= e($curso['nome']) ?></h1>
      <p class="page-subtitle mb-0">
        <span class="badge-status badge-<?= $curso['tipo'] ?>"><?= strtoupper($curso['tipo']) ?></span>
        &nbsp;·&nbsp; <?= $curso['carga_horaria'] ?>h
        &nbsp;·&nbsp; <span class="badge-status badge-<?= $curso['status'] ? 'ativo' : 'inativo' ?>"><?= $curso['status'] ? 'Ativo' : 'Inativo' ?></span>
      </p>
    </div>
  </div>
  <a href="?acao=deletar&id=<?= $id ?>" class="btn btn-outline-danger btn-sm"
     data-confirm="Excluir o curso '<?= e($curso['nome']) ?>'? Isso removerá tudo!">
    <i class="bi bi-trash me-1"></i>Excluir Curso
  </a>
</div>

<!-- ABAS -->
<div class="course-tabs">
  <a class="course-tab <?= $tab === 'config'    ? 'active' : '' ?>"
     href="?acao=detalhe&id=<?= $id ?>&tab=config">
    <i class="bi bi-gear-fill"></i> Configurações
  </a>
  <a class="course-tab <?= $tab === 'aulas'     ? 'active' : '' ?>"
     href="?acao=detalhe&id=<?= $id ?>&tab=aulas">
    <i class="bi bi-play-circle-fill"></i> Aulas
    <span class="badge bg-primary ms-1" style="font-size:10px"><?= count($aulas) ?></span>
  </a>
  <a class="course-tab <?= $tab === 'avaliacao' ? 'active' : '' ?>"
     href="?acao=detalhe&id=<?= $id ?>&tab=avaliacao">
    <i class="bi bi-patch-question-fill"></i> Avaliação
    <span class="badge bg-warning ms-1" style="font-size:10px"><?= count($perguntas) ?></span>
  </a>
  <a class="course-tab <?= $tab === 'certificado' ? 'active' : '' ?>"
     href="?acao=detalhe&id=<?= $id ?>&tab=certificado">
    <i class="bi bi-award-fill"></i> Materiais & Certificado
    <span class="badge bg-secondary ms-1" style="font-size:10px"><?= count($materiais) ?></span>
  </a>
</div>

<!-- ── ABA CONFIGURAÇÕES ──────────────────────── -->
<?php if ($tab === 'config'): ?>
<div class="form-card">
  <form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <input type="hidden" name="form" value="curso">
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Nome do Curso *</label>
        <input type="text" name="nome" class="form-control" required value="<?= e($curso['nome']) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Tipo</label>
        <select name="tipo" class="form-select">
          <option value="ead"        <?= $curso['tipo'] === 'ead'        ? 'selected':'' ?>>EAD</option>
          <option value="presencial" <?= $curso['tipo'] === 'presencial' ? 'selected':'' ?>>Presencial</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Carga Horária (h)</label>
        <input type="number" name="carga_horaria" class="form-control" min="1" value="<?= e($curso['carga_horaria']) ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control" rows="4"><?= e($curso['descricao'] ?? '') ?></textarea>
      </div>
      <div class="col-md-5">
        <label class="form-label">Instrutores</label>
        <input type="text" name="instrutores" class="form-control" value="<?= e($curso['instrutores'] ?? '') ?>" placeholder="Separe por vírgula">
      </div>
      <div class="col-md-2">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="1" <?= $curso['status'] == 1 ? 'selected':'' ?>>Ativo</option>
          <option value="0" <?= $curso['status'] == 0 ? 'selected':'' ?>>Inativo</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Avaliação</label>
        <select name="tem_avaliacao" class="form-select">
          <option value="0" <?= $curso['tem_avaliacao'] == 0 ? 'selected':'' ?>>Sem avaliação</option>
          <option value="1" <?= $curso['tem_avaliacao'] == 1 ? 'selected':'' ?>>Com avaliação</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Nota Mínima (%)</label>
        <input type="number" name="nota_minima" class="form-control" min="0" max="100" value="<?= e($curso['nota_minima'] ?? 60) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Imagem de Capa</label>
        <input type="file" name="imagem" class="form-control" accept="image/*">
        <?php if (!empty($curso['imagem'])): ?>
        <div class="mt-2">
          <img src="<?= APP_URL ?>/public/uploads/cursos/<?= e($curso['imagem']) ?>" height="48" class="rounded border">
        </div>
        <?php endif; ?>
      </div>
      <div class="col-12">
        <label class="form-label">Conteúdo Programático</label>
        <textarea name="conteudo_programatico" class="form-control" rows="6"
                  placeholder="Um tópico por linha — será exibido no verso do certificado"><?= e($curso['conteudo_programatico'] ?? '') ?></textarea>
        <small class="text-muted">Um tópico por linha.</small>
      </div>
    </div>
    <hr class="my-3">
    <button type="submit" class="btn btn-primary px-4">
      <i class="bi bi-check-lg me-1"></i>Salvar Configurações
    </button>
  </form>
</div>

<!-- ── ABA AULAS ──────────────────────────────── -->
<?php elseif ($tab === 'aulas'): ?>
<div class="row g-3">
  <!-- Formulário adicionar/editar aula -->
  <div class="col-md-4">
    <div class="form-card">
      <h6 class="mb-3">
        <i class="bi bi-<?= $editAula ? 'pencil' : 'plus-circle' ?> me-2 text-primary"></i>
        <?= $editAula ? 'Editar Aula' : 'Nova Aula' ?>
      </h6>
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="form" value="aula">
        <input type="hidden" name="aula_id" value="<?= $editAulaId ?>">

        <div class="mb-3">
          <label class="form-label">Título *</label>
          <input type="text" name="titulo" class="form-control" required value="<?= e($editAula['titulo'] ?? '') ?>">
        </div>

        <div class="row g-2 mb-3">
          <div class="col">
            <label class="form-label">Ordem</label>
            <input type="number" name="ordem" class="form-control" min="1" value="<?= e($editAula['ordem'] ?? count($aulas)+1) ?>">
          </div>
          <div class="col">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="1" <?= ($editAula['status'] ?? 1) == 1 ? 'selected':'' ?>>Ativo</option>
              <option value="0" <?= ($editAula['status'] ?? 1) == 0 ? 'selected':'' ?>>Inativo</option>
            </select>
          </div>
        </div>

        <!-- Tipo de aula -->
        <?php
          $tipoAtual = '';
          if ($editAula) {
            $tipoAtual = (!empty($editAula['url_video']) && str_starts_with($editAula['url_video'], 'local://')) ? 'upload' : 'link';
          }
        ?>
        <div class="mb-3">
          <label class="form-label">Tipo de Aula</label>
          <div class="d-flex gap-2">
            <div class="form-check flex-fill border rounded p-2 <?= $tipoAtual !== 'upload' ? 'border-primary bg-light' : '' ?>">
              <input class="form-check-input" type="radio" name="tipo_aula" id="tipoLink" value="link"
                     <?= $tipoAtual !== 'upload' ? 'checked' : '' ?> onchange="setTipoAula(this.value)">
              <label class="form-check-label w-100 cursor-pointer" for="tipoLink">
                <i class="bi bi-link-45deg text-primary"></i> Link de Vídeo
              </label>
            </div>
            <div class="form-check flex-fill border rounded p-2 <?= $tipoAtual === 'upload' ? 'border-success bg-light' : '' ?>">
              <input class="form-check-input" type="radio" name="tipo_aula" id="tipoUpload" value="upload"
                     <?= $tipoAtual === 'upload' ? 'checked' : '' ?> onchange="setTipoAula(this.value)">
              <label class="form-check-label w-100 cursor-pointer" for="tipoUpload">
                <i class="bi bi-upload text-success"></i> Upload de Vídeo
              </label>
            </div>
          </div>
        </div>

        <!-- Campo link -->
        <div id="campoLink" class="mb-3" <?= $tipoAtual === 'upload' ? 'style="display:none"' : '' ?>>
          <label class="form-label">URL do Vídeo</label>
          <input type="url" name="url_video" class="form-control" placeholder="https://youtube.com/watch?v=..."
                 value="<?= e($editAula && $tipoAtual !== 'upload' ? ($editAula['url_video'] ?? '') : '') ?>">
          <small class="text-muted">YouTube, Vimeo ou URL direta.</small>
        </div>

        <!-- Campo upload -->
        <div id="campoUpload" class="mb-3" <?= $tipoAtual !== 'upload' ? 'style="display:none"' : '' ?>>
          <label class="form-label">Arquivo de Vídeo</label>
          <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/ogg">
          <small class="text-muted">MP4, WebM ou OGG. Máx 200MB.</small>
          <?php if ($editAula && $tipoAtual === 'upload'): ?>
          <div class="mt-2 alert-crmv">
            <i class="bi bi-info-circle me-1"></i>
            Vídeo atual: <?= e(str_replace('local://', '', $editAula['url_video'])) ?>
            <input type="hidden" name="manter_video" value="1">
          </div>
          <?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label">Descrição</label>
          <textarea name="descricao" class="form-control" rows="2"><?= e($editAula['descricao'] ?? '') ?></textarea>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1">
            <i class="bi bi-check-lg me-1"></i><?= $editAula ? 'Salvar' : 'Adicionar' ?>
          </button>
          <?php if ($editAula): ?>
          <a href="?acao=detalhe&id=<?= $id ?>&tab=aulas" class="btn btn-outline-secondary">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Lista de aulas -->
  <div class="col-md-8">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-play-circle me-2"></i>Aulas do Curso</h6>
        <span class="badge bg-primary"><?= count($aulas) ?> aula(s)</span>
      </div>
      <?php if ($aulas): ?>
      <?php foreach ($aulas as $a):
        $isLocal = str_starts_with($a['url_video'] ?? '', 'local://');
        $tipoLabel = $isLocal ? 'upload' : ($a['url_video'] ? 'link' : '');
      ?>
      <div class="aula-item">
        <div class="aula-num"><?= $a['ordem'] ?></div>
        <div class="aula-title">
          <?= e($a['titulo']) ?>
          <?php if ($tipoLabel === 'upload'): ?>
          <span class="aula-type-badge aula-type-upload ms-2"><i class="bi bi-upload me-1"></i>Upload</span>
          <?php elseif ($tipoLabel === 'link'): ?>
          <span class="aula-type-badge aula-type-link ms-2"><i class="bi bi-link-45deg me-1"></i>Link</span>
          <?php endif; ?>
        </div>
        <span class="badge-status badge-<?= $a['status'] ? 'ativo':'inativo' ?>"><?= $a['status'] ? 'Ativo':'Inativo' ?></span>
        <div class="d-flex gap-1 ms-2">
          <a href="?acao=detalhe&id=<?= $id ?>&tab=aulas&edit_aula=<?= $a['id'] ?>"
             class="btn btn-icon btn-outline-primary btn-sm" title="Editar">
            <i class="bi bi-pencil"></i>
          </a>
          <a href="?acao=del_aula&id=<?= $id ?>&aid=<?= $a['id'] ?>"
             class="btn btn-icon btn-outline-danger btn-sm"
             data-confirm="Excluir a aula '<?= e($a['titulo']) ?>'?">
            <i class="bi bi-trash"></i>
          </a>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="empty-state"><i class="bi bi-camera-video-off"></i><p>Nenhuma aula cadastrada.<br>Adicione a primeira aula ao lado.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ── ABA AVALIAÇÃO ──────────────────────────── -->
<?php elseif ($tab === 'avaliacao'): ?>
<?php if (!$curso['tem_avaliacao']): ?>
<div class="alert-crmv mb-4">
  <i class="bi bi-info-circle me-2"></i>
  Este curso está configurado sem avaliação. Ative a opção "Com avaliação" na aba <strong>Configurações</strong> para gerenciar a avaliação aqui.
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-md-4">
    <div class="form-card mb-3">
      <h6 class="mb-3"><i class="bi bi-sliders me-2 text-primary"></i>Configurações da Avaliação</h6>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="form" value="avaliacao">
        <div class="mb-3">
          <label class="form-label">Título</label>
          <input type="text" name="titulo" class="form-control" value="<?= e($aval['titulo'] ?? 'Avaliação Final') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Descrição/Instrução</label>
          <textarea name="descricao" class="form-control" rows="3"><?= e($aval['descricao'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Tentativas Permitidas</label>
          <input type="number" name="tentativas" class="form-control" min="1" max="10" value="<?= e($aval['tentativas'] ?? 1) ?>">
        </div>
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save me-1"></i>Salvar Configuração</button>
      </form>
    </div>

    <?php if ($aval): ?>
    <!-- Formulário nova pergunta -->
    <div class="form-card">
      <h6 class="mb-3"><i class="bi bi-plus-circle me-2 text-success"></i>Nova Pergunta</h6>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="form" value="pergunta">
        <div class="mb-3">
          <label class="form-label">Enunciado *</label>
          <textarea name="enunciado" class="form-control" rows="3" required placeholder="Digite a pergunta..."></textarea>
        </div>
        <div class="row g-2 mb-3">
          <div class="col">
            <label class="form-label">Pontos</label>
            <input type="number" name="pontos" class="form-control" min="1" value="1">
          </div>
          <div class="col">
            <label class="form-label">Ordem</label>
            <input type="number" name="ordem" class="form-control" min="1" value="<?= count($perguntas)+1 ?>">
          </div>
        </div>
        <label class="form-label">Alternativas (marque a correta)</label>
        <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="d-flex gap-2 mb-2 align-items-center">
          <input type="radio" name="correta" value="<?= $i ?>" class="form-check-input mt-0" <?= $i === 0 ? 'checked' : '' ?> title="Correta">
          <input type="text" name="alternativas[]" class="form-control form-control-sm" placeholder="Alternativa <?= chr(65+$i) ?>">
        </div>
        <?php endfor; ?>
        <button type="submit" class="btn btn-success w-100 mt-2"><i class="bi bi-plus me-1"></i>Adicionar Pergunta</button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-md-8">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-list-check me-2"></i>Perguntas</h6>
        <span class="badge bg-warning text-dark"><?= count($perguntas) ?> pergunta(s)</span>
      </div>
      <?php if ($perguntas): ?>
      <?php foreach ($perguntas as $idx => $p): ?>
      <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <span class="badge bg-primary me-2"><?= $idx+1 ?></span>
            <strong style="font-size:14px"><?= e($p['enunciado']) ?></strong>
            <small class="text-muted ms-2">(<?= $p['pontos'] ?> pt)</small>
          </div>
          <a href="?acao=del_pergunta&id=<?= $id ?>&pid=<?= $p['id'] ?>&tab=avaliacao"
             class="btn btn-icon btn-outline-danger btn-sm"
             data-confirm="Excluir esta pergunta?"><i class="bi bi-trash"></i></a>
        </div>
        <div class="d-flex flex-column gap-1 ps-4">
          <?php foreach ($p['alternativas'] as $alt): ?>
          <div class="d-flex align-items-center gap-2 p-2 rounded <?= $alt['correta'] ? 'bg-success bg-opacity-10 border border-success border-opacity-25' : '' ?>">
            <i class="bi bi-<?= $alt['correta'] ? 'check-circle-fill text-success' : 'circle text-muted' ?>"></i>
            <span style="font-size:13px"><?= e($alt['texto']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="empty-state"><i class="bi bi-patch-question"></i><p>Nenhuma pergunta cadastrada.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ── ABA CERTIFICADO & MATERIAIS ───────────── -->
<?php elseif ($tab === 'certificado'): ?>
<div class="row g-3">
  <div class="col-md-4">
    <div class="form-card">
      <h6 class="mb-3"><i class="bi bi-cloud-upload me-2 text-primary"></i>Enviar Material Didático</h6>
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <input type="hidden" name="form" value="material">
        <div class="mb-3">
          <label class="form-label">Título</label>
          <input type="text" name="titulo" class="form-control" placeholder="Nome do arquivo...">
        </div>
        <div class="mb-3">
          <label class="form-label">Arquivo *</label>
          <input type="file" name="arquivo" class="form-control" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.jpg,.png">
          <small class="text-muted">PDF, DOC, PPT, XLS, ZIP. Máx <?= MAX_UPLOAD_MB ?>MB.</small>
        </div>
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i>Enviar</button>
      </form>
    </div>

    <div class="form-card mt-3">
      <h6 class="mb-2"><i class="bi bi-award me-2 text-warning"></i>Certificado</h6>
      <p class="text-muted" style="font-size:13px">
        O certificado é gerado automaticamente quando o aluno conclui o curso (e passa na avaliação, se houver).
        Edite o <strong>Conteúdo Programático</strong> e os <strong>Instrutores</strong> na aba Configurações para personalizar o verso do certificado.
      </p>
      <?php
        $certs = $certModel->doCurso($id);
      ?>
      <div class="d-flex align-items-center gap-2 p-2 rounded bg-light">
        <i class="bi bi-award-fill text-warning fs-4"></i>
        <div><strong><?= count($certs) ?></strong> certificado(s) emitido(s)</div>
      </div>
    </div>
  </div>

  <div class="col-md-8">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-files me-2"></i>Materiais Didáticos</h6>
        <span class="badge bg-primary"><?= count($materiais) ?> arquivo(s)</span>
      </div>
      <?php if ($materiais): ?>
      <div class="table-responsive">
        <table class="table table-ead">
          <thead><tr><th>Título</th><th>Tipo</th><th>Tamanho</th><th>Ações</th></tr></thead>
          <tbody>
          <?php foreach ($materiais as $m): ?>
          <tr>
            <td><?= e($m['titulo']) ?></td>
            <td><span class="badge bg-secondary text-uppercase"><?= e($m['tipo']) ?></span></td>
            <td><?= $m['tamanho'] ? round($m['tamanho']/1024) . ' KB' : '—' ?></td>
            <td>
              <a href="<?= APP_URL ?>/public/uploads/materiais/<?= e($m['arquivo']) ?>" target="_blank"
                 class="btn btn-icon btn-outline-success btn-sm"><i class="bi bi-download"></i></a>
              <a href="?acao=del_material&id=<?= $id ?>&mid=<?= $m['id'] ?>&tab=certificado"
                 class="btn btn-icon btn-outline-danger btn-sm"
                 data-confirm="Remover '<?= e($m['titulo']) ?>'?"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-state"><i class="bi bi-folder-x"></i><p>Nenhum material enviado.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php else: ?>
<!-- ════ LISTAGEM DE CURSOS ════ -->
<div class="page-header">
  <div>
    <h1>Gerenciar Cursos</h1>
    <p class="page-subtitle"><?= $pag['total'] ?> curso(s) cadastrado(s)</p>
  </div>
  <a href="?acao=novo" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i>Novo Curso
  </a>
</div>

<div class="data-card mb-4">
  <div class="data-card-header">
    <form method="GET" class="d-flex gap-2 flex-wrap">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" name="busca" class="form-control" placeholder="Buscar curso..." value="<?= e($busca) ?>" style="min-width:200px">
      </div>
      <select name="tipo" class="form-select" style="width:140px">
        <option value="">Todos os tipos</option>
        <option value="ead"        <?= $tipo === 'ead'        ? 'selected':'' ?>>EAD</option>
        <option value="presencial" <?= $tipo === 'presencial' ? 'selected':'' ?>>Presencial</option>
      </select>
      <button class="btn btn-outline-primary">Filtrar</button>
      <?php if ($busca || $tipo): ?>
      <a href="<?= APP_URL ?>/admin/cursos.php" class="btn btn-outline-secondary">Limpar</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if ($cursos): ?>
<div class="row g-4">
  <?php foreach ($cursos as $c): ?>
  <div class="col-md-6 col-xl-4">
    <div class="curso-admin-card">
      <div class="curso-admin-thumb">
        <?php if (!empty($c['imagem'])): ?>
        <img src="<?= APP_URL ?>/public/uploads/cursos/<?= e($c['imagem']) ?>" alt="<?= e($c['nome']) ?>">
        <?php else: ?>
        <i class="bi bi-journal-play"></i>
        <?php endif; ?>
        <span class="curso-admin-status status-<?= $c['status'] ? 'ativo':'inativo' ?>">
          <?= $c['status'] ? 'Ativo':'Inativo' ?>
        </span>
      </div>
      <div class="curso-admin-body">
        <div class="curso-admin-title"><?= e($c['nome']) ?></div>
        <div>
          <span class="curso-meta-tag"><i class="bi bi-laptop"></i><?= strtoupper($c['tipo']) ?></span>
          <span class="curso-meta-tag"><i class="bi bi-clock"></i><?= $c['carga_horaria'] ?>h</span>
          <?php if ($c['tem_avaliacao']): ?>
          <span class="curso-meta-tag" style="background:#fef3c7;color:#b45309"><i class="bi bi-patch-question"></i>Avaliação</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($c['instrutores'])): ?>
        <div class="mt-2"><small class="text-muted"><i class="bi bi-person me-1"></i><?= e($c['instrutores']) ?></small></div>
        <?php endif; ?>
      </div>
      <div class="curso-admin-actions">
        <!-- Botão principal: abre detalhe do curso -->
        <a href="?acao=detalhe&id=<?= $c['id'] ?>" class="btn btn-primary btn-sm flex-grow-1">
          <i class="bi bi-folder2-open me-1"></i>Gerenciar
        </a>
        <a href="?acao=deletar&id=<?= $c['id'] ?>"
           class="btn btn-icon btn-outline-danger btn-sm"
           data-confirm="Excluir '<?= e($c['nome']) ?>'? Isso removerá aulas, materiais e matrículas!">
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
  <p>Nenhum curso encontrado.</p>
  <a href="?acao=novo" class="btn btn-primary btn-sm mt-2"><i class="bi bi-plus me-1"></i>Criar primeiro curso</a>
</div>
<?php endif; ?>

<?php if ($pag['pages'] > 1): ?>
<nav class="mt-4"><ul class="pagination pagination-sm justify-content-center">
  <?php for ($i = 1; $i <= $pag['pages']; $i++): ?>
  <li class="page-item <?= $i == $page ? 'active':'' ?>">
    <a class="page-link" href="?busca=<?= urlencode($busca) ?>&tipo=<?= $tipo ?>&p=<?= $i ?>"><?= $i ?></a>
  </li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php endif; ?>

<script>
function setTipoAula(tipo) {
  document.getElementById('campoLink').style.display   = tipo === 'link'   ? '' : 'none';
  document.getElementById('campoUpload').style.display = tipo === 'upload' ? '' : 'none';
}
</script>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>