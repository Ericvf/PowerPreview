# run this in developer command prompt as admin

gacutil -u PreviewHandlers.dll
gacutil -u RoslynPreviewHandler.dll
gacutil -u FastColoredTextBox.dll
regasm /unregister RoslynPreviewHandler.dll