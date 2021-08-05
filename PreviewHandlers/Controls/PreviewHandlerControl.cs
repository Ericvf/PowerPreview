using System;
using System.IO;
using System.Reflection;
using System.Text;
using System.Windows.Forms;

namespace PreviewHandlers
{
    public abstract class PreviewHandlerControl : Form
    {
        protected PreviewHandlerControl()
        {
            base.FormBorderStyle = FormBorderStyle.None;
            this.SetTopLevel(false);
        }

        public abstract void Preview(FileInfo file);

        public abstract void Preview(Stream stream);

        public virtual void Unload()
        {
            foreach (Control c in Controls) c.Dispose();
            Controls.Clear();
        }

        protected static string BuildLoaderExceptionMessage(ReflectionTypeLoadException ex)
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

        protected void ShowMessageInControl(string errorMessage)
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

        //protected static string CreateTempPath(string extension)
        //{
        //    return Path.GetTempPath() + Guid.NewGuid().ToString("N") + extension;
        //}
    }
}