-- --------------------------------------------------------
-- Servidor:                     127.0.0.1
-- Versão do servidor:           8.4.3 - MySQL Community Server - GPL
-- OS do Servidor:               Win64
-- HeidiSQL Versão:              12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Copiando dados para a tabela plataforma_ead.alternativas: ~24 rows (aproximadamente)
INSERT IGNORE INTO `alternativas` (`id`, `pergunta_id`, `texto`, `correta`) VALUES
	(53, 14, 'dsadsadas', 1),
	(54, 14, 'asdasdsad', 0),
	(55, 14, 'asdas', 0),
	(56, 14, 'asdasd', 0),
	(57, 15, 'asdsadsad', 1),
	(58, 15, 'sdasdas', 0),
	(59, 15, 'sadasda', 0),
	(60, 15, 'sadsadas', 0),
	(61, 17, 'dasdas', 1),
	(62, 17, 'asdasdas', 0),
	(63, 17, 'asdasd', 0),
	(64, 17, 'dasda', 0),
	(65, 18, 'asdsadas', 1),
	(66, 18, 'asdasdsa', 0),
	(67, 18, 'sadasd', 0),
	(68, 18, 'asdas', 0),
	(73, 20, 'asdsada', 1),
	(74, 20, 'asdsada', 0),
	(75, 20, 'asdasda', 0),
	(76, 20, 'asdsadas', 0),
	(77, 21, 'asdasdasd', 1),
	(78, 21, 'asdsadas', 0),
	(79, 21, 'asdasd', 0),
	(80, 21, 'asdasd', 0);

-- Copiando dados para a tabela plataforma_ead.aulas: ~7 rows (aproximadamente)
INSERT IGNORE INTO `aulas` (`id`, `curso_id`, `titulo`, `descricao`, `url_video`, `ordem`, `status`, `criado_em`) VALUES
	(13, 7, 'asdsadasdasd', '', 'https://www.youtube.com/watch?v=RU6UQgo7VZw&list=RDDlLW1jD0VKk&index=6', 1, 1, '2026-04-01 03:02:25'),
	(14, 7, 'asdsadasd', '', 'local://vid_69cc8ac86230c7.66272140.mp4', 2, 1, '2026-04-01 03:02:32'),
	(15, 8, 'dasdsadasd', '', 'https://www.youtube.com/watch?v=7cp69qJB3iM&list=RD7cp69qJB3iM&start_radio=1', 1, 1, '2026-04-02 21:49:40'),
	(16, 8, 'sdasdasdas', '', 'local://vid_69cee47c581896.77430089.mp4', 2, 1, '2026-04-02 21:49:48'),
	(18, 10, 'asdasdas', '', 'https://www.youtube.com/watch?v=Us-GEZH13dY&list=RD7cp69qJB3iM&index=9', 1, 1, '2026-04-03 03:13:18'),
	(19, 10, 'sadasdasdas', '', 'local://vid_69cf3056858757.47486577.mp4', 2, 1, '2026-04-03 03:13:26'),
	(20, 11, 'eqweqweqweqw', '', 'https://www.youtube.com/watch?v=XRGU627_bEE&list=RD7cp69qJB3iM&index=12', 1, 1, '2026-04-03 03:31:29');

-- Copiando dados para a tabela plataforma_ead.avaliacoes: ~4 rows (aproximadamente)
INSERT IGNORE INTO `avaliacoes` (`id`, `curso_id`, `titulo`, `descricao`, `tentativas`, `criado_em`) VALUES
	(7, 7, 'Avaliação Final — curso de alguma coisa', '', 1, '2026-04-01 03:02:14'),
	(8, 8, 'Avaliação Final — Capacitação Eventos Pecuários', '', 1, '2026-04-02 21:50:03'),
	(10, 10, 'Avaliação Final — CURSO PLANEJAMENTO DA CONTRATAÇÃO NA NOVA LEI DE LICITAÇÕES, COM USO DA INTELIGÊNCIA ARTIFICIAL E JOGO DE CONTRATAÇÕES', '', 1, '2026-04-03 03:13:03'),
	(11, 11, 'Avaliação Final — wqeqweqwewqewqeqeqweqweqweqweqweqweqwe', '', 1, '2026-04-03 03:31:16');

-- Copiando dados para a tabela plataforma_ead.certificados: ~3 rows (aproximadamente)
INSERT IGNORE INTO `certificados` (`id`, `aluno_id`, `curso_id`, `codigo`, `arquivo`, `emitido_em`) VALUES
	(8, 3, 8, 'C6B969CE50F44D22E79CC23B2AC90EC1', '', '2026-04-02 22:38:21'),
	(10, 3, 10, '2ABBE7A7F284B061C80CE91FCE280382', '', '2026-04-03 03:17:27'),
	(11, 3, 11, '9C328B059CF50782699EC2FD8A4764A3', '', '2026-04-03 03:33:27'),
	(12, 6, 8, '95043CDA7E35E4F66E0FE812A79DBEFD', '', '2026-04-04 03:59:35');

