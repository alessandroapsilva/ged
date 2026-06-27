<?php
// API REST para integração com sistemas externos (endurecida)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Pré-vôo CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Document.php';
require_once __DIR__ . '/classes/User.php';

// Obter PDO para validações auxiliares
$db = Database::getInstance();
$pdo = $db->getConnection();

// --- Autenticação e Rate Limiting ---
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$providedApiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$bearerToken = null;
if (stripos($authHeader, 'Bearer ') === 0) {
    $bearerToken = trim(substr($authHeader, 7));
}

$auth = autenticar_chave_ou_token($pdo, $providedApiKey, $bearerToken);
if (!$auth['ok']) {
    http_response_code(401);
    echo json_encode(['error' => $auth['error'] ?? 'Não autorizado']);
    exit;
}

// Rate limit: 120 requisições por minuto por chave
if (!verificar_rate_limit($pdo, $auth['chave_id'] ?? null, $clientIp, 120, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit excedido. Tente novamente em instantes.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$endpoint = $request[0] ?? '';
$id = $request[1] ?? null;

$document = new Document();
$user = new User();

try {
    switch ($endpoint) {
        case 'documents':
            handleDocuments($method, $id, $document);
            break;

        case 'users':
            handleUsers($method, $id, $user);
            break;

        case 'stats':
            handleStats($method, $document);
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint não encontrado']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor', 'details' => $e->getMessage()]);
}

function handleDocuments($method, $id, $document) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $doc = $document->getById($id);
                if (!$doc) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Documento não encontrado']);
                    return;
                }
                echo json_encode($doc);
            } else {
                $filters = [
                    'status' => $_GET['status'] ?? '',
                    'search' => $_GET['search'] ?? '',
                    'limit' => (int)($_GET['limit'] ?? 50)
                ];
                $docs = $document->getAll($filters);
                echo json_encode(['documents' => $docs, 'total' => count($docs)]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos']);
                return;
            }

            // Usar o proprietário da chave/token quando disponível; fallback para admin
            $systemUserId = $GLOBALS['__api_user_id'] ?? 1; // definido em autenticar_chave_ou_token

            $docId = $document->create([
                'title' => $data['title'] ?? 'Documento via API',
                'content' => $data['content'] ?? '',
                'category_id' => $data['category_id'] ?? null,
                'priority' => $data['priority'] ?? 'Média',
                'deadline' => $data['deadline'] ?? null
            ], $systemUserId);

            echo json_encode(['id' => $docId, 'message' => 'Documento criado com sucesso']);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID do documento necessário']);
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos']);
                return;
            }

            // Atualizar status se fornecido
            if (isset($data['status'])) {
                $document->updateStatus($id, $data['status'], ($GLOBALS['__api_user_id'] ?? 1));
            }

            echo json_encode(['message' => 'Documento atualizado com sucesso']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
    }
}

function handleUsers($method, $id, $user) {
    switch ($method) {
        case 'GET':
            if ($id) {
                $u = $user->getById($id);
                if (!$u) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuário não encontrado']);
                    return;
                }
                unset($u['password_hash']); // Não retornar senha
                echo json_encode($u);
            } else {
                $users = $user->getAll();
                // Remover hashes de senha
                foreach ($users as &$u) {
                    unset($u['password_hash']);
                }
                echo json_encode(['users' => $users, 'total' => count($users)]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
    }
}

function handleStats($method, $document) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Método não permitido']);
        return;
    }

    $stats = $document->getStats();
    echo json_encode($stats);
}
?>

<?php
// --- Funções auxiliares ---
function autenticar_chave_ou_token(PDO $pdo, ?string $apiKey, ?string $bearer): array {
    // 1) Tenta via tabela api_keys (chave ativa e dentro da validade)
    if ($apiKey) {
        try {
            $sql = "SELECT id, user_id, nome, ativo, expira_em FROM api_keys WHERE chave = ? LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([$apiKey]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row && (int)$row['ativo'] === 1 && (empty($row['expira_em']) || strtotime($row['expira_em']) > time())) {
                $GLOBALS['__api_user_id'] = (int)$row['user_id'];
                return ['ok' => true, 'chave_id' => (int)$row['id']];
            }
        } catch (Throwable $e) {
            // Se a tabela não existir, ignora e tenta fallback
        }
    }

    // 2) Tenta via tabela api_tokens (Bearer)
    if ($bearer) {
        try {
            $sql = "SELECT id, user_id, ativo, expira_em FROM api_tokens WHERE token = ? LIMIT 1";
            $st = $pdo->prepare($sql);
            $st->execute([$bearer]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row && (int)$row['ativo'] === 1 && (empty($row['expira_em']) || strtotime($row['expira_em']) > time())) {
                $GLOBALS['__api_user_id'] = (int)$row['user_id'];
                return ['ok' => true, 'chave_id' => (int)$row['id']];
            }
        } catch (Throwable $e) {
            // ignora
        }
    }

    // 3) Fallback para lista estática (último recurso em dev)
    $validApiKeys = ['edoc-api-key-2024', 'test-key-123'];
    if ($apiKey && in_array($apiKey, $validApiKeys, true)) {
        $GLOBALS['__api_user_id'] = 1;
        return ['ok' => true, 'chave_id' => null];
    }

    return ['ok' => false, 'error' => 'Credenciais inválidas'];
}

function verificar_rate_limit(PDO $pdo, $chaveId, string $ip, int $maxReq, int $windowSec): bool {
    // Depende da existência de api_access_log; se não existir, não bloqueia
    try {
        $agora = date('Y-m-d H:i:s');
        $inicioJanela = date('Y-m-d H:i:s', time() - $windowSec);
        $ident = $chaveId ? (string)$chaveId : ('ip:' . $ip);

        // Registra acesso
        $ins = $pdo->prepare("INSERT INTO api_access_log (identificador, ip, criado_em) VALUES (?, ?, ?)");
        $ins->execute([$ident, $ip, $agora]);

        // Conta no período
        $sel = $pdo->prepare("SELECT COUNT(*) FROM api_access_log WHERE identificador = ? AND criado_em >= ?");
        $sel->execute([$ident, $inicioJanela]);
        $count = (int)$sel->fetchColumn();
        return $count <= $maxReq;
    } catch (Throwable $e) {
        return true; // sem tabela -> não limita
    }
}
?>