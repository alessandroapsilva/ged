<?php
// public/tipos_salvar.php (MODO DE DEPURAÇÃO PARA EXIBIR O ERRO)
require_once '../core/init.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

// ### MUDANÇA IMPORTANTE: Envolvemos tudo em um try...catch para exibir o erro ###
try {
    $tipo_id = isset($_POST['tipo_id']) ? (int)$_POST['tipo_id'] : 0;
    if ($tipo_id <= 0) {
        throw new Exception("ID de tipo de documento inválido.");
    }

    $pdo->beginTransaction();

    // 1. Atualiza as informações gerais
    $stmt_update_tipo = $pdo->prepare(
        "UPDATE tipos_documento SET nome = ?, pasta_destino = ?, codigo = ?, separador = ?, restrito = ?, palavras_chave = ? WHERE id = ?"
    );
    $stmt_update_tipo->execute([
        $_POST['nome'] ?? 'Sem Nome',
        $_POST['pasta_destino'] ?? null,
        $_POST['codigo'] ?? null,
        $_POST['separador'] ?? '-',
        isset($_POST['restrito']) ? 1 : 0,
        $_POST['palavras_chave'] ?? null,
        $tipo_id
    ]);

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
    header('Location: tipos_editar.php?id=' . $tipo_id);
    exit();

} catch (Exception $e) {
    // ### MUDANÇA PRINCIPAL: EM VEZ DE REDIRECIONAR, MOSTRAMOS O ERRO NA TELA ###
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Mostra o erro detalhado em vez de esconder
    die('<h1>Erro Detalhado Capturado:</h1><pre>' . $e->getMessage() . '</pre>');
}