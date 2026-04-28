<?php
/**
 * app/controllers/admin/CertificadoController.php
 */

class CertificadoController extends BaseController
{
    private CertificadoModel $model;

    public function __construct()
    {
        $this->model = new CertificadoModel();
    }

    /** Listagem geral de certificados emitidos */
    public function index(array $params = []): void
    {
        $this->authAdmin();

        $db    = getDB();
        $busca = sanitize($this->get('busca'));

        $where = "WHERE 1=1";
        $bind  = [];

        if ($busca) {
            $like   = "%{$busca}%";
            $where .= " AND (u.nome LIKE ? OR c.nome LIKE ? OR cert.codigo LIKE ?)";
            $bind   = [$like, $like, $like];
        }

        $countStmt = $db->prepare(
            "SELECT COUNT(*) FROM certificados cert
             JOIN usuarios u ON cert.aluno_id=u.id
             JOIN cursos   c ON cert.curso_id=c.id
             $where"
        );
        $countStmt->execute($bind);
        $total = (int)$countStmt->fetchColumn();

        $pag   = $this->paginate($total);
        $bind2 = [...$bind, $pag['per_page'], $pag['offset']];

        $stmt = $db->prepare(
            "SELECT cert.*, u.nome AS aluno_nome, u.crmv,
                    c.nome AS curso_nome, c.carga_horaria
             FROM certificados cert
             JOIN usuarios u ON cert.aluno_id=u.id
             JOIN cursos   c ON cert.curso_id=c.id
             $where
             ORDER BY cert.emitido_em DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute($bind2);
        $certificados = $stmt->fetchAll();

        $this->view('admin/certificados/listar', compact('certificados', 'pag', 'busca'));
    }

    /** Salva modelo de certificado do curso */
    public function salvarModelo(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $cursoId = (int)($params['id'] ?? 0);

        $d = [
            'texto_frente'   => sanitize_html($this->post('texto_frente')),
            'verso_conteudo' => sanitize_html($this->post('verso_conteudo')),
            'nome_cert'      => sanitize($this->post('nome_cert')),
            'instrutor'      => sanitize($this->post('instrutor')),
            'conteudo_prog'  => sanitize($this->post('conteudo_prog')),
            'ativar_verso'   => $this->intPost('ativar_verso'),
        ];

        foreach (['frente', 'verso'] as $campo) {
            if (!empty($_FILES[$campo]['name'])) {
                $img = uploadFile($_FILES[$campo], MODEL_PATH, ALLOWED_IMAGE);
                if ($img) $d[$campo] = $img;
            }
        }

        $this->model->salvarModelo($cursoId, $d);
        logAction('certificado.modelo', "Curso {$cursoId}");
        $this->success('Modelo de certificado salvo!');
        $this->redirect(APP_URL . "/admin/cursos/{$cursoId}?tab=certificado");
    }

    public function deletar(array $params = []): void
    {
        $this->authSoAdmin();
        $this->csrfVerify();

        $id   = (int)($params['id'] ?? 0);
        $stmt = getDB()->prepare('DELETE FROM certificados WHERE id=?');
        $stmt->execute([$id]);
        logAction('certificado.deletar', "Certificado ID {$id}");
        $this->success('Certificado removido.');
        $this->redirect(APP_URL . '/admin/certificados');
    }

    /** Validação pública por código */
    public function validar(array $params = []): void
    {
        $code  = sanitize($params['code'] ?? '');
        $cert  = $code ? $this->model->buscarPorCodigo($code) : null;

        $this->view('auth/validar', compact('cert', 'code'));
    }
}
