using System;
using System.IO;
using System.Runtime.InteropServices.ComTypes;

namespace PreviewHandlers
{
    public class ReadOnlyIStreamStream : Stream
    {
        private IStream _stream;

        public ReadOnlyIStreamStream(IStream stream)
        {
            _stream = stream ?? throw new ArgumentNullException("stream");
        }

        protected override void Dispose(bool disposing)
        {
            _stream = null;
            base.Dispose(disposing);
        }

        private void ThrowIfDisposed()
        {
            if (_stream == null)
                throw new ObjectDisposedException(GetType().Name);
        }

        public unsafe override int Read(byte[] buffer, int offset, int count)
        {
            ThrowIfDisposed();

            if (buffer == null) throw new ArgumentNullException("buffer");
            if (offset < 0) throw new ArgumentNullException("offset");
            if (count < 0) throw new ArgumentNullException("count");

            int bytesRead = 0;
            if (count > 0)
            {
                IntPtr ptr = new IntPtr(&bytesRead);
                if (offset == 0)
                {
                    if (count > buffer.Length) throw new ArgumentOutOfRangeException("count");
                    _stream.Read(buffer, count, ptr);
                }
                else
                {
                    byte[] tempBuffer = new byte[count];
                    _stream.Read(tempBuffer, count, ptr);
                    if (bytesRead > 0) Array.Copy(tempBuffer, 0, buffer, offset, bytesRead);
                }
            }
            return bytesRead;
        }

        public override bool CanRead => _stream != null;

        public override bool CanSeek => _stream != null;

        public override bool CanWrite => false;

        public override long Length
        {
            get
            {
                ThrowIfDisposed();
                const int STATFLAG_NONAME = 1;
                STATSTG stats;
                _stream.Stat(out stats, STATFLAG_NONAME);
                return stats.cbSize;
            }
        }

        public override long Position
        {
            get
            {
                ThrowIfDisposed();
                return Seek(0, SeekOrigin.Current);
            }
            set
            {
                ThrowIfDisposed();
                Seek(value, SeekOrigin.Begin);
            }
        }

        public override unsafe long Seek(long offset, SeekOrigin origin)
        {
            ThrowIfDisposed();
            long pos = 0;
            IntPtr posPtr = new IntPtr((void*)&pos);
            _stream.Seek(offset, (int)origin, posPtr);
            return pos;
        }

        public override void Flush()
        {
            ThrowIfDisposed();
        }

        public override void SetLength(long value)
        {
            ThrowIfDisposed();
            throw new NotSupportedException();
        }

        public override void Write(byte[] buffer, int offset, int count)
        {
            ThrowIfDisposed();
            throw new NotSupportedException();
        }
    }
}