-- Copiando dados para a tabela plataforma_ead.cursos: ~4 rows (aproximadamente)
INSERT IGNORE INTO `cursos` (`id`, `nome`, `descricao`, `tipo`, `carga_horaria`, `instrutores`, `status`, `tem_avaliacao`, `nota_minima`, `imagem`, `conteudo_programatico`, `criado_em`, `atualizado_em`) VALUES
	(7, 'curso de alguma coisa', 'asdasda', 'ead', 8, 'IAN FORMIGA - PHP TI, JOAO - Me. em TI, CHICO - EAD', 1, 1, 60.00, '69cc8aaebda414.61662584.png', '', '2026-04-01 03:02:06', '2026-04-01 03:02:14'),
	(8, 'Capacitação Eventos Pecuários', '', 'ead', 20, NULL, 1, 1, 60.00, '69ceeb5ccffe29.24493393.png', NULL, '2026-04-02 21:48:56', '2026-04-02 22:19:08'),
	(10, 'CURSO PLANEJAMENTO DA CONTRATAÇÃO NA NOVA LEI DE LICITAÇÕES, COM USO DA INTELIGÊNCIA ARTIFICIAL E JOGO DE CONTRATAÇÕES', '', 'ead', 8, NULL, 1, 1, 60.00, '69cf303b896528.11211466.png', NULL, '2026-04-03 03:12:59', '2026-04-03 03:13:03'),
	(11, 'wqeqweqwewqewqeqeqweqweqweqweqweqweqwe', '', 'ead', 8, NULL, 1, 1, 60.00, '69cf347f7e5701.95730081.png', NULL, '2026-04-03 03:31:11', '2026-04-03 03:31:16');

