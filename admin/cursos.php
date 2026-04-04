<?php
/**
 * admin/cursos.php — CRMV EAD
 * Gerenciamento de cursos com página de detalhe em abas
 * Operador e Admin podem acessar.
 */
require_once __DIR__ . '/../app/bootstrap.php';
if (!in_array($_SESSION['perfil'] ?? '', ['admin', 'operador'])) {
    redirect(APP_URL . '/login.php');
}

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
        'nome'          => sanitize($_POST['nome']          ?? ''),
        'descricao'     => sanitize($_POST['descricao']     ?? ''),
        'tipo'          => $_POST['tipo']                   ?? 'ead',
        'carga_horaria' => (int)($_POST['carga_horaria']    ?? 0),
        'status'        => (int)($_POST['status']           ?? 1),
        'tem_avaliacao' => (int)($_POST['tem_avaliacao']    ?? 0),
        'nota_minima'   => (float)($_POST['nota_minima']    ?? 60),
        // instrutores e conteudo_programatico NÃO são mais salvos aqui (estão no certificado)
    ];
    if (!empty($_FILES['imagem']['name'])) {
        $img = uploadFile($_FILES['imagem'], UPLOAD_PATH . '/cursos', ALLOWED_IMAGE);
        if ($img) $d['imagem'] = $img;
    }
    if ($id) {
        $cursoModel->atualizar($id, $d);
        if ($d['tem_avaliacao']) {
            $avalExiste = $avalModel->porCurso($id);
            if (!$avalExiste) {
                $avalModel->criar(['curso_id' => $id, 'titulo' => 'Avaliação Final — ' . $d['nome'], 'descricao' => '', 'tentativas' => 1]);
            }
        }
        logAction('curso.atualizar', "Curso ID $id");
        setFlash('success', 'Configurações salvas!');
        redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$id&tab=config");
    } else {
        $newId = $cursoModel->criar($d);
        if ($d['tem_avaliacao']) {
            $avalModel->criar(['curso_id' => $newId, 'titulo' => 'Avaliação Final — ' . $d['nome'], 'descricao' => '', 'tentativas' => 1]);
        }
        logAction('curso.criar', "Curso ID $newId");
        setFlash('success', 'Curso criado! Configure as aulas e o certificado.');
        redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id=$newId&tab=config");
    }
}

