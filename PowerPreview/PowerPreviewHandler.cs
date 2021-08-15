using FastColoredTextBoxNS;
using PreviewHandlers;
using System;
using System.Collections.Generic;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Reflection;
using System.Runtime.InteropServices;
using System.Text;
using System.Text.RegularExpressions;
using System.Windows.Forms;

namespace PowerPreview
{
    [PreviewHandler("Power Preview Handler", ".cs;.vb;.html;.sql;.xml;.config;.csproj;.js;.json;.bim;.sln;.c;.cpp;.h;.ahk;.hpp;.cshtml;.ts;.ps1;.xaml;.cmd;.ino;.rs;.php;.bat;.pas;.razor", "{29AFE73A-29FD-426D-A87E-8FF8315BFF2D}")]
    [ProgId("PowerPreview.PowerPreviewHandler")]
    [Guid("DB9FC691-1525-4D40-B758-55EF949290A2")]
    [ClassInterface(ClassInterfaceType.None)]
    [ComVisible(true)]
    public class PowerPreviewHandler : FileBasedPreviewHandler<PowerPreviewHandler.PowerPreview>
    {
        public class PowerPreview : FileBasedPreviewHandlerControl
        {
            private static readonly Regex ContentRegex = new Regex("content:(\"([^\"]*)\"|'([^']*)'|[^\\s]*)", RegexOptions.Compiled | RegexOptions.IgnoreCase);
            private static readonly Style HighlightStyle = new TextStyle(null, Brushes.Yellow, FontStyle.Regular);
            private static readonly Style BlueStyle = new TextStyle(Brushes.Blue, null, FontStyle.Regular);

            private static int NumberOfFilesPreviewed = 0;

            public static string GetActiveWindowTitle()
            {
                const int maxLength = 256;
                var stringBuilder = new StringBuilder(maxLength);
                var handle = User32.GetForegroundWindow();

                if (User32.GetWindowText(handle, stringBuilder, maxLength) > 0)
                {
                    return stringBuilder.ToString();
                }

                return null;
            }

            private static Language GetLanguageForExtension(string extension)
            {
                switch (extension.ToLowerInvariant())
                {
                    case ".c":
                    case ".cpp":
                    case ".h":
                    case ".hpp":
                    case ".ino":
                    case ".js":
                    case ".ts":
                    case ".json":
                    case ".bim":
                    case ".ps1":
                    case ".cmd":
                    case ".bat":
                        return Language.JS;

                    case ".php":
                        return Language.PHP;

                    case ".cs":
                        return Language.CSharp;

                    case ".vb":
                    case ".rs":
                    case ".pas":
                        return Language.VB;

                    case ".html":
                    case ".cshtml":
                    case ".xaml":
                    case ".css":
                    case ".razor":
                        return Language.HTML;

                    case ".sql":
                        return Language.SQL;

                    case ".xml":
                    case ".config":
                    case ".csproj":
                    case ".sqlproj":
                    case ".vbproj":
                    case ".sln":
                        return Language.XML;

                    default:
                        return Language.Custom;
                }
            }

            private static void UpdateVisibleRange(FastColoredTextBox fctb, Language language, IEnumerable<string> wordsToHighlight)
            {
                var range = fctb.VisibleRange;

                foreach (var word in wordsToHighlight)
                    range.SetStyle(HighlightStyle, new Regex(word, RegexOptions.Compiled | RegexOptions.IgnoreCase));

                if (language == Language.CSharp)
                    range.SetStyle(BlueStyle, "async|await");

                fctb.SyntaxHighlighter.HighlightSyntax(language, range);
            }

            public Control LoadControl(FileInfo fileInfo)
            {
                NumberOfFilesPreviewed++;

                var language = GetLanguageForExtension(fileInfo.Extension);

                var fctb = new FastColoredTextBox()
                {
                    Dock = DockStyle.Fill,
                    Language = language,
                    ReadOnly = true,
                    Font = new Font("Cascadia Code", 8),
                    DefaultMarkerSize = 10,
                };

                var wordsToHighlight = Enumerable.Empty<string>();

                var windowTitle = GetActiveWindowTitle();
                if (windowTitle != null)
                {
                    var matches = ContentRegex.Matches(windowTitle);

                    wordsToHighlight =
                        (from x in matches.Cast<Match>()
                         let l = x.Groups.Cast<Group>().Last(a => !string.IsNullOrEmpty(a.Value))
                         select l.Value).ToArray();

                    fctb.VisibleRangeChangedDelayed += (s, e) => UpdateVisibleRange(fctb, language, wordsToHighlight);
                    fctb.TextChangedDelayed += (s, e) => UpdateVisibleRange(fctb, language, wordsToHighlight);
                }

                if (fileInfo.Length < 25 * 1024)
                {
                    fctb.OpenFile(fileInfo.FullName, Encoding.UTF8);
                }
                else
                {
                    fctb.OpenBindingFile(fileInfo.FullName, Encoding.UTF8);
                }

                return fctb;
            }

            public override void Preview(FileInfo fileInfo)
            {
                try
                {
                    var control = LoadControl(fileInfo);
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
        }
    }
}