using System.Text;
using PreviewHandlers;

namespace RoslynPreviewHandler
{
    public static class WindowHelpers
    {
        public static string GetActiveWindowTitle()
        {
            const int maxLength = 256;
            var stringBuilder = new StringBuilder(maxLength);
            var handle = User32.GetForegroundWindow();

            if (User32.GetWindowText(handle, stringBuilder, maxLength) > 0)
            {
                return stringBuilder.ToString();
            }

            return null;
        }
    }
}