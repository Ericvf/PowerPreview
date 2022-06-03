using System.IO;

namespace PreviewHandlers
{
    /// <summary>
    /// 
    /// </summary>
    /// <typeparam name="T"></typeparam>
    public abstract class FileBasedPreviewHandler<T> : PreviewHandler<T>, IInitializeWithFile
        where T : PreviewHandlerControl, new()
    {
        private string? _filePath;
        private uint _fileMode;

        void IInitializeWithFile.Initialize(string pszFilePath, uint grfMode)
        {
            _filePath = pszFilePath;
            _fileMode = grfMode;
        }

        protected override void DoPreview(T previewHandlerControl)
        {
            if (_filePath != null)
            {
                previewHandlerControl.Preview(new FileInfo(_filePath));
            }
        }
    }
}