/* ── SALVAR AULA ───────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'aula') {
    csrfCheck();
    $aulaId   = (int)($_POST['aula_id'] ?? 0);
    $tipoAula = $_POST['tipo_aula'] ?? 'link';
    $d = [
        'curso_id'  => $id,
        'titulo'    => sanitize($_POST['titulo']    ?? ''),
        'descricao' => sanitize($_POST['descricao'] ?? ''),
        'url_video' => '',
        'ordem'     => (int)($_POST['ordem']  ?? 1),
        'status'    => (int)($_POST['status'] ?? 1),
    ];
    if ($tipoAula === 'link') {
        $d['url_video'] = sanitize($_POST['url_video'] ?? '');
    } elseif ($tipoAula === 'upload') {
        $videoDir = UPLOAD_PATH . '/videos';
        if (!is_dir($videoDir)) mkdir($videoDir, 0755, true);
        if (!empty($_FILES['video_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ALLOWED_VIDEO) && $_FILES['video_file']['size'] <= 200 * 1024 * 1024) {
                $nomeVideo = uniqid('vid_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $videoDir . '/' . $nomeVideo)) {
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
    if ($aulaId) { $aulaModel->atualizar($aulaId, $d); setFlash('success', 'Aula atualizada!'); }
    else         { $aulaModel->criar($d);              setFlash('success', 'Aula criada!'); }
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
    $pid = $avalModel->criarPergunta(['avaliacao_id' => $aval['id'], 'enunciado' => sanitize($_POST['enunciado']), 'pontos' => (float)($_POST['pontos'] ?? 1), 'ordem' => (int)($_POST['ordem'] ?? 1)]);
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

/* ── SALVAR CERTIFICADO (modelo) ───────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'certificado') {
    csrfCheck();
    $d = [
        'nome_cert'      => sanitize($_POST['nome_cert']      ?? ''),
        'instrutor'      => sanitize($_POST['instrutor']      ?? ''),
        // DEPOIS (correto — preserva HTML do CKEditor):
        'conteudo_prog'  => sanitize_html($_POST['conteudo_prog']  ?? ''),
        'texto_frente'   => sanitize_html($_POST['texto_frente']   ?? ''),
        'verso_conteudo' => sanitize_html($_POST['verso_conteudo'] ?? ''),
        'ativar_verso'   => (int)($_POST['ativar_verso']      ?? 0),
    ];
    // Upload imagem frente
    if (!empty($_FILES['imagem_frente']['name'])) {
        $certDir = UPLOAD_PATH . '/certificados';
        if (!is_dir($certDir)) mkdir($certDir, 0755, true);
        $img = uploadFile($_FILES['imagem_frente'], $certDir, ALLOWED_IMAGE);
        if ($img) $d['frente'] = $img;
    }
    // Upload imagem verso
    if (!empty($_FILES['imagem_verso']['name'])) {
        $certDir = UPLOAD_PATH . '/certificados';
        if (!is_dir($certDir)) mkdir($certDir, 0755, true);
        $img = uploadFile($_FILES['imagem_verso'], $certDir, ALLOWED_IMAGE);
        if ($img) $d['verso'] = $img;
    }
    $certModel->salvarModelo($id, $d);
    logAction('certificado.salvar', "Modelo certificado curso ID $id");
    setFlash('success', 'Configurações do certificado salvas!');
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
$modelo    = $id ? $certModel->modelo($id) : null;

// Para edição de aula
$editAulaId = (int)($_GET['edit_aula'] ?? 0);
$editAula   = $editAulaId ? $aulaModel->findById($editAulaId) : null;

$pageTitle = $acao === 'detalhe' && $curso ? $curso['nome'] : ($acao === 'novo' ? 'Novo Curso' : 'Gerenciar Cursos');
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<?php if ($acao === 'novo'): ?>
<!-- ════ NOVO CURSO ════ -->
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
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select"><option value="1">Ativo</option><option value="0">Inativo</option></select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Imagem de Capa</label>
        <input type="file" name="imagem" class="form-control" accept="image/*">
      </div>
    </div>
    <div class="alert mt-3 mb-0" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;font-size:13px">
      <i class="bi bi-info-circle-fill text-success me-2"></i>
      <strong>Instrutores e Conteúdo Programático</strong> são configurados na aba <strong>Certificado</strong>, após criar o curso.
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
  <a class="course-tab <?= $tab === 'config'      ? 'active' : '' ?>" href="?acao=detalhe&id=<?= $id ?>&tab=config">
    <i class="bi bi-gear-fill"></i> Configurações
  </a>
  <a class="course-tab <?= $tab === 'aulas'       ? 'active' : '' ?>" href="?acao=detalhe&id=<?= $id ?>&tab=aulas">
    <i class="bi bi-play-circle-fill"></i> Aulas
    <span class="badge bg-primary ms-1" style="font-size:10px"><?= count($aulas) ?></span>
  </a>
  <a class="course-tab <?= $tab === 'avaliacao'   ? 'active' : '' ?>" href="?acao=detalhe&id=<?= $id ?>&tab=avaliacao">
    <i class="bi bi-patch-question-fill"></i> Avaliação
    <span class="badge bg-warning ms-1" style="font-size:10px"><?= count($perguntas) ?></span>
  </a>
  <a class="course-tab <?= $tab === 'certificado' ? 'active' : '' ?>" href="?acao=detalhe&id=<?= $id ?>&tab=certificado">
    <i class="bi bi-award-fill"></i> Certificado & Materiais
    <span class="badge bg-secondary ms-1" style="font-size:10px"><?= count($materiais) ?></span>
  </a>
</div>

<!-- ═══════════════════════════════════════════
     ABA: CONFIGURAÇÕES (sem instrutor/conteúdo)
     ═══════════════════════════════════════════ -->
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
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="1" <?= $curso['status'] == 1 ? 'selected':'' ?>>Ativo</option>
          <option value="0" <?= $curso['status'] == 0 ? 'selected':'' ?>>Inativo</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Avaliação</label>
        <select name="tem_avaliacao" class="form-select">
          <option value="0" <?= $curso['tem_avaliacao'] == 0 ? 'selected':'' ?>>Sem avaliação</option>
          <option value="1" <?= $curso['tem_avaliacao'] == 1 ? 'selected':'' ?>>Com avaliação</option>
        </select>
      </div>
      <div class="col-md-3">
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
    </div>
    <div class="alert mt-3 mb-0" style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px;font-size:13px">
      <i class="bi bi-award-fill text-warning me-2"></i>
      <strong>Instrutores e Conteúdo Programático</strong> do certificado são configurados na aba
      <a href="?acao=detalhe&id=<?= $id ?>&tab=certificado" class="fw-bold">Certificado & Materiais</a>.
    </div>
    <hr class="my-3">
    <button type="submit" class="btn btn-primary px-4">
      <i class="bi bi-check-lg me-1"></i>Salvar Configurações
    </button>
  </form>
</div>

<!-- ═══════════════════════════════════════════
     ABA: AULAS
     ═══════════════════════════════════════════ -->
<?php elseif ($tab === 'aulas'): ?>
<div class="row g-3">
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
              <label class="form-check-label w-100" for="tipoLink">
                <i class="bi bi-link-45deg text-primary"></i> Link de Vídeo
              </label>
            </div>
            <div class="form-check flex-fill border rounded p-2 <?= $tipoAtual === 'upload' ? 'border-success bg-light' : '' ?>">
              <input class="form-check-input" type="radio" name="tipo_aula" id="tipoUpload" value="upload"
                     <?= $tipoAtual === 'upload' ? 'checked' : '' ?> onchange="setTipoAula(this.value)">
              <label class="form-check-label w-100" for="tipoUpload">
                <i class="bi bi-upload text-success"></i> Upload de Vídeo
              </label>
            </div>
          </div>
        </div>
        <div id="campoLink" class="mb-3" <?= $tipoAtual === 'upload' ? 'style="display:none"' : '' ?>>
          <label class="form-label">URL do Vídeo</label>
          <input type="url" name="url_video" class="form-control" placeholder="https://youtube.com/watch?v=..."
                 value="<?= e($editAula && $tipoAtual !== 'upload' ? ($editAula['url_video'] ?? '') : '') ?>">
          <small class="text-muted">YouTube, Vimeo ou URL direta.</small>
        </div>
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
  <div class="col-md-8">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-play-circle me-2"></i>Aulas do Curso</h6>
        <span class="badge bg-primary"><?= count($aulas) ?> aula(s)</span>
      </div>
      <?php if ($aulas): ?>
      <?php foreach ($aulas as $a):
        $isLocal   = str_starts_with($a['url_video'] ?? '', 'local://');
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
          <a href="?acao=detalhe&id=<?= $id ?>&tab=aulas&edit_aula=<?= $a['id'] ?>" class="btn btn-icon btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
          <a href="?acao=del_aula&id=<?= $id ?>&aid=<?= $a['id'] ?>" class="btn btn-icon btn-outline-danger btn-sm" data-confirm="Excluir a aula '<?= e($a['titulo']) ?>'?"><i class="bi bi-trash"></i></a>
        </div>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div class="empty-state"><i class="bi bi-camera-video-off"></i><p>Nenhuma aula cadastrada.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════
     ABA: AVALIAÇÃO
     ═══════════════════════════════════════════ -->
<?php elseif ($tab === 'avaliacao'): ?>
<?php if (!$curso['tem_avaliacao']): ?>
<div class="alert-crmv mb-4">
  <i class="bi bi-info-circle me-2"></i>
  Este curso está sem avaliação. Ative "Com avaliação" na aba <strong>Configurações</strong>.
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
          <div class="col"><label class="form-label">Pontos</label><input type="number" name="pontos" class="form-control" min="1" value="1"></div>
          <div class="col"><label class="form-label">Ordem</label><input type="number" name="ordem" class="form-control" min="1" value="<?= count($perguntas)+1 ?>"></div>
        </div>
        <label class="form-label">Alternativas (marque a correta)</label>
        <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="d-flex gap-2 mb-2 align-items-center">
          <input type="radio" name="correta" value="<?= $i ?>" class="form-check-input mt-0" <?= $i === 0 ? 'checked':'' ?> title="Correta">
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
          <a href="?acao=del_pergunta&id=<?= $id ?>&pid=<?= $p['id'] ?>&tab=avaliacao" class="btn btn-icon btn-outline-danger btn-sm" data-confirm="Excluir esta pergunta?"><i class="bi bi-trash"></i></a>
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

<!-- ═══════════════════════════════════════════
     ABA: CERTIFICADO & MATERIAIS (estilo Moodle)
     ═══════════════════════════════════════════ -->
<?php elseif ($tab === 'certificado'): ?>
<div class="row g-3">

  <!-- ── Coluna esquerda: Materiais ── -->
  <div class="col-md-4">
    <div class="form-card mb-3">
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

    <!-- Resumo certificados emitidos -->
    <div class="form-card">
      <h6 class="mb-2"><i class="bi bi-award me-2 text-warning"></i>Certificados Emitidos</h6>
      <?php $certs = $certModel->doCurso($id); ?>
      <div class="d-flex align-items-center gap-2 p-3 rounded" style="background:#fffbeb">
        <i class="bi bi-award-fill text-warning fs-3"></i>
        <div>
          <div style="font-size:22px;font-weight:700;line-height:1"><?= count($certs) ?></div>
          <div style="font-size:12px;color:#92400e">certificado(s) emitido(s)</div>
        </div>
      </div>
      <?php if ($certs): ?>
      <div class="mt-2" style="max-height:160px;overflow-y:auto">
        <?php foreach (array_slice($certs, 0, 5) as $c): ?>
        <div style="font-size:12px;padding:4px 0;border-bottom:1px solid #f3f4f6">
          <i class="bi bi-person-check text-success me-1"></i><?= e($c['aluno_nome']) ?>
          <span class="text-muted ms-1"><?= dataBR($c['emitido_em']) ?></span>
        </div>
        <?php endforeach; ?>
        <?php if (count($certs) > 5): ?>
        <div style="font-size:12px;color:#6b7280;padding-top:4px">… e mais <?= count($certs)-5 ?></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Coluna direita: Configuração do Certificado (estilo Moodle) ── -->
  <div class="col-md-8">

    <!-- Materiais lista -->
    <?php if ($materiais): ?>
    <div class="data-card mb-3">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-files me-2"></i>Materiais Didáticos</h6>
        <span class="badge bg-primary"><?= count($materiais) ?> arquivo(s)</span>
      </div>
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
              <a href="<?= APP_URL ?>/public/uploads/materiais/<?= e($m['arquivo']) ?>" target="_blank" class="btn btn-icon btn-outline-success btn-sm"><i class="bi bi-download"></i></a>
              <a href="?acao=del_material&id=<?= $id ?>&mid=<?= $m['id'] ?>&tab=certificado" class="btn btn-icon btn-outline-danger btn-sm" data-confirm="Remover '<?= e($m['titulo']) ?>'?"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- ══ CONFIGURAÇÃO DO CERTIFICADO ══ -->
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-award-fill me-2 text-warning"></i>Configurações do Certificado</h6>
      </div>

      <form method="POST" enctype="multipart/form-data" style="padding:20px">
        <?= csrfField() ?>
        <input type="hidden" name="form" value="certificado">

        <!-- ─ GERAL ─ -->
        <div class="cert-section">
          <div class="cert-section-title">
            <i class="bi bi-info-circle-fill me-2"></i>Geral
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Nome do Certificado</label>
            <input type="text" name="nome_cert" class="form-control"
                   value="<?= e($modelo['nome_cert'] ?? $curso['nome']) ?>"
                   placeholder="Ex: Certificado de Conclusão — <?= e($curso['nome']) ?>">
            <small class="text-muted">Nome exibido no topo do certificado. Deixe em branco para usar o nome do curso.</small>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Instrutor(es)</label>
            <input type="text" name="instrutor" class="form-control"
                   value="<?= e($modelo['instrutor'] ?? $curso['instrutores'] ?? '') ?>"
                   placeholder="Ex: Dr. João Silva, CRMV-TO 1234 / Dra. Maria Souza">
            <small class="text-muted">Separe múltiplos instrutores por barra ( / ) ou vírgula.</small>
          </div>
        </div>

        <!-- ─ FRENTE DO CERTIFICADO ─ -->
        <div class="cert-section">
          <div class="cert-section-title">
            <i class="bi bi-file-earmark-image-fill me-2"></i>Frente do Certificado
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Imagem de Fundo (Frente)</label>
            <?php if (!empty($modelo['frente'])): ?>
            <div class="cert-preview-img mb-2">
              <img src="<?= APP_URL ?>/public/uploads/certificados/<?= e($modelo['frente']) ?>"
                   alt="Frente atual" class="rounded border" style="max-height:120px">
              <div style="font-size:12px;color:#6b7280;margin-top:4px">
                <i class="bi bi-check-circle text-success me-1"></i>Imagem atual: <?= e($modelo['frente']) ?>
              </div>
            </div>
            <?php endif; ?>
            <input type="file" name="imagem_frente" class="form-control" accept="image/*">
            <small class="text-muted">
              Tipos aceitos: JPG, PNG, SVG. Tamanho recomendado: <strong>1122 × 794px</strong> (A4 paisagem, 96dpi).<br>
              <?php if (!empty($modelo['frente'])): ?>Envie um novo arquivo para substituir a imagem atual.<?php endif; ?>
            </small>
          </div>

          <div class="mb-0">
            <label class="form-label fw-semibold">
              Texto da Frente
              <small class="text-muted fw-normal ms-1">— variáveis disponíveis:</small>
            </label>
            <div class="cert-variaveis mb-2">
              <span class="cert-var" onclick="inserirVar('texto_frente','[NOME]')">[NOME]</span>
              <span class="cert-var" onclick="inserirVar('texto_frente','[CURSO]')">[CURSO]</span>
              <span class="cert-var" onclick="inserirVar('texto_frente','[CARGA_HORARIA]')">[CARGA_HORARIA]</span>
              <span class="cert-var" onclick="inserirVar('texto_frente','[DATA]')">[DATA]</span>
              <span class="cert-var" onclick="inserirVar('texto_frente','[CRMV]')">[CRMV]</span>
            </div>
            <textarea name="texto_frente" id="texto_frente" class="form-control auto-resize" rows="5"
                      placeholder="Deixe em branco para usar o texto padrão gerado automaticamente."><?= htmlspecialchars($modelo['texto_frente'] ?? '', ENT_QUOTES) ?></textarea>
            <small class="text-muted">
              Texto HTML exibido sobre a imagem de fundo. As variáveis são substituídas automaticamente.
              Deixe em branco para usar o layout padrão do sistema.
            </small>
          </div>
        </div>

        <!-- ─ VERSO DO CERTIFICADO ─ -->
        <div class="cert-section">
          <div class="cert-section-title">
            <i class="bi bi-file-earmark-text-fill me-2"></i>Verso do Certificado
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Ativar Verso do Certificado</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="ativar_verso" id="versoNao" value="0"
                       <?= ($modelo['ativar_verso'] ?? 0) == 0 ? 'checked':'' ?>
                       onchange="toggleVerso(0)">
                <label class="form-check-label" for="versoNao">Não</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="ativar_verso" id="versoSim" value="1"
                       <?= ($modelo['ativar_verso'] ?? 0) == 1 ? 'checked':'' ?>
                       onchange="toggleVerso(1)">
                <label class="form-check-label" for="versoSim">Sim</label>
              </div>
            </div>
          </div>

          <div id="blocoVerso" <?= ($modelo['ativar_verso'] ?? 0) == 0 ? 'style="display:none"' : '' ?>>

            <div class="mb-3">
              <label class="form-label fw-semibold">Imagem de Fundo (Verso)</label>
              <?php if (!empty($modelo['verso'])): ?>
              <div class="cert-preview-img mb-2">
                <img src="<?= APP_URL ?>/public/uploads/certificados/<?= e($modelo['verso']) ?>"
                     alt="Verso atual" class="rounded border" style="max-height:120px">
                <div style="font-size:12px;color:#6b7280;margin-top:4px">
                  <i class="bi bi-check-circle text-success me-1"></i>Imagem atual: <?= e($modelo['verso']) ?>
                </div>
              </div>
              <?php endif; ?>
              <input type="file" name="imagem_verso" class="form-control" accept="image/*">
              <small class="text-muted">
                Tipos aceitos: JPG, PNG, SVG. Tamanho recomendado: <strong>1122 × 794px</strong>.
                <?php if (!empty($modelo['verso'])): ?>Envie um novo arquivo para substituir.<?php endif; ?>
              </small>
            </div>

            <div class="mb-0">
              <label class="form-label fw-semibold">Conteúdo Programático</label>
              <small class="text-muted d-block mb-2">
                Este conteúdo será exibido no verso do certificado.<br>
                Use as ferramentas do editor para <strong>negrito</strong>, <em>itálico</em>, tamanho de fonte, listas com marcadores e muito mais.
              </small>
              <!-- CKEditor será inicializado aqui -->
              <textarea name="conteudo_prog" id="conteudo_prog"
                        rows="10"><?= htmlspecialchars($modelo['conteudo_prog'] ?? $curso['conteudo_programatico'] ?? '', ENT_QUOTES) ?></textarea>
            </div>

          </div><!-- /blocoVerso -->
        </div>

        <!-- ─ BOTÃO SALVAR ─ -->
        <div class="d-flex gap-2 mt-3">
          <button type="submit" class="btn btn-warning px-4 fw-semibold">
            <i class="bi bi-save me-1"></i>Salvar Configurações do Certificado
          </button>
          <?php if (!empty($certs)): ?>
          <a href="<?= APP_URL ?>/aluno/certificado.php?curso_id=<?= $id ?>" target="_blank"
             class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i>Pré-visualizar
          </a>
          <?php endif; ?>
        </div>
      </form>
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
      </div>
      <div class="curso-admin-actions">
        <a href="?acao=detalhe&id=<?= $c['id'] ?>" class="btn btn-primary btn-sm flex-grow-1">
          <i class="bi bi-folder2-open me-1"></i>Gerenciar
        </a>
        <a href="?acao=deletar&id=<?= $c['id'] ?>" class="btn btn-icon btn-outline-danger btn-sm"
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
  <li class="page-item <?= $i == $page ? 'active':'' ?>"><a class="page-link" href="?busca=<?= urlencode($busca) ?>&tipo=<?= $tipo ?>&p=<?= $i ?>"><?= $i ?></a></li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     SCRIPTS — carregados após todo o HTML
     ═══════════════════════════════════════════════════════════ -->

<!-- 1. CKEditor 4 full: carregado PRIMEIRO, antes de qualquer uso -->
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>

<!-- 2. Lógica da página: só roda depois que o CKEditor já está disponível -->
<script>
/* ── Utilitários de formulário ────────────────────────────────── */
function setTipoAula(tipo) {
  document.getElementById('campoLink').style.display   = tipo === 'link'   ? '' : 'none';
  document.getElementById('campoUpload').style.display = tipo === 'upload' ? '' : 'none';
}

