<?php
require_once '../core/init.php';

// Proteção da página: Apenas usuários com a permissão 'admin.access' podem entrar.
if (!isset($_SESSION['user_id']) || !usuario_tem_permissao('admin.access')) {
    header('Location: documentos.php'); // Redireciona para a página principal se não tiver permissão
    exit();
}

// Lógica para SALVAR as configurações quando o formulário é enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE configuracoes SET config_valor = :valor WHERE config_chave = :chave");
        
        // Loop através de todos os dados enviados pelo formulário
        foreach ($_POST as $chave => $valor) {
            // Verifica se a chave existe antes de tentar atualizar para segurança
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM configuracoes WHERE config_chave = ?");
            $check_stmt->execute([$chave]);
            if ($check_stmt->fetchColumn() > 0) {
                $stmt->execute([':valor' => trim($valor), ':chave' => $chave]);
            }
        }
        
        $pdo->commit();
        registrar_log($pdo, $_SESSION['user_id'], 'Atualizou as configurações do sistema.');
        header('Location: configuracoes.php?sucesso=1');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        // Em um ambiente de produção, logar o erro: error_log($e->getMessage());
        header('Location: configuracoes.php?erro=1');
        exit();
    }
}

// Lógica para CARREGAR as configurações atuais do banco para preencher o formulário
$configs_stmt = $pdo->query("SELECT * FROM configuracoes");
$configs = $configs_stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Cria um array ['chave' => 'valor']

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Configurações</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-check"></i> Sucesso!</h5>
                    Configurações salvas com sucesso!
                </div>
            <?php elseif (isset($_GET['erro'])): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Erro!</h5>
                    Ocorreu um erro ao salvar as configurações. Tente novamente.
                </div>
            <?php endif; ?>

            <form method="post" action="configuracoes.php">
                <div class="card card-dark card-outline collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title">eDok API</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="card-body" style="display: none;">
                        <p class="text-muted">Parâmetros de operação das APIs do eDok.</p>
                        <div class="form-group">
                            <label for="API_CHAVE_ACESSO">Chave de Acesso</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="API_CHAVE_ACESSO" name="API_CHAVE_ACESSO" value="<?php echo htmlspecialchars($configs['API_CHAVE_ACESSO'] ?? ''); ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" title="Copiar" onclick="navigator.clipboard.writeText(document.getElementById('API_CHAVE_ACESSO').value)"><i class="fas fa-copy"></i></button>
                                    <button class="btn btn-outline-secondary" type="button" title="Gerar Nova Chave (em breve)" disabled><i class="fas fa-sync-alt"></i></button>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <h5>Campos da API de Visualização</h5>
                        <p class="text-muted">Estes são os identificadores utilizados pela API ViewDocs para buscar e agrupar documentos.</p>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="API_ID_PRIMARIO">Primeiro Identificador</label>
                                <input type="text" class="form-control" id="API_ID_PRIMARIO" name="API_ID_PRIMARIO" value="<?php echo htmlspecialchars($configs['API_ID_PRIMARIO'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="API_ROTULO_PRIMARIO">Rótulo do Primeiro Identificador</label>
                                <input type="text" class="form-control" id="API_ROTULO_PRIMARIO" name="API_ROTULO_PRIMARIO" value="<?php echo htmlspecialchars($configs['API_ROTULO_PRIMARIO'] ?? ''); ?>">
                            </div>
                        </div>
                         <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="API_ID_SECUNDARIO">Segundo Identificador</label>
                                <input type="text" class="form-control" id="API_ID_SECUNDARIO" name="API_ID_SECUNDARIO" value="<?php echo htmlspecialchars($configs['API_ID_SECUNDARIO'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="API_ROTULO_SECUNDARIO">Rótulo do Segundo Identificador</label>
                                <input type="text" class="form-control" id="API_ROTULO_SECUNDARIO" name="API_ROTULO_SECUNDARIO" value="<?php echo htmlspecialchars($configs['API_ROTULO_SECUNDARIO'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-dark card-outline collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title">Recursos de Assinaturas Digitais</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="card-body" style="display: none;">
                        <p class="text-muted">Para obter assinaturas de longo prazo (LTV), um carimbo de tempo e a validação de certificados são obrigatórios.</p>
                        <div class="form-group">
                            <label>Endereço da ACT (Timestamp)</label>
                            <input type="text" class="form-control" value="http://timestamp.sectigo.com" disabled>
                             <small class="form-text text-muted">Endereço de carimbo de tempo padrão do mercado.</small>
                        </div>
                    </div>
                </div>

                <div class="card card-dark card-outline collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title">Robô de Captura (Ingest)</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="card-body" style="display: none;">
                        <p class="text-muted">Configure a pasta que será monitorada pelo robô para a captura automática de novos documentos.</p>
                        <div class="form-group">
                            <label for="INGEST_PASTA_MONITORADA">Caminho da Pasta a ser Monitorada</label>
                            <input type="text" class="form-control" id="INGEST_PASTA_MONITORADA" name="INGEST_PASTA_MONITORADA" value="<?php echo htmlspecialchars($configs['INGEST_PASTA_MONITORADA'] ?? ''); ?>">
                             <small class="form-text text-muted">Exemplo: <strong>C:/xampp/htdocs/ged/entrada</strong> ou <strong>/var/www/ged/entrada</strong> no Linux. O servidor precisa ter permissão de leitura e escrita nesta pasta.</small>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success float-right"><i class="fas fa-save mr-2"></i>Salvar Configurações</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>