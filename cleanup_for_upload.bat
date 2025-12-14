@echo off
echo Cleaning up for production upload...

REM Remove development files
del /f /q .env
del /f /q composer.lock
del /f /q package-lock.json

REM Remove cache and logs
rmdir /s /q storage\logs
rmdir /s /q storage\framework\cache
rmdir /s /q storage\framework\sessions
rmdir /s /q storage\framework\views
rmdir /s /q bootstrap\cache

REM Remove node_modules if exists
if exist node_modules rmdir /s /q node_modules

REM Remove development tools
if exist .git rmdir /s /q .git

REM Copy production env
copy .env.production .env

echo Cleanup completed! Ready for zip upload.
pause