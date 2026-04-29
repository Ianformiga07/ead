<?php
/**
 * admin/relatorios.php — CRMV EAD
 * Central de Relatórios com exportação Excel, DOCX e CSV
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$db = getDB();

/* ── Parâmetros do filtro ─────────────────────────────── */
$tipo      = $_GET['tipo']      ?? 'conclusoes';
$cursoId   = (int)($_GET['curso_id'] ?? 0);
$dataIni   = $_GET['data_ini']  ?? date('Y-m-01');
$dataFim   = $_GET['data_fim']  ?? date('Y-m-d');
$exportar  = $_GET['exportar']  ?? '';

/* ── Lista de cursos para filtro ──────────────────────── */
$cursos = $db->query("SELECT id, nome FROM cursos WHERE status=1 ORDER BY nome")->fetchAll();

/* ══════════════════════════════════════════════════════
   GERAÇÃO DOS DADOS POR TIPO DE RELATÓRIO
   ══════════════════════════════════════════════════════ */

$dados    = [];
$colunas  = [];
$titulo   = '';

switch ($tipo) {

    /* ── 1. Alunos que concluíram por curso ─── */
    case 'conclusoes':
        $titulo  = 'Alunos Concluintes por Curso';
        $colunas = ['Aluno','CPF/CRMV','E-mail','Curso','Carga Horária','Concluído em','Status'];
        $sql = "SELECT u.nome, u.cpf, u.email, c.nome AS curso, c.carga_horaria,
                       DATE_FORMAT(m.concluido_em,'%d/%m/%Y') AS concluido_em,
                       'Concluído' AS status
                FROM matriculas m
                JOIN usuarios u ON u.id = m.aluno_id
                JOIN cursos c   ON c.id = m.curso_id
                WHERE m.status = 'concluida'
                  AND DATE(m.concluido_em) BETWEEN ? AND ?";
        $params = [$dataIni, $dataFim];
        if ($cursoId) { $sql .= " AND c.id = ?"; $params[] = $cursoId; }
        $sql .= " ORDER BY c.nome, u.nome";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $dados = $stmt->fetchAll();
        break;

    /* ── 2. Emissão de certificados ─── */
    case 'certificados':
        $titulo  = 'Emissão de Certificados';
        $colunas = ['Aluno','CPF/CRMV','E-mail','Curso','Código','Emitido em'];
        $sql = "SELECT u.nome, u.cpf, u.email, c.nome AS curso,
                       cert.codigo, DATE_FORMAT(cert.emitido_em,'%d/%m/%Y %H:%i') AS emitido_em
                FROM certificados cert
                JOIN usuarios u ON u.id = cert.aluno_id
                JOIN cursos c   ON c.id = cert.curso_id
                WHERE DATE(cert.emitido_em) BETWEEN ? AND ?";
        $params = [$dataIni, $dataFim];
        if ($cursoId) { $sql .= " AND c.id = ?"; $params[] = $cursoId; }
        $sql .= " ORDER BY cert.emitido_em DESC";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $dados = $stmt->fetchAll();
        break;

    /* ── 3. Notas por avaliação ─── */
    case 'notas':
        $titulo  = 'Relatório de Notas';
        $colunas = ['Aluno','CPF/CRMV','Curso','Avaliação','Nota (%)','Aprovado','Tentativas','Realizado em'];
        $sql = "SELECT u.nome, u.cpf, c.nome AS curso, av.titulo AS avaliacao,
                       ROUND(t.nota,2) AS nota,
                       IF(t.aprovado,'Sim','Não') AS aprovado,
                       (SELECT COUNT(*) FROM tentativas t2 WHERE t2.aluno_id=u.id AND t2.avaliacao_id=av.id) AS tentativas,
                       DATE_FORMAT(t.realizado_em,'%d/%m/%Y %H:%i') AS realizado_em
                FROM tentativas t
                JOIN usuarios u    ON u.id  = t.aluno_id
                JOIN avaliacoes av ON av.id = t.avaliacao_id
                JOIN cursos c      ON c.id  = av.curso_id
                WHERE t.invalidado = 0
                  AND DATE(t.realizado_em) BETWEEN ? AND ?";
        $params = [$dataIni, $dataFim];
        if ($cursoId) { $sql .= " AND c.id = ?"; $params[] = $cursoId; }
        $sql .= " ORDER BY c.nome, u.nome, t.realizado_em DESC";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $dados = $stmt->fetchAll();
        break;

    /* ── 4. Matrículas ativas ─── */
    case 'matriculas':
        $titulo  = 'Matrículas Ativas';
        $colunas = ['Aluno','CPF/CRMV','E-mail','Curso','Progresso (%)','Matriculado em','Status'];
        $sql = "SELECT u.nome, u.cpf, u.email, c.nome AS curso,
                       m.progresso,
                       DATE_FORMAT(m.matriculado_em,'%d/%m/%Y') AS matriculado_em,
                       CASE m.status WHEN 'ativa' THEN 'Ativa' WHEN 'concluida' THEN 'Concluída' ELSE 'Cancelada' END AS status
                FROM matriculas m
                JOIN usuarios u ON u.id = m.aluno_id
                JOIN cursos c   ON c.id = m.curso_id
                WHERE m.status != 'cancelada'
                  AND DATE(m.matriculado_em) BETWEEN ? AND ?";
        $params = [$dataIni, $dataFim];
        if ($cursoId) { $sql .= " AND c.id = ?"; $params[] = $cursoId; }
        $sql .= " ORDER BY c.nome, u.nome";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $dados = $stmt->fetchAll();
        break;

    /* ── 5. Desempenho por curso (resumo) ─── */
    case 'desempenho':
        $titulo  = 'Desempenho por Curso';
        $colunas = ['Curso','Tipo','Matriculados','Concluídos','Taxa Conclusão (%)','Média de Nota (%)','Certificados Emitidos'];
        $sql = "SELECT c.nome AS curso, c.tipo,
                       COUNT(DISTINCT m.aluno_id)                                  AS matriculados,
                       SUM(IF(m.status='concluida',1,0))                           AS concluidos,
                       ROUND(SUM(IF(m.status='concluida',1,0))/COUNT(DISTINCT m.aluno_id)*100,1) AS taxa,
                       ROUND(AVG(CASE WHEN t.aprovado=1 THEN t.nota END),1)        AS media_nota,
                       COUNT(DISTINCT cert.id)                                     AS certificados
                FROM cursos c
                LEFT JOIN matriculas m    ON m.curso_id=c.id AND m.status!='cancelada'
                LEFT JOIN avaliacoes av   ON av.curso_id=c.id
                LEFT JOIN tentativas t    ON t.avaliacao_id=av.id AND t.invalidado=0
                LEFT JOIN certificados cert ON cert.curso_id=c.id
                WHERE c.status=1";
        $params = [];
        if ($cursoId) { $sql .= " AND c.id = ?"; $params[] = $cursoId; }
        $sql .= " GROUP BY c.id ORDER BY c.nome";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $dados = $stmt->fetchAll();
        break;

    /* ── 6. Alunos sem certificado (concluintes) ─── */
    case 'sem_certificado':
        $titulo  = 'Concluintes sem Certificado';
        $colunas = ['Aluno','CPF/CRMV','E-mail','Curso','Concluído em'];
        $sql = "SELECT u.nome, u.cpf, u.email, c.nome AS curso,
                       DATE_FORMAT(m.concluido_em,'%d/%m/%Y') AS concluido_em
                FROM matriculas m
                JOIN usuarios u ON u.id = m.aluno_id
                JOIN cursos c   ON c.id = m.curso_id
                WHERE m.status = 'concluida'
                  AND NOT EXISTS (
                      SELECT 1 FROM certificados cert
                      WHERE cert.aluno_id=m.aluno_id AND cert.curso_id=m.curso_id
                  )
                  AND DATE(m.concluido_em) BETWEEN ? AND ?";
        $params = [$dataIni, $dataFim];
        if ($cursoId) { $sql .= " AND c.id = ?"; $params[] = $cursoId; }
        $sql .= " ORDER BY c.nome, u.nome";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $dados = $stmt->fetchAll();
        break;

    /* ── 7. Alunos reprovados ─── */
    case 'reprovados':
        $titulo  = 'Alunos Reprovados';
        $colunas = ['Aluno','CPF/CRMV','E-mail','Curso','Avaliação','Melhor Nota (%)','Tentativas Usadas','Última tentativa'];
        $sql = "SELECT u.nome, u.cpf, u.email, c.nome AS curso, av.titulo AS avaliacao,
                       ROUND(MAX(t.nota),2) AS melhor_nota,
                       COUNT(t.id)          AS tentativas_usadas,
                       DATE_FORMAT(MAX(t.realizado_em),'%d/%m/%Y') AS ultima
                FROM tentativas t
                JOIN usuarios u    ON u.id  = t.aluno_id
                JOIN avaliacoes av ON av.id = t.avaliacao_id
                JOIN cursos c      ON c.id  = av.curso_id
                WHERE t.invalidado=0
                  AND t.aprovado=0
                  AND NOT EXISTS (
                    SELECT 1 FROM tentativas t2
                    WHERE t2.aluno_id=t.aluno_id AND t2.avaliacao_id=t.avaliacao_id AND t2.aprovado=1
                  )
                  AND DATE(t.realizado_em) BETWEEN ? AND ?";
        $params = [$dataIni, $dataFim];
        if ($cursoId) { $sql .= " AND c.id = ?"; $params[] = $cursoId; }
        $sql .= " GROUP BY u.id, av.id ORDER BY c.nome, u.nome";
        $stmt = $db->prepare($sql); $stmt->execute($params);
        $dados = $stmt->fetchAll();
        break;
}

