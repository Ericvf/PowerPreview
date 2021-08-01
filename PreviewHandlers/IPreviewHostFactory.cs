using System.IO;
using System.Windows.Forms;

namespace PreviewHandlers
{
    public interface IPreviewHostFactory
    {
        Control Load(FileInfo fileInfo);
    }
}