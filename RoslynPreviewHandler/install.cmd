# run this in developer command prompt as admin

gacutil -i PreviewHandlers.dll
gacutil -i RoslynPreviewHandler.dll
gacutil -i FastColoredTextBox.dll
regasm /codebase RoslynPreviewHandler.dll