
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Documento #<?php echo htmlspecialchars($process['id']); ?> - E-Doc</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <a href="index.php">&larr; Voltar ao Painel</a>

    <h2>Detalhes do Documento #<?php echo htmlspecialchars($process['id']); ?></h2>

    <?php if (in_array($_SESSION['user']['role'], ['Diretor', 'Administrador']) && $process['status'] === 'Aprovado'): ?>
        <a href="signature.php?id=<?php echo $process['id']; ?>" class="button" style="float: right;">🖊️ Assinar Documento</a>
    <?php endif; ?>

    <div class="process-details">
        <p><strong>Título:</strong> <?php echo htmlspecialchars($process['title']); ?></p>
        <p><strong>Status Atual:</strong> <span class="<?php echo get_status_class($process['status']); ?>"><?php echo htmlspecialchars($process['status']); ?></span></p>
        <p><strong>Data de Registro:</strong> <?php echo date('d/m/Y H:i', strtotime($process['created_at'])); ?></p>
        <p><strong>Criado por:</strong> <?php echo htmlspecialchars($process['creator_name']); ?></p>
        <?php if ($process['category_id']): ?>
            <p><strong>Categoria:</strong> <?php
                $categories = $document->getCategories();
                foreach ($categories as $cat) {
                    if ($cat['id'] == $process['category_id']) {
                        echo htmlspecialchars($cat['name']);
                        break;
                    }
                }
            ?></p>
        <?php endif; ?>
        <p><strong>Prioridade:</strong>
            <span class="priority-badge priority-<?php echo strtolower($process['priority']); ?>">
                <?php echo htmlspecialchars($process['priority']); ?>
            </span>
        </p>
        <?php if ($process['deadline']): ?>
            <p><strong>Prazo:</strong>
                <span class="<?php echo (strtotime($process['deadline']) < time() && $process['status'] !== 'Aprovado' && $process['status'] !== 'Arquivado') ? 'deadline-overdue' : 'deadline-normal'; ?>">
                    <?php echo date('d/m/Y', strtotime($process['deadline'])); ?>
                </span>
            </p>
        <?php endif; ?>
        <div><strong>Conteúdo do Documento:</strong>
            <pre style="background: #f9f9f9; padding: 1rem; border: 1px solid #eee; white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($process['content']); ?></pre>
        </div>
        <?php
        $attachments = $document->getAttachments($process_id);
        if (!empty($attachments)): ?>
            <div><strong>Anexos:</strong>
                <ul>
                    <?php foreach ($attachments as $attachment): ?>
                        <li><a href="<?php echo htmlspecialchars($attachment['filepath']); ?>" target="_blank"><?php echo htmlspecialchars($attachment['filename']); ?></a> (<?php echo date('d/m/Y H:i', strtotime($attachment['uploaded_at'])); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <!-- Seção de comentários -->
    <div class="comments-section">
        <h3>Comentários</h3>
        <?php
        $comments = $document->getComments($process_id);
        if (!empty($comments)): ?>
            <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-header">
                            <strong><?php echo htmlspecialchars($comment['user_name']); ?></strong>
                            <span class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></span>
                        </div>
                        <div class="comment-content">
                            <?php echo htmlspecialchars($comment['comment']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-comments">Nenhum comentário ainda.</p>
        <?php endif; ?>

        <form action="add_comment.php?id=<?php echo $process_id; ?>" method="POST" class="comment-form">
            <textarea name="comment" placeholder="Adicione um comentário..." required></textarea>
            <button type="submit" class="button">Comentar</button>
        </form>
    </div>

    <!-- Seção de Avaliação -->
    <div class="rating-section">
        <h3>Avaliação do Documento</h3>
        <?php
        $userRating = $document->getRating($process_id, $_SESSION['user']['id']);
        $avgRating = $document->getRating($process_id);
        ?>

        <div class="rating-display">
            <div class="avg-rating">
                <h4>Avaliação Geral</h4>
                <div class="stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?php echo $i <= round($avgRating['avg_rating']) ? 'filled' : ''; ?>">★</span>
                    <?php endfor; ?>
                </div>
                <p><?php echo number_format($avgRating['avg_rating'], 1); ?> estrelas (<?php echo $avgRating['total_ratings']; ?> avaliações)</p>
            </div>

            <div class="user-rating">
                <h4>Sua Avaliação</h4>
                <?php if ($userRating): ?>
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo $i <= $userRating['rating'] ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <?php if ($userRating['review']): ?>
                        <p class="user-review">"<?php echo htmlspecialchars($userRating['review']); ?>"</p>
                    <?php endif; ?>
                    <button onclick="showRatingForm()" class="button button-secondary">Editar Avaliação</button>
                <?php else: ?>
                    <button onclick="showRatingForm()" class="button">Avaliar Documento</button>
                <?php endif; ?>
            </div>
        </div>

        <div id="rating-form" class="rating-form" style="display: none;">
            <form action="add_rating.php?id=<?php echo $process_id; ?>" method="POST">
                <div class="star-rating">
                    <label>Sua avaliação:</label>
                    <div class="stars-input">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" <?php echo ($userRating && $userRating['rating'] == $i) ? 'checked' : ''; ?>>
                            <label for="star<?php echo $i; ?>" class="star-label">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div style="margin-top: 1rem;">
                    <label>Comentário (opcional):</label>
                    <textarea name="review" placeholder="Deixe seu comentário..."><?php echo $userRating['review'] ?? ''; ?></textarea>
                </div>
                <div style="margin-top: 1rem;">
                    <button type="submit" class="button">Salvar Avaliação</button>
                    <button type="button" onclick="hideRatingForm()" class="button button-secondary">Cancelar</button>
                </div>
            </form>
        </div>

        <!-- Outras avaliações -->
        <?php
        $allRatings = $document->getAllRatings($process_id);
        if (!empty($allRatings)): ?>
            <div class="other-ratings">
                <h4>Outras Avaliações</h4>
                <?php foreach ($allRatings as $rating): ?>
                    <div class="rating-item">
                        <div class="rating-header">
                            <strong><?php echo htmlspecialchars($rating['user_name']); ?></strong>
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= $rating['rating'] ? 'filled' : ''; ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-date"><?php echo date('d/m/Y', strtotime($rating['created_at'])); ?></span>
                        </div>
                        <?php if ($rating['review']): ?>
                            <p class="rating-review">"<?php echo htmlspecialchars($rating['review']); ?>"</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Seção de Versionamento -->
    <div class="version-section">
        <h3>Histórico de Versões</h3>
        <?php
        $versions = $document->getVersions($process_id);
        if (!empty($versions)): ?>
            <div class="versions-list">
                <?php foreach ($versions as $version): ?>
                    <div class="version-item <?php echo $version['version_number'] === max(array_column($versions, 'version_number')) ? 'current' : ''; ?>">
                        <div class="version-header">
                            <h4>Versão <?php echo $version['version_number']; ?></h4>
                            <span class="version-date"><?php echo date('d/m/Y H:i', strtotime($version['created_at'])); ?></span>
                            <span class="version-author">por <?php echo htmlspecialchars($version['changed_by_name']); ?></span>
                        </div>
                        <?php if ($version['change_reason']): ?>
                            <p class="version-reason"><strong>Motivo:</strong> <?php echo htmlspecialchars($version['change_reason']); ?></p>
                        <?php endif; ?>
                        <div class="version-actions">
                            <button onclick="showVersionContent(<?php echo $version['id']; ?>)" class="button button-secondary">Ver Conteúdo</button>
                            <?php if ($version['version_number'] !== max(array_column($versions, 'version_number'))): ?>
                                <button onclick="restoreVersion(<?php echo $version['id']; ?>)" class="button button-danger">Restaurar</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-versions">Nenhuma versão anterior encontrada.</p>
        <?php endif; ?>

        <!-- Botão para criar nova versão -->
        <div style="margin-top: 1rem;">
            <button onclick="createNewVersion()" class="button">Criar Nova Versão</button>
        </div>

        <!-- Modal para nova versão -->
        <div id="version-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" onclick="closeVersionModal()">&times;</span>
                <h3>Criar Nova Versão</h3>
                <form action="create_version.php?id=<?php echo $process_id; ?>" method="POST">
                    <div style="margin-bottom: 1rem;">
                        <label>Motivo da alteração:</label>
                        <textarea name="change_reason" placeholder="Descreva as alterações feitas..." required style="width: 100%; min-height: 80px;"></textarea>
                    </div>
                    <button type="submit" class="button">Criar Versão</button>
                    <button type="button" onclick="closeVersionModal()" class="button button-secondary">Cancelar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function showRatingForm() {
        document.getElementById('rating-form').style.display = 'block';
    }

    function hideRatingForm() {
        document.getElementById('rating-form').style.display = 'none';
    }

    function createNewVersion() {
        document.getElementById('version-modal').style.display = 'block';
    }

    function closeVersionModal() {
        document.getElementById('version-modal').style.display = 'none';
    }

    function showVersionContent(versionId) {
        // Implementar visualização de versão específica
        alert('Funcionalidade de visualização de versão será implementada em breve.');
    }

    function restoreVersion(versionId) {
        if (confirm('Tem certeza que deseja restaurar esta versão? Isso criará uma nova versão com o conteúdo antigo.')) {
            // Implementar restauração de versão
            alert('Funcionalidade de restauração de versão será implementada em breve.');
        }
    }

    // Star rating interaction
    document.querySelectorAll('.star-label').forEach(label => {
        label.addEventListener('click', function() {
            const rating = this.getAttribute('for').replace('star', '');
            document.querySelectorAll('.star-label').forEach(s => {
                const starRating = s.getAttribute('for').replace('star', '');
                s.classList.toggle('selected', starRating <= rating);
            });
        });
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('version-modal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    </script>
    </div>

    <div class="actions">
        <h3>Ações de Workflow</h3>
        <form action="ver_processo.php?id=<?php echo $process_id; ?>" method="POST">
            <?php
            $user_role = $_SESSION['user']['role'];
            switch ($process['status']):
                case 'Protocolado':
                    if ($user_role === 'Analista') { ?>
                        <button type="submit" name="new_status" value="Em Revisão" class="button">Enviar para Revisão</button>
                    <?php } else { ?>
                        <p>Aguardando ação do Analista.</p>
                    <?php }
                    break;

                case 'Em Revisão':
                    if ($user_role === 'Analista') { ?>
                        <button type="submit" name="new_status" value="Aguardando Aprovação" class="button">Enviar para Aprovação</button>
                        <button type="submit" name="new_status" value="Reprovado" class="button button-danger">Reprovar</button>
                    <?php } else { ?>
                        <p>Aguardando ação do Analista.</p>
                    <?php }
                    break;

                case 'Aguardando Aprovação':
                    if ($user_role === 'Diretor') { ?>
                        <button type="submit" name="new_status" value="Em Análise" class="button">Enviar para Análise</button>
                        <button type="submit" name="new_status" value="Reprovado" class="button button-danger">Reprovar</button>
                    <?php } else { ?>
                        <p>Aguardando ação do Diretor.</p>
                    <?php }
                    break;

                case 'Em Análise':
                    if ($user_role === 'Diretor') { ?>
                        <button type="submit" name="new_status" value="Aprovado" class="button button-success">Aprovar</button>
                        <button type="submit" name="new_status" value="Reprovado" class="button button-danger">Reprovar</button>
                    <?php } else { ?>
                        <p>Aguardando ação do Diretor.</p>
                    <?php }
                    break;

                case 'Aprovado':
                    if ($user_role === 'Diretor') { ?>
                        <button type="submit" name="new_status" value="Arquivado" class="button button-secondary">Arquivar</button>
                    <?php } else { ?>
                        <p>Aguardando ação do Diretor.</p>
                    <?php }
                    break;

                default: ?>
                    <p>Nenhuma ação disponível para este status.</p>
            <?php endswitch; ?>
        </form>
    </div>

    <div class="history">
        <h3>Histórico de Movimentação</h3>
        <table>
            <thead>
                <tr><th>Status</th><th>Data</th><th>Responsável</th></tr>
            </thead>
            <tbody>
            <?php
            $history = $document->getHistory($process_id);
            foreach ($history as $item): ?>
                <tr>
                    <td><span class="<?php echo get_status_class($item['new_status']); ?>"><?php echo htmlspecialchars($item['new_status']); ?></span></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($item['changed_at'])); ?></td>
                    <td><?php echo htmlspecialchars($item['changed_by_name']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
