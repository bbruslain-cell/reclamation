Set shell = CreateObject("WScript.Shell")
shell.Run "powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File ""C:\Suivi-reclamation\scripts\ensure-queue-worker.ps1""", 0, False
