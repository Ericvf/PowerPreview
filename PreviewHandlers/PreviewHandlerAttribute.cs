using System;

namespace PreviewHandlers
{
    [AttributeUsage(AttributeTargets.Class, AllowMultiple = false, Inherited = false)]
    public sealed class PreviewHandlerAttribute : Attribute
    {
        public string Name { get; }

        public string Extension { get; }

        public string AppId { get; }

        public PreviewHandlerAttribute(string name, string extension, string appId)
        {
            Name = name ?? throw new ArgumentNullException(nameof(name));
            Extension = extension ?? throw new ArgumentNullException(nameof(extension));
            AppId = appId ?? throw new ArgumentNullException(nameof(appId));
        }
    }
}