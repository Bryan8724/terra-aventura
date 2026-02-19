<?php

namespace Controllers;

class DeployController
{
    private $triggerFile = '/srv/scripts/deploy.trigger';
    private $logFile     = '/srv/scripts/deploy.log';

    public function deploy()
    {
        // DEV uniquement
        if (getenv('APP_ENV') !== 'dev') {
            http_response_code(403);
            exit('Interdit en production');
        }

        // POST uniquement
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Méthode non autorisée');
        }

        // Admin uniquement
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            http_response_code(403);
            exit('Accès refusé');
        }

        // CSRF
        if (
            empty($_POST['csrf_token']) ||
            empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            http_response_code(403);
            exit('Token CSRF invalide');
        }

        // Déjà en attente ?
        if (file_exists($this->triggerFile)) {
            $_SESSION['toast'] = "⚠️ Un déploiement est déjà en attente.";
            header('Location: /');
            exit;
        }

        // Vérifie que le dossier existe
        if (!is_dir(dirname($this->triggerFile))) {
            $_SESSION['toast'] = "❌ Dossier de script introuvable.";
            header('Location: /');
            exit;
        }

        // Écriture sécurisée du trigger
        $result = @file_put_contents($this->triggerFile, (string) time());

        if ($result === false) {
            $_SESSION['toast'] = "❌ Impossible de créer le trigger de déploiement.";
            header('Location: /');
            exit;
        }

        // Regénération CSRF
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $_SESSION['toast'] = "🚀 Déploiement programmé avec succès.";
        header('Location: /');
        exit;
    }

    public function log()
    {
        if (getenv('APP_ENV') !== 'dev') {
            http_response_code(403);
            exit('Interdit');
        }

        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            http_response_code(403);
            exit('Accès refusé');
        }

        if (!file_exists($this->logFile)) {
            echo "Aucun log disponible.";
            return;
        }

        $content = @file_get_contents($this->logFile);

        if ($content === false) {
            echo "Impossible de lire le log.";
            return;
        }

        echo "<pre style='background:#111;color:#0f0;padding:20px;overflow:auto;'>";
        echo htmlspecialchars($content);
        echo "</pre>";
    }
}