function inserirVar(fieldId, variavel) {
  var el = document.getElementById(fieldId);
  if (!el) return;
  var s  = el.selectionStart, e = el.selectionEnd;
  el.value = el.value.slice(0, s) + variavel + el.value.slice(e);
  el.focus();
  el.selectionStart = el.selectionEnd = s + variavel.length;
}

/* ── Auto-resize para textarea.auto-resize ────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('textarea.auto-resize').forEach(function (ta) {
    ta.style.resize    = 'vertical';
    ta.style.minHeight = '120px';
    ta.style.overflowY = 'auto';

    function grow() {
      if (ta.dataset.userResized === '1') return;
      ta.style.height    = 'auto';
      ta.style.height    = (ta.scrollHeight + 2) + 'px';
      ta.style.overflowY = 'hidden';
    }
    grow();

    ta.addEventListener('input', function () {
      this.dataset.userResized = '0';
      grow.call(this);
    });

    ta.addEventListener('mousedown', function () {
      var self = this, startH = self.offsetHeight;
      function onUp() {
        if (self.offsetHeight !== startH) {
          self.dataset.userResized = '1';
          self.style.overflowY     = 'auto';
          self.style.height        = self.offsetHeight + 'px';
        }
        document.removeEventListener('mouseup', onUp);
      }
      document.addEventListener('mouseup', onUp);
    });
  });
});

/* ══════════════════════════════════════════════════════════════
   CKEditor — inicialização controlada
   ══════════════════════════════════════════════════════════════
   CORREÇÕES APLICADAS:
   1. Flag _ckReady era global e nunca resetava ao ocultar o bloco
      → editor não reinicializava ao alternar Não→Sim múltiplas vezes.
      CORREÇÃO: destruir + reinicializar sempre que o bloco ficar
      visível; checar pela instância real, não por flag estática.

   2. autogrow + resize_enabled juntos causam colapso de altura em
      alguns navegadores (autogrow redimensiona para 0 ao abrir).
      CORREÇÃO: usar apenas resize manual (resize_enabled:true) +
      altura inicial fixa via startupFocus; remover autoGrow.

   3. Listas (ul/li) perdiam list-style no certificado gerado porque
      strip_tags removia atributos style.
      CORREÇÃO: contentsCss garante estilos dentro do editor;
      sanitize_html no backend foi ampliado para preservar <font><a>.
   ══════════════════════════════════════════════════════════════ */