-- Copiando dados para a tabela plataforma_ead.logs: ~171 rows (aproximadamente)
INSERT IGNORE INTO `logs` (`id`, `usuario_id`, `acao`, `detalhes`, `ip`, `criado_em`) VALUES
	(1, 1, 'login', 'Login realizado: admin@ead.com', '::1', '2026-03-31 00:59:25'),
	(2, 1, 'curso.criar', 'Curso criado ID 1', '::1', '2026-03-31 01:06:12'),
	(3, 1, 'aluno.criar', 'Aluno criado ID 2', '::1', '2026-03-31 01:18:39'),
	(4, 1, 'matricula.criar', 'Aluno 2 matriculado no curso 1', '::1', '2026-03-31 01:19:11'),
	(5, 1, 'curso.atualizar', 'Curso ID 1 atualizado', '::1', '2026-03-31 01:20:50'),
	(6, 1, 'logout', 'Logout realizado', '::1', '2026-03-31 01:22:18'),
	(7, 2, 'login', 'Login realizado: formigaian@gmail.com', '::1', '2026-03-31 01:22:24'),
	(8, 2, 'logout', 'Logout realizado', '::1', '2026-03-31 01:28:09'),
	(9, 1, 'login', 'Login realizado: admin@ead.com', '::1', '2026-03-31 01:28:20'),
	(10, 1, 'logout', 'Logout realizado', '::1', '2026-03-31 01:29:49'),
	(11, 2, 'login', 'Login realizado: formigaian@gmail.com', '::1', '2026-03-31 01:29:53'),
	(12, 2, 'logout', 'Logout realizado', '::1', '2026-03-31 01:30:19'),
	(13, 1, 'login', 'Login realizado: admin@ead.com', '::1', '2026-03-31 01:30:25'),
	(14, 1, 'logout', 'Logout realizado', '::1', '2026-03-31 01:58:42'),
	(15, 1, 'login', 'Login realizado: admin@ead.com', '::1', '2026-03-31 01:58:54'),
	(16, 1, 'curso.criar', 'Curso criado ID 2', '::1', '2026-03-31 02:01:53'),
	(17, 1, 'matricula.criar', 'Aluno 2 matriculado no curso 2', '::1', '2026-03-31 02:04:07'),
	(18, 1, 'logout', 'Logout realizado', '::1', '2026-03-31 02:10:05'),
	(19, 2, 'login', 'Login realizado: formigaian@gmail.com', '::1', '2026-03-31 02:10:13'),
	(20, 2, 'avaliacao.realizada', 'Curso 2 — Nota: 100% — Aprovado', '::1', '2026-03-31 02:10:42'),
	(21, 2, 'logout', 'Logout realizado', '::1', '2026-03-31 02:12:27'),
	(22, 1, 'login', 'Login realizado: admin@ead.com', '::1', '2026-03-31 02:12:36'),
	(23, 1, 'logout', 'Logout realizado', '::1', '2026-03-31 02:14:42'),
	(24, 1, 'login', 'Login realizado: admin@ead.com', '::1', '2026-03-31 03:02:09'),
	(25, 1, 'logout', 'Logout realizado', '::1', '2026-03-31 03:03:07'),
	(26, 1, 'login', 'Login: admin@ead.com', '::1', '2026-03-31 18:55:10'),
	(27, 1, 'logout', 'Logout realizado', '::1', '2026-03-31 19:10:02'),
	(28, 2, 'login', 'Login: formigaian@gmail.com', '::1', '2026-03-31 19:14:45'),
	(29, 2, 'logout', 'Logout realizado', '::1', '2026-03-31 19:15:18'),
	(30, 1, 'login', 'Login: admin@ead.com', '::1', '2026-03-31 19:15:27'),
	(31, 1, 'logout', 'Logout realizado', '::1', '2026-03-31 19:23:50'),
	(32, 1, 'login', 'Login: admin@ead.com', '::1', '2026-03-31 19:24:02'),
	(33, 1, 'curso.criar', 'Curso ID 3', '::1', '2026-03-31 19:24:59'),
	(34, 1, 'curso.atualizar', 'Curso ID 3', '::1', '2026-03-31 19:30:00'),
	(35, 1, 'matricula.criar', 'Aluno 2 matriculado no curso 3', '::1', '2026-03-31 19:30:34'),
	(36, 1, 'logout', 'Logout realizado', '::1', '2026-03-31 19:34:35'),
	(37, 2, 'login', 'Login: formigaian@gmail.com', '::1', '2026-03-31 19:34:40'),
	(38, 2, 'logout', 'Logout realizado', '::1', '2026-03-31 19:38:16'),
	(39, 1, 'login', 'Login: admin@ead.com', '::1', '2026-03-31 19:38:21'),
	(40, 1, 'aluno.criar', 'Aluno criado ID 3', '::1', '2026-03-31 19:42:23'),
	(41, 1, 'matricula.criar', 'Aluno 3 matriculado no curso 2', '::1', '2026-03-31 19:42:41'),
	(42, 1, 'logout', 'Logout realizado', '::1', '2026-03-31 19:42:45'),
	(43, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-03-31 19:42:51'),
	(44, 3, 'avaliacao.realizada', 'Curso 2 — Nota: 66.67% — Aprovado', '::1', '2026-03-31 19:43:15'),
	(45, 3, 'logout', 'Logout realizado', '::1', '2026-03-31 19:44:04'),
	(46, 1, 'login', 'Login: admin@ead.com', '::1', '2026-03-31 19:44:12'),
	(47, 1, 'matricula.criar', 'Aluno 3 matriculado no curso 3', '::1', '2026-03-31 19:44:27'),
	(48, 1, 'logout', 'Logout realizado', '::1', '2026-03-31 19:44:51'),
	(49, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-03-31 19:44:56'),
	(50, 3, 'logout', 'Logout realizado', '::1', '2026-04-01 02:26:50'),
	(51, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-01 02:26:59'),
	(52, 1, 'logout', 'Logout realizado', '::1', '2026-04-01 02:27:41'),
	(53, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-04-01 02:27:47'),
	(54, 3, 'logout', 'Logout realizado', '::1', '2026-04-01 02:27:55'),
	(55, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-01 02:28:03'),
	(56, 1, 'curso.criar', 'Curso ID 4', '::1', '2026-04-01 02:28:28'),
	(57, 1, 'matricula.criar', 'Aluno 3 matriculado no curso 4', '::1', '2026-04-01 02:29:42'),
	(58, 1, 'logout', 'Logout realizado', '::1', '2026-04-01 02:29:46'),
	(59, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-04-01 02:29:51'),
	(60, 3, 'logout', 'Logout realizado', '::1', '2026-04-01 02:48:52'),
	(61, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-04-01 02:48:57'),
	(62, 3, 'logout', 'Logout realizado', '::1', '2026-04-01 02:51:04'),
	(63, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-01 02:51:09'),
	(64, 1, 'curso.deletar', 'Curso ID 4', '::1', '2026-04-01 02:51:33'),
	(65, 1, 'curso.criar', 'Curso ID 5', '::1', '2026-04-01 02:51:51'),
	(66, 1, 'matricula.criar', 'Aluno 3 matriculado no curso 5', '::1', '2026-04-01 02:52:59'),
	(67, 1, 'logout', 'Logout realizado', '::1', '2026-04-01 02:53:01'),
	(68, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-04-01 02:53:08'),
	(69, 3, 'logout', 'Logout realizado', '::1', '2026-04-01 02:57:07'),
	(70, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-01 02:57:19'),
	(71, 1, 'curso.deletar', 'Curso ID 5', '::1', '2026-04-01 02:57:28'),
	(72, 1, 'curso.deletar', 'Curso ID 1', '::1', '2026-04-01 02:57:33'),
	(73, 1, 'curso.criar', 'Curso ID 6', '::1', '2026-04-01 02:57:53'),
	(74, 1, 'curso.atualizar', 'Curso ID 6', '::1', '2026-04-01 02:58:03'),
	(75, 1, 'curso.atualizar', 'Curso ID 6', '::1', '2026-04-01 02:59:23'),
	(76, 1, 'matricula.criar', 'Aluno 3 matriculado no curso 6', '::1', '2026-04-01 02:59:31'),
	(77, 1, 'logout', 'Logout realizado', '::1', '2026-04-01 02:59:32'),
	(78, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-04-01 02:59:37'),
	(79, 3, 'logout', 'Logout realizado', '::1', '2026-04-01 03:01:34'),
	(80, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-01 03:01:41'),
	(81, 1, 'curso.criar', 'Curso ID 7', '::1', '2026-04-01 03:02:06'),
	(82, 1, 'curso.atualizar', 'Curso ID 7', '::1', '2026-04-01 03:02:14'),
	(83, 1, 'matricula.criar', 'Aluno 3 matriculado no curso 7', '::1', '2026-04-01 03:03:28'),
	(84, 1, 'logout', 'Logout realizado', '::1', '2026-04-01 03:04:05'),
	(85, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-04-01 03:04:12'),
	(86, 3, 'avaliacao.realizada', 'Curso 7 — Nota: 0% — Reprovado', '::1', '2026-04-01 03:04:46'),
	(87, 3, 'avaliacao.realizada', 'Curso 6 — Nota: 100% — Aprovado', '::1', '2026-04-02 14:13:30'),
	(88, 3, 'logout', 'Logout realizado', '::1', '2026-04-02 21:14:54'),
	(89, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-02 21:15:06'),
	(90, 1, 'logout', 'Logout realizado', '::1', '2026-04-02 21:39:09'),
	(91, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-02 21:39:32'),
	(92, 1, 'logout', 'Logout realizado', '::1', '2026-04-02 21:47:27'),
	(93, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-02 21:47:37'),
	(94, 1, 'curso.criar', 'Curso ID 8', '::1', '2026-04-02 21:48:56'),
	(95, 1, 'curso.atualizar', 'Curso ID 8', '::1', '2026-04-02 21:50:03'),
	(96, 1, 'certificado.salvar', 'Modelo certificado curso ID 8', '::1', '2026-04-02 21:53:12'),
	(97, 1, 'certificado.salvar', 'Modelo certificado curso ID 8', '::1', '2026-04-02 21:53:25'),
	(98, 1, 'curso.atualizar', 'Curso ID 8', '::1', '2026-04-02 21:53:34'),
	(99, 1, 'matricula.criar', 'Aluno 2 matriculado no curso 8', '::1', '2026-04-02 21:53:47'),
	(100, 1, 'logout', 'Logout realizado', '::1', '2026-04-02 21:53:49'),
	(101, 2, 'login', 'Login: formigaian@gmail.com', '::1', '2026-04-02 21:53:56'),
	(102, 2, 'logout', 'Logout realizado', '::1', '2026-04-02 21:54:07'),
	(103, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-02 21:54:15'),
	(104, 1, 'matricula.criar', 'Aluno 3 matriculado no curso 8', '::1', '2026-04-02 21:54:31'),
	(105, 1, 'aluno.atualizar', 'Aluno ID 2 atualizado', '::1', '2026-04-02 21:55:48'),
	(106, 1, 'logout', 'Logout realizado', '::1', '2026-04-02 21:55:52'),
	(107, 2, 'login', 'Login: formigaian@gmail.com', '::1', '2026-04-02 21:55:58'),
	(108, 2, 'avaliacao.realizada', 'Curso 8 — Nota: 100% — Aprovado', '::1', '2026-04-02 21:57:26'),
	(109, 2, 'logout', 'Logout realizado', '::1', '2026-04-02 22:04:39'),
	(110, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-02 22:04:49'),
	(111, 1, 'certificado.salvar', 'Modelo certificado curso ID 8', '::1', '2026-04-02 22:15:25'),
	(112, 1, 'logout', 'Logout realizado', '::1', '2026-04-02 22:18:40'),
	(113, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-02 22:18:52'),
	(114, 1, 'curso.atualizar', 'Curso ID 8', '::1', '2026-04-02 22:19:08'),
	(115, 1, 'curso.atualizar', 'Curso ID 8', '::1', '2026-04-02 22:19:10'),
	(116, 1, 'usuario.criar', 'Usuário criado ID 4 perfil=admin', '::1', '2026-04-02 22:20:57'),
	(117, 1, 'usuario.criar', 'Usuário criado ID 5 perfil=operador', '::1', '2026-04-02 22:21:22'),
	(118, 1, 'logout', 'Logout realizado', '::1', '2026-04-02 22:21:23'),
	(119, 5, 'login', 'Login: iformiga06@gmail.com', '::1', '2026-04-02 22:21:30'),
	(120, 5, 'certificado.salvar', 'Modelo certificado curso ID 8', '::1', '2026-04-02 22:37:12'),
	(121, 5, 'matricula.criar', 'Aluno 3 matriculado no curso 8', '::1', '2026-04-02 22:37:23'),
	(122, 5, 'logout', 'Logout realizado', '::1', '2026-04-02 22:37:25'),
	(123, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-04-02 22:37:31'),
	(124, 3, 'avaliacao.realizada', 'Curso 8 — Nota: 100% — Aprovado', '::1', '2026-04-02 22:38:18'),
	(125, 3, 'logout', 'Logout realizado', '::1', '2026-04-02 22:38:33'),
	(126, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-03 02:47:58'),
	(127, 1, 'curso.criar', 'Curso ID 9', '::1', '2026-04-03 02:48:37'),
	(128, 1, 'curso.atualizar', 'Curso ID 9', '::1', '2026-04-03 02:48:45'),
	(129, 1, 'certificado.salvar', 'Modelo certificado curso ID 9', '::1', '2026-04-03 02:55:54'),
	(130, 1, 'curso.atualizar', 'Curso ID 9', '::1', '2026-04-03 02:56:11'),
	(131, 1, 'matricula.criar', 'Aluno 3 matriculado no curso 9', '::1', '2026-04-03 02:56:29'),
	(132, 1, 'logout', 'Logout realizado', '::1', '2026-04-03 02:56:32'),
	(133, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-04-03 02:56:40'),
	(134, 3, 'avaliacao.realizada', 'Curso 9 — Nota: 100% — Aprovado', '::1', '2026-04-03 02:57:56'),
	(135, 3, 'logout', 'Logout realizado', '::1', '2026-04-03 03:12:02'),
	(136, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-03 03:12:17'),
	(137, 1, 'curso.deletar', 'Curso ID 2', '::1', '2026-04-03 03:12:28'),
	(138, 1, 'curso.deletar', 'Curso ID 3', '::1', '2026-04-03 03:12:32'),
	(139, 1, 'curso.deletar', 'Curso ID 6', '::1', '2026-04-03 03:12:35'),
	(140, 1, 'curso.deletar', 'Curso ID 9', '::1', '2026-04-03 03:12:37'),
	(141, 1, 'curso.criar', 'Curso ID 10', '::1', '2026-04-03 03:12:59'),
	(142, 1, 'curso.atualizar', 'Curso ID 10', '::1', '2026-04-03 03:13:03'),
	(143, 1, 'certificado.salvar', 'Modelo certificado curso ID 10', '::1', '2026-04-03 03:15:12'),
	(144, 1, 'matricula.criar', 'Aluno 3 matriculado no curso 10', '::1', '2026-04-03 03:15:21'),
	(145, 1, 'logout', 'Logout realizado', '::1', '2026-04-03 03:15:24'),
	(146, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-04-03 03:15:30'),
	(147, 3, 'avaliacao.realizada', 'Curso 10 — Nota: 100% — Aprovado', '::1', '2026-04-03 03:17:20'),
	(148, 3, 'logout', 'Logout realizado', '::1', '2026-04-03 03:30:14'),
	(149, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-03 03:30:19'),
	(150, 1, 'curso.criar', 'Curso ID 11', '::1', '2026-04-03 03:31:11'),
	(151, 1, 'curso.atualizar', 'Curso ID 11', '::1', '2026-04-03 03:31:16'),
	(152, 1, 'certificado.salvar', 'Modelo certificado curso ID 11', '::1', '2026-04-03 03:32:33'),
	(153, 1, 'matricula.criar', 'Aluno 3 matriculado no curso 11', '::1', '2026-04-03 03:32:45'),
	(154, 1, 'logout', 'Logout realizado', '::1', '2026-04-03 03:32:47'),
	(155, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-04-03 03:32:52'),
	(156, 3, 'avaliacao.realizada', 'Curso 11 — Nota: 100% — Aprovado', '::1', '2026-04-03 03:33:24'),
	(157, 3, 'logout', 'Logout realizado', '::1', '2026-04-03 22:13:25'),
	(158, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-03 22:14:37'),
	(159, 1, 'logout', 'Logout realizado', '::1', '2026-04-03 22:19:00'),
	(160, 3, 'login', 'Login: lrmorais29@gmail.com', '::1', '2026-04-03 22:19:05'),
	(161, 3, 'logout', 'Logout realizado', '::1', '2026-04-03 22:43:28'),
	(162, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-03 22:43:37'),
	(163, 1, 'logout', 'Logout realizado', '::1', '2026-04-04 01:59:53'),
	(164, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-04 02:00:00'),
	(165, 1, 'logout', 'Logout realizado', '::1', '2026-04-04 02:08:41'),
	(166, 5, 'login', 'Login: iformiga06@gmail.com', '::1', '2026-04-04 02:08:54'),
	(167, 5, 'login', 'Login: iformiga06@gmail.com', '::1', '2026-04-04 02:16:25'),
	(168, 5, 'login', 'Login: iformiga06@gmail.com', '::1', '2026-04-04 02:16:34'),
	(169, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-04 02:16:46'),
	(170, 1, 'logout', 'Logout realizado', '::1', '2026-04-04 02:18:00'),
	(171, 5, 'login', 'Login: iformiga06@gmail.com', '::1', '2026-04-04 02:18:08'),
	(172, 5, 'logout', 'Logout realizado', '::1', '2026-04-04 03:42:51'),
	(173, 1, 'login', 'Login: admin@ead.com', '::1', '2026-04-04 03:55:14'),
	(174, 1, 'certificado.salvar', 'Modelo certificado curso ID 8', '::1', '2026-04-04 03:57:15'),
	(175, 1, 'aluno.criar', 'Aluno criado ID 6', '::1', '2026-04-04 03:58:26'),
	(176, 1, 'matricula.criar', 'Aluno 6 matriculado no curso 8', '::1', '2026-04-04 03:58:51'),
	(177, 1, 'logout', 'Logout realizado', '::1', '2026-04-04 03:58:53'),
	(178, 6, 'login', 'Login: jonhfilho@gmail.com', '::1', '2026-04-04 03:59:00'),
	(179, 6, 'avaliacao.realizada', 'Curso 8 — Nota: 100% — Aprovado', '::1', '2026-04-04 03:59:33'),
	(180, 6, 'logout', 'Logout realizado', '::1', '2026-04-04 04:01:32');

-- Copiando dados para a tabela plataforma_ead.materiais: ~6 rows (aproximadamente)
INSERT IGNORE INTO `materiais` (`id`, `curso_id`, `titulo`, `arquivo`, `tipo`, `tamanho`, `criado_em`) VALUES
	(12, 7, 'sadasdsada', '69cc8aec9b1802.46368473.pdf', 'pdf', 201681, '2026-04-01 03:03:08'),
	(13, 7, 'asdsadsa', '69cc8af2bd4966.52236574.pdf', 'pdf', 159374, '2026-04-01 03:03:14'),
	(14, 8, 'asdasda', '69cee4bedc0fc9.80839055.pdf', 'pdf', 150676, '2026-04-02 21:50:54'),
	(15, 8, 'sadasdas', '69cee4c7cba9e5.05343523.pdf', 'pdf', 201681, '2026-04-02 21:51:03'),
	(17, 10, 'aasdsadas', '69cf306cc259d1.92668313.pdf', 'pdf', 554891, '2026-04-03 03:13:48'),
	(18, 11, 'asdasdasda', '69cf34a6b1d728.45803998.pdf', 'pdf', 554891, '2026-04-03 03:31:50');

-- Copiando dados para a tabela plataforma_ead.matriculas: ~4 rows (aproximadamente)
INSERT IGNORE INTO `matriculas` (`id`, `aluno_id`, `curso_id`, `status`, `progresso`, `matriculado_em`, `concluido_em`) VALUES
	(9, 3, 7, 'ativa', 100, '2026-04-01 03:03:28', NULL),
	(11, 3, 8, 'concluida', 100, '2026-04-02 21:54:31', '2026-04-02 22:38:18'),
	(14, 3, 10, 'concluida', 100, '2026-04-03 03:15:21', '2026-04-03 03:17:20'),
	(15, 3, 11, 'concluida', 100, '2026-04-03 03:32:45', '2026-04-03 03:33:24'),
	(16, 6, 8, 'concluida', 100, '2026-04-04 03:58:51', '2026-04-04 03:59:33');

-- Copiando dados para a tabela plataforma_ead.modelos_certificado: ~3 rows (aproximadamente)
INSERT IGNORE INTO `modelos_certificado` (`id`, `curso_id`, `frente`, `verso`, `verso_conteudo`, `texto_frente`, `nome_cert`, `instrutor`, `conteudo_prog`, `ativar_verso`) VALUES
	(4, 8, '69ceea7d52b4d6.20018976.png', '69ceea7d52e373.30155570.png', '', '', 'Capacitação Eventos Pecuários', 'PROFESSOR ANTONIO NETTO / PROFESSOR SILVIO LIMA', '<p><strong>CONTE&Uacute;DO PROGRAM&Aacute;TICO</strong></p>\r\n\r\n<ul>\r\n	<li>VIS&Atilde;O SIST&Ecirc;MICA DO PROCESSO DE CONTRATA&Ccedil;&Atilde;O NA ADMINSTRA&Ccedil;&Atilde;O P&Uacute;BLICA PROCESSO,</li>\r\n	<li>LINHA DO TEMPO DO PROCESSO DE CONTRATA&Ccedil;&Atilde;O E O PLANEJAMENTO DA CONTRATA&Ccedil;&Atilde;O</li>\r\n	<li>CONCEITOS FUNDAMENTAIS PLANEJAMENTO DA CONTRATA&Ccedil;&Atilde;O</li>\r\n	<li>VIS&Atilde;O GERAL CONCEITOS SOBRE INTELIG&Ecirc;NCIA ARTIFICIAL</li>\r\n	<li>PROCESSO INICIAL DA INSTRU&Ccedil;&Atilde;O DA CONTRATA&Ccedil;&Atilde;O ESTUDO T&Eacute;CNICO PRELIMINAR</li>\r\n	<li>GERENCIAMENTO DE RISCOS ELABORA&Ccedil;&Atilde;O DO TERMO DE REFER&Ecirc;NCIA</li>\r\n	<li>CONCEITO DE IMR PREVISTO NA IN no 05/2017 E A CRIA&Ccedil;&Atilde;O DE INDICADORES NO TR PARA ACOMPANHAMENTO DE CONTRATOS ADMINISTRATIVOS A PESQUISA DE PRE&Ccedil;OS NA COMPOSI&Ccedil;&Atilde;O DO TR</li>\r\n</ul>\r\n\r\n<p><strong>INSTRUTORES</strong></p>\r\n\r\n<ul>\r\n	<li>IAN FORMIGA</li>\r\n	<li>IDEMAR FURMIGA</li>\r\n	<li>LAURA REGINA</li>\r\n</ul>', 1),
	(6, 10, NULL, NULL, '', '', 'CURSO PLANEJAMENTO DA CONTRATAÇÃO NA NOVA LEI DE LICITAÇÕES, COM USO DA INTELIGÊNCIA ARTIFICIAL E JOGO DE CONTRATAÇÕES', 'PROFESSOR ANTONIO NETTO / PROFESSOR SILVIO LIMA', 'CONTEÚDO PROGRAMÁTICOVISÃO SISTÊMICA DO PROCESSO DE CONTRATAÇÃO NA ADMINSTRAÇÃO PÚBLICAPROCESSO, LINHA DO TEMPO DO PROCESSO DE CONTRATAÇÃO E O PLANEJAMENTO DA CONTRATAÇÃOCONCEITOS FUNDAMENTAISPLANEJAMENTO DA CONTRATAÇÃO VISÃO GERALCONCEITOS SOBRE INTELIGÊNCIA ARTIFICIALPROCESSO INICIAL DA INSTRUÇÃO DA CONTRATAÇÃOESTUDO TÉCNICO PRELIMINARGERENCIAMENTO DE RISCOSELABORAÇÃO DO TERMO DE REFERÊNCIACONCEITO DE IMR PREVISTO NA IN no 05/2017 E A CRIAÇÃO DE INDICADORES NO TR PARA ACOMPANHAMENTO DECONTRATOSADMINISTRATIVOSA PESQUISA DE PREÇOS NA COMPOSIÇÃO DO TR&nbsp;', 1),
	(7, 11, NULL, NULL, '', '', 'wqeqweqwewqewqeqeqweqweqweqweqweqweqwe', 'PROFESSOR ANTONIO NETTO / PROFESSOR SILVIO LIMA', 'asdasdasdasdasdasdasasdsadsadasdasdasdaasdasdasdasdasdsadasdasdasdsadasdasdasdasdassadsadsadasdasdasdasasdsadsadasdasdsadsasaddasdsaaaaaaaaaaaaaddasdaasssssssssssssssssssdsadassadddddddddd', 1);

-- Copiando dados para a tabela plataforma_ead.perguntas: ~6 rows (aproximadamente)
INSERT IGNORE INTO `perguntas` (`id`, `avaliacao_id`, `enunciado`, `pontos`, `ordem`) VALUES
	(14, 7, 'asdsadasdasdasd', 1.00, 1),
	(15, 7, 'sadsadsadsad', 1.00, 2),
	(17, 8, 'asdasdas', 1.00, 1),
	(18, 8, 'asdasdad', 1.00, 2),
	(20, 10, 'sadsadas', 1.00, 1),
	(21, 11, 'asdsadads', 1.00, 1);

-- Copiando dados para a tabela plataforma_ead.progresso_aulas: ~7 rows (aproximadamente)
INSERT IGNORE INTO `progresso_aulas` (`id`, `aluno_id`, `aula_id`, `assistido`, `assistido_em`) VALUES
	(8, 3, 13, 1, '2026-04-01 03:04:26'),
	(9, 3, 14, 1, '2026-04-01 03:04:35'),
	(13, 3, 15, 1, '2026-04-02 22:38:01'),
	(14, 3, 16, 1, '2026-04-02 22:38:08'),
	(16, 3, 18, 1, '2026-04-03 03:16:11'),
	(17, 3, 19, 1, '2026-04-03 03:17:05'),
	(18, 3, 20, 1, '2026-04-03 03:33:11'),
	(19, 6, 15, 1, '2026-04-04 03:59:18'),
	(20, 6, 16, 1, '2026-04-04 03:59:25');

-- Copiando dados para a tabela plataforma_ead.respostas_aluno: ~6 rows (aproximadamente)
INSERT IGNORE INTO `respostas_aluno` (`id`, `tentativa_id`, `pergunta_id`, `alternativa_id`, `correta`) VALUES
	(8, 4, 14, 54, 0),
	(9, 4, 15, 58, 0),
	(14, 7, 17, 61, 1),
	(15, 7, 18, 65, 1),
	(17, 9, 20, 73, 1),
	(18, 10, 21, 77, 1),
	(19, 11, 17, 61, 1),
	(20, 11, 18, 65, 1);

-- Copiando dados para a tabela plataforma_ead.tentativas_avaliacao: ~4 rows (aproximadamente)
INSERT IGNORE INTO `tentativas_avaliacao` (`id`, `aluno_id`, `avaliacao_id`, `nota`, `aprovado`, `realizado_em`) VALUES
	(4, 3, 7, 0.00, 0, '2026-04-01 03:04:46'),
	(7, 3, 8, 100.00, 1, '2026-04-02 22:38:18'),
	(9, 3, 10, 100.00, 1, '2026-04-03 03:17:20'),
	(10, 3, 11, 100.00, 1, '2026-04-03 03:33:24'),
	(11, 6, 8, 100.00, 1, '2026-04-04 03:59:33');

-- Copiando dados para a tabela plataforma_ead.usuarios: ~4 rows (aproximadamente)
INSERT IGNORE INTO `usuarios` (`id`, `nome`, `email`, `senha`, `cpf`, `telefone`, `perfil`, `status`, `foto`, `criado_em`, `atualizado_em`, `crmv`, `data_nascimento`, `sexo`, `cep`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `estado`, `especialidade`) VALUES
	(1, 'Administrador', 'admin@ead.com', '$2y$10$b1lZf61tXimhxhrSz9zj9eR1hwlyzyuYhk8gKqbKjUQGxa7quRtgq', NULL, NULL, 'admin', 1, NULL, '2026-03-31 00:37:33', '2026-03-31 00:59:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
	(3, 'Laura Regina da Silva Morais', 'lrmorais29@gmail.com', '$2y$12$MR8SxKtGam0g32XU6gqVce9UjifkzLxRD2iQiSrSmqITek3x3263W', '086.794.420-03', '63992863557', 'aluno', 1, NULL, '2026-03-31 19:42:23', '2026-03-31 19:42:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
	(4, 'Ian Leandro Cardoso Formiga', 'formigaian@gmail.com', '$2y$12$oUc8NOjISWi3RBYV17IGbuMMddsjg0Cjm2tJFnhCu.BMg7T1ANSK2', NULL, NULL, 'admin', 1, NULL, '2026-04-02 22:20:57', '2026-04-02 22:20:57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
	(5, 'Idemar Leandro Furmiga', 'iformiga06@gmail.com', '$2y$12$U8k.G25Wi/..WA3g7uE4KexY0dvfRzaLAeLBJT1H2QsyArKpSVUk.', NULL, NULL, 'operador', 1, NULL, '2026-04-02 22:21:22', '2026-04-02 22:21:22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
	(6, 'joao teste testando', 'jonhfilho@gmail.com', '$2y$12$CvUGZ48Og7coq/iaHwD8mejfyBgTsuyvvt6aPEOWAK0fPnp.0.yxi', '044.263.300-73', '(63) 99286-3557', 'aluno', 1, NULL, '2026-04-04 03:58:26', '2026-04-04 03:58:26', '1234568', '1997-12-06', 'M', '77021-668', 'Quadra ARSE 51 Alameda 9', '9', 'Casa', 'Plano Diretor Sul', 'Palmas', 'TO', 'Pets');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
