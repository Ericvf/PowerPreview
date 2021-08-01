using System;
using System.IO;
using System.Reflection;
using System.Runtime.InteropServices;
using System.Text;
using System.Windows.Forms;
using PreviewHandlers;

namespace RoslynPreviewHandler
{
    [PreviewHandler("Roslyn Code Preview Handler", ".cs;.vb;.html;.sql;.xml;.config;.csproj;.js;.json;.bim;.sln;.c;.cpp;.h;.ahk;.hpp;.cshtml;.ts;.ps1;.xaml;.cmd;.ino;.rs;.php", "{29AFE73A-29FD-426D-A87E-8FF8315BFF2D}")]
    [ProgId("Roslyn.RoslynPreviewHandler")]
    [Guid("DB9FC691-1525-4D40-B758-55EF949290A2")]
    [ClassInterface(ClassInterfaceType.None)]
    [ComVisible(true)]
    public sealed class RoslynPreviewHandler : FileBasedPreviewHandler
    {
        protected override PreviewHandlerControl CreatePreviewHandlerControl()
        {
            return new RoslynPreviewHandlerControl(new RoslynPreviewHostFactory());
        }

        private sealed class RoslynPreviewHandlerControl : FileBasedPreviewHandlerControl
        {
            private readonly IPreviewHostFactory _previewHostFactory;

            public RoslynPreviewHandlerControl(IPreviewHostFactory previewHostFactory)
            {
                _previewHostFactory = previewHostFactory;
            }

            public override void Load(FileInfo fileInfo)
            {
                try
                {
                    var control = _previewHostFactory.Load(fileInfo);
                    Controls.Add(control);
                }
                catch (ReflectionTypeLoadException reflectionTypeLoadException)
                {
                    var errorMessage = BuildLoaderExceptionMessage(reflectionTypeLoadException);
                    ShowMessageInControl(errorMessage);
                }
                catch (Exception exception)
                {
                    ShowMessageInControl(exception.ToString());
                }
            }

            private static string BuildLoaderExceptionMessage(ReflectionTypeLoadException ex)
            {
                var stringBuilder = new StringBuilder();

                foreach (var loaderException in ex.LoaderExceptions)
                {
                    stringBuilder.AppendLine(loaderException.Message);

                    if (loaderException is FileNotFoundException fileNotFoundException)
                    {
                        if (!string.IsNullOrEmpty(fileNotFoundException.FusionLog))
                        {
                            stringBuilder.AppendLine("Fusion Log:");
                            stringBuilder.AppendLine(fileNotFoundException.FusionLog);
                        }
                    }

                    stringBuilder.AppendLine();
                }

                return stringBuilder.ToString();
            }

            private void ShowMessageInControl(string errorMessage)
            {
                var textBox = new TextBox
                {
                    Dock = DockStyle.Fill,
                    ReadOnly = true,
                    Multiline = true,
                    Text = errorMessage
                };

                Controls.Add(textBox);
            }
        }
    }
}