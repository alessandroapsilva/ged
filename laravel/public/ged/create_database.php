<?php
require_once 'config.php';

try {
    // Conectar sem especificar banco para criar
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Criar banco de dados se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS edoc CHARACTER SET utf8 COLLATE utf8_general_ci");
    $pdo->exec("USE edoc");

    // Tabela de usuários
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            role ENUM('Analista', 'Diretor', 'Administrador') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Tabela de documentos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            status ENUM('Protocolado', 'Em Revisão', 'Aguardando Aprovação', 'Em Análise', 'Aprovado', 'Reprovado', 'Arquivado') DEFAULT 'Protocolado',
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");

    // Tabela de anexos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS attachments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            document_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            filepath VARCHAR(500) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
        )
    ");

    // Tabela de histórico de status
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS document_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            document_id INT NOT NULL,
            old_status VARCHAR(50),
            new_status VARCHAR(50) NOT NULL,
            changed_by INT NOT NULL,
            changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
            FOREIGN KEY (changed_by) REFERENCES users(id)
        )
    ");

    // Tabela de notificações
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Tabela de comentários
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            document_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Tabela de avaliações/ratings
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            document_id INT NOT NULL,
            user_id INT NOT NULL,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            review TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_rating (document_id, user_id),
            FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Tabela de categorias
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Tabela de templates
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            content_template TEXT NOT NULL,
            category_id INT,
            created_by INT NOT NULL,
            is_public BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )
    ");

    // Tabela de versões de documentos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS document_versions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            document_id INT NOT NULL,
            version_number INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            changed_by INT NOT NULL,
            change_reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
            FOREIGN KEY (changed_by) REFERENCES users(id)
        )
    ");

    // Adicionar categoria aos documentos (verificar se já existe)
    try {
        $pdo->exec("ALTER TABLE documents ADD COLUMN category_id INT DEFAULT NULL");
    } catch (Exception $e) {
        // Coluna já existe
    }

    try {
        $pdo->exec("ALTER TABLE documents ADD COLUMN priority ENUM('Baixa', 'Média', 'Alta', 'Urgente') DEFAULT 'Média'");
    } catch (Exception $e) {
        // Coluna já existe
    }

    try {
        $pdo->exec("ALTER TABLE documents ADD COLUMN deadline DATE DEFAULT NULL");
    } catch (Exception $e) {
        // Coluna já existe
    }

    try {
        $pdo->exec("ALTER TABLE documents ADD FOREIGN KEY (category_id) REFERENCES categories(id)");
    } catch (Exception $e) {
        // FK já existe
    }

    // Tabela de logs de auditoria
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");

    // Inserir usuários padrão
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password_hash, name, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['ana.silva', password_hash('123456', PASSWORD_DEFAULT), 'Ana Silva', 'Analista']);
    $stmt->execute(['joao.souza', password_hash('123456', PASSWORD_DEFAULT), 'João Souza', 'Diretor']);
    $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Administrador', 'Administrador']);

    // Inserir categorias padrão
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?, ?)");
    $stmt->execute(['Administrativo', 'Documentos administrativos e burocráticos']);
    $stmt->execute(['Financeiro', 'Documentos relacionados a finanças e orçamento']);
    $stmt->execute(['Jurídico', 'Documentos legais e contratuais']);
    $stmt->execute(['RH', 'Recursos Humanos e pessoal']);
    $stmt->execute(['Técnico', 'Documentos técnicos e operacionais']);

    echo "Banco de dados criado com sucesso!";

} catch (PDOException $e) {
    die("Erro ao criar banco de dados: " . $e->getMessage());
}
?>