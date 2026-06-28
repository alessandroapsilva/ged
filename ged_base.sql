-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 19/10/2025 às 21:24
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `ged`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `assinaturas`
--

CREATE TABLE `assinaturas` (
  `id` int(11) NOT NULL,
  `documento_id` int(11) NOT NULL,
  `requisitante_id` int(11) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `email_signatario` varchar(255) NOT NULL,
  `nome_signatario` varchar(255) DEFAULT NULL,
  `status` enum('pendente','assinado','rejeitado') NOT NULL DEFAULT 'pendente',
  `data_requisicao` timestamp NOT NULL DEFAULT current_timestamp(),
  `data_assinatura` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `assinaturas`
--

INSERT INTO `assinaturas` (`id`, `documento_id`, `requisitante_id`, `token`, `email_signatario`, `nome_signatario`, `status`, `data_requisicao`, `data_assinatura`) VALUES
(1, 27, 1, 'bed3313907e6c6d3a33017793caf4c0adf05ca698c2a8cdd2ffcd27f84d69ab2', 'alessandrosilva@enfas.com.br', NULL, 'pendente', '2025-10-14 21:38:57', NULL),
(2, 29, 1, 'fb4d26f3c5b53ad11e2a83a4eae2b7566472e61b0b01b6c36ce01d60fbee44df', 'alessandrosilva@enfas.com.br', NULL, 'pendente', '2025-10-14 21:40:37', NULL),
(3, 28, 1, '9dd043e9cede18f6ca081b0ff59e35a19e7deeeddeb626f16d254ca29ff1e0f5', 'alessandrosilva@enfas.com.br', NULL, 'pendente', '2025-10-14 21:47:53', NULL),
(4, 11, 1, '343e9e7b4301034a9a004aaa4a0a4db1a502e4bde6f0548c0dace2a7255b0b49', 'alessandrosilva@enfas.com.br', NULL, 'pendente', '2025-10-15 00:39:22', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `config_chave` varchar(100) NOT NULL,
  `config_valor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`config_chave`, `config_valor`) VALUES
('API_CHAVE_ACESSO', ''),
('API_ID_PRIMARIO', 'NR_ATENDIMENTO'),
('API_ID_SECUNDARIO', 'NR_PRONTUARIO'),
('API_ROTULO_PRIMARIO', 'Atendimento'),
('API_ROTULO_SECUNDARIO', 'Prontuário'),
('INGEST_PASTA_MONITORADA', 'C:/ged_ingest_entrada');

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos`
--

CREATE TABLE `documentos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `conteudo_ocr` longtext DEFAULT NULL,
  `hash_arquivo` varchar(128) DEFAULT NULL COMMENT 'Checksum (SHA-256 ou XXH128) do arquivo físico',
  `quantidade_paginas` int(11) DEFAULT NULL,
  `data_vencimento` date DEFAULT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `tipo_documento_id` int(11) DEFAULT NULL,
  `pasta_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_upload` datetime NOT NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `apagado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `documentos`
--

INSERT INTO `documentos` (`id`, `titulo`, `descricao`, `conteudo_ocr`, `hash_arquivo`, `quantidade_paginas`, `data_vencimento`, `caminho_arquivo`, `tipo_documento_id`, `pasta_id`, `usuario_id`, `data_upload`, `data_atualizacao`, `apagado_em`) VALUES
(3, '66665', NULL, NULL, '63e27d7bbe463fc42422a9d9a033cf6ac2d31fc1ab19f5dc181aae41ddea2b48', 1, NULL, 'storage/uploads/68e6e966e3e30-EspelhoDePonto_10_2025.pdf', 5, NULL, 1, '2025-10-08 19:44:54', '2025-10-19 15:23:29', NULL),
(5, 'ASO HOSP EINSTEIN - ALESSANDRO', NULL, NULL, '82535acd811948fb53b48672d5e875e7e26e9707d56e073a415a8b8ee7b2df31', 1, NULL, 'storage/uploads/68e71ee3c02d2-ASOHOSPEINSTEIN-ALESSANDRO.pdf', NULL, NULL, 1, '2025-10-08 23:33:08', '2025-10-19 15:23:29', NULL),
(6, 'PROCURAÇÃO ALEXSANDRO APARECIDO', NULL, NULL, '722880f07749279f7698ca52c8eaa765b5c2ea3b8265dcbde72a44be41ad54c6', 1, NULL, 'storage/uploads/68e71ee4226ff-PROCURAOALEXSANDROAPARECIDO.pdf', NULL, NULL, 1, '2025-10-08 23:33:08', '2025-10-19 15:23:29', NULL),
(7, 'RESTRITO', NULL, NULL, 'b31a9f7ae3be73c74ea631fdc2355fd7a2f2cd7a76b43ebee0cc6b943681a272', 1, NULL, 'storage/uploads/68e7ff1885e31-Alessandro aparecido da silva domingues.pdf', 12, NULL, 1, '2025-10-09 15:29:44', '2025-10-19 15:23:29', NULL),
(8, '2222', NULL, NULL, '00eb55fad45df4b94102ae9c450f8beda06dd8467074854a70d86936e5ca6c48', 27, NULL, 'storage/uploads/68eabe948b37c-SP_demo1 edok codigo barra.pdf', 12, NULL, 1, '2025-10-11 17:31:16', '2025-10-19 15:23:29', NULL),
(10, ' - Página 1', NULL, NULL, 'f2995cef1b864d5941a63e219beb1a3914141835ad693d4de46eceda1682880a', 1, NULL, 'storage/uploads/68ead448eeed5-F.HABF.059 - Checklist - Carro de parada_pagina_1_1760226943_782.pdf', 8, NULL, 1, '2025-10-11 20:55:43', '2025-10-19 15:23:29', NULL),
(11, ' - Página 2', NULL, NULL, '5cd94115acddd0fc42b927e2575be60e4e68328f0aa04a2db0e2c8e3a2a09bd1', 1, NULL, 'storage/uploads/68ead448eeed5-F.HABF.059 - Checklist - Carro de parada_pagina_2_1760226943_834.pdf', 8, NULL, 1, '2025-10-11 20:55:44', '2025-10-19 15:23:29', NULL),
(12, ' - Página 3', NULL, NULL, 'eafea3160791c7bfde0493dc9ca31625ee5ce728ae3c63eb1f86120a669da731', 1, NULL, 'storage/uploads/68ead448eeed5-F.HABF.059 - Checklist - Carro de parada_pagina_3_1760226944_511.pdf', 8, NULL, 1, '2025-10-11 20:55:45', '2025-10-19 15:23:29', NULL),
(13, 'SEP-68EAF0487F4A0', 'Documento criado a partir da página 1 do documento original \'\' (ID: 8).', NULL, '337e9ee78197c9155d0694e2dc2ea099a964d4154278171e844fad5554be7078', 1, NULL, 'storage/uploads/sep-68eaf0487f4a0_1760227400.pdf', 12, NULL, 1, '2025-10-11 21:03:21', '2025-10-19 15:23:29', NULL),
(14, 'SEP-68EAF0491B9A5', 'Documento criado a partir da página 2 do documento original \'\' (ID: 8).', NULL, 'e22c1352386deb5d6d3910b632854057475a05bed069f7664f42140e8c5c0d73', 1, NULL, 'storage/uploads/sep-68eaf0491b9a5_1760227401.pdf', 12, NULL, 1, '2025-10-11 21:03:22', '2025-10-19 15:23:29', NULL),
(15, 'SEP-68EAF6ECBE834', 'Documento criado a partir da página 1 do documento original \'RESTRITO\' (ID: 7).', NULL, '5204b39f73a78a9008be128d1afe84c58e8faa75b97838b3b25b5ab12dcefabc', 1, NULL, 'storage/uploads/sep-68eaf6ecbe834_1760229100.pdf', 12, NULL, 1, '2025-10-11 21:31:42', '2025-10-19 15:23:29', NULL),
(16, 'SEP-68EAF6EE8E1C8', 'Documento criado a partir da página 2 do documento original \'RESTRITO\' (ID: 7).', NULL, 'dc6dda20d1b0b383eb6e7f47d1e4887c31555a203fc4635a9b8e9c88205825b1', 1, NULL, 'storage/uploads/sep-68eaf6ee8e1c8_1760229102.pdf', 12, NULL, 1, '2025-10-11 21:31:43', '2025-10-19 15:23:29', NULL),
(17, 'SEP-68EAF7189DEB9', 'Documento criado a partir da página 1 do documento original \'PROCURAÇÃO ALEXSANDRO APARECIDO\' (ID: 6).', NULL, 'a395dabe24024559f89d39aefd7a5d33394afb43c6acd4e788b3120a23ea1316', 1, NULL, 'storage/uploads/sep-68eaf7189deb9_1760229144.pdf', NULL, NULL, 1, '2025-10-11 21:32:25', '2025-10-19 15:23:29', NULL),
(18, 'SEP-68EAF7192B633', 'Documento criado a partir da página 2 do documento original \'PROCURAÇÃO ALEXSANDRO APARECIDO\' (ID: 6).', NULL, '34f91ca63fac4de87e8bde36b8ac695c6fb4c2a19adc266362ff1fd391f933aa', 1, NULL, 'storage/uploads/sep-68eaf7192b633_1760229145.pdf', NULL, NULL, 1, '2025-10-11 21:32:25', '2025-10-19 15:23:29', NULL),
(19, 'SEP-68EAF8E330816', 'Documento criado a partir da página 1 do documento original \'PROCURAÇÃO ALEXSANDRO APARECIDO\' (ID: 6).', NULL, '25d6b16fb0c29431949313c3a2850f4367f214d73b57dcb8b6f92cc0a13721a3', 1, NULL, 'storage/uploads/sep-68eaf8e330816_1760229603.pdf', 10, NULL, 1, '2025-10-11 21:40:04', '2025-10-19 15:23:29', NULL),
(20, 'SEP-68EAF8E42A95C', 'Documento criado a partir da página 2 do documento original \'PROCURAÇÃO ALEXSANDRO APARECIDO\' (ID: 6).', NULL, '790f2cd67e7db2be24c0e449225c9078250854da213f46df12535fa91179fda5', 1, NULL, 'storage/uploads/sep-68eaf8e42a95c_1760229604.pdf', NULL, NULL, 1, '2025-10-11 21:40:04', '2025-10-19 15:23:29', NULL),
(21, 'SEP-68EB0B13326EC', 'Documento criado a partir da página 1 do documento original \'ASO HOSP EINSTEIN - ALESSANDRO\' (ID: 5).', NULL, 'd4fb484c10015d4a9950f55b89fafde47ad397811b5964fc1dac7cb9d07a8852', 1, NULL, 'storage/uploads/sep-68eb0b13326ec_1760234259.pdf', NULL, NULL, 1, '2025-10-11 22:57:42', '2025-10-19 15:23:29', NULL),
(22, 'SEP-68EB0B165169B', 'Documento criado a partir da página 2 do documento original \'ASO HOSP EINSTEIN - ALESSANDRO\' (ID: 5).', NULL, 'da19e394fd3a29a09f023c17174414616a3fd1b7e311e4d0c8677b7badad0059', 1, NULL, 'storage/uploads/sep-68eb0b165169b_1760234262.pdf', NULL, NULL, 1, '2025-10-11 22:57:42', '2025-10-19 15:23:29', NULL),
(23, '52', '', NULL, '3ff20122e4fbd928803e7d6ace3b55e3b549cb91ae6ce00d0ca83d30686bfc8c', 5, NULL, 'storage/uploads/doc_68ec19aa4ad187.73964158.pdf', 10, NULL, 1, '2025-10-12 18:12:10', '2025-10-19 15:23:29', NULL),
(24, '52', '', NULL, '3ff20122e4fbd928803e7d6ace3b55e3b549cb91ae6ce00d0ca83d30686bfc8c', 5, NULL, 'storage/uploads/doc_68ec1ace0101d3.78354102.pdf', 10, NULL, 1, '2025-10-12 18:17:02', '2025-10-19 15:23:29', NULL),
(25, 'teste', '', NULL, 'ef3da0eaf2a47999ea42aea7b21609689e87e7ad8c598a82a7e09d5d6e0e5fc2', 1, NULL, 'storage/uploads/doc_68ec1b20d00fd8.97918121.pdf', 12, NULL, 1, '2025-10-12 18:18:24', '2025-10-19 15:23:29', NULL),
(26, 'teste', '', NULL, 'ef3da0eaf2a47999ea42aea7b21609689e87e7ad8c598a82a7e09d5d6e0e5fc2', 1, NULL, 'storage/uploads/doc_68ec1b53a36528.85312031.pdf', 12, NULL, 1, '2025-10-12 18:19:15', '2025-10-19 15:23:29', NULL),
(27, '2555555', '', NULL, 'ea3a312cec1917483981f3ea5d2cade6ab8ee8964c9f980f74ca5b65b94e9ca3', 1, NULL, 'storage/uploads/doc_68ec1b9bace6f9.12766319.pdf', 9, NULL, 1, '2025-10-12 18:20:27', '2025-10-19 15:23:29', NULL),
(28, '2555555', '', NULL, 'ea3a312cec1917483981f3ea5d2cade6ab8ee8964c9f980f74ca5b65b94e9ca3', 1, NULL, 'storage/uploads/doc_68ec1c3016a742.55721288.pdf', 9, NULL, 1, '2025-10-12 18:22:56', '2025-10-19 15:23:29', NULL),
(29, '2555555', '', NULL, '722880f07749279f7698ca52c8eaa765b5c2ea3b8265dcbde72a44be41ad54c6', 1, NULL, 'storage/uploads/doc_68ec1c55d84200.49348643.pdf', 9, NULL, 1, '2025-10-12 18:23:33', '2025-10-19 15:23:29', NULL),
(30, 'SEP-68EEB7F2B160A', 'Documento criado a partir da página 1 do documento original \' - Página 3\' (ID: 12).', NULL, '972bd1c742bea8eb1bd8440712bc7bb935e7132188bb999de98be1db89c5c5e1', 1, NULL, 'storage/uploads/sep-68eeb7f2b160a_1760475122.pdf', 8, NULL, 1, '2025-10-14 17:52:06', '2025-10-19 15:23:29', NULL),
(31, 'SEP-68EEB7F6E389C', 'Documento criado a partir da página 2 do documento original \' - Página 3\' (ID: 12).', NULL, '59e92190265d3ec67261f732ad8243bec88ae41ec43938b6a3b7ac6c509a1720', 1, NULL, 'storage/uploads/sep-68eeb7f6e389c_1760475126.pdf', 8, NULL, 1, '2025-10-14 17:52:07', '2025-10-19 15:23:29', NULL),
(32, 'Documento Digitalizado 15-10-2025 18:35:00', NULL, '', '31629e9b59f86967493ece9e0836167f0b4803d7bad3a3a85e01189192b061a9', 1, NULL, 'storage/uploads/scan_68f013849ce3f0.25435830.pdf', NULL, NULL, 1, '2025-10-15 18:35:00', '2025-10-19 15:23:29', NULL),
(33, '221414', '', NULL, 'c3fb548f7ecf0d8a47beccf03ee9bd6d3344c42b3cab73dc2f8bf7dda4855a28', 1, NULL, 'storage/uploads/doc_68f43b49c74918.07811074.pdf', 3, NULL, 1, '2025-10-18 22:13:45', '2025-10-19 15:23:29', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos_antigo`
--

CREATE TABLE `documentos_antigo` (
  `id` int(11) NOT NULL,
  `titulo_original` varchar(255) NOT NULL,
  `id_usuario_criador` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `apagado_em` datetime DEFAULT NULL,
  `versao_atual` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `documentos_antigo`
--

INSERT INTO `documentos_antigo` (`id`, `titulo_original`, `id_usuario_criador`, `data_criacao`, `apagado_em`, `versao_atual`) VALUES
(3, '66665', 1, '2025-10-08 22:44:54', NULL, 1),
(5, 'ASO HOSP EINSTEIN - ALESSANDRO', 1, '2025-10-09 02:33:08', NULL, 1),
(6, 'PROCURAÇÃO ALEXSANDRO APARECIDO', 1, '2025-10-09 02:33:08', NULL, 1),
(7, 'RESTRITO', 1, '2025-10-09 18:29:44', NULL, 1),
(8, '2222', 1, '2025-10-11 20:31:16', '2025-10-11 21:03:22', 1),
(9, '++.66666666', 1, '2025-10-11 22:03:53', '2025-10-11 20:55:45', 1),
(10, ' - Página 1', 0, '2025-10-11 23:55:43', '2025-10-11 21:03:04', 1),
(11, ' - Página 2', 0, '2025-10-11 23:55:44', '2025-10-11 21:03:04', 1),
(12, ' - Página 3', 0, '2025-10-11 23:55:45', '2025-10-11 21:03:04', 1),
(13, 'SEP-68EAF0487F4A0', 0, '2025-10-12 00:03:20', '2025-10-11 21:30:36', 1),
(14, 'SEP-68EAF0491B9A5', 0, '2025-10-12 00:03:22', '2025-10-11 21:30:36', 1),
(15, 'SEP-68EAF6ECBE834', 0, '2025-10-12 00:31:42', '2025-10-11 21:39:49', 1),
(16, 'SEP-68EAF6EE8E1C8', 0, '2025-10-12 00:31:43', '2025-10-11 21:39:49', 1),
(17, 'SEP-68EAF7189DEB9', 0, '2025-10-12 00:32:25', '2025-10-11 21:39:49', 1),
(18, 'SEP-68EAF7192B633', 0, '2025-10-12 00:32:25', '2025-10-11 21:39:49', 1),
(19, 'SEP-68EAF8E330816', 0, '2025-10-12 00:40:04', NULL, 1),
(20, 'SEP-68EAF8E42A95C', 0, '2025-10-12 00:40:04', NULL, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `documento_metadados`
--

CREATE TABLE `documento_metadados` (
  `id` int(11) NOT NULL,
  `documento_id` int(11) NOT NULL,
  `campo_id` int(11) NOT NULL,
  `valor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `documento_versoes_antigo`
--

CREATE TABLE `documento_versoes_antigo` (
  `id` int(11) NOT NULL,
  `documento_id` int(11) NOT NULL COMMENT 'ID do documento pai',
  `versao` int(11) NOT NULL DEFAULT 1 COMMENT 'Número da versão (1, 2, 3...)',
  `status` varchar(50) DEFAULT 'admitido' COMMENT 'Status do processamento do Ingest',
  `data_processamento` datetime DEFAULT NULL COMMENT 'Data em que o Ingest processou o arquivo',
  `tamanho_arquivo` bigint(20) DEFAULT NULL COMMENT 'Tamanho do arquivo em bytes',
  `titulo` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `caminho_arquivo` varchar(512) NOT NULL,
  `tipo_documento_id` int(11) DEFAULT NULL,
  `pasta_id` int(11) DEFAULT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL,
  `apagado_em` timestamp NULL DEFAULT NULL,
  `nome_arquivo_original` varchar(255) NOT NULL,
  `nome_arquivo_sistema` varchar(255) NOT NULL,
  `hash_sha256` varchar(64) DEFAULT NULL COMMENT 'Hash SHA-256 do arquivo físico (Verificador de Arquivamento)',
  `page_count` int(11) DEFAULT NULL COMMENT 'Quantidade de páginas do documento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `documento_versoes_antigo`
--

INSERT INTO `documento_versoes_antigo` (`id`, `documento_id`, `versao`, `status`, `data_processamento`, `tamanho_arquivo`, `titulo`, `descricao`, `caminho_arquivo`, `tipo_documento_id`, `pasta_id`, `data_upload`, `usuario_id`, `apagado_em`, `nome_arquivo_original`, `nome_arquivo_sistema`, `hash_sha256`, `page_count`) VALUES
(7, 0, 1, 'admitido', '2025-10-03 17:08:41', NULL, 'Documento Combinado 03-10-2025 22:08', NULL, 'uploads/combinado_1759522121.pdf', 3, NULL, '2025-10-03 20:08:41', 1, NULL, '', '', NULL, NULL),
(8, 0, 1, 'admitido', '2025-10-03 21:05:05', NULL, 'Documento Combinado 03-10-2025 22:17', '', 'uploads/doc_68e064b165a0c_1759536305.pdf', 3, NULL, '2025-10-04 00:05:05', 1, NULL, '', '', NULL, NULL),
(9, 0, 1, 'admitido', '2025-10-04 18:11:42', 232347, 'YESTSTS', NULL, '../uploads/doc_68e18d8dde99d8.67867924.pdf', NULL, NULL, '2025-10-04 21:11:42', 1, NULL, 'LAUDO CARACTERIZADOR ALBERT EINSTEIN - ALESSANDRO.pdf', 'doc_68e18d8dde99d8.67867924.pdf', NULL, NULL),
(10, 0, 1, 'admitido', '2025-10-04 18:12:27', 232347, 'TTETSSSGGSGS', NULL, '../uploads/doc_68e18dbb81be22.37507683.pdf', NULL, NULL, '2025-10-04 21:12:27', 1, NULL, 'LAUDO CARACTERIZADOR ALBERT EINSTEIN - ALESSANDRO.pdf', 'doc_68e18dbb81be22.37507683.pdf', NULL, NULL),
(11, 0, 1, 'admitido', '2025-10-04 18:15:31', 108075, 'Relatório Mensal', NULL, '../uploads/doc_68e18e73308ee4.28430731.pdf', NULL, NULL, '2025-10-04 21:15:31', 1, NULL, 'CPF ALESSANDRO.pdf', 'doc_68e18e73308ee4.28430731.pdf', NULL, NULL),
(12, 0, 1, 'admitido', '2025-10-04 18:35:19', 232347, '000000000000', NULL, '../uploads/doc_68e19317bd7ac2.60946842.pdf', NULL, NULL, '2025-10-04 21:35:19', 1, NULL, 'LAUDO CARACTERIZADOR ALBERT EINSTEIN - ALESSANDRO.pdf', 'doc_68e19317bd7ac2.60946842.pdf', NULL, NULL),
(13, 0, 1, 'admitido', '2025-10-04 18:44:48', 232347, 'TESTE TESTE ', NULL, '../uploads/doc_68e195505db512.91974848.pdf', NULL, NULL, '2025-10-04 21:44:48', 1, NULL, 'LAUDO CARACTERIZADOR ALBERT EINSTEIN - ALESSANDRO.pdf', 'doc_68e195505db512.91974848.pdf', NULL, NULL),
(17, 0, 2, 'admitido', '2025-10-04 18:48:34', 71755, '000000000000', NULL, '../uploads/doc_68e196324fa6b5.97395123.pdf', NULL, NULL, '2025-10-04 21:48:34', 1, NULL, 'documento-digitalizado0110.pdf', 'doc_68e196324fa6b5.97395123.pdf', NULL, NULL),
(18, 0, 1, 'admitido', '2025-10-04 19:13:46', 163670, 'teste reste', NULL, '../uploads/doc_68e19c1ada2734.35973459.pdf', 9, NULL, '2025-10-04 22:13:46', 1, NULL, 'ASO HOSP EINSTEIN - ALESSANDRO.pdf', 'doc_68e19c1ada2734.35973459.pdf', NULL, NULL),
(19, 1, 1, 'admitido', '2025-10-04 21:16:31', 157048, '', NULL, '../uploads/doc_v1_68e1b8dfb44ca4.95270826.pdf', 8, NULL, '2025-10-05 00:16:31', 1, NULL, 'anti hbs einstein 01-10 ALESSANDRO.pdf', 'doc_v1_68e1b8dfb44ca4.95270826.pdf', NULL, NULL),
(20, 1, 2, 'assinado', NULL, 174443, '', NULL, 'uploads/doc_v2_68e1d593b68b3_1.pdf', 8, NULL, '2025-10-05 02:19:02', 1, NULL, 'anti hbs einstein 01-10 ALESSANDRO.pdf', 'doc_v2_68e1d593b68b3_1.pdf', NULL, NULL),
(21, 2, 1, 'admitido', '2025-10-05 15:18:20', 157048, 'teste', NULL, '../uploads/doc_v1_68e2b66ccb40f1.66631155.pdf', 9, NULL, '2025-10-05 18:18:20', 1, NULL, 'anti hbs einstein 01-10 ALESSANDRO (1).pdf', 'doc_v1_68e2b66ccb40f1.66631155.pdf', NULL, NULL),
(22, 2, 2, 'assinado', NULL, 174443, 'teste', NULL, 'uploads/doc_v2_68e2b7f84c8cf_2.pdf', 9, NULL, '2025-10-05 18:25:01', 1, NULL, 'anti hbs einstein 01-10 ALESSANDRO (1).pdf', 'doc_v2_68e2b7f84c8cf_2.pdf', NULL, NULL),
(23, 2, 3, 'assinado', NULL, 175803, 'teste', NULL, 'uploads/doc_v3_68e2e7c08b944_2.pdf', 9, NULL, '2025-10-05 21:48:51', 1, NULL, 'anti hbs einstein 01-10 ALESSANDRO (1).pdf', 'doc_v3_68e2e7c08b944_2.pdf', NULL, NULL),
(24, 2, 4, 'assinado', NULL, 177163, 'teste', NULL, 'uploads/doc_v4_68e341c0217a0_2.pdf', 9, NULL, '2025-10-06 04:12:50', 1, NULL, 'anti hbs einstein 01-10 ALESSANDRO (1).pdf', 'doc_v4_68e341c0217a0_2.pdf', NULL, NULL),
(25, 3, 1, 'admitido', NULL, NULL, '', NULL, 'storage/uploads/68e6e966e3e30-EspelhoDePonto_10_2025.pdf', 5, NULL, '2025-10-08 22:44:54', 1, NULL, 'EspelhoDePonto_10_2025.pdf', '68e6e966e3e30-EspelhoDePonto_10_2025.pdf', NULL, NULL),
(26, 4, 1, 'admitido', NULL, NULL, '', NULL, 'storage/uploads/68e6ea6146615-EspelhoDePonto_10_2025.pdf', 5, NULL, '2025-10-08 22:49:05', 1, NULL, 'EspelhoDePonto_10_2025.pdf', '68e6ea6146615-EspelhoDePonto_10_2025.pdf', NULL, NULL),
(27, 5, 1, 'admitido', NULL, NULL, '', NULL, 'storage/uploads/68e71ee3c02d2-ASOHOSPEINSTEIN-ALESSANDRO.pdf', NULL, NULL, '2025-10-09 02:33:08', 1, NULL, 'ASO HOSP EINSTEIN - ALESSANDRO.pdf', '68e71ee3c02d2-ASOHOSPEINSTEIN-ALESSANDRO.pdf', '82535acd811948fb53b48672d5e875e7e26e9707d56e073a415a8b8ee7b2df31', NULL),
(28, 6, 1, 'admitido', NULL, NULL, '', NULL, 'storage/uploads/68e71ee4226ff-PROCURAOALEXSANDROAPARECIDO.pdf', NULL, NULL, '2025-10-09 02:33:08', 1, NULL, 'PROCURAÇÃO ALEXSANDRO APARECIDO.pdf', '68e71ee4226ff-PROCURAOALEXSANDROAPARECIDO.pdf', '722880f07749279f7698ca52c8eaa765b5c2ea3b8265dcbde72a44be41ad54c6', NULL),
(29, 7, 1, 'admitido', NULL, NULL, '', NULL, 'storage/uploads/68e7ff1885e31-Alessandro aparecido da silva domingues.pdf', 12, NULL, '2025-10-09 18:29:44', 1, NULL, 'Alessandro aparecido da silva domingues.pdf', '68e7ff1885e31-Alessandro aparecido da silva domingues.pdf', NULL, NULL),
(30, 8, 1, 'admitido', NULL, NULL, '333333333', NULL, 'storage/uploads/68eabe948b37c-SP_demo1 edok codigo barra.pdf', 12, NULL, '2025-10-11 20:31:16', 1, NULL, 'SP_demo1 edok codigo barra.pdf', '68eabe948b37c-SP_demo1 edok codigo barra.pdf', NULL, NULL),
(31, 9, 1, 'admitido', NULL, NULL, '', NULL, 'storage/uploads/68ead448eeed5-F.HABF.059 - Checklist - Carro de parada.pdf', 8, NULL, '2025-10-11 22:03:53', 1, NULL, 'F.HABF.059 - Checklist - Carro de parada.pdf', '68ead448eeed5-F.HABF.059 - Checklist - Carro de parada.pdf', NULL, NULL),
(32, 10, 1, 'admitido', NULL, NULL, ' - Página 1', NULL, 'storage/uploads/68ead448eeed5-F.HABF.059 - Checklist - Carro de parada_pagina_1_1760226943_782.pdf', 8, NULL, '2025-10-11 23:55:43', 1, NULL, '', '', NULL, NULL),
(33, 11, 1, 'admitido', NULL, NULL, ' - Página 2', NULL, 'storage/uploads/68ead448eeed5-F.HABF.059 - Checklist - Carro de parada_pagina_2_1760226943_834.pdf', 8, NULL, '2025-10-11 23:55:44', 1, NULL, '', '', NULL, NULL),
(34, 12, 1, 'admitido', NULL, NULL, ' - Página 3', NULL, 'storage/uploads/68ead448eeed5-F.HABF.059 - Checklist - Carro de parada_pagina_3_1760226944_511.pdf', 8, NULL, '2025-10-11 23:55:45', 1, NULL, '', '', NULL, NULL),
(35, 13, 1, 'admitido', NULL, NULL, 'SEP-68EAF0487F4A0', 'Documento criado a partir da página 1 do documento original \'\' (ID: 8).', 'storage/uploads/sep-68eaf0487f4a0_1760227400.pdf', 12, NULL, '2025-10-12 00:03:21', 1, NULL, '', '', NULL, NULL),
(36, 14, 1, 'admitido', NULL, NULL, 'SEP-68EAF0491B9A5', 'Documento criado a partir da página 2 do documento original \'\' (ID: 8).', 'storage/uploads/sep-68eaf0491b9a5_1760227401.pdf', 12, NULL, '2025-10-12 00:03:22', 1, NULL, '', '', NULL, NULL),
(37, 15, 1, 'admitido', NULL, NULL, 'SEP-68EAF6ECBE834', 'Documento criado a partir da página 1 do documento original \'RESTRITO\' (ID: 7).', 'storage/uploads/sep-68eaf6ecbe834_1760229100.pdf', 12, NULL, '2025-10-12 00:31:42', 1, NULL, '', '', NULL, NULL),
(38, 16, 1, 'admitido', NULL, NULL, 'SEP-68EAF6EE8E1C8', 'Documento criado a partir da página 2 do documento original \'RESTRITO\' (ID: 7).', 'storage/uploads/sep-68eaf6ee8e1c8_1760229102.pdf', 12, NULL, '2025-10-12 00:31:43', 1, NULL, '', '', NULL, NULL),
(39, 17, 1, 'admitido', NULL, NULL, 'SEP-68EAF7189DEB9', 'Documento criado a partir da página 1 do documento original \'PROCURAÇÃO ALEXSANDRO APARECIDO\' (ID: 6).', 'storage/uploads/sep-68eaf7189deb9_1760229144.pdf', NULL, NULL, '2025-10-12 00:32:25', 1, NULL, '', '', NULL, NULL),
(40, 18, 1, 'admitido', NULL, NULL, 'SEP-68EAF7192B633', 'Documento criado a partir da página 2 do documento original \'PROCURAÇÃO ALEXSANDRO APARECIDO\' (ID: 6).', 'storage/uploads/sep-68eaf7192b633_1760229145.pdf', NULL, NULL, '2025-10-12 00:32:25', 1, NULL, '', '', NULL, NULL),
(41, 19, 1, 'admitido', NULL, NULL, 'SEP-68EAF8E330816', 'Documento criado a partir da página 1 do documento original \'PROCURAÇÃO ALEXSANDRO APARECIDO\' (ID: 6).', 'storage/uploads/sep-68eaf8e330816_1760229603.pdf', NULL, NULL, '2025-10-12 00:40:04', 1, NULL, '', '', NULL, NULL),
(42, 20, 1, 'admitido', NULL, NULL, 'SEP-68EAF8E42A95C', 'Documento criado a partir da página 2 do documento original \'PROCURAÇÃO ALEXSANDRO APARECIDO\' (ID: 6).', 'storage/uploads/sep-68eaf8e42a95c_1760229604.pdf', NULL, NULL, '2025-10-12 00:40:04', 1, NULL, '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcao_permissao`
--

CREATE TABLE `funcao_permissao` (
  `funcao_id` int(11) NOT NULL,
  `permissao_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `funcao_permissao`
--

INSERT INTO `funcao_permissao` (`funcao_id`, `permissao_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(2, 10),
(2, 11);

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcoes`
--

CREATE TABLE `funcoes` (
  `id` int(11) NOT NULL,
  `nome_funcao` varchar(100) NOT NULL,
  `chave` varchar(50) DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `nivel` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `funcoes`
--

INSERT INTO `funcoes` (`id`, `nome_funcao`, `chave`, `descricao`, `nivel`) VALUES
(1, 'Administrador', 'admin', 'Administradores do sistema', 10),
(2, 'Usuário', 'user', 'Usuário padrão com permissões limitadas', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(255) NOT NULL,
  `categoria` varchar(50) NOT NULL DEFAULT 'Atividade' COMMENT 'Ex: Atividade, Sistema',
  `documento_id` int(11) DEFAULT NULL,
  `pasta_id` int(11) DEFAULT NULL,
  `data_ocorrencia` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `logs`
--

INSERT INTO `logs` (`id`, `usuario_id`, `acao`, `categoria`, `documento_id`, `pasta_id`, `data_ocorrencia`) VALUES
(1, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-02 20:39:37'),
(2, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-02 20:44:18'),
(3, 1, 'Atualizou o usuário \'Admin Sistema\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-02 20:44:30'),
(4, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-02 20:44:46'),
(5, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-02 21:33:00'),
(6, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-02 21:33:24'),
(7, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-02 21:33:28'),
(8, 1, 'Atualizou o usuário \'Admin Sistema\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-02 21:39:57'),
(9, 1, 'Atualizou o usuário \'Admin Sistema\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-02 21:40:22'),
(10, 1, 'Atualizou a função \'Usuário\' (ID: 2).', 'Atividade', NULL, NULL, '2025-10-02 21:48:28'),
(11, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-02 21:48:34'),
(12, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-02 21:48:53'),
(13, 1, 'Fez upload do documento \'teste\' (ID: 4).', 'Atividade', NULL, NULL, '2025-10-02 22:20:35'),
(14, 1, 'Fez upload do documento \'00000\' (ID: 5).', 'Atividade', NULL, NULL, '2025-10-02 22:21:04'),
(15, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-02 22:41:14'),
(16, 1, 'Moveu o documento \'00000\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-03 18:26:50'),
(17, 1, 'Restaurou o documento \'00000\' da lixeira.', 'Atividade', NULL, NULL, '2025-10-03 18:33:52'),
(18, 1, 'Moveu a pasta \'CONTRATO\' (e seu conteúdo) para a lixeira.', 'Atividade', NULL, NULL, '2025-10-03 18:36:10'),
(19, 1, 'Excluiu permanentemente a pasta \'CONTRATO\' e todo o seu conteúdo.', 'Atividade', NULL, NULL, '2025-10-03 18:36:23'),
(20, 1, 'Combinou 2 documentos para criar \'Documento Combinado 03-10-2025 21:42\'.', 'Atividade', NULL, NULL, '2025-10-03 19:42:18'),
(21, 1, 'Moveu o documento \'Documento Combinado 03-10-2025 21:42\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-03 19:57:39'),
(22, 1, 'Excluiu permanentemente o documento \'Documento Combinado 03-10-2025 21:42\'.', 'Atividade', NULL, NULL, '2025-10-03 19:57:45'),
(23, 1, 'Combinou 2 documentos para criar \'Documento Combinado 03-10-2025 22:08\'.', 'Atividade', NULL, NULL, '2025-10-03 20:08:41'),
(24, 1, 'Combinou 2 docs para criar \'Documento Combinado 03-10-2025 22:17\' e moveu os originais para a lixeira.', 'Atividade', NULL, NULL, '2025-10-03 20:17:00'),
(25, 1, 'Excluiu permanentemente o documento \'teste\'.', 'Atividade', NULL, NULL, '2025-10-03 20:17:30'),
(26, 1, 'Excluiu permanentemente o documento \'00000\'.', 'Atividade', NULL, NULL, '2025-10-03 20:17:32'),
(27, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-03 21:08:13'),
(28, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-03 21:12:48'),
(29, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-03 21:39:28'),
(30, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-03 21:40:58'),
(31, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-03 21:42:53'),
(32, 1, 'Atualizou o usuário \'Admin Sistema\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-03 22:34:35'),
(33, 1, 'Atualizou a função \'Administrador\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-03 22:43:20'),
(34, 1, 'Editou os metadados do documento \'Documento Combinado 03-10-2025 22:17\' (ID: 8).', 'Atividade', NULL, NULL, '2025-10-04 00:01:34'),
(35, 1, 'Editou os metadados do documento \'Documento Combinado 03-10-2025 22:17\' (ID: 8).', 'Atividade', NULL, NULL, '2025-10-04 00:02:07'),
(36, 1, 'Criou a versão 2 do documento \'Documento Combinado 03-10-2025 22:17\' (ID: 8). Motivo: yest', 'Atividade', NULL, NULL, '2025-10-04 00:05:05'),
(37, 1, 'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.', 'Atividade', NULL, NULL, '2025-10-04 01:07:42'),
(38, 1, 'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.', 'Atividade', NULL, NULL, '2025-10-04 01:08:06'),
(39, 1, 'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.', 'Atividade', NULL, NULL, '2025-10-04 01:13:30'),
(40, 1, 'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.', 'Atividade', NULL, NULL, '2025-10-04 01:15:29'),
(41, 1, 'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.', 'Atividade', NULL, NULL, '2025-10-04 01:27:12'),
(42, 1, 'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.', 'Atividade', NULL, NULL, '2025-10-04 01:32:42'),
(43, 1, 'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.', 'Atividade', NULL, NULL, '2025-10-04 02:00:43'),
(44, NULL, 'Atualizou o usuário \'Admin Sistema\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-05 00:10:26'),
(45, 1, 'Apagou o usuário \'Administrador do Sistema\'.', 'Atividade', NULL, NULL, '2025-10-05 00:29:32'),
(46, 1, 'Assinou digitalmente o documento \'\', gerando a versão 2.', 'Atividade', NULL, NULL, '2025-10-05 02:19:02'),
(47, 1, 'Moveu o documento ID 20 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-05 17:22:38'),
(48, 1, 'Moveu o documento ID 20 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-05 17:25:12'),
(49, 1, 'Moveu o documento ID 20 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-05 17:26:32'),
(50, 1, 'Moveu o documento ID 20 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-05 17:38:37'),
(51, 1, 'Moveu o documento ID 1 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-05 17:50:11'),
(52, 1, 'Moveu o documento ID 1 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-05 17:50:15'),
(53, 1, 'Moveu o documento ID 1 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-05 18:14:27'),
(54, 1, 'Moveu o documento ID 1 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-05 18:17:44'),
(55, 1, 'Excluiu permanentemente o documento \'ID 1\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-05 18:17:51'),
(56, 1, 'Assinou digitalmente o documento \'teste\', gerando a versão 2.', 'Atividade', NULL, NULL, '2025-10-05 18:25:02'),
(57, 1, 'Moveu a pasta ID 3 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-05 21:47:02'),
(58, 1, 'Excluiu permanentemente a pasta \'5\' (ID: 3).', 'Atividade', NULL, NULL, '2025-10-05 21:47:16'),
(59, 1, 'Assinou digitalmente o documento \'teste\', gerando a versão 3.', 'Atividade', NULL, NULL, '2025-10-05 21:48:51'),
(60, 1, 'Assinou digitalmente o documento \'teste\', gerando a versão 4.', 'Atividade', NULL, NULL, '2025-10-06 04:12:50'),
(61, 1, 'Criou a função \'22222\' (ID: 3).', 'Atividade', NULL, NULL, '2025-10-06 22:35:58'),
(62, 1, 'Atualizou o usuário \'Admin Sistema\' (ID: 1).', 'Atividade', NULL, NULL, '2025-10-06 22:51:34'),
(63, 1, 'Moveu o documento ID 2 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-08 01:15:34'),
(64, 1, 'Moveu a pasta ID 4 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-08 17:55:56'),
(65, 1, 'Excluiu permanentemente a pasta \'014\' (ID: 4).', 'Atividade', NULL, NULL, '2025-10-08 17:56:02'),
(66, 1, 'Moveu a pasta ID 5 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-08 20:24:09'),
(67, 1, 'Excluiu permanentemente a pasta \'99\' (ID: 5).', 'Atividade', NULL, NULL, '2025-10-08 20:24:15'),
(68, 1, 'Moveu o documento ID 4 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-08 23:31:02'),
(69, 1, 'Moveu o documento ID 2 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-08 23:31:02'),
(70, 1, 'Excluiu permanentemente o documento \'teste\' (ID: 2).', 'Atividade', NULL, NULL, '2025-10-08 23:31:09'),
(71, 1, 'Excluiu permanentemente o documento \'.666666\' (ID: 4).', 'Atividade', NULL, NULL, '2025-10-08 23:31:11'),
(72, 1, 'Atualizou as configurações do sistema.', 'Atividade', NULL, NULL, '2025-10-09 01:42:23'),
(73, 1, 'Moveu o documento ID 5 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-09 17:28:39'),
(74, 1, 'Moveu o documento ID 6 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-09 17:28:39'),
(75, 1, 'Criou o usuário \'TESTE RESTRITO\' (ID: 5).', 'Atividade', NULL, NULL, '2025-10-09 18:31:22'),
(76, 1, 'Moveu a pasta ID 7 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-11 21:59:55'),
(77, 1, 'Excluiu permanentemente a pasta \'6\' (ID: 7).', 'Atividade', NULL, NULL, '2025-10-11 22:00:03'),
(78, 1, 'Moveu a pasta ID 6 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-11 22:23:37'),
(79, 1, 'Moveu a pasta ID 8 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-11 22:23:37'),
(80, 1, 'Excluiu permanentemente a pasta \'6\' (ID: 6).', 'Atividade', NULL, NULL, '2025-10-11 22:23:44'),
(81, 1, 'Excluiu permanentemente a pasta \'89\' (ID: 8).', 'Atividade', NULL, NULL, '2025-10-11 22:23:46'),
(82, 1, 'Separou as páginas \'1,2,3\' do documento \'\'.', 'Atividade', NULL, NULL, '2025-10-11 23:55:45'),
(83, 1, 'Moveu o documento ID 12 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 00:03:04'),
(84, 1, 'Moveu o documento ID 11 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 00:03:04'),
(85, 1, 'Moveu o documento ID 10 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 00:03:04'),
(86, 1, 'Separou as páginas \'1-2\' do documento \'\'.', 'Atividade', NULL, NULL, '2025-10-12 00:03:22'),
(87, 1, 'Moveu o documento ID 14 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 00:30:36'),
(88, 1, 'Moveu o documento ID 13 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 00:30:36'),
(89, 1, 'Separou as páginas \'1,2\' do documento \'RESTRITO\'.', 'Atividade', NULL, NULL, '2025-10-12 00:31:43'),
(90, 1, 'Separou as páginas \'1-2\' do documento \'PROCURAÇÃO ALEXSANDRO APARECIDO\'.', 'Atividade', NULL, NULL, '2025-10-12 00:32:25'),
(91, 1, 'Moveu o documento ID 17 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 00:39:49'),
(92, 1, 'Moveu o documento ID 18 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 00:39:49'),
(93, 1, 'Moveu o documento ID 16 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 00:39:49'),
(94, 1, 'Moveu o documento ID 15 para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 00:39:49'),
(95, 1, 'Separou as páginas \'1-2\' do documento \'PROCURAÇÃO ALEXSANDRO APARECIDO\'.', 'Atividade', NULL, NULL, '2025-10-12 00:40:04'),
(96, 1, 'Moveu para a lixeira 0 documento(s) e 1 pasta(s) em lote.', 'Atividade', NULL, NULL, '2025-10-12 01:49:35'),
(97, 1, 'Editou o documento \'SEP-68EAF8E330816\' (ID: 19).', 'Atividade', NULL, NULL, '2025-10-12 01:56:05'),
(98, 1, 'Separou as páginas \'1,2\' do documento \'ASO HOSP EINSTEIN - ALESSANDRO\'.', 'Atividade', NULL, NULL, '2025-10-12 01:57:42'),
(99, 1, 'Moveu para a lixeira 2 documento(s) e 0 pasta(s) em lote.', 'Atividade', NULL, NULL, '2025-10-12 01:58:14'),
(100, 1, 'Moveu o documento \'66665\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 15:04:38'),
(101, 1, 'Moveu o documento \'PROCURAÇÃO ALEXSANDRO APARECIDO\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 15:04:39'),
(102, 1, 'Moveu o documento \'RESTRITO\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 16:31:06'),
(103, 1, 'Moveu o documento \'SEP-68EAF8E330816\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 18:39:12'),
(104, 1, 'Moveu o documento \'SEP-68EAF8E42A95C\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 18:44:32'),
(105, 1, 'Moveu o documento \' - Página 1\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:42'),
(106, 1, 'Moveu a pasta \'TESTE23\' (e seu conteúdo) para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:43'),
(107, 1, 'Moveu o documento \' - Página 3\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:45'),
(108, 1, 'Moveu o documento \' - Página 2\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:46'),
(109, 1, 'Moveu o documento \'2555555\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:47'),
(110, 1, 'Moveu o documento \'2222\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:48'),
(111, 1, 'Moveu o documento \'2222\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:48'),
(112, 1, 'Moveu o documento \'2222\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:48'),
(113, 1, 'Moveu o documento \'2222\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:49'),
(114, 1, 'Moveu o documento \'2222\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:49'),
(115, 1, 'Moveu o documento \'2222\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:51'),
(116, 1, 'Moveu o documento \'PROCURAÇÃO ALEXSANDRO APARECIDO\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:55'),
(117, 1, 'Moveu o documento \'ASO HOSP EINSTEIN - ALESSANDRO\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:04:57'),
(118, 1, 'Moveu o documento \'SEP-68EAF6EE8E1C8\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:05:02'),
(119, 1, 'Moveu o documento \'RESTRITO\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:05:04'),
(120, 1, 'Moveu o documento \'66665\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:05:06'),
(121, 1, 'Moveu o documento \'52\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:05:08'),
(122, 1, 'Moveu o documento \'52\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:05:09'),
(123, 1, 'Moveu o documento \'SEP-68EAF0487F4A0\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:05:10'),
(124, 1, 'Moveu o documento \'SEP-68EB0B165169B\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:05:16'),
(125, 1, 'Moveu o documento \'SEP-68EAF0491B9A5\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-12 23:45:30'),
(126, 1, 'Atualizou o usuário \'TESTE RESTRITO\' (ID: 5).', 'Atividade', NULL, NULL, '2025-10-13 16:16:27'),
(127, 1, 'Atualizou o usuário \'TESTE RESTRITO\' (ID: 5).', 'Atividade', NULL, NULL, '2025-10-14 15:47:37'),
(128, 1, 'Atualizou o usuário \'TESTE RESTRITO\' (ID: 5).', 'Atividade', NULL, NULL, '2025-10-14 15:48:56'),
(129, 1, 'Moveu o documento \'66665\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-14 16:23:08'),
(130, 1, 'Moveu o documento \'2222\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-14 16:32:46'),
(131, 1, 'Moveu o documento \'52\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-14 16:32:49'),
(132, 1, 'Moveu o documento \' - Página 1\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-14 16:43:15'),
(133, 1, 'Moveu o documento \'SEP-68EAF0487F4A0\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-14 16:52:52'),
(134, 1, 'Separou as páginas \'1,2\' do documento \' - Página 3\'.', 'Atividade', NULL, NULL, '2025-10-14 20:52:07'),
(135, 1, 'Moveu a pasta \'555\' (e seu conteúdo) para a lixeira.', 'Atividade', NULL, NULL, '2025-10-14 20:52:55'),
(136, 1, 'Criou o usuário \'Alessandro Silva\' (ID: 6).', 'Atividade', NULL, NULL, '2025-10-14 22:07:02'),
(137, 1, 'Moveu o documento \'2555555\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-15 00:38:57'),
(138, 1, 'Moveu o documento \'66665\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-15 00:39:02'),
(139, 1, 'Moveu o documento \' - Página 3\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-19 01:06:01'),
(140, 1, 'Moveu o documento \'2222\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-19 01:06:02'),
(141, 1, 'Moveu o documento \'2555555\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-19 01:06:04'),
(142, 1, 'Moveu o documento \'2555555\' para a lixeira.', 'Atividade', NULL, NULL, '2025-10-19 01:06:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `metadado_campos`
--

CREATE TABLE `metadado_campos` (
  `id` int(11) NOT NULL,
  `tipo_documento_id` int(11) NOT NULL,
  `identificador` varchar(255) DEFAULT NULL,
  `rotulo` varchar(255) NOT NULL,
  `conteudo` varchar(50) DEFAULT 'Alfanumerico',
  `largura` int(11) DEFAULT 8,
  `mascara` varchar(50) DEFAULT NULL,
  `obrigatorio` tinyint(1) NOT NULL DEFAULT 0,
  `ordem` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pastas`
--

CREATE TABLE `pastas` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `pasta_pai_id` int(11) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `apagado_em` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pastas`
--

INSERT INTO `pastas` (`id`, `nome`, `descricao`, `pasta_pai_id`, `data_criacao`, `apagado_em`) VALUES
(2, 'TESTE23', NULL, NULL, '2025-10-03 20:46:16', '2025-10-12 23:04:43'),
(9, '6', NULL, NULL, '2025-10-12 01:49:30', '2025-10-12 01:49:35'),
(10, '555', NULL, NULL, '2025-10-14 20:28:54', '2025-10-14 20:52:54');

-- --------------------------------------------------------

--
-- Estrutura para tabela `permissoes`
--

CREATE TABLE `permissoes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `chave` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `permissoes`
--

INSERT INTO `permissoes` (`id`, `nome`, `chave`, `descricao`) VALUES
(1, 'Acessar Administração', 'admin.access', 'Permite o acesso às páginas do menu Administração.'),
(2, 'Funções (Apagar)', 'roles.delete', NULL),
(3, 'Funções (Criar)', 'roles.create', NULL),
(4, 'Funções (Editar)', 'roles.edit', NULL),
(5, 'Funções (Ver)', 'roles.view', NULL),
(6, 'Apagar Usuários', 'users.delete', 'Permite apagar usuários'),
(7, 'Criar Usuários', 'users.create', 'Permite criar usuários'),
(8, 'Editar Usuários', 'users.edit', 'Permite editar usuários'),
(9, 'Ver Usuários', 'users.view', 'Permite ver usuários'),
(10, 'Ver Registros', 'logs.view', 'Permite visualizar a página de logs de auditoria.'),
(11, 'Editar Documentos', 'docs.edit', 'Permite editar metadados e subir novas versões de documentos.');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_documento`
--

CREATE TABLE `tipos_documento` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `pasta_destino` varchar(255) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `separador` varchar(5) DEFAULT '-',
  `restrito` tinyint(1) NOT NULL DEFAULT 0,
  `assinado` tinyint(1) NOT NULL DEFAULT 0,
  `palavras_chave` text DEFAULT NULL,
  `vencimento_prazo` int(11) DEFAULT NULL,
  `vencimento_unidade` varchar(20) DEFAULT NULL,
  `destinacao` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tipos_documento`
--

INSERT INTO `tipos_documento` (`id`, `nome`, `pasta_destino`, `codigo`, `separador`, `restrito`, `assinado`, `palavras_chave`, `vencimento_prazo`, `vencimento_unidade`, `destinacao`) VALUES
(2, 'Ficha de Internação', NULL, 'FI', '-', 0, 1, NULL, NULL, NULL, NULL),
(3, 'Documento de identidade', '', 'RG', '-', 1, 1, '', NULL, NULL, NULL),
(4, 'Carteira de Convênio', '', 'CC', '-', 1, 0, '', NULL, NULL, NULL),
(5, 'Guia TISS', NULL, 'GT', '-', 0, 1, NULL, NULL, NULL, NULL),
(6, 'Laboratório', NULL, 'LA', '-', 1, 1, NULL, NULL, NULL, NULL),
(7, 'Legado', NULL, 'ZZ', '-', 0, 0, NULL, NULL, NULL, NULL),
(8, 'Matrícula', NULL, 'MA', '-', 1, 0, NULL, NULL, NULL, NULL),
(12, 'Exame (PoC)', NULL, 'ZA', '-', 0, 0, NULL, NULL, NULL, NULL),
(13, 'Guia (PoC)', '', 'ZB', '-', 1, 0, '', NULL, NULL, NULL),
(14, 'Nota Fiscal (PoC)', NULL, 'ZC', '-', 0, 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipo_documento_funcoes`
--

CREATE TABLE `tipo_documento_funcoes` (
  `tipo_documento_id` int(11) NOT NULL,
  `funcao_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tipo_documento_funcoes`
--

INSERT INTO `tipo_documento_funcoes` (`tipo_documento_id`, `funcao_id`) VALUES
(4, 1),
(4, 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `funcao_id` int(11) DEFAULT 2,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `assinatura` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `reset_token`, `reset_expires`, `funcao_id`, `ativo`, `data_criacao`, `assinatura`) VALUES
(1, 'Admin Sistema3', 'admin@sistema.com', '$2y$10$CNWXlsYM4lFRa5Tago719.BUIVK1niRFq1OL0RM8Jsdqr4H3ye3j2', '06603861aa61f6a2386c807961ab4fda2227116c1d5eec07ffa9b8dddb269fb4', '2025-10-14 20:06:05', 1, 1, '2025-10-02 19:13:42', NULL),
(5, 'TESTE RESTRITO', 'teste@sistema.com', '$2y$10$VwARIVJAoY0OFiUuKLrDd.pPy7NSDzorkplbOS1eReveSvYEwyBLG', NULL, NULL, 2, 1, '2025-10-09 18:31:22', NULL),
(6, 'Alessandro Silva', 'alessandrosilva@enfas.com.br', '$2y$10$T/92C06ia8KkQ3sG.OMhy.XWefrLaM.Gl/QJKzyqSPhcEPuIg0sWa', NULL, NULL, 1, 1, '2025-10-14 22:07:02', NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `assinaturas`
--
ALTER TABLE `assinaturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `documento_id` (`documento_id`),
  ADD KEY `fk_assinaturas_requisitante` (`requisitante_id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`config_chave`);

--
-- Índices de tabela `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pasta_id` (`pasta_id`),
  ADD KEY `idx_tipo_documento_id` (`tipo_documento_id`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_apagado_em` (`apagado_em`);

--
-- Índices de tabela `documentos_antigo`
--
ALTER TABLE `documentos_antigo`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `documento_metadados`
--
ALTER TABLE `documento_metadados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documento_id` (`documento_id`),
  ADD KEY `campo_id` (`campo_id`);

--
-- Índices de tabela `documento_versoes_antigo`
--
ALTER TABLE `documento_versoes_antigo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_documento_id` (`tipo_documento_id`),
  ADD KEY `pasta_id` (`pasta_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_documento_id` (`documento_id`);

--
-- Índices de tabela `funcao_permissao`
--
ALTER TABLE `funcao_permissao`
  ADD PRIMARY KEY (`funcao_id`,`permissao_id`),
  ADD KEY `permissao_id` (`permissao_id`);

--
-- Índices de tabela `funcoes`
--
ALTER TABLE `funcoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices de tabela `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `metadado_campos`
--
ALTER TABLE `metadado_campos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_documento_id` (`tipo_documento_id`);

--
-- Índices de tabela `pastas`
--
ALTER TABLE `pastas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pasta_pai_id` (`pasta_pai_id`);

--
-- Índices de tabela `permissoes`
--
ALTER TABLE `permissoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

--
-- Índices de tabela `tipos_documento`
--
ALTER TABLE `tipos_documento`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tipo_documento_funcoes`
--
ALTER TABLE `tipo_documento_funcoes`
  ADD PRIMARY KEY (`tipo_documento_id`,`funcao_id`),
  ADD KEY `fk_tdf_funcoes` (`funcao_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `funcao_id` (`funcao_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `assinaturas`
--
ALTER TABLE `assinaturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT de tabela `documentos_antigo`
--
ALTER TABLE `documentos_antigo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `documento_metadados`
--
ALTER TABLE `documento_metadados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `documento_versoes_antigo`
--
ALTER TABLE `documento_versoes_antigo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de tabela `funcoes`
--
ALTER TABLE `funcoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT de tabela `metadado_campos`
--
ALTER TABLE `metadado_campos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pastas`
--
ALTER TABLE `pastas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `permissoes`
--
ALTER TABLE `permissoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `tipos_documento`
--
ALTER TABLE `tipos_documento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `assinaturas`
--
ALTER TABLE `assinaturas`
  ADD CONSTRAINT `fk_assinaturas_para_documentos` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assinaturas_requisitante` FOREIGN KEY (`requisitante_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `documento_metadados`
--
ALTER TABLE `documento_metadados`
  ADD CONSTRAINT `fk_metadados_campos` FOREIGN KEY (`campo_id`) REFERENCES `metadado_campos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_metadados_documentos` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `documento_versoes_antigo`
--
ALTER TABLE `documento_versoes_antigo`
  ADD CONSTRAINT `documento_versoes_antigo_ibfk_1` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`),
  ADD CONSTRAINT `documento_versoes_antigo_ibfk_2` FOREIGN KEY (`pasta_id`) REFERENCES `pastas` (`id`),
  ADD CONSTRAINT `documento_versoes_antigo_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `funcao_permissao`
--
ALTER TABLE `funcao_permissao`
  ADD CONSTRAINT `funcao_permissao_ibfk_1` FOREIGN KEY (`funcao_id`) REFERENCES `funcoes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `funcao_permissao_ibfk_2` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `metadado_campos`
--
ALTER TABLE `metadado_campos`
  ADD CONSTRAINT `fk_metadado_campos_para_tipos` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pastas`
--
ALTER TABLE `pastas`
  ADD CONSTRAINT `pastas_ibfk_1` FOREIGN KEY (`pasta_pai_id`) REFERENCES `pastas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `tipo_documento_funcoes`
--
ALTER TABLE `tipo_documento_funcoes`
  ADD CONSTRAINT `fk_tdf_funcoes` FOREIGN KEY (`funcao_id`) REFERENCES `funcoes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tdf_tipos` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`funcao_id`) REFERENCES `funcoes` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- Erro ao ler a estrutura para a tabela ged.arquivo: #1932 - Table &#039;ged.arquivo&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.arquivo: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`arquivo`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `assinaturas`
--
-- Erro ao ler a estrutura para a tabela ged.assinaturas: #1932 - Table &#039;ged.assinaturas&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.assinaturas: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`assinaturas`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria`
--
-- Erro ao ler a estrutura para a tabela ged.auditoria: #1932 - Table &#039;ged.auditoria&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.auditoria: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`auditoria`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--
-- Erro ao ler a estrutura para a tabela ged.configuracoes: #1932 - Table &#039;ged.configuracoes&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.configuracoes: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`configuracoes`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos`
--
-- Erro ao ler a estrutura para a tabela ged.documentos: #1932 - Table &#039;ged.documentos&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.documentos: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`documentos`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos_antigo`
--
-- Erro ao ler a estrutura para a tabela ged.documentos_antigo: #1932 - Table &#039;ged.documentos_antigo&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.documentos_antigo: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`documentos_antigo`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos_assinaturas`
--
-- Erro ao ler a estrutura para a tabela ged.documentos_assinaturas: #1932 - Table &#039;ged.documentos_assinaturas&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.documentos_assinaturas: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`documentos_assinaturas`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos_assinaturas_convites`
--
-- Erro ao ler a estrutura para a tabela ged.documentos_assinaturas_convites: #1932 - Table &#039;ged.documentos_assinaturas_convites&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.documentos_assinaturas_convites: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`documentos_assinaturas_convites`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos_indice`
--
-- Erro ao ler a estrutura para a tabela ged.documentos_indice: #1932 - Table &#039;ged.documentos_indice&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.documentos_indice: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`documentos_indice`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos_ocr`
--
-- Erro ao ler a estrutura para a tabela ged.documentos_ocr: #1932 - Table &#039;ged.documentos_ocr&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.documentos_ocr: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`documentos_ocr`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `documento_funcoes`
--
-- Erro ao ler a estrutura para a tabela ged.documento_funcoes: #1932 - Table &#039;ged.documento_funcoes&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.documento_funcoes: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`documento_funcoes`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `documento_metadados`
--
-- Erro ao ler a estrutura para a tabela ged.documento_metadados: #1932 - Table &#039;ged.documento_metadados&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.documento_metadados: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`documento_metadados`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `documento_versoes_antigo`
--
-- Erro ao ler a estrutura para a tabela ged.documento_versoes_antigo: #1932 - Table &#039;ged.documento_versoes_antigo&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.documento_versoes_antigo: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`documento_versoes_antigo`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `doc_templates`
--
-- Erro ao ler a estrutura para a tabela ged.doc_templates: #1932 - Table &#039;ged.doc_templates&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.doc_templates: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`doc_templates`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `emails_log`
--
-- Erro ao ler a estrutura para a tabela ged.emails_log: #1932 - Table &#039;ged.emails_log&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.emails_log: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`emails_log`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `email_templates`
--
-- Erro ao ler a estrutura para a tabela ged.email_templates: #1932 - Table &#039;ged.email_templates&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.email_templates: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`email_templates`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `entidade`
--
-- Erro ao ler a estrutura para a tabela ged.entidade: #1932 - Table &#039;ged.entidade&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.entidade: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`entidade`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `exercicio`
--
-- Erro ao ler a estrutura para a tabela ged.exercicio: #1932 - Table &#039;ged.exercicio&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.exercicio: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`exercicio`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcao_permissao`
--
-- Erro ao ler a estrutura para a tabela ged.funcao_permissao: #1932 - Table &#039;ged.funcao_permissao&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.funcao_permissao: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`funcao_permissao`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `funcoes`
--
-- Erro ao ler a estrutura para a tabela ged.funcoes: #1932 - Table &#039;ged.funcoes&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.funcoes: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`funcoes`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `ingest_arquivos`
--
-- Erro ao ler a estrutura para a tabela ged.ingest_arquivos: #1932 - Table &#039;ged.ingest_arquivos&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.ingest_arquivos: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`ingest_arquivos`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `ingest_eventos`
--
-- Erro ao ler a estrutura para a tabela ged.ingest_eventos: #1932 - Table &#039;ged.ingest_eventos&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.ingest_eventos: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`ingest_eventos`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `lbr`
--
-- Erro ao ler a estrutura para a tabela ged.lbr: #1932 - Table &#039;ged.lbr&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.lbr: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`lbr`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs`
--
-- Erro ao ler a estrutura para a tabela ged.logs: #1932 - Table &#039;ged.logs&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.logs: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`logs`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `mes`
--
-- Erro ao ler a estrutura para a tabela ged.mes: #1932 - Table &#039;ged.mes&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.mes: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`mes`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `metadado_campos`
--
-- Erro ao ler a estrutura para a tabela ged.metadado_campos: #1932 - Table &#039;ged.metadado_campos&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.metadado_campos: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`metadado_campos`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `pastas`
--
-- Erro ao ler a estrutura para a tabela ged.pastas: #1932 - Table &#039;ged.pastas&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.pastas: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`pastas`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `permissoes`
--
-- Erro ao ler a estrutura para a tabela ged.permissoes: #1932 - Table &#039;ged.permissoes&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.permissoes: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`permissoes`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `relacaosessao`
--
-- Erro ao ler a estrutura para a tabela ged.relacaosessao: #1932 - Table &#039;ged.relacaosessao&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.relacaosessao: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`relacaosessao`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `sessao`
--
-- Erro ao ler a estrutura para a tabela ged.sessao: #1932 - Table &#039;ged.sessao&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.sessao: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`sessao`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipoarquivo`
--
-- Erro ao ler a estrutura para a tabela ged.tipoarquivo: #1932 - Table &#039;ged.tipoarquivo&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.tipoarquivo: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`tipoarquivo`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `tiposessao`
--
-- Erro ao ler a estrutura para a tabela ged.tiposessao: #1932 - Table &#039;ged.tiposessao&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.tiposessao: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`tiposessao`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_documento`
--
-- Erro ao ler a estrutura para a tabela ged.tipos_documento: #1932 - Table &#039;ged.tipos_documento&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.tipos_documento: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`tipos_documento`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipo_documento_funcoes`
--
-- Erro ao ler a estrutura para a tabela ged.tipo_documento_funcoes: #1932 - Table &#039;ged.tipo_documento_funcoes&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.tipo_documento_funcoes: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`tipo_documento_funcoes`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario`
--
-- Erro ao ler a estrutura para a tabela ged.usuario: #1932 - Table &#039;ged.usuario&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.usuario: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`usuario`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--
-- Erro ao ler a estrutura para a tabela ged.usuarios: #1932 - Table &#039;ged.usuarios&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.usuarios: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`usuarios`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuariosessao`
--
-- Erro ao ler a estrutura para a tabela ged.usuariosessao: #1932 - Table &#039;ged.usuariosessao&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.usuariosessao: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`usuariosessao`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario_certificados`
--
-- Erro ao ler a estrutura para a tabela ged.usuario_certificados: #1932 - Table &#039;ged.usuario_certificados&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.usuario_certificados: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`usuario_certificados`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `workflows`
--
-- Erro ao ler a estrutura para a tabela ged.workflows: #1932 - Table &#039;ged.workflows&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.workflows: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`workflows`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `workflow_aprovacoes`
--
-- Erro ao ler a estrutura para a tabela ged.workflow_aprovacoes: #1932 - Table &#039;ged.workflow_aprovacoes&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.workflow_aprovacoes: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`workflow_aprovacoes`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `workflow_aprovadores`
--
-- Erro ao ler a estrutura para a tabela ged.workflow_aprovadores: #1932 - Table &#039;ged.workflow_aprovadores&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.workflow_aprovadores: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`workflow_aprovadores`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `workflow_documentos`
--
-- Erro ao ler a estrutura para a tabela ged.workflow_documentos: #1932 - Table &#039;ged.workflow_documentos&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.workflow_documentos: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`workflow_documentos`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `workflow_etapas`
--
-- Erro ao ler a estrutura para a tabela ged.workflow_etapas: #1932 - Table &#039;ged.workflow_etapas&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.workflow_etapas: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`workflow_etapas`&#039; na linha 1

-- --------------------------------------------------------

--
-- Estrutura para tabela `workflow_notificacoes`
--
-- Erro ao ler a estrutura para a tabela ged.workflow_notificacoes: #1932 - Table &#039;ged.workflow_notificacoes&#039; doesn&#039;t exist in engine
-- Erro ao ler dados para tabela ged.workflow_notificacoes: #1064 - Você tem um erro de sintaxe no seu SQL próximo a &#039;FROM `ged`.`workflow_notificacoes`&#039; na linha 1
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
