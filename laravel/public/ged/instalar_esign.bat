@echo off
echo === Instalando Dependencias do eSign ===
echo.

cd /d C:\xampp\htdocs\ged

echo [1/2] Instalando dependencias Composer (FPDI/TCPDF)...
composer install --no-interaction --prefer-dist
if %errorlevel% neq 0 (
    echo ERRO: Falha ao instalar dependencias do Composer
    echo Verifique se o Composer esta instalado: composer --version
    pause
    exit /b 1
)

echo.
echo [2/2] Verificando instalacao...
php scripts\install_assinaturas.php

echo.
echo === Instalacao Concluida ===
pause
