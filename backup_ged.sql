-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ged
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `assinaturas`
--

DROP TABLE IF EXISTS `assinaturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assinaturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `documento_id` int(11) NOT NULL,
  `versao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nome_signatario` varchar(255) NOT NULL,
  `cpf_cnpj_signatario` varchar(20) DEFAULT NULL,
  `email_signatario` varchar(255) DEFAULT NULL,
  `data_assinatura` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_assinatura` varchar(45) DEFAULT NULL,
  `verificador` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `verificador` (`verificador`),
  KEY `documento_id` (`documento_id`),
  KEY `versao_id` (`versao_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `assinaturas_ibfk_1` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assinaturas_ibfk_2` FOREIGN KEY (`versao_id`) REFERENCES `documento_versoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assinaturas_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assinaturas`
--

LOCK TABLES `assinaturas` WRITE;
/*!40000 ALTER TABLE `assinaturas` DISABLE KEYS */;
INSERT INTO `assinaturas` VALUES (8,2,22,1,'Admin Sistema','33268899846',NULL,'2025-10-05 18:25:02','::1','b941bf93411b855469fec09b3a4e0e9eb99be4806922ddff08b8d795eadf6fea'),(9,2,23,1,'Admin Sistema','45552297890',NULL,'2025-10-05 21:48:51','::1','147fc6c95d4afc4416567d2181567d48ad045bf3b0389eea8a324e671a57adda');
/*!40000 ALTER TABLE `assinaturas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracoes`
--

DROP TABLE IF EXISTS `configuracoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracoes` (
  `config_chave` varchar(50) NOT NULL,
  `config_valor` text DEFAULT NULL,
  PRIMARY KEY (`config_chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracoes`
--

LOCK TABLES `configuracoes` WRITE;
/*!40000 ALTER TABLE `configuracoes` DISABLE KEYS */;
INSERT INTO `configuracoes` VALUES ('ITENS_POR_PAGINA','25'),('NOME_SISTEMA','GED - DEMONSTRAÇÃO');
/*!40000 ALTER TABLE `configuracoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documento_index`
--

DROP TABLE IF EXISTS `documento_index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documento_index` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `documento_id` int(11) NOT NULL,
  `conteudo_texto` longtext NOT NULL,
  `palavras_chave` text DEFAULT NULL,
  `data_indexacao` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `documento_id` (`documento_id`),
  FULLTEXT KEY `conteudo_texto` (`conteudo_texto`),
  FULLTEXT KEY `palavras_chave` (`palavras_chave`),
  CONSTRAINT `documento_index_ibfk_1` FOREIGN KEY (`documento_id`) REFERENCES `documento_versoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_index`
--

LOCK TABLES `documento_index` WRITE;
/*!40000 ALTER TABLE `documento_index` DISABLE KEYS */;
/*!40000 ALTER TABLE `documento_index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documento_metadados`
--

DROP TABLE IF EXISTS `documento_metadados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documento_metadados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_versao` int(11) NOT NULL,
  `id_campo` int(11) NOT NULL,
  `valor` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_versao_campo` (`id_versao`,`id_campo`),
  KEY `id_versao` (`id_versao`),
  KEY `id_campo` (`id_campo`),
  CONSTRAINT `fk_dm_campo_novo` FOREIGN KEY (`id_campo`) REFERENCES `metadado_campos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dm_versao_novo` FOREIGN KEY (`id_versao`) REFERENCES `documento_versoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_metadados`
--

LOCK TABLES `documento_metadados` WRITE;
/*!40000 ALTER TABLE `documento_metadados` DISABLE KEYS */;
/*!40000 ALTER TABLE `documento_metadados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documento_versoes`
--

DROP TABLE IF EXISTS `documento_versoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documento_versoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `page_count` int(11) DEFAULT NULL COMMENT 'Quantidade de páginas do documento',
  PRIMARY KEY (`id`),
  KEY `tipo_documento_id` (`tipo_documento_id`),
  KEY `pasta_id` (`pasta_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_documento_id` (`documento_id`),
  CONSTRAINT `documento_versoes_ibfk_1` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`),
  CONSTRAINT `documento_versoes_ibfk_2` FOREIGN KEY (`pasta_id`) REFERENCES `pastas` (`id`),
  CONSTRAINT `documento_versoes_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_versoes`
--

LOCK TABLES `documento_versoes` WRITE;
/*!40000 ALTER TABLE `documento_versoes` DISABLE KEYS */;
INSERT INTO `documento_versoes` VALUES (7,0,1,'admitido','2025-10-03 17:08:41',NULL,'Documento Combinado 03-10-2025 22:08',NULL,'uploads/combinado_1759522121.pdf',3,NULL,'2025-10-03 20:08:41',1,NULL,'','',NULL,NULL),(8,0,1,'admitido','2025-10-03 21:05:05',NULL,'Documento Combinado 03-10-2025 22:17','','uploads/doc_68e064b165a0c_1759536305.pdf',3,NULL,'2025-10-04 00:05:05',1,NULL,'','',NULL,NULL),(9,0,1,'admitido','2025-10-04 18:11:42',232347,'YESTSTS',NULL,'../uploads/doc_68e18d8dde99d8.67867924.pdf',NULL,NULL,'2025-10-04 21:11:42',1,NULL,'LAUDO CARACTERIZADOR ALBERT EINSTEIN - ALESSANDRO.pdf','doc_68e18d8dde99d8.67867924.pdf',NULL,NULL),(10,0,1,'admitido','2025-10-04 18:12:27',232347,'TTETSSSGGSGS',NULL,'../uploads/doc_68e18dbb81be22.37507683.pdf',NULL,NULL,'2025-10-04 21:12:27',1,NULL,'LAUDO CARACTERIZADOR ALBERT EINSTEIN - ALESSANDRO.pdf','doc_68e18dbb81be22.37507683.pdf',NULL,NULL),(11,0,1,'admitido','2025-10-04 18:15:31',108075,'Relatório Mensal',NULL,'../uploads/doc_68e18e73308ee4.28430731.pdf',NULL,NULL,'2025-10-04 21:15:31',1,NULL,'CPF ALESSANDRO.pdf','doc_68e18e73308ee4.28430731.pdf',NULL,NULL),(12,0,1,'admitido','2025-10-04 18:35:19',232347,'000000000000',NULL,'../uploads/doc_68e19317bd7ac2.60946842.pdf',NULL,NULL,'2025-10-04 21:35:19',1,NULL,'LAUDO CARACTERIZADOR ALBERT EINSTEIN - ALESSANDRO.pdf','doc_68e19317bd7ac2.60946842.pdf',NULL,NULL),(13,0,1,'admitido','2025-10-04 18:44:48',232347,'TESTE TESTE ',NULL,'../uploads/doc_68e195505db512.91974848.pdf',NULL,NULL,'2025-10-04 21:44:48',1,NULL,'LAUDO CARACTERIZADOR ALBERT EINSTEIN - ALESSANDRO.pdf','doc_68e195505db512.91974848.pdf',NULL,NULL),(17,0,2,'admitido','2025-10-04 18:48:34',71755,'000000000000',NULL,'../uploads/doc_68e196324fa6b5.97395123.pdf',NULL,NULL,'2025-10-04 21:48:34',1,NULL,'documento-digitalizado0110.pdf','doc_68e196324fa6b5.97395123.pdf',NULL,NULL),(18,0,1,'admitido','2025-10-04 19:13:46',163670,'teste reste',NULL,'../uploads/doc_68e19c1ada2734.35973459.pdf',9,NULL,'2025-10-04 22:13:46',1,NULL,'ASO HOSP EINSTEIN - ALESSANDRO.pdf','doc_68e19c1ada2734.35973459.pdf',NULL,NULL),(19,1,1,'admitido','2025-10-04 21:16:31',157048,'',NULL,'../uploads/doc_v1_68e1b8dfb44ca4.95270826.pdf',8,NULL,'2025-10-05 00:16:31',1,NULL,'anti hbs einstein 01-10 ALESSANDRO.pdf','doc_v1_68e1b8dfb44ca4.95270826.pdf',NULL,NULL),(20,1,2,'assinado',NULL,174443,'',NULL,'uploads/doc_v2_68e1d593b68b3_1.pdf',8,NULL,'2025-10-05 02:19:02',1,NULL,'anti hbs einstein 01-10 ALESSANDRO.pdf','doc_v2_68e1d593b68b3_1.pdf',NULL,NULL),(21,2,1,'admitido','2025-10-05 15:18:20',157048,'teste',NULL,'../uploads/doc_v1_68e2b66ccb40f1.66631155.pdf',9,NULL,'2025-10-05 18:18:20',1,NULL,'anti hbs einstein 01-10 ALESSANDRO (1).pdf','doc_v1_68e2b66ccb40f1.66631155.pdf',NULL,NULL),(22,2,2,'assinado',NULL,174443,'teste',NULL,'uploads/doc_v2_68e2b7f84c8cf_2.pdf',9,NULL,'2025-10-05 18:25:01',1,NULL,'anti hbs einstein 01-10 ALESSANDRO (1).pdf','doc_v2_68e2b7f84c8cf_2.pdf',NULL,NULL),(23,2,3,'assinado',NULL,175803,'teste',NULL,'uploads/doc_v3_68e2e7c08b944_2.pdf',9,NULL,'2025-10-05 21:48:51',1,NULL,'anti hbs einstein 01-10 ALESSANDRO (1).pdf','doc_v3_68e2e7c08b944_2.pdf',NULL,NULL);
/*!40000 ALTER TABLE `documento_versoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documentos`
--

DROP TABLE IF EXISTS `documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo_original` varchar(255) NOT NULL,
  `id_usuario_criador` int(11) NOT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `apagado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documentos`
--

LOCK TABLES `documentos` WRITE;
/*!40000 ALTER TABLE `documentos` DISABLE KEYS */;
INSERT INTO `documentos` VALUES (2,'teste',1,'2025-10-05 18:18:20',NULL);
/*!40000 ALTER TABLE `documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `funcao_permissao`
--

DROP TABLE IF EXISTS `funcao_permissao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `funcao_permissao` (
  `funcao_id` int(11) NOT NULL,
  `permissao_id` int(11) NOT NULL,
  PRIMARY KEY (`funcao_id`,`permissao_id`),
  KEY `permissao_id` (`permissao_id`),
  CONSTRAINT `funcao_permissao_ibfk_1` FOREIGN KEY (`funcao_id`) REFERENCES `funcoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `funcao_permissao_ibfk_2` FOREIGN KEY (`permissao_id`) REFERENCES `permissoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `funcao_permissao`
--

LOCK TABLES `funcao_permissao` WRITE;
/*!40000 ALTER TABLE `funcao_permissao` DISABLE KEYS */;
INSERT INTO `funcao_permissao` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11);
/*!40000 ALTER TABLE `funcao_permissao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `funcoes`
--

DROP TABLE IF EXISTS `funcoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `funcoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_funcao` varchar(100) NOT NULL,
  `chave` varchar(50) DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `nivel` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `funcoes`
--

LOCK TABLES `funcoes` WRITE;
/*!40000 ALTER TABLE `funcoes` DISABLE KEYS */;
INSERT INTO `funcoes` VALUES (1,'Administrador','admin','Administradores do sistema',10),(2,'Usuário','user','Usuário padrão com permissões limitadas',1);
/*!40000 ALTER TABLE `funcoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(255) NOT NULL,
  `data_ocorrencia` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs`
--

LOCK TABLES `logs` WRITE;
/*!40000 ALTER TABLE `logs` DISABLE KEYS */;
INSERT INTO `logs` VALUES (1,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-02 20:39:37'),(2,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-02 20:44:18'),(3,1,'Atualizou o usuário \'Admin Sistema\' (ID: 1).','2025-10-02 20:44:30'),(4,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-02 20:44:46'),(5,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-02 21:33:00'),(6,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-02 21:33:24'),(7,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-02 21:33:28'),(8,1,'Atualizou o usuário \'Admin Sistema\' (ID: 1).','2025-10-02 21:39:57'),(9,1,'Atualizou o usuário \'Admin Sistema\' (ID: 1).','2025-10-02 21:40:22'),(10,1,'Atualizou a função \'Usuário\' (ID: 2).','2025-10-02 21:48:28'),(11,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-02 21:48:34'),(12,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-02 21:48:53'),(13,1,'Fez upload do documento \'teste\' (ID: 4).','2025-10-02 22:20:35'),(14,1,'Fez upload do documento \'00000\' (ID: 5).','2025-10-02 22:21:04'),(15,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-02 22:41:14'),(16,1,'Moveu o documento \'00000\' para a lixeira.','2025-10-03 18:26:50'),(17,1,'Restaurou o documento \'00000\' da lixeira.','2025-10-03 18:33:52'),(18,1,'Moveu a pasta \'CONTRATO\' (e seu conteúdo) para a lixeira.','2025-10-03 18:36:10'),(19,1,'Excluiu permanentemente a pasta \'CONTRATO\' e todo o seu conteúdo.','2025-10-03 18:36:23'),(20,1,'Combinou 2 documentos para criar \'Documento Combinado 03-10-2025 21:42\'.','2025-10-03 19:42:18'),(21,1,'Moveu o documento \'Documento Combinado 03-10-2025 21:42\' para a lixeira.','2025-10-03 19:57:39'),(22,1,'Excluiu permanentemente o documento \'Documento Combinado 03-10-2025 21:42\'.','2025-10-03 19:57:45'),(23,1,'Combinou 2 documentos para criar \'Documento Combinado 03-10-2025 22:08\'.','2025-10-03 20:08:41'),(24,1,'Combinou 2 docs para criar \'Documento Combinado 03-10-2025 22:17\' e moveu os originais para a lixeira.','2025-10-03 20:17:00'),(25,1,'Excluiu permanentemente o documento \'teste\'.','2025-10-03 20:17:30'),(26,1,'Excluiu permanentemente o documento \'00000\'.','2025-10-03 20:17:32'),(27,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-03 21:08:13'),(28,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-03 21:12:48'),(29,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-03 21:39:28'),(30,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-03 21:40:58'),(31,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-03 21:42:53'),(32,1,'Atualizou o usuário \'Admin Sistema\' (ID: 1).','2025-10-03 22:34:35'),(33,1,'Atualizou a função \'Administrador\' (ID: 1).','2025-10-03 22:43:20'),(34,1,'Editou os metadados do documento \'Documento Combinado 03-10-2025 22:17\' (ID: 8).','2025-10-04 00:01:34'),(35,1,'Editou os metadados do documento \'Documento Combinado 03-10-2025 22:17\' (ID: 8).','2025-10-04 00:02:07'),(36,1,'Criou a versão 2 do documento \'Documento Combinado 03-10-2025 22:17\' (ID: 8). Motivo: yest','2025-10-04 00:05:05'),(37,1,'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.','2025-10-04 01:07:42'),(38,1,'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.','2025-10-04 01:08:06'),(39,1,'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.','2025-10-04 01:13:30'),(40,1,'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.','2025-10-04 01:15:29'),(41,1,'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.','2025-10-04 01:27:12'),(42,1,'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.','2025-10-04 01:32:42'),(43,1,'Assinou digitalmente o documento \'Documento Combinado 03-10-2025 22:17\'.','2025-10-04 02:00:43'),(44,NULL,'Atualizou o usuário \'Admin Sistema\' (ID: 1).','2025-10-05 00:10:26'),(45,1,'Apagou o usuário \'Administrador do Sistema\'.','2025-10-05 00:29:32'),(46,1,'Assinou digitalmente o documento \'\', gerando a versão 2.','2025-10-05 02:19:02'),(47,1,'Moveu o documento ID 20 para a lixeira.','2025-10-05 17:22:38'),(48,1,'Moveu o documento ID 20 para a lixeira.','2025-10-05 17:25:12'),(49,1,'Moveu o documento ID 20 para a lixeira.','2025-10-05 17:26:32'),(50,1,'Moveu o documento ID 20 para a lixeira.','2025-10-05 17:38:37'),(51,1,'Moveu o documento ID 1 para a lixeira.','2025-10-05 17:50:11'),(52,1,'Moveu o documento ID 1 para a lixeira.','2025-10-05 17:50:15'),(53,1,'Moveu o documento ID 1 para a lixeira.','2025-10-05 18:14:27'),(54,1,'Moveu o documento ID 1 para a lixeira.','2025-10-05 18:17:44'),(55,1,'Excluiu permanentemente o documento \'ID 1\' (ID: 1).','2025-10-05 18:17:51'),(56,1,'Assinou digitalmente o documento \'teste\', gerando a versão 2.','2025-10-05 18:25:02'),(57,1,'Moveu a pasta ID 3 para a lixeira.','2025-10-05 21:47:02'),(58,1,'Excluiu permanentemente a pasta \'5\' (ID: 3).','2025-10-05 21:47:16'),(59,1,'Assinou digitalmente o documento \'teste\', gerando a versão 3.','2025-10-05 21:48:51');
/*!40000 ALTER TABLE `logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `metadado_campos`
--

DROP TABLE IF EXISTS `metadado_campos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `metadado_campos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_documento_id` int(11) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `identificador` varchar(100) NOT NULL,
  `rotulo` varchar(100) NOT NULL,
  `conteudo` enum('Numerico','Alfanumerico','Data') NOT NULL DEFAULT 'Alfanumerico',
  `largura` int(11) DEFAULT NULL,
  `mascara` varchar(50) DEFAULT NULL,
  `contem_cod_tipo` tinyint(1) NOT NULL DEFAULT 0,
  `data_de_origem` tinyint(1) NOT NULL DEFAULT 0,
  `obrigatorio` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tipo_documento_id` (`tipo_documento_id`),
  CONSTRAINT `fk_metadado_tipo_doc` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `metadado_campos`
--

LOCK TABLES `metadado_campos` WRITE;
/*!40000 ALTER TABLE `metadado_campos` DISABLE KEYS */;
INSERT INTO `metadado_campos` VALUES (1,5,10,'NR_NF','NOTA FISCAL','Numerico',100,NULL,1,1,0);
/*!40000 ALTER TABLE `metadado_campos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pastas`
--

DROP TABLE IF EXISTS `pastas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pastas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `pasta_pai_id` int(11) DEFAULT NULL,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `apagado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pasta_pai_id` (`pasta_pai_id`),
  CONSTRAINT `pastas_ibfk_1` FOREIGN KEY (`pasta_pai_id`) REFERENCES `pastas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pastas`
--

LOCK TABLES `pastas` WRITE;
/*!40000 ALTER TABLE `pastas` DISABLE KEYS */;
INSERT INTO `pastas` VALUES (2,'TESTE',NULL,'2025-10-03 20:46:16',NULL);
/*!40000 ALTER TABLE `pastas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissoes`
--

DROP TABLE IF EXISTS `permissoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `chave` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissoes`
--

LOCK TABLES `permissoes` WRITE;
/*!40000 ALTER TABLE `permissoes` DISABLE KEYS */;
INSERT INTO `permissoes` VALUES (1,'Acessar Administração','admin.access','Permite o acesso às páginas do menu Administração.'),(2,'Funções (Apagar)','roles.delete',NULL),(3,'Funções (Criar)','roles.create',NULL),(4,'Funções (Editar)','roles.edit',NULL),(5,'Funções (Ver)','roles.view',NULL),(6,'Apagar Usuários','users.delete','Permite apagar usuários'),(7,'Criar Usuários','users.create','Permite criar usuários'),(8,'Editar Usuários','users.edit','Permite editar usuários'),(9,'Ver Usuários','users.view','Permite ver usuários'),(10,'Ver Registros','logs.view','Permite visualizar a página de logs de auditoria.'),(11,'Editar Documentos','docs.edit','Permite editar metadados e subir novas versões de documentos.');
/*!40000 ALTER TABLE `permissoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_documento_funcoes`
--

DROP TABLE IF EXISTS `tipo_documento_funcoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipo_documento_funcoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_documento_id` int(11) NOT NULL,
  `funcao_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `regra_unica` (`tipo_documento_id`,`funcao_id`),
  KEY `fk_tdf_tipo_doc` (`tipo_documento_id`),
  KEY `fk_tdf_funcao` (`funcao_id`),
  CONSTRAINT `fk_tdf_funcao` FOREIGN KEY (`funcao_id`) REFERENCES `funcoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tdf_tipo_doc` FOREIGN KEY (`tipo_documento_id`) REFERENCES `tipos_documento` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_documento_funcoes`
--

LOCK TABLES `tipo_documento_funcoes` WRITE;
/*!40000 ALTER TABLE `tipo_documento_funcoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `tipo_documento_funcoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_documento`
--

DROP TABLE IF EXISTS `tipos_documento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_documento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `codigo` varchar(10) DEFAULT NULL,
  `pasta_destino_id` int(11) DEFAULT NULL COMMENT 'ID da pasta para onde os docs serão movidos',
  `arquivar_original` tinyint(1) NOT NULL DEFAULT 0,
  `assinatura_certificadora` tinyint(1) NOT NULL DEFAULT 0,
  `palavras_chave` varchar(255) DEFAULT NULL,
  `separador_campos` varchar(5) DEFAULT '-',
  `restrito` tinyint(1) NOT NULL DEFAULT 0,
  `assinado` tinyint(1) NOT NULL DEFAULT 0,
  `codigo_tipo` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_documento`
--

LOCK TABLES `tipos_documento` WRITE;
/*!40000 ALTER TABLE `tipos_documento` DISABLE KEYS */;
INSERT INTO `tipos_documento` VALUES (1,'Contrato',NULL,NULL,0,0,NULL,'-',0,0,'CO'),(2,'Digitalizado',NULL,NULL,0,0,NULL,'-',0,0,'DO'),(3,'Combinado',NULL,NULL,0,0,NULL,'-',0,0,'CO'),(5,'Nota Fiscal','NF',NULL,0,0,NULL,'-',0,0,''),(6,'Relatório',NULL,NULL,0,0,NULL,'-',0,0,''),(7,'Apresentação','AP',NULL,0,0,'','-',0,0,''),(8,'Documento Pessoal',NULL,NULL,0,0,NULL,'-',0,0,''),(9,'Procuração',NULL,NULL,0,0,NULL,'-',0,0,''),(10,'Outros',NULL,NULL,0,0,NULL,'-',0,0,'');
/*!40000 ALTER TABLE `tipos_documento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `funcao_id` int(11) DEFAULT 2,
  `ativo` tinyint(1) DEFAULT 1,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp(),
  `assinatura` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `funcao_id` (`funcao_id`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`funcao_id`) REFERENCES `funcoes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Admin Sistema','admin@sistema.com','$2y$10$BGx.46FDovj9HQ8aj5ZFL.Sc8o0rJBiSyFeo5n9lSNXMGumXcJbYC',1,1,'2025-10-02 19:13:42',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `versoes`
--

DROP TABLE IF EXISTS `versoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `versoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `documento_id` int(11) NOT NULL,
  `versao` int(11) NOT NULL,
  `caminho_arquivo` varchar(512) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `data_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  `motivo_alteracao` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documento_id` (`documento_id`),
  KEY `versoes_ibfk_2` (`usuario_id`),
  CONSTRAINT `versoes_ibfk_1` FOREIGN KEY (`documento_id`) REFERENCES `documento_versoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `versoes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `versoes`
--

LOCK TABLES `versoes` WRITE;
/*!40000 ALTER TABLE `versoes` DISABLE KEYS */;
INSERT INTO `versoes` VALUES (1,8,1,'uploads/combinado_1759522620.pdf',1,'2025-10-03 20:17:00','yest');
/*!40000 ALTER TABLE `versoes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-06  0:32:27
