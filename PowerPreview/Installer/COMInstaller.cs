using System;
using System.Collections;
using System.ComponentModel;
using System.Configuration.Install;
using System.Diagnostics;
using System.Runtime.InteropServices;

namespace PowerPreview
{
    [RunInstaller(true)]
    public partial class COMInstaller : Installer
    {
        public COMInstaller()
        {
            InitializeComponent();
        }

        [System.Security.Permissions.SecurityPermission(System.Security.Permissions.SecurityAction.Demand)]
        public override void Commit(IDictionary savedState)
        {
            base.Commit(savedState);
        }

        public override void Install(IDictionary stateSaver)
        {
            try
            {
                RegisterAssembly();

                base.Install(stateSaver);

                var regsrv = new RegistrationServices();
                if (!regsrv.RegisterAssembly(GetType().Assembly, AssemblyRegistrationFlags.SetCodeBase))
                {
                    throw new InstallException("Failed to register for COM interop.");
                }
            }
            catch (Exception ex)
            {
                Trace.WriteLine(ex.ToString());
                throw;
            }
        }

        public override void Uninstall(IDictionary savedState)
        {
            try
            {
                UnregisterAssembly();

                base.Uninstall(savedState);

                var regsrv = new RegistrationServices();

                if (!regsrv.UnregisterAssembly(GetType().Assembly))
                {
                    throw new InstallException("Failed to unregister for COM interop.");
                }
            }
            catch (Exception ex)
            {
                Trace.WriteLine(ex.ToString());
                throw;
            }
        }

        private void RegisterAssembly()
        {
            var regasmPath = RuntimeEnvironment.GetRuntimeDirectory() + @"regasm.exe";
            var componentPath = GetType().Assembly.Location;
            Process.Start(regasmPath, $@"/codebase ""{componentPath}""");
        }

        private void UnregisterAssembly()
        {
            var regasmPath = RuntimeEnvironment.GetRuntimeDirectory() + @"regasm.exe";
            var componentPath = GetType().Assembly.Location;
            Process.Start(regasmPath, $@"/unregister ""{componentPath}""");
        }
    }
}