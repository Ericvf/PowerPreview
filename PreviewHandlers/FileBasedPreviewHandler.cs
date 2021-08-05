using System.IO;

namespace PreviewHandlers
{
    public abstract class FileBasedPreviewHandler<T> : PreviewHandler<T>, IInitializeWithFile
        where T : PreviewHandlerControl, new()
    {
        private string _filePath;
        private uint _fileMode;

        void IInitializeWithFile.Initialize(string pszFilePath, uint grfMode)
        {
            _filePath = pszFilePath;
            _fileMode = grfMode;
        }

        protected override void DoPreview(T previewHandlerControl)
        {
            previewHandlerControl.Preview(new FileInfo(_filePath));
        }
    }
}