/* ══════════════════════════════════════════════════════
   EXPORTAÇÃO
   ══════════════════════════════════════════════════════ */
if ($exportar && $dados !== null) {
    $nomeArquivo = 'relatorio_' . $tipo . '_' . date('Ymd_His');

    if ($exportar === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '.csv"');
        echo "\xEF\xBB\xBF"; // BOM UTF-8
        $out = fopen('php://output', 'w');
        fputcsv($out, $colunas, ';');
        foreach ($dados as $row) {
            fputcsv($out, array_values($row), ';');
        }
        fclose($out);
        exit;
    }

    if ($exportar === 'excel') {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '.xls"');
        echo "\xEF\xBB\xBF";
        echo '<table border="1">';
        echo '<tr style="background:#003d7c;color:#fff;font-weight:bold">';
        foreach ($colunas as $col) echo '<th>' . htmlspecialchars($col) . '</th>';
        echo '</tr>';
        foreach ($dados as $row) {
            echo '<tr>';
            foreach (array_values($row) as $v) echo '<td>' . htmlspecialchars((string)$v) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        exit;
    }

    if ($exportar === 'docx') {
        /* DOCX simples via OpenXML sem dependência externa */
        $rows = '';
        $thCells = '';
        foreach ($colunas as $col) {
            $thCells .= '<w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="003d7c"/></w:tcPr>'
                      . '<w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="18"/></w:rPr>'
                      . '<w:t>' . htmlspecialchars($col) . '</w:t></w:r></w:p></w:tc>';
        }
        $rows .= '<w:tr>' . $thCells . '</w:tr>';
        foreach ($dados as $idx => $row) {
            $fill = ($idx % 2 === 0) ? 'FFFFFF' : 'EFF6FF';
            $cells = '';
            foreach (array_values($row) as $v) {
                $cells .= '<w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="' . $fill . '"/></w:tcPr>'
                        . '<w:p><w:r><w:rPr><w:sz w:val="18"/></w:rPr>'
                        . '<w:t xml:space="preserve">' . htmlspecialchars((string)$v) . '</w:t></w:r></w:p></w:tc>';
            }
            $rows .= '<w:tr>' . $cells . '</w:tr>';
        }

        $cols = count($colunas);
        $gridCols = str_repeat('<w:gridCol w:w="1500"/>', $cols);
        $tblW = $cols * 1500;

        $docXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"'
            . ' xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>'
            . '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
            . '<w:r><w:rPr><w:b/><w:sz w:val="28"/><w:color w:val="003d7c"/></w:rPr>'
            . '<w:t>' . htmlspecialchars($titulo . ' — CRMV-TO') . '</w:t></w:r></w:p>'
            . '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
            . '<w:r><w:rPr><w:sz w:val="18"/><w:color w:val="666666"/></w:rPr>'
            . '<w:t>Período: ' . date('d/m/Y', strtotime($dataIni)) . ' a ' . date('d/m/Y', strtotime($dataFim)) . ' | Gerado em: ' . date('d/m/Y H:i') . '</w:t></w:r></w:p>'
            . '<w:p/>'
            . '<w:tbl>'
            . '<w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="' . $tblW . '" w:type="dxa"/><w:tblBorders>'
            . '<w:top w:val="single" w:sz="4" w:space="0" w:color="003d7c"/>'
            . '<w:left w:val="single" w:sz="4" w:space="0" w:color="003d7c"/>'
            . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="003d7c"/>'
            . '<w:right w:val="single" w:sz="4" w:space="0" w:color="003d7c"/>'
            . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>'
            . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>'
            . '</w:tblBorders></w:tblPr>'
            . '<w:tblGrid>' . $gridCols . '</w:tblGrid>'
            . $rows
            . '</w:tbl>'
            . '<w:p/>'
            . '<w:sectPr><w:pgSz w:w="16838" w:h="11906" w:orient="landscape"/>'
            . '<w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720"/>'
            . '</w:sectPr>'
            . '</w:body></w:document>';

        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';

        $wordRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '</Relationships>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml"  ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';

        $tmpFile = tempnam(sys_get_temp_dir(), 'docx_');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);
        $zip->addFromString('_rels/.rels',             $relsXml);
        $zip->addFromString('[Content_Types].xml',     $contentTypes);
        $zip->addFromString('word/document.xml',       $docXml);
        $zip->addFromString('word/_rels/document.xml.rels', $wordRelsXml);
        $zip->close();

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '.docx"');
        readfile($tmpFile);
        unlink($tmpFile);
        exit;
    }
}

