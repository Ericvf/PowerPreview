using Microsoft.Win32;
using System;
using System.ComponentModel;
using System.Diagnostics;
using System.Drawing;
using System.Linq;
using System.Runtime.InteropServices;
using System.Windows.Forms;

namespace PreviewHandlers
{
    public abstract class PreviewHandler<T> : IPreviewHandler, IPreviewHandlerVisuals, IOleWindow, IObjectWithSite
        where T : PreviewHandlerControl, new()
    {
        private bool _showPreview;
        private T _previewControl;
        private IntPtr _parentHwnd;
        private Rectangle _windowBounds;
        private object _unkSite;
        private IPreviewHandlerFrame _frame;

        protected abstract void DoPreview(T c);

        protected PreviewHandler()
        {
            _previewControl = new T();
            _ = _previewControl.Handle;
        }

        private void InvokeOnUIThread(MethodInvoker d)
        {
            _previewControl.Invoke(d);
        }

        private void UpdateWindowBounds()
        {
            if (_showPreview)
            {
                InvokeOnUIThread(delegate ()
                {
                    User32.SetParent(_previewControl.Handle, _parentHwnd);
                    _previewControl.Bounds = _windowBounds;
                    _previewControl.Visible = true;
                });
            }
        }

        #region IPreviewHandler

        void IPreviewHandler.SetWindow(IntPtr hwnd, ref RECT rect)
        {
            _parentHwnd = hwnd;
            _windowBounds = rect.ToRectangle();
            UpdateWindowBounds();
        }

        void IPreviewHandler.SetRect(ref RECT rect)
        {
            _windowBounds = rect.ToRectangle();
            UpdateWindowBounds();
        }

        void IPreviewHandler.DoPreview()
        {
            _showPreview = true;
            InvokeOnUIThread(delegate ()
            {
                try
                {
                    DoPreview(_previewControl);
                }
                catch (Exception exc)
                {
                    _previewControl.Controls.Clear();
                    TextBox text = new TextBox
                    {
                        ReadOnly = true,
                        Multiline = true,
                        Dock = DockStyle.Fill,
                        Text = exc.ToString()
                    };
                    _previewControl.Controls.Add(text);
                }
                UpdateWindowBounds();
            });
        }

        void IPreviewHandler.Unload()
        {
            _showPreview = false;
            InvokeOnUIThread(delegate ()
            {
                _previewControl.Visible = false;
                _previewControl.Unload();
            });
        }

        void IPreviewHandler.SetFocus()
        {
            InvokeOnUIThread(delegate () { _previewControl.Focus(); });
        }

        void IPreviewHandler.QueryFocus(out IntPtr phwnd)
        {
            IntPtr result = IntPtr.Zero;
            InvokeOnUIThread(delegate () { result = User32.GetFocus(); });
            phwnd = result;
            if (phwnd == IntPtr.Zero) throw new Win32Exception();
        }

        uint IPreviewHandler.TranslateAccelerator(ref MSG pmsg)
        {
            if (_frame != null) return _frame.TranslateAccelerator(ref pmsg);
            const uint S_FALSE = 1;
            return S_FALSE;
        }

        #endregion

        #region IPreviewHandlerVisuals

        void IPreviewHandlerVisuals.SetBackgroundColor(COLORREF color)
        {
            Color c = color.Color;
            InvokeOnUIThread(delegate () { _previewControl.BackColor = c; });
        }

        void IPreviewHandlerVisuals.SetTextColor(COLORREF color)
        {
            Color c = color.Color;
            InvokeOnUIThread(delegate () { _previewControl.ForeColor = c; });
        }

        void IPreviewHandlerVisuals.SetFont(ref LOGFONT plf)
        {
            Font f = Font.FromLogFont(plf);
            InvokeOnUIThread(delegate () { _previewControl.Font = f; });
        }

        #endregion

        #region IOleWindow

        void IOleWindow.GetWindow(out IntPtr phwnd)
        {
            phwnd = IntPtr.Zero;
            phwnd = _previewControl.Handle;
        }

        void IOleWindow.ContextSensitiveHelp(bool fEnterMode)
        {
            throw new NotImplementedException();
        }

        #endregion

        #region IObjectWithSite

        void IObjectWithSite.SetSite(object pUnkSite)
        {
            _unkSite = pUnkSite;
            _frame = _unkSite as IPreviewHandlerFrame;
        }

        void IObjectWithSite.GetSite(ref Guid riid, out object ppvSite)
        {
            ppvSite = _unkSite;
        }

        #endregion

        #region COM registrations (called by regasm)

        [ComRegisterFunction]
        public static void Register(Type t)
        {
            var attributes = t.GetCustomAttributes(typeof(PreviewHandlerAttribute), true);
            if (attributes.Any())
            {
                var attr = attributes.Cast<PreviewHandlerAttribute>().First();
                RegisterPreviewHandler(attr.Name, attr.Extension, t.GUID.ToString("B"), attr.AppId);
            }
        }

        [ComUnregisterFunction]
        public static void Unregister(Type t)
        {
            var attributes = t.GetCustomAttributes(typeof(PreviewHandlerAttribute), true);
            if (attributes.Any())
            {
                var attr = attributes.Cast<PreviewHandlerAttribute>().First();
                UnregisterPreviewHandler(attr.Extension, t.GUID.ToString("B"), attr.AppId);
            }
        }

        #endregion

        protected static void RegisterPreviewHandler(string name, string extensions, string previewerGuid, string appId)
        {
            // Create a new prevhost AppID so that this always runs in its own isolated process``
            using (var appIdsKey = Registry.ClassesRoot.OpenSubKey("AppID", true))
            using (var appIdKey = appIdsKey.CreateSubKey(appId))
            {
                appIdKey.SetValue("DllSurrogate", @"%SystemRoot%\system32\prevhost.exe", RegistryValueKind.ExpandString);
            }

            // Add preview handler to preview handler list
            using (var handlersKey = Registry.LocalMachine.OpenSubKey("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\PreviewHandlers", true))
            {
                handlersKey.SetValue(previewerGuid, name, RegistryValueKind.String);
            }

            // Modify preview handler registration
            using (var clsidKey = Registry.ClassesRoot.OpenSubKey("CLSID"))
            using (var idKey = clsidKey.OpenSubKey(previewerGuid, true))
            {
                idKey.SetValue("DisplayName", name, RegistryValueKind.String);
                idKey.SetValue("AppID", appId, RegistryValueKind.String);
                idKey.SetValue("DisableLowILProcessIsolation", 1, RegistryValueKind.DWord); // optional, depending on what preview handler needs to be able to do
            }

            foreach (var extension in extensions.Split(new char[] { ';' }, StringSplitOptions.RemoveEmptyEntries))
            {
                Trace.WriteLine($"Registering extension '{extension}' with previewer '{previewerGuid}'");

                // Set preview handler for specific extension
                using (var extensionKey = Registry.ClassesRoot.CreateSubKey(extension))
                using (var shellexKey = extensionKey.CreateSubKey("shellex"))
                using (var previewKey = shellexKey.CreateSubKey("{8895b1c6-b41f-4c1c-a562-0d564250836f}"))
                {
                    previewKey.SetValue(null, previewerGuid, RegistryValueKind.String);
                }
            }
        }

        protected static void UnregisterPreviewHandler(string extensions, string previewerGuid, string appId)
        {
            foreach (var extension in extensions.Split(new char[] { ';' }, StringSplitOptions.RemoveEmptyEntries))
            {
                Trace.WriteLine($"Unregistering extension '{extension}' with previewer '{previewerGuid}'");

                using (var shellexKey = Registry.ClassesRoot.OpenSubKey(extension + "\\shellex", true))
                {
                    shellexKey.DeleteSubKey("{8895b1c6-b41f-4c1c-a562-0d564250836f}");
                }
            }

            using (var appIdsKey = Registry.ClassesRoot.OpenSubKey("AppID", true))
            {
                appIdsKey.DeleteSubKey(appId);
            }

            using (var classesKey = Registry.LocalMachine.OpenSubKey("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\PreviewHandlers", true))
            {
                classesKey.DeleteValue(previewerGuid);
            }
        }
    }
}