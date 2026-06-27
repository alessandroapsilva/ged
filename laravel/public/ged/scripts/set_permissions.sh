#!/bin/bash

# Diretórios que precisam de permissão de escrita
WRITE_DIRS=(
    "storage"
    "storage/documentos"
    "storage/temp"
    "storage/logs"
)

# Define permissões
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Ajusta permissões especiais para diretórios de escrita
for dir in "${WRITE_DIRS[@]}"
do
    chmod -R 775 public/$dir
done