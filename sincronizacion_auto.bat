@echo off
REM Script para ejecutar sincronización automática cada 5 minutos
REM Guardar como: sincronizacion_auto.bat

:loop
echo [%date% %time%] Ejecutando sincronizacion...
php "c:\xampp\htdocs\DB_lycaios\sincronizar_ordenes.php"
echo [%date% %time%] Sincronizacion completada. Esperando 60 Segundos...
timeout /t 60 /nobreak
goto loop
