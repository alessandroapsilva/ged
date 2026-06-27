<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Document.php';
require_once __DIR__ . '/../classes/User.php';

class DatabaseTest {
    private $db;
    private $document;
    private $user;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->document = new Document();
        $this->user = new User();
    }

    public function runAllTests() {
        echo "🧪 Executando testes automatizados...\n\n";

        $results = [
            'testDatabaseConnection' => $this->testDatabaseConnection(),
            'testUserAuthentication' => $this->testUserAuthentication(),
            'testDocumentCreation' => $this->testDocumentCreation(),
            'testDocumentStatusUpdate' => $this->testDocumentStatusUpdate(),
            'testDocumentSearch' => $this->testDocumentSearch(),
            'testAuditLogging' => $this->testAuditLogging()
        ];

        $passed = 0;
        $total = count($results);

        echo "\n📊 Resultados dos Testes:\n";
        echo str_repeat("=", 50) . "\n";

        foreach ($results as $test => $result) {
            $status = $result ? "✅ PASSOU" : "❌ FALHOU";
            echo sprintf("%-25s %s\n", $test, $status);
            if ($result) $passed++;
        }

        echo str_repeat("=", 50) . "\n";
        echo "Total: $passed/$total testes passaram\n";

        if ($passed === $total) {
            echo "🎉 Todos os testes passaram!\n";
        } else {
            echo "⚠️ Alguns testes falharam. Verifique os logs.\n";
        }

        return $passed === $total;
    }

    private function testDatabaseConnection() {
        try {
            $stmt = $this->db->query("SELECT 1 as test");
            $result = $stmt->fetch();
            return $result['test'] === 1;
        } catch (Exception $e) {
            echo "Erro na conexão: " . $e->getMessage() . "\n";
            return false;
        }
    }

    private function testUserAuthentication() {
        // Testar login válido
        $user = $this->user->authenticate('ana.silva', '123456');
        if (!$user || $user['username'] !== 'ana.silva') {
            return false;
        }

        // Testar login inválido
        $invalidUser = $this->user->authenticate('ana.silva', 'wrongpassword');
        if ($invalidUser !== false) {
            return false;
        }

        return true;
    }

    private function testDocumentCreation() {
        $testData = [
            'title' => 'Documento de Teste - ' . time(),
            'content' => 'Conteúdo de teste para validação automática',
            'priority' => 'Média'
        ];

        $docId = $this->document->create($testData, 1); // Usuário admin

        if (!$docId) {
            return false;
        }

        // Verificar se foi criado
        $createdDoc = $this->document->getById($docId);
        if (!$createdDoc || $createdDoc['title'] !== $testData['title']) {
            return false;
        }

        // Limpar documento de teste
        $this->db->query("DELETE FROM documents WHERE id = ?", [$docId]);

        return true;
    }

    private function testDocumentStatusUpdate() {
        // Criar documento de teste
        $testData = [
            'title' => 'Documento Status Test - ' . time(),
            'content' => 'Teste de atualização de status',
            'priority' => 'Baixa'
        ];

        $docId = $this->document->create($testData, 1);

        // Atualizar status
        $result = $this->document->updateStatus($docId, 'Em Análise', 1);

        if (!$result) {
            return false;
        }

        // Verificar se status foi atualizado
        $updatedDoc = $this->document->getById($docId);
        if ($updatedDoc['status'] !== 'Em Análise') {
            return false;
        }

        // Verificar histórico
        $history = $this->document->getHistory($docId);
        if (empty($history) || count($history) < 2) {
            return false;
        }

        // Limpar
        $this->db->query("DELETE FROM documents WHERE id = ?", [$docId]);

        return true;
    }

    private function testDocumentSearch() {
        // Criar documentos de teste
        $docs = [];
        for ($i = 1; $i <= 3; $i++) {
            $testData = [
                'title' => 'Documento Pesquisa Test ' . $i . ' - ' . time(),
                'content' => 'Conteúdo único para teste de busca ' . $i,
                'priority' => 'Média'
            ];
            $docs[] = $this->document->create($testData, 1);
        }

        // Testar busca
        $searchResults = $this->document->getAll(['search' => 'único para teste']);
        if (count($searchResults) < 3) {
            return false;
        }

        // Testar filtro por status
        $statusResults = $this->document->getAll(['status' => 'Protocolado']);
        if (count($statusResults) < 3) {
            return false;
        }

        // Limpar
        foreach ($docs as $docId) {
            $this->db->query("DELETE FROM documents WHERE id = ?", [$docId]);
        }

        return true;
    }

    private function testAuditLogging() {
        // Contar logs antes
        $logsBefore = $this->db->fetchOne("SELECT COUNT(*) as count FROM audit_logs")['count'];

        // Realizar uma ação que gera log
        $this->user->logAction(1, 'TEST', 'system', null, 'Teste de log automático');

        // Contar logs depois
        $logsAfter = $this->db->fetchOne("SELECT COUNT(*) as count FROM audit_logs")['count'];

        return $logsAfter > $logsBefore;
    }
}

// Executar testes se chamado diretamente
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $tester = new DatabaseTest();
    $tester->runAllTests();
}
?>