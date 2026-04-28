<?php $pageTitle = 'Curso: '.($curso['nome'] ?? ''); ?>
<div class="mb-3">
  <h1 style="font-size:20px;font-weight:800;margin:0;color:var(--primary)">
    <i class="bi bi-journal-bookmark-fill me-2"></i><?= e($curso['nome']) ?>
  </h1>
  <p class="page-subtitle"><a href="<?= APP_URL ?>/admin/cursos">Cursos</a> / Detalhe</p>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4">
  <?php
  $tabs = ['config'=>'Configurações','aulas'=>'Aulas','materiais'=>'Materiais','avaliacao'=>'Avaliação','certificado'=>'Certificado'];
  foreach($tabs as $k=>$v):
  ?>
  <li class="nav-item">
    <a class="nav-link <?= $tab===$k?'active':'' ?>" href="<?= APP_URL ?>/admin/cursos/<?= $curso['id'] ?>?tab=<?= $k ?>">
      <?= $v ?>
    </a>
  </li>
  <?php endforeach; ?>
</ul>

<?php if($tab==='config'): ?>
<div class="data-card">
  <div class="data-card-header"><h6 class="data-card-title">Configurações do Curso</h6></div>
  <div class="p-4">
    <form method="POST" action="<?= APP_URL ?>/admin/cursos/<?= $curso['id'] ?>/salvar" enctype="multipart/form-data">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label fw-semibold">Nome *</label>
          <input type="text" name="nome" class="form-control" value="<?= e($curso['nome']) ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Tipo</label>
          <select name="tipo" class="form-select">
            <?php foreach(['ead'=>'EAD','presencial'=>'Presencial','hibrido'=>'Híbrido'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= $curso['tipo']===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">C/H (horas)</label>
          <input type="number" name="carga_horaria" class="form-control" value="<?= (int)$curso['carga_horaria'] ?>">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Descrição</label>
          <textarea name="descricao" class="form-control" rows="3"><?= e($curso['descricao']) ?></textarea>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Status</label>
          <select name="status" class="form-select">
            <option value="1" <?= $curso['status']==1?'selected':'' ?>>Ativo</option>
            <option value="0" <?= $curso['status']==0?'selected':'' ?>>Inativo</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Tem Avaliação?</label>
          <select name="tem_avaliacao" class="form-select">
            <option value="0" <?= $curso['tem_avaliacao']==0?'selected':'' ?>>Não</option>
            <option value="1" <?= $curso['tem_avaliacao']==1?'selected':'' ?>>Sim</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Nota Mínima (%)</label>
          <input type="number" name="nota_minima" class="form-control" value="<?= (int)$curso['nota_minima'] ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Imagem</label>
          <input type="file" name="imagem" class="form-control" accept="image/*">
          <?php if(!empty($curso['imagem'])): ?><small class="text-muted">Atual: <?= e($curso['imagem']) ?></small><?php endif; ?>
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Salvar</button>
        <a href="<?= APP_URL ?>/admin/cursos" class="btn btn-outline-secondary">Voltar</a>
      </div>
    </form>
  </div>
</div>

<?php elseif($tab==='aulas'): ?>
<div class="data-card mb-4">
  <div class="data-card-header"><h6 class="data-card-title">Aulas do Curso</h6></div>
  <div class="table-responsive">
    <table class="table table-ead">
      <thead><tr><th>#</th><th>Título</th><th>Vídeo</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
      <tbody>
      <?php if($aulas): foreach($aulas as $a): ?>
      <tr>
        <td><?= (int)$a['ordem'] ?></td>
        <td><?= e($a['titulo']) ?></td>
        <td><?php
          $url = $a['url_video'] ?? '';
          if(str_starts_with($url,'upload:')) echo '<span class="badge bg-secondary">Upload</span>';
          elseif($url) echo '<span class="badge bg-info text-dark">Link</span>';
          else echo '<span class="badge bg-light text-dark">—</span>';
        ?></td>
        <td><?= $a['status'] ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-danger">Inativa</span>' ?></td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary"
            onclick="abrirModalAula(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)">
            <i class="bi bi-pencil"></i>
          </button>
          <form method="POST" action="<?= APP_URL ?>/admin/cursos/<?= $curso['id'] ?>/aulas/<?= $a['id'] ?>/deletar" class="d-inline"
                onsubmit="return confirm('Remover aula?')">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma aula cadastrada.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="data-card">
  <div class="data-card-header"><h6 class="data-card-title">Adicionar Aula</h6></div>
  <div class="p-4">
    <form method="POST" action="<?= APP_URL ?>/admin/cursos/<?= $curso['id'] ?>/aulas/salvar" enctype="multipart/form-data" id="formAula">
      <?= csrfField() ?>
      <input type="hidden" name="aula_id" id="aulaIdField" value="">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Título *</label>
          <input type="text" name="titulo" id="aulaTitulo" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label fw-semibold">Ordem</label>
          <input type="number" name="ordem" id="aulaOrdem" class="form-control" value="<?= count($aulas)+1 ?>" min="1"></div>
        <div class="col-md-2"><label class="form-label fw-semibold">Status</label>
          <select name="status" id="aulaStatus" class="form-select">
            <option value="1">Ativa</option><option value="0">Inativa</option>
          </select></div>
        <div class="col-md-2"><label class="form-label fw-semibold">Tipo de Vídeo</label>
          <select name="tipo_aula" id="tipoAula" class="form-select" onchange="toggleVideoInput()">
            <option value="link">URL / YouTube</option>
            <option value="upload">Upload</option>
            <option value="sem">Sem vídeo</option>
          </select></div>
        <div class="col-12" id="divLink">
          <label class="form-label fw-semibold">URL do Vídeo</label>
          <input type="text" name="url_video" id="aulaUrl" class="form-control" placeholder="https://youtube.com/watch?v=..."></div>
        <div class="col-12" id="divUpload" style="display:none">
          <label class="form-label fw-semibold">Arquivo de Vídeo</label>
          <input type="file" name="video_file" class="form-control" accept="video/*"></div>
        <div class="col-12"><label class="form-label fw-semibold">Descrição</label>
          <textarea name="descricao" id="aulaDesc" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Salvar Aula</button>
        <button type="button" class="btn btn-outline-secondary" onclick="limparFormAula()">Limpar</button>
      </div>
    </form>
  </div>
</div>

<?php elseif($tab==='materiais'): ?>
<div class="data-card mb-4">
  <div class="data-card-header"><h6 class="data-card-title">Materiais</h6></div>
  <div class="table-responsive">
    <table class="table table-ead">
      <thead><tr><th>Título</th><th>Tipo</th><th>Tamanho</th><th class="text-end">Ações</th></tr></thead>
      <tbody>
      <?php if($materiais): foreach($materiais as $m): ?>
      <tr>
        <td><?= e($m['titulo']) ?></td>
        <td><span class="badge bg-secondary"><?= strtoupper(e($m['tipo'])) ?></span></td>
        <td><?= round($m['tamanho']/1024,1) ?> KB</td>
        <td class="text-end">
          <a href="<?= uploadUrl('materiais/'.$m['arquivo']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
            <i class="bi bi-download"></i>
          </a>
          <form method="POST" action="<?= APP_URL ?>/admin/cursos/<?= $curso['id'] ?>/materiais/<?= $m['id'] ?>/deletar" class="d-inline"
                onsubmit="return confirm('Remover material?')">
            <?= csrfField() ?>
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="4" class="text-center text-muted py-4">Nenhum material enviado.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="data-card">
  <div class="data-card-header"><h6 class="data-card-title">Enviar Material</h6></div>
  <div class="p-4">
    <form method="POST" action="<?= APP_URL ?>/admin/cursos/<?= $curso['id'] ?>/materiais/salvar" enctype="multipart/form-data">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-5"><label class="form-label fw-semibold">Título</label>
          <input type="text" name="titulo" class="form-control" placeholder="Nome do material"></div>
        <div class="col-md-5"><label class="form-label fw-semibold">Arquivo *</label>
          <input type="file" name="arquivo" class="form-control" required></div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i>Enviar</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php elseif($tab==='avaliacao'): ?>
<div class="data-card mb-4">
  <div class="data-card-header"><h6 class="data-card-title">Configuração da Avaliação</h6></div>
  <div class="p-4">
    <form method="POST" action="<?= APP_URL ?>/admin/cursos/<?= $curso['id'] ?>/avaliacao/salvar">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Título</label>
          <input type="text" name="titulo" class="form-control" value="<?= e($avaliacao['titulo'] ?? 'Avaliação Final — '.$curso['nome']) ?>" required></div>
        <div class="col-md-2"><label class="form-label fw-semibold">Tentativas</label>
          <input type="number" name="tentativas" class="form-control" value="<?= (int)($avaliacao['tentativas'] ?? 1) ?>" min="1"></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Descrição</label>
          <input type="text" name="descricao" class="form-control" value="<?= e($avaliacao['descricao'] ?? '') ?>"></div>
      </div>
      <div class="mt-3"><button type="submit" class="btn btn-primary">Salvar Configuração</button></div>
    </form>
  </div>
</div>

<?php if($avaliacao): ?>
<div class="data-card mb-4">
  <div class="data-card-header"><h6 class="data-card-title">Perguntas (<?= count($perguntasCompletas) ?>)</h6></div>
  <div class="p-3">
    <?php foreach($perguntasCompletas as $p): ?>
    <div class="border rounded p-3 mb-3">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <strong><?= e($p['enunciado']) ?></strong>
        <div class="d-flex gap-1">
          <span class="badge bg-secondary"><?= $p['pontos'] ?> pt<?= $p['pontos']!=1?'s':'' ?></span>
          <form method="POST" action="<?= APP_URL ?>/admin/cursos/<?= $curso['id'] ?>/avaliacao/perguntas/<?= $p['id'] ?>/deletar" class="d-inline" onsubmit="return confirm('Remover pergunta?')">
            <?= csrfField() ?>
            <button class="btn btn-xs btn-outline-danger px-1 py-0"><i class="bi bi-trash" style="font-size:11px"></i></button>
          </form>
        </div>
      </div>
      <ul class="list-unstyled mb-0 ps-2">
        <?php foreach($p['alternativas'] as $alt): ?>
        <li class="small <?= $alt['correta'] ? 'text-success fw-bold' : 'text-muted' ?>">
          <?= $alt['correta'] ? '✓' : '○' ?> <?= e($alt['texto']) ?>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<div class="data-card">
  <div class="data-card-header"><h6 class="data-card-title">Adicionar Pergunta</h6></div>
  <div class="p-4">
    <form method="POST" action="<?= APP_URL ?>/admin/cursos/<?= $curso['id'] ?>/avaliacao/perguntas/salvar" id="formPergunta">
      <?= csrfField() ?>
      <div class="row g-3 mb-3">
        <div class="col-md-8"><label class="form-label fw-semibold">Enunciado *</label>
          <input type="text" name="enunciado" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label fw-semibold">Pontos</label>
          <input type="number" name="pontos" class="form-control" value="1" min="1"></div>
        <div class="col-md-2"><label class="form-label fw-semibold">Ordem</label>
          <input type="number" name="ordem" class="form-control" value="<?= count($perguntasCompletas)+1 ?>" min="1"></div>
      </div>
      <label class="form-label fw-semibold">Alternativas (marque a correta)</label>
      <div id="alternativas">
        <?php for($i=0;$i<4;$i++): ?>
        <div class="input-group mb-2">
          <div class="input-group-text">
            <input type="checkbox" name="correta[<?= $i ?>]" value="1">
          </div>
          <input type="text" name="alternativas[<?= $i ?>]" class="form-control" placeholder="Alternativa <?= chr(65+$i) ?>">
        </div>
        <?php endfor; ?>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addAlternativa()">
        <i class="bi bi-plus"></i> Mais alternativa
      </button>
      <div><button type="submit" class="btn btn-primary">Salvar Pergunta</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php elseif($tab==='certificado'): ?>
<div class="data-card">
  <div class="data-card-header"><h6 class="data-card-title">Modelo de Certificado</h6></div>
  <div class="p-4">
    <form method="POST" action="<?= APP_URL ?>/admin/cursos/<?= $curso['id'] ?>/certificado/salvar" enctype="multipart/form-data">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Imagem da Frente</label>
          <input type="file" name="frente" class="form-control" accept="image/*">
          <?php if(!empty($modelo['frente'])): ?><small class="text-muted">Atual: <?= e($modelo['frente']) ?></small><?php endif; ?></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Imagem do Verso</label>
          <input type="file" name="verso" class="form-control" accept="image/*">
          <?php if(!empty($modelo['verso'])): ?><small class="text-muted">Atual: <?= e($modelo['verso']) ?></small><?php endif; ?></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Nome no Certificado</label>
          <input type="text" name="nome_cert" class="form-control" value="<?= e($modelo['nome_cert'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Instrutor / Assinatura</label>
          <input type="text" name="instrutor" class="form-control" value="<?= e($modelo['instrutor'] ?? '') ?>"></div>
        <div class="col-12"><label class="form-label fw-semibold">Texto da Frente</label>
          <textarea name="texto_frente" class="form-control" rows="4"><?= e($modelo['texto_frente'] ?? '') ?></textarea></div>
        <div class="col-12"><label class="form-label fw-semibold">Conteúdo Programático</label>
          <textarea name="conteudo_prog" class="form-control" rows="4"><?= e($modelo['conteudo_prog'] ?? '') ?></textarea></div>
        <div class="col-md-4">
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="ativar_verso" value="1"
                   id="ativarVerso" <?= !empty($modelo['ativar_verso']) ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="ativarVerso">Ativar Verso</label>
          </div>
        </div>
      </div>
      <div class="mt-4"><button type="submit" class="btn btn-primary">Salvar Modelo</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function abrirModalAula(a){
  document.getElementById('aulaIdField').value=a.id;
  document.getElementById('aulaTitulo').value=a.titulo;
  document.getElementById('aulaOrdem').value=a.ordem;
  document.getElementById('aulaStatus').value=a.status;
  document.getElementById('aulaDesc').value=a.descricao||'';
  var url=a.url_video||'';
  if(url.startsWith('upload:')){document.getElementById('tipoAula').value='upload';toggleVideoInput();}
  else if(url){document.getElementById('tipoAula').value='link';document.getElementById('aulaUrl').value=url;toggleVideoInput();}
  document.getElementById('formAula').scrollIntoView({behavior:'smooth'});
}
function limparFormAula(){
  document.getElementById('aulaIdField').value='';
  document.getElementById('formAula').reset();
}
function toggleVideoInput(){
  var t=document.getElementById('tipoAula').value;
  document.getElementById('divLink').style.display=t==='link'?'':'none';
  document.getElementById('divUpload').style.display=t==='upload'?'':'none';
}
var altCount=4;
function addAlternativa(){
  var d=document.getElementById('alternativas');
  var i=altCount++;
  var html='<div class="input-group mb-2">'
    +'<div class="input-group-text"><input type="checkbox" name="correta['+i+']" value="1"></div>'
    +'<input type="text" name="alternativas['+i+']" class="form-control" placeholder="Alternativa '+(i+1)+'">'
    +'</div>';
  d.insertAdjacentHTML('beforeend',html);
}
</script>