/* ── Totalizadores rápidos ──────────────────────────── */
$totais = [
    'conclusoes'     => $db->query("SELECT COUNT(*) FROM matriculas WHERE status='concluida'")->fetchColumn(),
    'certificados'   => $db->query("SELECT COUNT(*) FROM certificados")->fetchColumn(),
    'matriculas'     => $db->query("SELECT COUNT(*) FROM matriculas WHERE status='ativa'")->fetchColumn(),
    'sem_cert'       => $db->query("SELECT COUNT(*) FROM matriculas m WHERE status='concluida' AND NOT EXISTS (SELECT 1 FROM certificados c WHERE c.aluno_id=m.aluno_id AND c.curso_id=m.curso_id)")->fetchColumn(),
];

$pageTitle = 'Relatórios';
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<!-- Cabeçalho -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
  <div>
    <h1 style="font-size:24px;font-weight:800;margin:0;color:var(--primary)">
      <i class="bi bi-bar-chart-fill me-2"></i>Central de Relatórios
    </h1>
    <p class="page-subtitle">Dados para auditoria e acompanhamento</p>
  </div>
  <div style="font-size:12px;color:var(--text-muted)">
    <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y H:i') ?>
  </div>
</div>

<!-- Cards de totais rápidos -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['Concluintes',           $totais['conclusoes'],   'bi-check-circle-fill',   'green'],
    ['Certificados Emitidos', $totais['certificados'],  'bi-award-fill',          'blue'],
    ['Matrículas Ativas',     $totais['matriculas'],   'bi-person-badge-fill',   'indigo'],
    ['Sem Certificado',       $totais['sem_cert'],     'bi-exclamation-triangle-fill', 'orange'],
  ];
  $colors = ['green'=>['#d1fae5','#065f46'],'blue'=>['#dbeafe','#1e40af'],'indigo'=>['#e0e7ff','#3730a3'],'orange'=>['#fef3c7','#92400e']];
  foreach ($cards as [$label, $val, $icon, $color]):
    [$bg, $fg] = $colors[$color];
  ?>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="border-radius:14px">
      <div class="stat-icon" style="background:<?= $bg ?>;color:<?= $fg ?>">
        <i class="bi <?= $icon ?>"></i>
      </div>
      <div>
        <div class="stat-value"><?= number_format((int)$val) ?></div>
        <div class="stat-label"><?= $label ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Painel principal -->
