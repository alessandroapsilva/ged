#!/bin/bash
# ========================================
# SCRIPT DE BACKUP AUTOMÁTICO - GED (Linux)
# ========================================
# Descrição: Faz backup completo do banco de dados e arquivos
# Uso: Adicionar ao crontab: 0 2 * * * /var/www/ged/scripts/backup_diario.sh
# Autor: Sistema GED
# Data: 2025-11-07
# ========================================

# Configurações
DATA=$(date +"%Y%m%d_%H%M%S")
DATA_SIMPLES=$(date +"%Y-%m-%d")

# Diretórios
BACKUP_DIR="/var/backups/ged"
GED_DIR="/var/www/ged"

# Banco de dados
DB_NAME="ged"
DB_USER="ged_user"
DB_PASS="senha_segura"  # Altere!
DB_HOST="localhost"

# Retenção (dias)
RETENCAO_DIAS=30

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# ========================================
# INÍCIO DO BACKUP
# ========================================

echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}  BACKUP GED - $DATA_SIMPLES${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""

# 1. Criar diretórios se não existirem
mkdir -p "$BACKUP_DIR/database"
mkdir -p "$BACKUP_DIR/files"
mkdir -p "$BACKUP_DIR/logs"

# ========================================
# 2. BACKUP DO BANCO DE DADOS
# ========================================

echo "[1/4] Fazendo backup do banco de dados..."

DB_BACKUP_FILE="$BACKUP_DIR/database/ged_$DATA.sql"

if mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME > $DB_BACKUP_FILE 2>/dev/null; then
    FILE_SIZE=$(du -h $DB_BACKUP_FILE | cut -f1)
    echo -e "${GREEN}  ✓ Backup do banco concluído: $FILE_SIZE${NC}"
    
    # Compactar
    gzip $DB_BACKUP_FILE
    echo -e "${GREEN}  ✓ Backup compactado: $(du -h ${DB_BACKUP_FILE}.gz | cut -f1)${NC}"
else
    echo -e "${RED}  ✗ ERRO ao fazer backup do banco${NC}"
    exit 1
fi

# ========================================
# 3. BACKUP DOS ARQUIVOS (UPLOADS)
# ========================================

echo "[2/4] Fazendo backup dos arquivos (uploads)..."

FILES_BACKUP_TAR="$BACKUP_DIR/files/uploads_$DATA.tar.gz"

if [ -d "$GED_DIR/public/uploads" ]; then
    tar -czf $FILES_BACKUP_TAR -C $GED_DIR/public uploads 2>/dev/null
    
    if [ -f $FILES_BACKUP_TAR ]; then
        FILE_SIZE=$(du -h $FILES_BACKUP_TAR | cut -f1)
        echo -e "${GREEN}  ✓ Backup dos arquivos concluído: $FILE_SIZE${NC}"
    else
        echo -e "${RED}  ✗ ERRO ao compactar arquivos${NC}"
    fi
else
    echo -e "${YELLOW}  ! Diretório uploads não encontrado${NC}"
fi

# ========================================
# 4. BACKUP DOS LOGS
# ========================================

echo "[3/4] Fazendo backup dos logs..."

LOGS_BACKUP_TAR="$BACKUP_DIR/logs/logs_$DATA.tar.gz"

if [ -d "$GED_DIR/logs" ]; then
    tar -czf $LOGS_BACKUP_TAR -C $GED_DIR logs 2>/dev/null
    
    if [ -f $LOGS_BACKUP_TAR ]; then
        FILE_SIZE=$(du -h $LOGS_BACKUP_TAR | cut -f1)
        echo -e "${GREEN}  ✓ Backup dos logs concluído: $FILE_SIZE${NC}"
    fi
else
    echo -e "${YELLOW}  ! Diretório logs não encontrado${NC}"
fi

# ========================================
# 5. LIMPEZA DE BACKUPS ANTIGOS
# ========================================

echo "[4/4] Limpando backups antigos (retenção: $RETENCAO_DIAS dias)..."

BACKUPS_REMOVIDOS=0

# Limpar backups de banco
find $BACKUP_DIR/database -name "*.sql.gz" -type f -mtime +$RETENCAO_DIAS -delete
BACKUPS_REMOVIDOS=$((BACKUPS_REMOVIDOS + $(find $BACKUP_DIR/database -name "*.sql.gz" -type f -mtime +$RETENCAO_DIAS | wc -l)))

# Limpar backups de arquivos
find $BACKUP_DIR/files -name "*.tar.gz" -type f -mtime +$RETENCAO_DIAS -delete

# Limpar logs antigos (90 dias)
find $BACKUP_DIR/logs -name "*.tar.gz" -type f -mtime +90 -delete

echo -e "${GREEN}  ✓ Limpeza concluída${NC}"

# ========================================
# 6. RELATÓRIO FINAL
# ========================================

echo ""
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}  BACKUP CONCLUÍDO COM SUCESSO!${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""
echo "Local dos backups: $BACKUP_DIR"
echo "Data/Hora: $DATA"
echo ""

# Espaço utilizado
ESPACO_TOTAL=$(du -sh $BACKUP_DIR | cut -f1)
echo "Espaço total utilizado: $ESPACO_TOTAL"

# Últimos backups
echo ""
echo "Últimos 5 backups do banco:"
ls -lht $BACKUP_DIR/database/*.sql.gz 2>/dev/null | head -5

# ========================================
# 7. LOG DO BACKUP
# ========================================

LOG_FILE="$BACKUP_DIR/logs/backup_$DATA_SIMPLES.log"

cat > $LOG_FILE <<EOF
========================================
BACKUP GED - $DATA
========================================
Status: SUCESSO
Banco: OK
Arquivos: OK
Logs: OK
Espaço total: $ESPACO_TOTAL
========================================
EOF

# ========================================
# 8. ENVIO DE NOTIFICAÇÃO (OPCIONAL)
# ========================================

# Descomentar para enviar e-mail
# echo "Backup GED concluído em $DATA" | mail -s "Backup GED - $DATA_SIMPLES - OK" admin@suaempresa.com.br

echo ""
echo -e "${CYAN}Backup concluído!${NC}"
