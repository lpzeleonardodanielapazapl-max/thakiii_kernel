$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$meta = Get-Content (Join-Path $PSScriptRoot 'entorno.json') -Raw | ConvertFrom-Json
if ($meta.database -notmatch '^thaki_revision_\d{8}_\d{6}$') { throw 'Base de pruebas inválida' }
$dbConfig = @{}
Get-Content (Join-Path $projectRoot 'thaki-voyage-backend/.env') | ForEach-Object {
    if ($_ -match '^([A-Z_]+)=(.*)$') { $dbConfig[$Matches[1]] = $Matches[2].Trim('"', "'") }
}
$env:THAKI_DB_USER = $dbConfig['THAKI_DB_USER']
$env:THAKI_DB_PASSWORD = $dbConfig['THAKI_DB_PASSWORD']
$env:THAKI_DB_URL = "jdbc:mysql://127.0.0.1:3306/$($meta.database)?useSSL=false&allowPublicKeyRetrieval=true&serverTimezone=America/La_Paz"
$env:QUARKUS_HTTP_PORT = '18080'
$env:QUARKUS_HTTP_HOST = '127.0.0.1'
$api = Start-Process -FilePath 'C:/Program Files/Java/jdk-25.0.4/bin/java.exe' -ArgumentList '-jar','target/quarkus-app/quarkus-run.jar' -WorkingDirectory (Join-Path $projectRoot 'thaki-voyage-backend') -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $PSScriptRoot 'quarkus-runtime.log') -RedirectStandardError (Join-Path $PSScriptRoot 'quarkus-runtime-error.log')
$env:QUARKUS_URL = 'http://127.0.0.1:18080'
$env:APP_URL = 'http://127.0.0.1:18000'
$env:APP_DEBUG = 'false'
$env:SESSION_DRIVER = 'file'
$web = Start-Process -FilePath (Get-Command php).Source -ArgumentList '-S','127.0.0.1:18000','-t','public' -WorkingDirectory (Join-Path $projectRoot 'web-laravel') -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $PSScriptRoot 'laravel-runtime.log') -RedirectStandardError (Join-Path $PSScriptRoot 'laravel-runtime-error.log')
[pscustomobject]@{apiPid=$api.Id;webPid=$web.Id;apiPort=18080;webPort=18000} | ConvertTo-Json | Tee-Object -FilePath (Join-Path $PSScriptRoot 'servicios.json')
