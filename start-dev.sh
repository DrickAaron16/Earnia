#!/bin/bash

# Script pour démarrer le serveur Laravel en mode développement
# Accessible depuis le réseau local pour les tests mobile

echo "🚀 Démarrage du serveur Laravel..."
echo "📱 Accessible depuis: http://192.168.1.75:8000"
echo "🔧 API endpoint: http://192.168.1.75:8000/api"
echo ""
echo "Pour arrêter le serveur, appuyez sur Ctrl+C"
echo ""

php artisan serve --host=0.0.0.0 --port=8000
