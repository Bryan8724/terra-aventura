<?php

namespace Controllers;

class DeployController
{
    private string $triggerFile = '/srv/scripts/deploy.trigger';
    private string $statusFile  = '/srv/scripts/deploy-status.json';
    private string $logFile     = '/srv/scripts/deploy.log';

    private function json(array $data, int $code = 200): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function deploy(): void
    {
        // 🔒 DEV uniquement
        if (getenv('APP_ENV') !== 'dev') {
            $this->json(['success' => false, 'error' => 'Interdit en production'], 403);
        }

        // 🔒 POST uniquement
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Méthode non autorisée'], 405);
        }

        // 🔒 Admin uniquement
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        // 🔒 CSRF
        if (
            empty($_POST['csrf_token']) ||
            empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            $this->json(['success' => false, 'error' => 'Token CSRF invalide'], 403);
        }

        // ⚠️ Déploiement déjà en cours ?
        if (file_exists($this->statusFile)) {
            $content       = json_decode(file_get_contents($this->statusFile), true);
            $currentStatus = $content['status'] ?? null;

            if (in_array($currentStatus, ['pushing', 'starting', 'pulling', 'building', 'backup', 'migrating', 'verifying', 'finalizing'])) {
                $this->json(['success' => false, 'error' => 'Un déploiement est déjà en cours.'], 409);
            }
        }

        // 💬 Message de commit (optionnel, sinon horodatage automatique)
        $commitMsg = trim($_POST['commit_message'] ?? '');
        if ($commitMsg === '') {
            $commitMsg = 'deploy: ' . date('Y-m-d H:i:s');
        }
        // Sanitisation : caractères simples + accents FR, max 100 caractères
        $commitMsg = mb_substr(
            preg_replace('/[^\w\s:\-\.éèêëàâùûüîïôçœæÉÈÊËÀÂÙÛÜÎÏÔÇŒÆ]/u', '', $commitMsg),
            0, 100
        );

        // 📡 Statut : git push en cours
        file_put_contents($this->statusFile, json_encode([
            'status'   => 'pushing',
            'progress' => 3
        ]));

        // 🚀 GIT : checkout → add → commit → push
        $projectRoot = ROOT_PATH;

        $gitCommands = [
            "git -C " . escapeshellarg($projectRoot) . " checkout main 2>&1",
            "git -C " . escapeshellarg($projectRoot) . " add -A 2>&1",
            // --allow-empty : ne plante pas s'il n'y a rien de nouveau à commiter
            "git -C " . escapeshellarg($projectRoot) . " commit --allow-empty -m " . escapeshellarg($commitMsg) . " 2>&1",
            "git -C " . escapeshellarg($projectRoot) . " push origin main 2>&1",
        ];

        foreach ($gitCommands as $cmd) {
            exec($cmd, $out, $code);

            if ($code !== 0) {
                file_put_contents($this->statusFile, json_encode([
                    'status'   => 'error',
                    'progress' => 0,
                    'error'    => implode("\n", $out)
                ]));
                $this->json(['success' => false, 'error' => 'Erreur git : ' . implode("\n", $out)], 500);
            }

            $out = [];
        }

        // ✅ Git OK → on déclenche le déploiement prod
        file_put_contents($this->statusFile, json_encode([
            'status'   => 'starting',
            'progress' => 5
        ]));

        // 📝 Création du trigger (le cron root surveille ce fichier)
        if (@file_put_contents($this->triggerFile, time()) === false) {
            file_put_contents($this->statusFile, json_encode([
                'status'   => 'error',
                'progress' => 0
            ]));
            $this->json(['success' => false, 'error' => 'Impossible de créer le trigger de déploiement.'], 500);
        }

        // 🔄 Rotation du token CSRF
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $this->json(['success' => true]);
    }

    public function log(): void
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