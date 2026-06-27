<?php
require_once '../core/init.php';
require_once '../core/documento_ocr.php';

// Verifica se usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$termo = isset($_GET['q']) ? trim($_GET['q']) : '';
$resultados = [];

if ($termo) {
    $resultados = DocumentoOCR::buscarTexto($pdo, $termo);
}

include '../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Busca Avançada</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" 
                                   value="<?php echo htmlspecialchars($termo); ?>" 
                                   placeholder="Digite o texto para buscar nos documentos...">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            A busca é realizada no conteúdo dos documentos processados por OCR.
                            Use aspas para buscar frases exatas, ex: "contrato de prestação"
                        </small>
                    </form>

                    <?php if ($termo): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Documento</th>
                                        <th>Tipo</th>
                                        <th>Data</th>
                                        <th>Relevância</th>
                                        <th>Trecho</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resultados as $doc): 
                                        // Extrai um trecho do texto com o termo buscado
                                        $texto = $doc['texto_completo'];
                                        $pos = stripos($texto, $termo);
                                        if ($pos !== false) {
                                            $inicio = max(0, $pos - 100);
                                            $trecho = substr($texto, $inicio, 200);
                                            if ($inicio > 0) $trecho = '...' . $trecho;
                                            if (strlen($texto) > ($inicio + 200)) $trecho .= '...';
                                            
                                            // Destaca o termo buscado
                                            $trecho = preg_replace('/(' . preg_quote($termo, '/') . ')/i', '<mark>$1</mark>', $trecho);
                                        } else {
                                            $trecho = "Termo encontrado no documento...";
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doc['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['tipo']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($doc['data_upload'])); ?></td>
                                        <td>
                                            <?php 
                                            $relevancia = round($doc['relevancia'] * 100);
                                            echo "<div class='progress'>
                                                    <div class='progress-bar' role='progressbar' 
                                                         style='width: {$relevancia}%' 
                                                         aria-valuenow='{$relevancia}' 
                                                         aria-valuemin='0' 
                                                         aria-valuemax='100'>
                                                        {$relevancia}%
                                                    </div>
                                                  </div>";
                                            ?>
                                        </td>
                                        <td><?php echo $trecho; ?></td>
                                        <td>
                                            <a href="documentos_ver.php?id=<?php echo $doc['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="documentos_download.php?id=<?php echo $doc['id']; ?>" 
                                               class="btn btn-sm btn-success" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($resultados)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            Nenhum documento encontrado com o termo "<?php echo htmlspecialchars($termo); ?>"
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../templates/footer.php'; ?>