-- Tabela de Agendamentos/Visitas
-- Armazena todos os dados necessários para enviar para a API do CRM

CREATE TABLE IF NOT EXISTS `wp_positivo_agendamentos` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  
  -- Dados do Responsável
  `responsavel_nome` varchar(100) NOT NULL,
  `responsavel_sobrenome` varchar(100) NOT NULL,
  `responsavel_email` varchar(255) NOT NULL,
  `responsavel_telefone` varchar(20) NOT NULL,
  `responsavel_serie_interesse` varchar(255) DEFAULT NULL,
  `responsavel_serie_id` varchar(255) DEFAULT NULL COMMENT 'GUID da série de interesse do responsável',
  `responsavel_como_conheceu` int(11) DEFAULT NULL,
  
  -- Dados do Aluno (Dependente)
  `aluno_nome` varchar(100) NOT NULL,
  `aluno_sobrenome` varchar(100) NOT NULL,
  `aluno_serie_interesse` varchar(255) NOT NULL,
  `aluno_serie_id` varchar(255) DEFAULT NULL COMMENT 'GUID da série de interesse do aluno',
  `aluno_ano_interesse` int(11) NOT NULL,
  `aluno_escola_origem` varchar(255) DEFAULT NULL,
  
  -- Dados da Unidade
  `unidade_id` varchar(255) NOT NULL COMMENT 'GUID da unidade (cad_categoriaid)',
  `unidade_nome` varchar(255) DEFAULT NULL COMMENT 'Nome da unidade (para exibição)',
  
  -- Dados do Agendamento
  `data_agendamento` date NOT NULL,
  `hora_agendamento` time NOT NULL,
  `duracao_minutos` int(11) DEFAULT 60,
  
  -- Dados de Controle
  `status` varchar(50) DEFAULT 'pendente' COMMENT 'pendente, enviado, erro, cancelado',
  `enviado_crm` tinyint(1) DEFAULT 0,
  `data_envio_crm` datetime DEFAULT NULL,
  `lead_id` varchar(255) DEFAULT NULL COMMENT 'ID do lead retornado pela API',
  `atividade_id` varchar(255) DEFAULT NULL COMMENT 'ID da atividade retornada pela API',
  `erro_envio` text DEFAULT NULL COMMENT 'Mensagem de erro se houver',
  
  -- Metadados
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` bigint(20) DEFAULT NULL COMMENT 'ID do usuário que criou',
  
  PRIMARY KEY (`id`),
  KEY `idx_email` (`responsavel_email`),
  KEY `idx_unidade` (`unidade_id`),
  KEY `idx_data` (`data_agendamento`),
  KEY `idx_status` (`status`),
  KEY `idx_enviado` (`enviado_crm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
