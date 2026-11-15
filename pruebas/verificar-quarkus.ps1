$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$dbConfig = @{}
Get-Content (Join-Path $projectRoot 'thaki-voyage-backend/.env') | ForEach-Object {
    if ($_ -match '^([A-Z_]+)=(.*)$') { $dbConfig[$Matches[1]] = $Matches[2].Trim('"', "'") }
}
$env:THAKI_DB_USER = $dbConfig['THAKI_DB_USER']
$env:THAKI_DB_PASSWORD = $dbConfig['THAKI_DB_PASSWORD']
$env:THAKI_INVENTARIO_READ_URL = 'jdbc:mysql://127.0.0.1:3306/thaki_modificado?useSSL=false&allowPublicKeyRetrieval=true&serverTimezone=America/La_Paz'
$env:THAKI_INVENTARIO_READ_USER = $env:THAKI_DB_USER
$env:THAKI_INVENTARIO_READ_PASSWORD = $env:THAKI_DB_PASSWORD
$maven = Get-ChildItem "$env:USERPROFILE/.m2/wrapper/dists" -Filter mvn.cmd -Recurse | Select-Object -First 1 -ExpandProperty FullName
Push-Location (Join-Path $projectRoot 'thaki-voyage-backend')
try {
    & $maven "-Dmaven.repo.local=$env:USERPROFILE/.m2/repository" -o '-DskipITs=false' clean verify 2>&1 | Tee-Object -FilePath (Join-Path $PSScriptRoot 'quarkus-verificacion.log')
    exit $LASTEXITCODE
} finally { Pop-Location }
