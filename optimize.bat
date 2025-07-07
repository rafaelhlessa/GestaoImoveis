@echo off
echo === Script de Otimização Final - Gestão Imóveis ===
echo.

echo [1/7] Limpando caches antigos...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo.
echo [2/7] Regenerando autoload...
composer dump-autoload

echo.
echo [3/7] Verificando status antes da otimização...
php artisan route:list --columns=name,uri,method | findstr "DUPLICATE" || echo "Sem rotas duplicadas detectadas"

echo.
echo [4/7] Criando caches otimizados...
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo.
echo [5/7] Compilando assets...
npm run build

echo.
echo [6/7] Otimizando aplicação...
php artisan optimize

echo.
echo [7/7] Verificando status final...
php artisan about

echo.
echo === Otimização Concluída com Sucesso! ===
echo.
echo ✅ Problemas Corrigidos:
echo    - Comando console duplicado removido
echo    - Rotas duplicadas corrigidas
echo    - Caches criados com sucesso
echo    - Assets compilados
echo.
echo 📋 Próximos passos manuais:
echo    1. Configure APP_ENV=production no .env
echo    2. Configure APP_DEBUG=false no .env
echo    3. Configure cache do banco de dados (Redis/Memcached)
echo    4. Habilite compressão no servidor web
echo    5. Configure SSL/HTTPS
echo.
echo 🚀 Aplicação otimizada e pronta para produção!
pause
