using System.Runtime.InteropServices.ComTypes;

namespace PreviewHandlers
{
    public abstract partial class StreamBasedPreviewHandler<T> : PreviewHandler<T>, IInitializeWithStream
        where T : PreviewHandlerControl, new()
    {
        private IStream _stream;
        private uint _streamMode;

        void IInitializeWithStream.Initialize(IStream pstream, uint grfMode)
        {
            _stream = pstream;
            _streamMode = grfMode;
        }

        protected override void DoPreview(T previewHandlerControl)
        {
            previewHandlerControl.Preview(new ReadOnlyIStreamStream(_stream));
        }
    }
}