using System.IO;

namespace PreviewHandlers
{
    public abstract class FileBasedPreviewHandler : PreviewHandler, IInitializeWithFile
    {
        private string _filePath;
        private uint _fileMode;

        void IInitializeWithFile.Initialize(string pszFilePath, uint grfMode)
        {
            _filePath = pszFilePath;
            _fileMode = grfMode;
        }

        protected override void Load(PreviewHandlerControl c)
        {
            c.Load(new FileInfo(_filePath));
        }
    }
}