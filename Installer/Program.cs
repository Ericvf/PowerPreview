using System;
using System.Diagnostics;
using System.IO;
using System.Reflection;

namespace Installer
{
    public class Program
    {
        static void Main(string[] args)
        {
            var workingDirectory = AssemblyDirectory;
            var arg = args.Length > 0 ? args[0] : default;

            Console.WriteLine($"Running custom installer for COM registrations: {workingDirectory} {arg}");

            var startInfo = new ProcessStartInfo()
            {
                FileName = @"C:\Windows\SysWOW64\regsvr32.exe",
                Arguments = $"PowerPreview.comhost.dll /s {arg}",
                UseShellExecute = false,
                WorkingDirectory = workingDirectory,
                Verb = "runas"
            };

            var process = Process.Start(startInfo);
            process.WaitForExit();

            Console.WriteLine();
            Console.WriteLine($"Done with exit code: {process.ExitCode}");
            if (process.ExitCode != 0)
            {
                Console.ReadLine();
            }

            Environment.Exit(process.ExitCode);
        }

        public static string AssemblyDirectory
        {
            get
            {
                string codeBase = Assembly.GetExecutingAssembly().CodeBase;
                UriBuilder uri = new UriBuilder(codeBase);
                string path = Uri.UnescapeDataString(uri.Path);
                return Path.GetDirectoryName(path);
            }
        }
    }
}