<div class="row g-4">

  <!-- Coluna esquerda: seleção de relatório -->
  <div class="col-lg-3">
    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
      <div class="card-header border-0 py-3" style="background:linear-gradient(135deg,#003d7c,#0066cc)">
        <h6 class="mb-0 text-white fw-bold"><i class="bi bi-list-ul me-2"></i>Tipo de Relatório</h6>
      </div>
      <div class="card-body p-2">
        <?php
        $relatorios = [
          'conclusoes'     => ['bi-check-circle-fill','Alunos Concluintes','Quem concluiu por curso'],
          'certificados'   => ['bi-award-fill','Certificados Emitidos','Histórico de emissões'],
          'notas'          => ['bi-clipboard-data-fill','Notas / Avaliações','Desempenho nas provas'],
          'matriculas'     => ['bi-person-badge-fill','Matrículas','Todas as matrículas ativas'],
          'desempenho'     => ['bi-graph-up-arrow','Desempenho por Curso','Resumo e taxas'],
          'sem_certificado'=> ['bi-exclamation-circle-fill','Sem Certificado','Concluintes pendentes'],
          'reprovados'     => ['bi-x-circle-fill','Reprovados','Alunos que não passaram'],
        ];
        foreach ($relatorios as $key => [$icon, $nome, $desc]):
          $ativo = $tipo === $key;
        ?>
        <a href="?tipo=<?= $key ?>&curso_id=<?= $cursoId ?>&data_ini=<?= $dataIni ?>&data_fim=<?= $dataFim ?>"
           class="d-flex align-items-start gap-2 p-3 rounded-3 text-decoration-none mb-1"
           style="background:<?= $ativo ? '#eff6ff' : 'transparent' ?>;border:1.5px solid <?= $ativo ? '#003d7c' : 'transparent' ?>;transition:all .15s"
           onmouseover="this.style.background='#f0f4f9'" onmouseout="this.style.background='<?= $ativo ? '#eff6ff' : 'transparent' ?>'">
          <div style="width:32px;height:32px;border-radius:8px;background:<?= $ativo ? '#003d7c' : '#f0f4f9' ?>;color:<?= $ativo ? '#fff' : '#64748b' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px">
            <i class="bi <?= $icon ?>"></i>
          </div>
          <div>
            <div style="font-size:13px;font-weight:700;color:<?= $ativo ? '#003d7c' : '#1a2035' ?>"><?= $nome ?></div>
            <div style="font-size:11px;color:#8898aa"><?= $desc ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Coluna direita: filtros + tabela -->
  <div class="col-lg-9">
    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">

      <!-- Header com título e exportação -->
      <div class="card-header border-0 py-3 px-4" style="background:#fff;border-bottom:1px solid #e9edf5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h6 class="mb-0 fw-bold" style="color:#1a2035"><?= htmlspecialchars($titulo) ?></h6>
            <small class="text-muted"><?= count($dados) ?> registro(s)</small>
          </div>
          <?php if ($dados): ?>
          <div class="d-flex gap-2 flex-wrap">
            <a href="?tipo=<?= $tipo ?>&curso_id=<?= $cursoId ?>&data_ini=<?= $dataIni ?>&data_fim=<?= $dataFim ?>&exportar=csv"
               class="btn btn-sm btn-outline-success fw-semibold">
              <i class="bi bi-filetype-csv me-1"></i>CSV
            </a>
            <a href="?tipo=<?= $tipo ?>&curso_id=<?= $cursoId ?>&data_ini=<?= $dataIni ?>&data_fim=<?= $dataFim ?>&exportar=excel"
               class="btn btn-sm btn-outline-primary fw-semibold">
              <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="?tipo=<?= $tipo ?>&curso_id=<?= $cursoId ?>&data_ini=<?= $dataIni ?>&data_fim=<?= $dataFim ?>&exportar=docx"
               class="btn btn-sm btn-outline-secondary fw-semibold">
              <i class="bi bi-file-earmark-word me-1"></i>DOCX
            </a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Filtros -->
      <div class="p-4 pb-0">
        <form method="GET" class="row g-2 align-items-end">
          <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">

          <?php if ($tipo !== 'desempenho'): ?>
          <div class="col-sm-6 col-md-4">
            <label class="form-label fw-semibold" style="font-size:12px">Data Inicial</label>
            <input type="date" name="data_ini" value="<?= $dataIni ?>" class="form-control form-control-sm">
          </div>
          <div class="col-sm-6 col-md-4">
            <label class="form-label fw-semibold" style="font-size:12px">Data Final</label>
            <input type="date" name="data_fim" value="<?= $dataFim ?>" class="form-control form-control-sm">
          </div>
          <?php endif; ?>

          <div class="col-sm-6 col-md-<?= $tipo !== 'desempenho' ? '3' : '8' ?>">
            <label class="form-label fw-semibold" style="font-size:12px">Curso</label>
            <select name="curso_id" class="form-select form-select-sm">
              <option value="">Todos os cursos</option>
              <?php foreach ($cursos as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $cursoId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm fw-bold">
              <i class="bi bi-funnel me-1"></i>Filtrar
            </button>
          </div>
        </form>
        <hr class="mt-3 mb-0">
      </div>

      <!-- Tabela de dados -->
      <div class="card-body p-0">
        <?php if ($dados): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size:13px">
            <thead style="background:#f8fafc">
              <tr>
                <?php foreach ($colunas as $col): ?>
                <th class="py-3 px-3 border-0 fw-bold" style="font-size:11.5px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap">
                  <?= htmlspecialchars($col) ?>
                </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dados as $row): ?>
              <tr>
                <?php foreach (array_values($row) as $i => $val): ?>
                <td class="py-2 px-3 border-0" style="border-bottom:1px solid #f0f4f9 !important">
                  <?php
                  $colNome = strtolower($colunas[$i] ?? '');
                  // Aprovado
                  if (in_array($colNome, ['aprovado'])) {
                      if ($val === 'Sim') echo '<span class="badge" style="background:#d1fae5;color:#065f46">✓ Sim</span>';
                      else               echo '<span class="badge" style="background:#fee2e2;color:#991b1b">✗ Não</span>';
                  }
                  // Status matrícula
                  elseif ($colNome === 'status') {
                      $c = ['Concluído'=>'#d1fae5:#065f46','Ativa'=>'#dbeafe:#1e40af','Cancelada'=>'#fee2e2:#991b1b'];
                      $parts = isset($c[$val]) ? explode(':', $c[$val]) : ['#f0f4f9','#64748b'];
                      echo '<span class="badge" style="background:' . $parts[0] . ';color:' . $parts[1] . '">' . htmlspecialchars($val) . '</span>';
                  }
                  // Progresso
                  elseif (strpos($colNome, 'progresso') !== false || strpos($colNome, 'taxa') !== false) {
                      $pct = (float)$val;
                      $color = $pct >= 80 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
                      echo '<div style="display:flex;align-items:center;gap:8px">'
                         . '<div style="flex:1;height:6px;background:#e2e8f0;border-radius:3px;min-width:60px">'
                         . '<div style="width:' . min(100,$pct) . '%;height:100%;background:' . $color . ';border-radius:3px"></div></div>'
                         . '<span style="font-weight:600;color:' . $color . '">' . $pct . '%</span></div>';
                  }
                  // Nota
                  elseif (strpos($colNome, 'nota') !== false || strpos($colNome, 'média') !== false) {
                      $n = (float)$val;
                      $color = $n >= 70 ? '#10b981' : ($n >= 50 ? '#f59e0b' : '#ef4444');
                      echo '<span style="font-weight:700;color:' . $color . '">' . $val . ($val !== '' ? '%' : '') . '</span>';
                  }
                  else {
                      echo htmlspecialchars((string)$val);
                  }
                  ?>
                </td>
                <?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
          <i class="bi bi-inbox" style="font-size:48px;color:#cbd5e1;display:block;margin-bottom:12px"></i>
          <h6 class="text-muted">Nenhum registro encontrado</h6>
          <small class="text-muted">Ajuste os filtros e tente novamente.</small>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div><!-- /col-lg-9 -->
</div><!-- /row -->

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