/**
 * Destrói a instância existente (se houver) e cria uma nova.
 * Seguro para chamar múltiplas vezes.
 */
function initCKEditor() {
  /* Só existe na aba certificado */
  var el = document.getElementById('conteudo_prog');
  if (!el) return;

  /* Destrói instância anterior sem exceção */
  try {
    if (CKEDITOR.instances && CKEDITOR.instances['conteudo_prog']) {
      CKEDITOR.instances['conteudo_prog'].destroy(true);
    }
  } catch (e) { /* ignora erro de destruição */ }

  CKEDITOR.replace('conteudo_prog', {

    /* ── Conteúdo permitido ─────────────────────────────────── */
    allowedContent: true,   /* aceita TODO HTML: ul, li, ol, style, etc. */

    /* ── Resize manual (sem autogrow para evitar conflito) ──── */
    resize_enabled: true,
    resize_dir:     'vertical',
    /* Altura inicial — sem autogrow evita colapso em display:none */
    height: 280,

    /* ── Plugins necessários ────────────────────────────────── */
    /* autogrow REMOVIDO: conflitava com resize_enabled causando
       altura = 0 ao inicializar dentro de bloco oculto.           */
    extraPlugins: 'colorbutton,font',
    removePlugins: 'image,flash,iframe,forms,pagebreak,scayt,wsc',
    /* list, indent, basicstyles, enterkey já vêm no build "full" */

    language: 'pt-br',

    /* ── Toolbar completa com listas ────────────────────────── */
    toolbar: [
      { name: 'styles',      items: ['Format', 'FontSize', 'TextColor', 'BGColor'] },
      { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', '-', 'RemoveFormat'] },
      { name: 'paragraph',   items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight'] },
      { name: 'insert',      items: ['HorizontalRule'] },
      '/',
      { name: 'clipboard',   items: ['Undo', 'Redo'] },
      { name: 'tools',       items: ['Maximize', 'Source'] }
    ],

    /* ── CSS dentro do iframe do editor ────────────────────── */
    /* String simples — CKEditor 4 NÃO aceita array de CSS inline */
    contentsCss: [
      'body{font-family:Arial,sans-serif;font-size:13px;line-height:1.6;margin:12px;color:#222}',
      'ul{padding-left:22px;margin:6px 0 10px 0;list-style-type:disc !important}',
      'ol{padding-left:22px;margin:6px 0 10px 0;list-style-type:decimal !important}',
      'li{margin-bottom:4px;display:list-item !important}',
      'p{margin-bottom:8px}',
      'strong,b{font-weight:bold}',
      'em,i{font-style:italic}'
    ].join('')
  });

  /* ── Sincroniza textarea antes de qualquer submit ─────────── */
  /* Registra apenas uma vez por form usando dataset */
  document.querySelectorAll('form').forEach(function (form) {
    if (form.dataset.ckSubmitBound) return;
    form.dataset.ckSubmitBound = '1';
    form.addEventListener('submit', function () {
      if (CKEDITOR.instances && CKEDITOR.instances['conteudo_prog']) {
        var ta = document.getElementById('conteudo_prog');
        if (ta) ta.value = CKEDITOR.instances['conteudo_prog'].getData();
      }
    });
  });
}

/* ── toggleVerso: controla visibilidade + ciclo de vida do editor ── */
function toggleVerso(ativar) {
  var bloco = document.getElementById('blocoVerso');
  if (!bloco) return;

  if (ativar) {
    bloco.style.display = '';
    /* Pequeno delay garante que o bloco está visível e tem dimensões
       antes do CKEditor medir a área — resolve o bug de altura zero. */
    setTimeout(initCKEditor, 50);
  } else {
    bloco.style.display = 'none';
    /* Destrói o editor ao ocultar para liberar memória e
       permitir reinicialização limpa se o usuário voltar a ativar. */
    try {
      if (CKEDITOR.instances && CKEDITOR.instances['conteudo_prog']) {
        CKEDITOR.instances['conteudo_prog'].destroy(true);
      }
    } catch (e) { /* ignora */ }
  }
}

/* ── DOMContentLoaded: inicializa se verso já estiver ativo ─────── */
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('conteudo_prog');
  if (!el) return; /* não é a aba certificado — sai sem fazer nada */

  var bloco = document.getElementById('blocoVerso');

  /* Se o bloco estiver visível (ativar_verso = 1 salvo no BD),
     inicializa o editor diretamente. Caso contrário aguarda o usuário
     clicar "Sim" para que toggleVerso(1) o inicialize. */
  if (!bloco || bloco.style.display !== 'none') {
    initCKEditor();
  }
});
</script>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
