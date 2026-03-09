# Script pour configurer le firewall Windows pour le développement Laravel
# Doit être exécuté en tant qu'administrateur

Write-Host "🔥 Configuration du firewall Windows pour Laravel..." -ForegroundColor Cyan
Write-Host ""

# Vérifier si on est administrateur
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "❌ Ce script doit être exécuté en tant qu'administrateur!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Faites un clic droit sur PowerShell et sélectionnez 'Exécuter en tant qu'administrateur'" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Appuyez sur Entrée pour quitter"
    exit 1
}

# Supprimer l'ancienne règle si elle existe
Write-Host "🗑️  Suppression de l'ancienne règle..." -ForegroundColor Yellow
Remove-NetFirewallRule -DisplayName "Laravel Dev Server" -ErrorAction SilentlyContinue

# Créer la nouvelle règle
Write-Host "➕ Création de la règle de firewall..." -ForegroundColor Yellow
New-NetFirewallRule `
    -DisplayName "Laravel Dev Server" `
    -Description "Autorise les connexions entrantes sur le port 8000 pour le serveur Laravel" `
    -Direction Inbound `
    -LocalPort 8000 `
    -Protocol TCP `
    -Action Allow `
    -Profile Any `
    -Enabled True

if ($?) {
    Write-Host ""
    Write-Host "✅ Firewall configuré avec succès!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Le serveur Laravel est maintenant accessible depuis:" -ForegroundColor Cyan
    Write-Host "  - Localhost: http://localhost:8000" -ForegroundColor White
    Write-Host "  - Réseau local: http://192.168.1.75:8000" -ForegroundColor White
    Write-Host ""
    Write-Host "Pour démarrer le serveur:" -ForegroundColor Cyan
    Write-Host "  php artisan serve --host=0.0.0.0 --port=8000" -ForegroundColor White
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "❌ Erreur lors de la configuration du firewall" -ForegroundColor Red
    Write-Host ""
}

Read-Host "Appuyez sur Entrée pour quitter"
