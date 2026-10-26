param([string]$BaseUrl = 'http://127.0.0.1:8000')
$ErrorActionPreference = 'Stop'
$passed = 0
function Assert-Check([bool]$Condition, [string]$Label) {
    if (!$Condition) { throw "HTTP check failed: $Label" }
    $script:passed++
    Write-Host "PASS $Label"
}
function Get-Token([string]$Html) {
    $token = [regex]::Match($Html, 'name="_token" value="([^"]+)"').Groups[1].Value
    if (!$token) { throw 'Could not read CSRF token.' }
    return $token
}
function Sign-In([string]$Role) {
    $session = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
    $page = Invoke-WebRequest -Uri "$BaseUrl/login" -WebSession $session
    $login = Invoke-WebRequest -Uri "$BaseUrl/login" -Method POST -WebSession $session -Body @{email="$Role@forma.test";password='BuildTogether!2026';_token=(Get-Token $page.Content)}
    Assert-Check ($login.Content.Contains('Workspace overview')) "$Role signs in with a valid CSRF token"
    return $session
}
$guest = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
$csrf = Invoke-WebRequest -Uri "$BaseUrl/login" -Method POST -WebSession $guest -Body @{email='admin@forma.test';password='bad'} -SkipHttpErrorCheck
Assert-Check ($csrf.StatusCode -eq 419) 'Sign-in rejects a missing CSRF token'
$session = Sign-In 'admin'
$dashboard = Invoke-WebRequest -Uri $BaseUrl -WebSession $session
Assert-Check ($dashboard.Headers['X-Frame-Options'] -contains 'DENY') 'Frame embedding is denied'
Assert-Check ($dashboard.Headers['X-Content-Type-Options'] -contains 'nosniff') 'Content sniffing is disabled'
Assert-Check (($dashboard.Headers['Content-Security-Policy'] -join '').Contains("script-src 'self'")) 'Same-origin script policy is sent'
$write = Invoke-WebRequest -Uri "$BaseUrl/manage/projects" -WebSession $session -Method POST -Body @{name='CSRF must reject this'} -SkipHttpErrorCheck
Assert-Check ($write.StatusCode -eq 419) 'Authenticated writes reject a missing CSRF token'
$projectId = [regex]::Match($dashboard.Content, '/manage/projects/(\d+)').Groups[1].Value
Assert-Check ([bool]$projectId) 'A demo project is available'
foreach ($path in @('/manage/projects',"/manage/projects/$projectId",'/manage/contractors','/manage/tasks','/manage/documents','/reports','/settings','/audit','/help')) {
    $response = Invoke-WebRequest -Uri "$BaseUrl$path" -WebSession $session
    Assert-Check ($response.StatusCode -eq 200) "Admin page $path renders"
}
$documentsPage = Invoke-WebRequest -Uri "$BaseUrl/manage/documents" -WebSession $session
$documentId = [regex]::Match($documentsPage.Content, '/documents/(\d+)/download').Groups[1].Value
Assert-Check ([bool]$documentId) 'A demo document is available'
$document = Invoke-WebRequest -Uri "$BaseUrl/documents/$documentId/download" -WebSession $session
Assert-Check (($document.Headers['Content-Disposition'] -join '').Contains('attachment')) 'Authorized document is served as an attachment'
$viewerSession = Sign-In 'viewer'
foreach($path in @('/settings','/audit','/reports','/manage/expenses','/manage/users')) {
    $response = Invoke-WebRequest -Uri "$BaseUrl$path" -WebSession $viewerSession -SkipHttpErrorCheck
    Assert-Check ($response.StatusCode -eq 403) "Viewer is denied $path"
}
$viewPage = Invoke-WebRequest -Uri $BaseUrl -WebSession $viewerSession
Assert-Check (!$viewPage.Content.Contains('Budget snapshot')) 'Viewer dashboard hides financial summary'
Write-Host "$passed live HTTP checks passed. No business records were created or modified."
