<?php
// public/cron_alertas_vencimento.php
// Script para enviar alertas de documentos próximos ao vencimento ou vencidos
// Executar via cron diariamente: php public/cron_alertas_vencimento.php

require_once __DIR__ . '/../core/init.php';

// Log de execução
$log_file = __DIR__ . '/../logs/alertas_vencimento.log';
@mkdir(__DIR__ . '/../logs', 0755, true);

function logMsg($msg) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[{$timestamp}] {$msg}\n", FILE_APPEND);
}

logMsg("=== Iniciando verificação de vencimentos ===");

try {
    // 1. Buscar documentos a vencer nos próximos 7 dias
    $stmt_a_vencer = $pdo->query("
        SELECT d.id, d.titulo, d.data_vencimento, u.nome as proprietario, u.email
        FROM documentos d
        JOIN usuarios u ON d.usuario_id = u.id
        WHERE d.apagado_em IS NULL
          AND d.data_vencimento IS NOT NULL
          AND d.data_vencimento >= CURDATE()
          AND d.data_vencimento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY d.data_vencimento ASC
    ");
    $a_vencer = $stmt_a_vencer->fetchAll(PDO::FETCH_ASSOC);

    // 2. Buscar documentos vencidos nos últimos 7 dias
    $stmt_vencidos = $pdo->query("
        SELECT d.id, d.titulo, d.data_vencimento, u.nome as proprietario, u.email
        FROM documentos d
        JOIN usuarios u ON d.usuario_id = u.id
        WHERE d.apagado_em IS NULL
          AND d.data_vencimento IS NOT NULL
          AND d.data_vencimento < CURDATE()
          AND d.data_vencimento >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY d.data_vencimento DESC
    ");
    $vencidos = $stmt_vencidos->fetchAll(PDO::FETCH_ASSOC);

    logMsg("Encontrados: " . count($a_vencer) . " a vencer, " . count($vencidos) . " vencidos recentemente");

    // 3. Enviar notificações internas (workflow_notificacoes)
    $stmt_notif = $pdo->prepare("
        INSERT INTO workflow_notificacoes (usuario_id, tipo, mensagem, data_envio)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE data_envio = NOW()
    ");

    foreach ($a_vencer as $doc) {
        $dias = floor((strtotime($doc['data_vencimento']) - time()) / 86400);
        $msg = "⚠️ Documento '{$doc['titulo']}' vence em {$dias} dia(s) ({$doc['data_vencimento']})";
        
        // Notificação interna
        try {
            $stmt_usuario = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
            $stmt_usuario->execute([$doc['email']]);
            $user_id = $stmt_usuario->fetchColumn();
            if ($user_id) {
                $stmt_notif->execute([$user_id, 'vencimento_proximo', $msg]);
            }
        } catch (Exception $e) {
            logMsg("Erro ao criar notificação para {$doc['email']}: " . $e->getMessage());
        }

        // E-mail (se configurado)
        if (function_exists('enviar_email_sistema')) {
            try {
                enviar_email_sistema(
                    $doc['email'],
                    'Alerta: Documento próximo ao vencimento',
                    "Olá {$doc['proprietario']},\n\n{$msg}\n\nAcesse: " . BASE_URL . "/public/documentos_propriedades.php?id={$doc['id']}\n\nGED - Sistema de Gestão Eletrônica de Documentos"
                );
                logMsg("E-mail enviado para {$doc['email']} (doc {$doc['id']})");
            } catch (Exception $e) {
                logMsg("Erro ao enviar e-mail para {$doc['email']}: " . $e->getMessage());
            }
        }
    }

    foreach ($vencidos as $doc) {
        $dias = floor((time() - strtotime($doc['data_vencimento'])) / 86400);
        $msg = "🔴 Documento '{$doc['titulo']}' venceu há {$dias} dia(s) ({$doc['data_vencimento']})";
        
        // Notificação interna
        try {
            $stmt_usuario = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
            $stmt_usuario->execute([$doc['email']]);
            $user_id = $stmt_usuario->fetchColumn();
            if ($user_id) {
                $stmt_notif->execute([$user_id, 'vencimento_vencido', $msg]);
            }
        } catch (Exception $e) {
            logMsg("Erro ao criar notificação para {$doc['email']}: " . $e->getMessage());
        }

        // E-mail (se configurado)
        if (function_exists('enviar_email_sistema')) {
            try {
                enviar_email_sistema(
                    $doc['email'],
                    'URGENTE: Documento vencido',
                    "Olá {$doc['proprietario']},\n\n{$msg}\n\nPor favor, tome as providências necessárias.\n\nAcesse: " . BASE_URL . "/public/documentos_propriedades.php?id={$doc['id']}\n\nGED - Sistema de Gestão Eletrônica de Documentos"
                );
                logMsg("E-mail de vencimento enviado para {$doc['email']} (doc {$doc['id']})");
            } catch (Exception $e) {
                logMsg("Erro ao enviar e-mail para {$doc['email']}: " . $e->getMessage());
            }
        }
    }

    logMsg("Alertas processados com sucesso!");
    echo "✓ Alertas de vencimento processados.\n";
    echo "  - A vencer (7d): " . count($a_vencer) . "\n";
    echo "  - Vencidos (7d): " . count($vencidos) . "\n";
    echo "  - Log: {$log_file}\n";

} catch (Exception $e) {
    logMsg("ERRO: " . $e->getMessage());
    echo "✗ Erro ao processar alertas: " . $e->getMessage() . "\n";
    exit(1);
}

logMsg("=== Execução finalizada ===\n");
exit(0);
