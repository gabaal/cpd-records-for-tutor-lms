# Build a distributable plugin ZIP with the correct folder name.
$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$slug = 'cpd-records-for-tutor-lms'
$version = '0.1.0'
$dist = Join-Path $root 'dist'
$stage = Join-Path $dist $slug

if (Test-Path $stage) {
    Remove-Item -Recurse -Force $stage
}

New-Item -ItemType Directory -Force -Path $stage | Out-Null

Copy-Item (Join-Path $root "$slug.php") $stage
Copy-Item (Join-Path $root 'includes') $stage -Recurse
Copy-Item (Join-Path $root 'assets') $stage -Recurse
Copy-Item (Join-Path $root 'uninstall.php') $stage
Copy-Item (Join-Path $root 'readme.txt') $stage

$zip = Join-Path $dist "$slug-$version.zip"

if (Test-Path $zip) {
    Remove-Item -Force $zip
}

# Build the archive with forward-slash entry names so it installs on Linux hosts.
Add-Type -AssemblyName System.IO.Compression.FileSystem

$archive = [System.IO.Compression.ZipFile]::Open($zip, 'Create')

try {
    Get-ChildItem $stage -Recurse -File | ForEach-Object {
        $relative = $_.FullName.Substring($stage.Length + 1).Replace('\', '/')
        $entry    = "$slug/$relative"
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $_.FullName, $entry) | Out-Null
    }
} finally {
    $archive.Dispose()
}

Write-Output "Built $zip"
