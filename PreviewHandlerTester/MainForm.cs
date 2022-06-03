using System;
using System.Diagnostics;
using System.IO;
using System.Windows.Forms;
using FastColoredTextBoxNS;

namespace PowerPreviewTester
{
    public partial class MainForm : Form
    {
        private static Stopwatch stopWatch = new Stopwatch();

        private readonly PowerPreview.PowerPreviewHandler.PowerPreview previewHostFactory;
        private FastColoredTextBox? fctb;

        public MainForm()
        {
            InitializeComponent();
            Load += Form1_Load;

            previewHostFactory = new PowerPreview.PowerPreviewHandler.PowerPreview();
        }

        private void Form1_Load(object? sender, EventArgs e)
        {
            listBox1.Items.AddRange(Directory.GetFiles("cs"));
            listBox1.SelectedValueChanged += ListBox1_SelectedValueChanged;
        }

        private void ListBox1_SelectedValueChanged(object? sender, EventArgs e)
        {
            fctb?.CloseBindingFile();
            panel1.Controls.Remove(fctb);

            var filename = listBox1.SelectedItem.ToString();
            if (filename is not null)
            {
                var fileInfo = new FileInfo(filename);

                stopWatch.Restart();

                fctb = (FastColoredTextBox)previewHostFactory.LoadControl(fileInfo);
                panel1.Controls.Clear();
                panel1.Controls.Add(fctb);

                stopWatch.Stop();

                toolStripStatusLabel1.Text = stopWatch.Elapsed.ToString();
                toolStripStatusLabel2.Text = filename;
            }
        }
    }
}
