# ⚡ Referência Rápida - GED Profissional

## 🎯 Snippets Mais Usados

### 1. Inicializar Página Profissional
```php
<?php
require_once '../core/init_professional.php';
requireAuth(); // Verifica login

$layout = new ProfessionalLayout('Título da Página');
$layout->setContent('<div>Conteúdo aqui</div>');
echo $layout->render();
```

### 2. Validar Formulário
```php
$rules = [
    'email' => ['required' => true, 'email' => true],
    'password' => ['required' => true, 'min_length' => 8],
];

foreach ($rules as $field => $field_rules) {
    $result = SecurityHelper::validate($_POST[$field], $field_rules);
    if (!$result['valid']) {
        echo ApiResponse::error('Validação falhou', $result['errors']);
        exit;
    }
}
```

### 3. Consultar Banco
```php
$db = DatabaseManager::getInstance();

// Buscar um
$user = $db->queryOne("SELECT * FROM usuarios WHERE id = ?", [123]);

// Buscar múltiplos
$users = $db->query("SELECT * FROM usuarios WHERE role = ?", ['admin']);

// Contar
$total = $db->count('usuarios');

// Inserir
$id = $db->insert('usuarios', ['email' => 'novo@email.com', 'name' => 'João']);

// Atualizar
$db->update('usuarios', ['name' => 'João Silva'], ['id' => 123]);

// Deletar
$db->delete('usuarios', ['id' => 123]);
```

### 4. Usar Cache
```php
$docs = $db->cache('all_documents', function() {
    return $db->query("SELECT * FROM documentos ORDER BY created_at DESC");
}, 3600); // 1 hora

// Limpar cache
$db->clearCache('all_documents');
```

### 5. Transações
```php
$db->transaction(function($db) {
    $db->insert('pedidos', ['total' => 100]);
    $db->update('estoque', ['quantidade' => 10], ['id' => 5]);
    // Se tudo OK: commit automático
    // Se erro: rollback automático
});
```

### 6. Hash de Senha
```php
// Criar hash
$hash = SecurityHelper::hashPassword('senha123');

// Verificar
if (SecurityHelper::verifyPassword('senha123', $hash)) {
    echo 'Senha correta';
}
```

### 7. Auditoria
```php
SecurityHelper::auditLog(
    $db->getPDO(),
    $_SESSION['user_id'],
    'ACTION_NAME',
    'tabela',
    ['field' => 'value', 'reason' => 'motivo']
);
```

### 8. API Response
```php
// Sucesso
echo ApiResponse::success(['documento' => $doc], 'Criado com sucesso', 201);

// Erro
echo ApiResponse::error('Erro ao processar', ['Campo obrigatório'], 400);

// Paginado
echo ApiResponse::paginated($items, 100, 1, 25, 'OK');
```

### 9. Logger
```php
Logger::info('User logged in', ['user_id' => 123, 'ip' => $_SERVER['REMOTE_ADDR']]);
Logger::warning('Large file uploaded', ['size' => 50000000]);
Logger::error('Database error', ['query' => $sql]);
```

### 10. Mensagem Flash
```php
// Salvar mensagem
redirectWithMessage('/home.php', 'Documento salvo com sucesso!', 'success');

// Em outra página
<?php echo renderFlashMessage(); ?>
```

---

## 🎨 Classes CSS Mais Usadas

```html
<!-- Botões -->
<button class="btn btn-primary">Salvar</button>
<button class="btn btn-secondary btn-sm">Cancelar</button>
<button class="btn btn-danger btn-lg">Deletar</button>

<!-- Cards -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Título</h3>
  </div>
  <div class="card-body">Conteúdo</div>
  <div class="card-footer">Rodapé</div>
</div>

<!-- Inputs -->
<div class="form-group">
  <label class="form-label required">Email</label>
  <input class="form-input" type="email" required>
  <div class="form-help">Seu email de acesso</div>
</div>

<!-- Alertas -->
<div class="alert alert-success">✓ Sucesso!</div>
<div class="alert alert-error">✗ Erro</div>

<!-- Badges -->
<span class="badge badge-primary">Novo</span>
<span class="badge badge-success">Aprovado</span>

<!-- Tabelas -->
<div class="table-container">
  <table class="table">
    <thead>
      <tr><th>Coluna 1</th></tr>
    </thead>
    <tbody>
      <tr><td>Dado</td></tr>
    </tbody>
  </table>
</div>

<!-- Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--spacing-lg);">
  <!-- items aqui -->
</div>

<!-- Flex -->
<div class="flex justify-between items-center gap-lg">
  <!-- items -->
</div>
```

---

## 🔍 Variáveis CSS Disponíveis

