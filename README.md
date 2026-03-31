# 🎓 Plataforma EAD — Instruções de Instalação (LARAGON)

## Requisitos
- Laragon (com Apache + PHP 8.1+ + MySQL)
- Extensões PHP: PDO, PDO_MySQL, GD, fileinfo, mbstring

---

## 1. INSTALAR NO LARAGON

### Passo 1 — Copiar arquivos
Descompacte o arquivo `ead_plataforma.zip` dentro da pasta:
```
C:\laragon\www\ead\
```
A estrutura final deve ser:
```
C:\laragon\www\ead\
  ├── index.php
  ├── login.php
  ├── logout.php
  ├── validar.php
  ├── .htaccess
  ├── admin\
  ├── aluno\
  ├── app\
  ├── config\
  ├── database\
  └── public\
```

---

### Passo 2 — Criar o banco de dados

1. Abra o **HeidiSQL** (já vem com o Laragon) ou **phpMyAdmin**
2. Crie um banco de dados com o nome: `plataforma_ead`
3. Selecione o banco criado
4. Vá em **Arquivo → Executar arquivo SQL** e selecione `database/ead.sql`
5. Execute — todas as tabelas serão criadas automaticamente

Ou via linha de comando (no terminal do Laragon):
```bash
mysql -u root -e "CREATE DATABASE plataforma_ead CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root plataforma_ead < C:\laragon\www\ead\database\ead.sql
```

---

### Passo 3 — Configurar conexão

Edite o arquivo `config/database.php`:
```php
define('DB_HOST', 'localhost');   // padrão Laragon
define('DB_NAME', 'plataforma_ead');
define('DB_USER', 'root');        // padrão Laragon
define('DB_PASS', '');            // Laragon não tem senha por padrão
```

---

### Passo 4 — Configurar URL base

Edite o arquivo `config/app.php`:
```php
define('APP_URL', 'http://localhost/ead');
```

> ⚠️ Se usar domínio virtual no Laragon (ex: `ead.test`), altere para:
> `define('APP_URL', 'http://ead.test');`

---

### Passo 5 — Configurar permissões de upload

Certifique-se que as pastas de upload têm permissão de escrita:
```
ead/public/uploads/materiais/
ead/public/uploads/certificados/
ead/public/uploads/modelos/
```
No Windows com Laragon, geralmente não há problema. No Linux:
```bash
chmod -R 755 public/uploads/
```

---

### Passo 6 — Habilitar mod_rewrite (Apache)

No Laragon, o `mod_rewrite` já vem habilitado.
Verifique se o `AllowOverride All` está no Apache — normalmente está por padrão.

---

## 2. ACESSAR O SISTEMA

Abra o navegador e acesse:
```
http://localhost/ead
```

### Credenciais padrão:
| Campo  | Valor             |
|--------|-------------------|
| E-mail | admin@ead.com     |
| Senha  | password          |

> ⚠️ **IMPORTANTE:** Troque a senha do admin imediatamente após o primeiro login!

---

## 3. PRIMEIROS PASSOS

### Como configurar a plataforma:

1. **Faça login** como administrador
2. **Cadastre um curso** em *Gerenciar Cursos → Novo Curso*
3. **Adicione aulas** clicando no ícone de play na listagem de cursos
4. **Envie materiais** clicando no ícone de arquivo
5. **(Opcional)** Configure avaliação clicando no ícone de interrogação
6. **Cadastre alunos** em *Alunos → Novo Aluno*
7. **Matricule alunos** em *Matrículas → Nova Matrícula*
8. **Configure modelo de certificado** em *Certificados* (imagem frente/verso)

---

## 4. ESTRUTURA DE PASTAS

