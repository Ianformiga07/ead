<?php
/**
 * app/controllers/aluno/CertificadoController.php
 */

class CertificadoController extends BaseController
{
    public function index(array $params = []): void
    {
        $this->authAluno();

        $alunoId = $this->userId();
        $cursoId = (int)($params['id'] ?? 0);

        $certModel  = new CertificadoModel();
        $matModel   = new MatriculaModel();
        $cursoModel = new CursoModel();
        $usuModel   = new UsuarioModel();

        $matricula = $matModel->buscar($alunoId, $cursoId);

        if (!$matricula || $matricula['status'] !== 'concluida') {
            $this->error('Conclua o curso para emitir o certificado.');
            $this->redirect(APP_URL . "/aluno/cursos/{$cursoId}");
        }

        // Cria certificado se ainda não existe
        $cert = $certModel->buscar($alunoId, $cursoId);
        if (!$cert) {
            $codigo = gerarCodigoCertificado();
            $certModel->criar($alunoId, $cursoId, $codigo);
            $cert = $certModel->buscar($alunoId, $cursoId);
        }

        $modelo  = $certModel->modelo($cursoId);
        $usuario = $usuModel->findById($alunoId);
        $curso   = $cursoModel->findById($cursoId);

        $this->view('aluno/certificado', compact('cert', 'modelo', 'usuario', 'curso'));
    }

    public function pdf(array $params = []): void
    {
        $this->authAluno();

        $alunoId = $this->userId();
        $certId  = (int)($params['id'] ?? 0);

        $certModel = new CertificadoModel();

        // Busca via DB direto para garantir que é do aluno
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM certificados WHERE id=? AND aluno_id=?');
        $stmt->execute([$certId, $alunoId]);
        $cert = $stmt->fetch();

        if (!$cert) {
            $this->error('Certificado não encontrado.');
            $this->redirect(APP_URL . '/aluno/dashboard');
        }

        $cursoId = $cert['curso_id'];
        $modelo  = $certModel->modelo($cursoId);
        $usuario = (new UsuarioModel())->findById($alunoId);
        $curso   = (new CursoModel())->findById($cursoId);

        // Renderiza view de PDF (sem layout)
        partial('aluno/certificado_pdf', compact('cert', 'modelo', 'usuario', 'curso'));
    }
}
