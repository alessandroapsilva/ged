<?php
require_once '../core/init.php';
if (file_exists(PROJECT_ROOT . '/helpers/csrf_helper.php')) { require_once PROJECT_ROOT . '/helpers/csrf_helper.php'; }

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

// Proteção CSRF quando disponível
if (function_exists('require_csrf_or_abort')) { require_csrf_or_abort(); }

$tipo_id = isset($_POST['tipo_id']) ? (int)$_POST['tipo_id'] : 0;
if ($tipo_id <= 0) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'ID de tipo de documento inválido.'];
    header('Location: tipos_listar');
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Atualiza informações gerais apenas para colunas existentes
    $cols = [];
    try {
        $rs = $pdo->query("SHOW COLUMNS FROM tipos_documento");
        foreach ($rs->fetchAll(PDO::FETCH_COLUMN, 0) as $c) { $cols[strtolower($c)] = true; }
    } catch (Throwable $e) { $cols = []; }

    $sets = [];
    $vals = [];
    $put = function(string $column, $value) use (&$sets, &$vals, $cols) {
        if (isset($cols[strtolower($column)])) { $sets[] = "$column = ?"; $vals[] = $value; }
    };

    $put('nome', $_POST['nome'] ?? 'Sem Nome');
    $put('pasta_destino', $_POST['pasta_destino'] ?? null);
    $put('codigo', $_POST['codigo'] ?? null);
    $put('separador', $_POST['separador'] ?? '-');
    $put('restrito', isset($_POST['restrito']) ? 1 : 0);
    $put('assinado', isset($_POST['assinado']) ? 1 : 0);
    $put('palavras_chave', $_POST['palavras_chave'] ?? null);
    // Trata vencimento (permite "permanente" limpar os campos)
    $prazo = !empty($_POST['vencimento_prazo']) ? (int)$_POST['vencimento_prazo'] : null;
    $unid  = $_POST['vencimento_unidade'] ?? null;
    if (!empty($_POST['permanente'])) { $prazo = null; $unid = null; }
    $put('vencimento_prazo', $prazo);
    $put('vencimento_unidade', $unid);
    // Campo opcional em alguns bancos
    $put('destinacao', $_POST['destinacao'] ?? null);

    if (!empty($sets)) {
        $sql = 'UPDATE tipos_documento SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $vals[] = $tipo_id;
        $stmt_update_tipo = $pdo->prepare($sql);
        $stmt_update_tipo->execute($vals);
    }

    // 2. Atualiza as permissões de acesso por função
    $funcoes_permitidas = $_POST['funcoes_permitidas'] ?? [];
    $stmt_delete_funcoes = $pdo->prepare("DELETE FROM tipo_documento_funcoes WHERE tipo_documento_id = ?");
    $stmt_delete_funcoes->execute([$tipo_id]);
    if (!empty($funcoes_permitidas)) {
        $stmt_insert_funcao = $pdo->prepare("INSERT INTO tipo_documento_funcoes (tipo_documento_id, funcao_id) VALUES (?, ?)");
        foreach ($funcoes_permitidas as $funcao_id) {
            $stmt_insert_funcao->execute([$tipo_id, (int)$funcao_id]);
        }
    }

    // 3. Processa os campos de metadados
    $ids_dos_campos_enviados = [];
    if (isset($_POST['campo_id'])) {
        foreach ($_POST['campo_id'] as $index => $campo_id) {
            $dados_campo = [
                'tipo_documento_id' => $tipo_id,
                'identificador'     => $_POST['identificador'][$index],
                'rotulo'            => $_POST['rotulo'][$index],
                'conteudo'          => $_POST['conteudo'][$index],
                'largura'           => (int)$_POST['largura'][$index],
                'mascara'           => $_POST['mascara'][$index],
                'obrigatorio'       => isset($_POST['obrigatorio'][$campo_id]) ? 1 : 0,
                'ordem'             => (int)$_POST['ordem'][$index]
            ];

            if (strpos($campo_id, 'new_') === 0) {
                $stmt = $pdo->prepare("INSERT INTO metadado_campos (tipo_documento_id, identificador, rotulo, conteudo, largura, mascara, obrigatorio, ordem) VALUES (:tipo_documento_id, :identificador, :rotulo, :conteudo, :largura, :mascara, :obrigatorio, :ordem)");
                $stmt->execute($dados_campo);
                $ids_dos_campos_enviados[] = $pdo->lastInsertId();
            } else {
                $dados_campo['id'] = (int)$campo_id;
                $stmt = $pdo->prepare("UPDATE metadado_campos SET identificador = :identificador, rotulo = :rotulo, conteudo = :conteudo, largura = :largura, mascara = :mascara, obrigatorio = :obrigatorio, ordem = :ordem WHERE id = :id AND tipo_documento_id = :tipo_documento_id");
                $stmt->execute($dados_campo);
                $ids_dos_campos_enviados[] = (int)$campo_id;
            }
        }
    }

    // Remove os campos que foram deletados da tela
    if (!empty($ids_dos_campos_enviados)) {
        $placeholders = implode(',', array_fill(0, count($ids_dos_campos_enviados), '?'));
        $params = array_merge([$tipo_id], $ids_dos_campos_enviados);
        $pdo->prepare("DELETE FROM metadado_campos WHERE tipo_documento_id = ? AND id NOT IN ($placeholders)")->execute($params);
    } else {
        $pdo->prepare("DELETE FROM metadado_campos WHERE tipo_documento_id = ?")->execute([$tipo_id]);
    }

    $pdo->commit();
    $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Tipo de documento atualizado com sucesso!'];

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Erro ao salvar: ' . $e->getMessage()];
    // Em caso de ERRO, volta para a EDIÇÃO
    header('Location: tipos_editar?id=' . $tipo_id);
    exit();
}

// Em sucesso, permanece na EDIÇÃO para continuar trabalhando
header('Location: tipos_editar?id=' . $tipo_id);
exit();
?>