```
ead/
├── .htaccess                    → Segurança Apache
├── index.php                    → Roteador principal
├── login.php                    → Página de login
├── logout.php                   → Logout
├── validar.php                  → Validação pública de certificados
│
├── admin/                       → Painel administrativo
│   ├── dashboard.php
│   ├── cursos.php
│   ├── aulas.php
│   ├── materiais.php
│   ├── alunos.php
│   ├── matriculas.php
│   ├── avaliacao.php
│   ├── certificados.php
│   └── logs.php
│
├── aluno/                       → Área do aluno
│   ├── dashboard.php
│   ├── curso.php                → Aulas + vídeo player
│   ├── avaliacao.php
│   ├── certificado.php
│   ├── gerar_pdf.php            → PDF do certificado
│   ├── marcar_aula.php          → AJAX: marcar aula concluída
│   └── perfil.php
│
├── app/                         → Lógica da aplicação (protegida)
│   ├── bootstrap.php            → Autoload de classes e configs
│   ├── models/                  → Camada de dados (PDO)
│   │   ├── Model.php            → Classe base
│   │   ├── UsuarioModel.php
│   │   ├── CursoModel.php
│   │   ├── AulaModel.php
│   │   ├── OtherModels.php      → Material, Matricula, Avaliacao
│   │   └── CertificadoModel.php
│   ├── helpers/
│   │   └── functions.php        → Auth, CSRF, Flash, Upload, etc.
│   └── views/layouts/           → Headers/Footers Admin e Aluno
│
├── config/                      → Configurações (protegida)
│   ├── app.php                  → Constantes da aplicação
│   └── database.php             → Conexão PDO
│
├── database/
│   └── ead.sql                  → Script SQL completo
│
└── public/                      → Arquivos públicos
    ├── css/
    │   ├── admin.css
    │   └── aluno.css
    ├── js/
    │   ├── admin.js
    │   └── aluno.js
    └── uploads/
        ├── materiais/           → Arquivos didáticos
        ├── certificados/        → PDFs gerados
        ├── modelos/             → Imagens frente/verso do certificado
        └── cursos/              → Imagens de capa dos cursos
```

---

## 5. FUNCIONALIDADES IMPLEMENTADAS

### ✅ Controle de Acesso
- Login com bcrypt (cost 12)
- Sessão segura com regeneração de ID
- Proteção de rotas por perfil (admin/aluno)
- Token CSRF em todos os formulários
- Logout com invalidação de sessão

### ✅ Área Admin
- Dashboard com estatísticas
- CRUD completo de cursos (EAD e Presencial)
- Gerenciar aulas com links de vídeo (YouTube/Vimeo)
- Upload de materiais didáticos
- CRUD de alunos com validações
- Sistema de matrículas (vincular aluno ↔ curso)
- Criação de avaliações com perguntas e alternativas
- Configuração de modelo de certificado (frente/verso)
- Visualização de certificados emitidos
- Log de ações do sistema

### ✅ Área do Aluno
- Dashboard com cursos matriculados e progresso
- Player de vídeo integrado (YouTube/Vimeo)
- Marcar aulas como concluídas (AJAX)
- Cálculo automático de progresso
- Download de materiais
- Realizar avaliação com múltipla escolha
- Ver resultado e nota
- Emissão de certificado ao concluir o curso
- Certificado com QR Code único
- Geração de PDF do certificado
- Editar perfil

### ✅ Certificados
- Código único gerado automaticamente
- QR Code via API pública (qrserver.com)
- Certificado em HTML otimizado para impressão/PDF
- Frente: Nome, Curso, Carga Horária, Data
- Verso: Conteúdo Programático, Instrutores
- Upload de imagem de fundo (frente e verso)

### ✅ Validação Pública
- Página pública em `/validar.php`
- Busca por código único
- Exibe: Aluno, Curso, Carga Horária, Data, Status

### ✅ Segurança
- PDO com prepared statements (anti SQL Injection)
- CSRF tokens em todos os formulários
- Senhas bcrypt
- Headers de segurança HTTP
- Pastas protegidas via .htaccess
- Sanitização de inputs

---

## 6. PERSONALIZAÇÃO

### Alterar nome da plataforma:
```php
// config/app.php
define('APP_NAME', 'Minha Plataforma EAD');
```

### Alterar cores (tema):
```css
/* public/css/admin.css e aluno.css */
:root {
  --primary: #4f46e5;  /* cor principal */
  --success: #10b981;  /* cor de sucesso */
}
```

### Aumentar limite de upload:
```php
// config/app.php
define('MAX_UPLOAD_MB', 50); // padrão: 20MB
```

---

## 7. PROBLEMAS COMUNS

| Problema | Solução |
|----------|---------|
| Página em branco | Ative erros em `config/app.php`: `ini_set('display_errors',1);` |
| Erro 403 | Verifique se `mod_rewrite` está ativo e `AllowOverride All` no Apache |
| Upload falha | Verifique permissões da pasta `public/uploads/` |
| Sessão expirando | Aumente `session.gc_maxlifetime` no php.ini |
| QR Code não aparece | Verifique conexão com internet (usa api.qrserver.com) |

---

## 8. PRÓXIMAS MELHORIAS SUGERIDAS

- Integração com **Dompdf** para PDF de qualidade profissional
- Notificações por e-mail (PHPMailer)
- Relatórios exportáveis em Excel
- Sistema de fórum/comentários por aula
- Integração com gateway de pagamento
- App mobile (API REST)

---

*Desenvolvido com PHP puro + MySQL + Bootstrap 5*
*Compatível com Laragon, WAMP, XAMPP e servidores Linux/Apache*
