# Configuration Réseau pour le Développement Mobile

## Problème
L'application mobile Flutter ne peut pas se connecter au backend Laravel à cause du firewall Windows.

## Solutions

### Solution 1: Configurer le Firewall Windows (RECOMMANDÉ)

**Étape 1:** Ouvrir PowerShell en tant qu'administrateur
- Clic droit sur le menu Démarrer → "Windows PowerShell (Admin)"

**Étape 2:** Exécuter le script de configuration
```powershell
cd D:\Earnia\backend-api
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\setup-firewall.ps1
```

**Étape 3:** Démarrer le serveur
```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

**Étape 4:** Tester depuis votre téléphone
- Ouvrir un navigateur sur votre téléphone
- Aller sur: `http://192.168.1.75:8000/api/games`
- Vous devriez voir du JSON

### Solution 2: Désactiver temporairement le Firewall (RAPIDE mais moins sécurisé)

**Windows Defender Firewall:**
1. Ouvrir "Panneau de configuration"
2. Aller dans "Système et sécurité" → "Pare-feu Windows Defender"
3. Cliquer sur "Activer ou désactiver le Pare-feu Windows Defender"
4. Désactiver pour le réseau privé uniquement
5. Tester la connexion
6. **NE PAS OUBLIER DE LE RÉACTIVER APRÈS!**

### Solution 3: Utiliser ngrok (FACILE, fonctionne partout)

**Installation:**
1. Télécharger ngrok: https://ngrok.com/download
2. Extraire dans un dossier (ex: `C:\ngrok`)
3. Créer un compte gratuit sur ngrok.com

**Utilisation:**
```powershell
# Terminal 1: Démarrer Laravel
cd D:\Earnia\backend-api
php artisan serve --port=8000

# Terminal 2: Démarrer ngrok
cd C:\ngrok
.\ngrok http 8000
```

**Configuration Flutter:**
1. Copier l'URL ngrok (ex: `https://abc123.ngrok.io`)
2. Modifier `frontend-flutter/lib/core/services/api_service.dart`:
```dart
static const String baseUrl = 'https://abc123.ngrok.io/api';
```

**Avantages:**
- Fonctionne partout (même en 4G)
- Pas besoin de configurer le firewall
- URL HTTPS gratuite

**Inconvénients:**
- L'URL change à chaque redémarrage de ngrok (version gratuite)
- Nécessite une connexion internet

### Solution 4: Utiliser l'émulateur Android avec 10.0.2.2

Si vous utilisez uniquement l'émulateur Android (pas un appareil physique):

**Configuration Flutter:**
```dart
// Dans api_service.dart
static const String baseUrl = 'http://10.0.2.2:8000/api';
```

**Démarrer le serveur:**
```powershell
php artisan serve --port=8000
```

**Note:** `10.0.2.2` est l'adresse spéciale de l'émulateur Android qui pointe vers `localhost` de votre PC.

## Vérification de la Configuration

### 1. Vérifier que le serveur est démarré
```powershell
netstat -an | Select-String "8000"
```
Vous devriez voir: `TCP    0.0.0.0:8000`

### 2. Tester depuis votre PC
```powershell
curl http://localhost:8000/api/games
# ou
Invoke-RestMethod http://localhost:8000/api/games
```

### 3. Tester depuis votre réseau local
```powershell
curl http://192.168.1.75:8000/api/games
```

### 4. Tester depuis votre téléphone
- Ouvrir un navigateur
- Aller sur: `http://192.168.1.75:8000/api/games`

## Trouver votre IP locale

```powershell
# Windows
ipconfig | Select-String "IPv4"

# Ou voir toutes les interfaces
ipconfig
```

Cherchez l'adresse IPv4 de votre adaptateur WiFi ou Ethernet.

## Dépannage

### Erreur "Connection timed out"
**Causes:**
- Firewall bloque le port 8000
- Serveur pas démarré avec `--host=0.0.0.0`
- Mauvaise IP dans `api_service.dart`
- Téléphone et PC pas sur le même réseau WiFi

**Solutions:**
1. Vérifier le firewall (Solution 1 ou 2)
2. Vérifier que le serveur écoute sur `0.0.0.0:8000`
3. Vérifier votre IP locale
4. Vérifier que le téléphone est sur le même WiFi

### Erreur "Connection refused"
**Causes:**
- Serveur Laravel pas démarré
- Mauvais port

**Solutions:**
1. Démarrer le serveur: `php artisan serve --host=0.0.0.0 --port=8000`
2. Vérifier le port dans `api_service.dart`

### Le serveur fonctionne en local mais pas depuis le téléphone
**Cause:** Firewall Windows

**Solution:** Utiliser la Solution 1 (configurer le firewall) ou Solution 3 (ngrok)

## Recommandation

Pour le développement quotidien:
1. **Utiliser la Solution 1** (firewall) - Configuration une seule fois
2. **Ou utiliser la Solution 3** (ngrok) - Plus simple mais URL change

Pour les tests rapides:
- **Solution 4** si vous utilisez uniquement l'émulateur Android