```css
/* Cores */
--color-primary
--color-secondary
--color-success
--color-warning
--color-error
--color-info

/* Espaçamento */
--spacing-xs  (0.25rem)
--spacing-sm  (0.5rem)
--spacing-md  (1rem)
--spacing-lg  (1.5rem)
--spacing-xl  (2rem)

/* Tipografia */
--font-size-sm    (0.875rem)
--font-size-base  (1rem)
--font-size-lg    (1.125rem)
--font-size-xl    (1.25rem)

/* Raio */
--radius-sm  (0.375rem)
--radius-md  (0.5rem)
--radius-lg  (0.75rem)

/* Sombras */
--shadow-sm  (sutil)
--shadow-md  (médio)
--shadow-lg  (grande)

/* Transições */
--transition (all 0.3s)
```

---

## 🛠️ Ferramentas Úteis

### Verificar Saúde do Sistema
```
https://seu-dominio.com/health_check.php
```

### Ver Query Log
```php
$logs = $db->getQueryLog();
foreach ($logs as $log) {
    echo $log['sql'];
    echo json_encode($log['params']);
}
```

### Gerar UUID
```php
$uuid = SecurityHelper::generateUuid();
// ou
$uuid = generateUuid(); // função global
```

### Formatar Data
```php
$data = formatDate('2025-01-31 14:23:45', 'd/m/Y H:i');
// Resultado: 31/01/2025 14:23
```

### Formatar Bytes
```php
$tamanho = formatBytes(1024000); // 1000 KB
$tamanho = formatBytes(1048576); // 1 MB
```

---

## 🚀 Estrutura de Arquivo Novo

```php
<?php
/**
 * Título e Descrição
 * 
 * O que faz este arquivo
 */

// Inicializar
require_once '../core/init_professional.php';

// Verificar acesso
requireAuth(); // ou requireAdmin()

// Obter dados
$db = DatabaseManager::getInstance();

try {
    // Validar entrada
    $id = SecurityHelper::sanitizeInput($_GET['id'] ?? '', 'int');
    
    $validation = SecurityHelper::validate($id, [
        'required' => true,
        'numeric' => true
    ]);
    
    if (!$validation['valid']) {
        throw new InvalidArgumentException('ID inválido');
    }
    
    // Consultar
    $data = $db->queryOne(
        "SELECT * FROM tabela WHERE id = ?",
        [(int)$id]
    );
    
    if (!$data) {
        throw new RuntimeException('Registro não encontrado');
    }
    
    // Registrar ação
    logActivity('VIEW_RECORD', 'tabela', ['id' => $id]);
    
    // Renderizar
    if (isAjaxRequest()) {
        echo ApiResponse::success($data, 'OK');
    } else {
        $layout = new ProfessionalLayout('Título');
        $layout->setContent('<div class="card">...</div>');
        echo $layout->render();
    }
    
} catch (InvalidArgumentException $e) {
    echo ApiResponse::error('Validação falhou', $e->getMessage(), 400);
} catch (RuntimeException $e) {
    echo ApiResponse::error('Erro ao processar', $e->getMessage(), 404);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo ApiResponse::error('Erro interno', 'Erro ao processar', 500);
}

function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
```

---

## 🐛 Troubleshooting Rápido

| Problema | Solução |
|----------|---------|
| "Database Connection Error" | Verifique config.php, credenciais MySQL, firewall |
| "CSRF Token inválido" | Limpe cookies, reinicie sessão |
| Página lenta | Acesse health_check.php, verifique query log |
| Upload falha | Verifique permissões /uploads (755), espaço em disco |
| Email não envia | Teste SMTP em: /teste_smtp_rapido.php |
| Autenticação falha | Verifique session, cookies (httponly=true) |
| Segurança warning | Consulte GUIA_PROFISSIONAL.md |

---

## 📚 Arquivos de Referência

| Arquivo | Uso |
|---------|-----|
| `/core/init_professional.php` | Inicialização de página |
| `/helpers/SecurityHelper.php` | Segurança, validação, hash |
| `/helpers/DatabaseManager.php` | Consultas, cache, transactions |
| `/helpers/ProfessionalLayout.php` | Template profissional |
| `/public/css/professional.css` | Estilos e componentes |
| `/docs/GUIA_PROFISSIONAL.md` | Documentação completa |

---

## ⌨️ Atalhos Úteis

```php
// Autenticação
requireAuth()              // Verifica login
requireAdmin()             // Verifica admin
getCurrentUser()           // Obtém dados do usuário

// Dados
formatDate($date)          // Formata data
formatBytes($bytes)        // Formata tamanho
translateStatus($status)   // Traduz status

// Resposta
redirectWithMessage()      // Redireciona com msg
renderFlashMessage()       // Renderiza mensagem

// Debug (desenvolvimento)
debug($var)                // Mostra debug info
```

---

**Última atualização**: 31 de janeiro de 2026  
**Versão**: 1.0
