@echo off
cls

echo.
echo [101;93mUninstalling... [0m
echo.
gacutil -u .\PreviewHandlers.dll
gacutil -u .\RoslynPreviewHandler.dll
regasm /unregister .\RoslynPreviewHandler.dll

pause

echo.
echo [101;93mInstalling... [0m
echo.
gacutil -i .\PreviewHandlers.dll
gacutil -i .\RoslynPreviewHandler.dll
regasm /codebase .\RoslynPreviewHandler.dll

echo [7mDone, you may close this window [0m