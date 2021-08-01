using System;
using System.Collections.Generic;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Text;
using System.Text.RegularExpressions;
using System.Windows.Forms;
using FastColoredTextBoxNS;
using PreviewHandlers;

namespace RoslynPreviewHandler
{
    public class RoslynPreviewHostFactory : IPreviewHostFactory
    {
        private static readonly Regex ContentRegex = new Regex("content:(\"([^\"]*)\"|'([^']*)'|[^\\s]*)", RegexOptions.Compiled | RegexOptions.IgnoreCase);
        private static readonly Style HighlightStyle = new TextStyle(null, Brushes.Yellow, FontStyle.Regular);
        private static readonly Style BlueStyle = new TextStyle(Brushes.Blue, null, FontStyle.Regular);

        private static Language GetLanguageForExtension(string extension)
        {
            switch (extension.ToLowerInvariant())
            {
                case ".c":
                case ".cpp":
                case ".h":
                case ".hpp":
                case ".ino":
                case ".rs":
                case ".php":
                    return Language.JS;

                case ".cs":
                    return Language.CSharp;

                case ".vb":
                    return Language.VB;

                case ".html":
                case ".cshtml":
                case ".xaml":
                    return Language.HTML;

                case ".sql":
                    return Language.SQL;

                case ".xml":
                case ".config":
                case ".csproj":
                case ".sln":
                    return Language.XML;

                case ".js":
                case ".ts":
                case ".json":
                case ".bim":
                case ".ps1":
                case ".cmd":
                    return Language.JS;

                default:
                    return Language.Custom;
            }
        }

        private static void Control_Keydown(object sender, KeyEventArgs e)
        {
            if (e.KeyCode == Keys.F4 && e.Alt)
            {
                e.Handled = true;
            }
        }

        private static void UpdateVisibleRange(FastColoredTextBox fctb, Language language, IEnumerable<string> wordsToHighlight)
        {
            const int margin = 1000;

            var visibleLineStart = fctb.VisibleRange.Start.iLine;
            var visibleLineEnd = fctb.VisibleRange.End.iLine;
            var totalLines = fctb.LinesCount - 1;

            var rangeLineStart = Math.Max(visibleLineStart - margin, 0);
            var rangeLineEnd = Math.Min(visibleLineEnd + margin, totalLines);
            var range = new Range(fctb, 0, rangeLineStart, 0, rangeLineEnd);

            foreach (var word in wordsToHighlight)
                range.SetStyle(HighlightStyle, new Regex(word, RegexOptions.Compiled | RegexOptions.IgnoreCase));

            if (language == Language.CSharp)
                range.SetStyle(BlueStyle, "async|await");

            fctb.SyntaxHighlighter.HighlightSyntax(language, range);
        }

        public Control Load(FileInfo fileInfo)
        {
            var language = GetLanguageForExtension(fileInfo.Extension);

            var fctb = new FastColoredTextBox()
            {
                Dock = DockStyle.Fill,
                Language = language,
                ReadOnly = true
            };

            var windowTitle = WindowHelpers.GetActiveWindowTitle();
            var wordsToHighlight = Enumerable.Empty<string>();

            if (windowTitle != null)
            {
                var matches = ContentRegex.Matches(windowTitle);

                wordsToHighlight =
                    (from x in matches.Cast<Match>()
                     let l = x.Groups.Cast<Group>().Last(a => !string.IsNullOrEmpty(a.Value))
                     select l.Value).ToArray();
            }

            fctb.VisibleRangeChangedDelayed += (s, e) => UpdateVisibleRange(fctb, language, wordsToHighlight);
            fctb.TextChangedDelayed += (s, e) => UpdateVisibleRange(fctb, language, wordsToHighlight);

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
    }
}