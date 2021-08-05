using System.IO;

namespace PreviewHandlers
{
    public abstract class StreamBasedPreviewHandlerControl : PreviewHandlerControl
    {
        public sealed override void Preview(FileInfo file)
        {
            //using (FileStream fs = new FileStream(file.FullName, FileMode.Open, FileAccess.Read, FileShare.Delete | FileShare.ReadWrite))
            //{
            //    Load(fs);
            //}
        }
    }
}