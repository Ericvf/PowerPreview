using System;
using System.Diagnostics;
using System.IO;
using System.Windows.Forms;
using FastColoredTextBoxNS;
using PreviewHandlers;
using RoslynPreviewHandler;

namespace PreviewHandlerTester
{
    public partial class Form1 : Form
    {
        private static Stopwatch stopWatch = new Stopwatch();

        private readonly IPreviewHostFactory previewHostFactory;
        private FastColoredTextBox fctb;

        public Form1()
        {
            InitializeComponent();
            Load += Form1_Load;

            previewHostFactory = new RoslynPreviewHostFactory();
        }

        private void Form1_Load(object sender, EventArgs e)
        {
            listBox1.Items.AddRange(Directory.GetFiles("cs"));
            listBox1.SelectedValueChanged += ListBox1_SelectedValueChanged;
        }

        private void ListBox1_SelectedValueChanged(object sender, EventArgs e)
        {
            fctb?.CloseBindingFile();
            panel1.Controls.Remove(fctb);

            var filename = listBox1.SelectedItem.ToString();
            var fileInfo = new FileInfo(filename);

            stopWatch.Restart();

            fctb = previewHostFactory.Load(fileInfo) as FastColoredTextBox;
            panel1.Controls.Add(fctb);

            stopWatch.Stop();

            toolStripStatusLabel1.Text = stopWatch.Elapsed.ToString();
            toolStripStatusLabel2.Text = filename;
        }
    }
}
