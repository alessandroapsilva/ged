<?php
require_once __DIR__ . '/../vendor/autoload.php';
use thiagoalessio\TesseractOCR\TesseractOCR;

class DocumentoOCR {
    private $pdo;
    private $documento_id;
    private $usuario_id;
    
    public function __construct($pdo, $documento_id, $usuario_id) {
        $this->pdo = $pdo;
        $this->documento_id = $documento_id;
        $this->usuario_id = $usuario_id;
    }
    
    /**
     * Processa OCR em um documento PDF ou imagem
     */
    public function processarOCR($arquivo_path) {
        try {
            // Se for PDF, converte páginas para imagens primeiro
            if (strtolower(pathinfo($arquivo_path, PATHINFO_EXTENSION)) === 'pdf') {
                $textos = $this->processarPDF($arquivo_path);
            } else {
                $textos = [$this->processarImagem($arquivo_path)];
            }
            
            // Salva o texto extraído no banco
            $this->salvarTextoExtraido($textos);
            
            // Indexa o conteúdo para busca
            $this->indexarConteudo($textos);
            
            return true;
        } catch (Exception $e) {
            error_log("Erro no OCR do documento {$this->documento_id}: " . $e->getMessage());
            throw new Exception("Erro ao processar OCR: " . $e->getMessage());
        }
    }
    
    /**
     * Processa OCR em uma imagem
     */
    private function processarImagem($imagem_path) {
        $tesseract = new TesseractOCR($imagem_path);
        $tesseract->lang('por'); // Português
        
        // Configurações para melhorar a precisão
        $tesseract->configFile('pdf'); // Otimizado para PDFs digitalizados
        $tesseract->dpi(300); // Resolução padrão
        
        return $tesseract->run();
    }
    
    /**
     * Processa OCR em um PDF
     */
    private function processarPDF($pdf_path) {
        $textos = [];
        
        // Converte PDF para imagens usando Imagick
        $imagick = new Imagick();
        $imagick->readImage($pdf_path);
        $imagick->setResolution(300, 300);
        
        foreach ($imagick as $i => $pagina) {
            // Converte para PNG para melhor qualidade
            $pagina->setImageFormat('png');
            $temp_path = sys_get_temp_dir() . "/page_{$i}.png";
            $pagina->writeImage($temp_path);
            
            // Processa OCR na página
            $textos[] = $this->processarImagem($temp_path);
            
            // Remove arquivo temporário
            unlink($temp_path);
        }
        
        return $textos;
    }
    
    /**
     * Salva o texto extraído no banco de dados
     */
    private function salvarTextoExtraido($textos) {
        // Primeiro remove qualquer texto existente
        $sql = "DELETE FROM documentos_ocr WHERE documento_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->documento_id]);
        
        // Insere o novo texto
        $sql = "INSERT INTO documentos_ocr (documento_id, pagina, texto, processado_em, processado_por) 
                VALUES (?, ?, ?, NOW(), ?)";
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($textos as $pagina => $texto) {
            $stmt->execute([
                $this->documento_id,
                $pagina + 1,
                $texto,
                $this->usuario_id
            ]);
        }
    }
    
    /**
     * Indexa o conteúdo para busca full-text
     */
    private function indexarConteudo($textos) {
        // Combina todos os textos em um único
        $texto_completo = implode("\n\n", $textos);
        
        // Remove caracteres especiais e formata o texto
        $texto_completo = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $texto_completo);
        $texto_completo = preg_replace('/\s+/', ' ', $texto_completo);
        $texto_completo = trim($texto_completo);
        
        // Atualiza o índice de busca
        $sql = "UPDATE documentos_indice SET 
                texto_completo = ?, 
                atualizado_em = NOW(), 
                atualizado_por = ? 
                WHERE documento_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $texto_completo,
            $this->usuario_id,
            $this->documento_id
        ]);
        
        // Se não existir, cria um novo registro
        if ($stmt->rowCount() == 0) {
            $sql = "INSERT INTO documentos_indice (documento_id, texto_completo, atualizado_em, atualizado_por) 
                    VALUES (?, ?, NOW(), ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $this->documento_id,
                $texto_completo,
                $this->usuario_id
            ]);
        }
    }
    
    /**
     * Busca texto em documentos
     */
    public static function buscarTexto($pdo, $termo) {
        $sql = "SELECT d.*, di.texto_completo,
                MATCH(di.texto_completo) AGAINST(? IN BOOLEAN MODE) as relevancia
                FROM documentos d
                JOIN documentos_indice di ON di.documento_id = d.id
                WHERE MATCH(di.texto_completo) AGAINST(? IN BOOLEAN MODE)
                ORDER BY relevancia DESC
                LIMIT 50";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$termo, $termo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}