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
        // Capturer toute sortie non-JSON (erreurs PHP → HTML) avant qu'elles partent au client
        ob_start();
        
        // Handler d'erreurs → JSON au lieu de HTML
        set_error_handler(function(int $errno, string $errstr) {
            ob_end_clean();
            $this->json(['success' => false, 'error' => "Erreur PHP: $errstr"], 500);
        });

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
            $ts            = (int)($content['ts'] ?? 0);
            $statusAge     = $ts > 0 ? time() - $ts : PHP_INT_MAX; // ts absent = très vieux = on laisse passer

            $activeStatuses = ['pushing', 'starting', 'pulling', 'building', 'backup', 'migrating', 'verifying', 'finalizing'];
            $isActive = in_array($currentStatus, $activeStatuses);

            if ($isActive && $statusAge < 300) {
                // Bloqué < 5 min → déploiement vraiment en cours
                $this->json(['success' => false, 'error' => 'Un déploiement est déjà en cours.'], 409);
            } elseif ($isActive) {
                // Bloqué > 5 min → zombie, on réinitialise silencieusement
                file_put_contents($this->statusFile, json_encode(['status' => 'idle', 'progress' => 0, 'ts' => time()]));
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
            'progress' => 3,
            'ts'       => time()
        ]));

        // 🚀 GIT : checkout → add → commit → push
        $projectRoot = ROOT_PATH;

        // ✅ Fix permissions .git
        // ROOT_PATH = /var/www/html/app  →  .git est dans le dossier PARENT
        // On remonte d'un niveau pour trouver la racine git réelle.
        $gitRoot = dirname($projectRoot);
        // Vérifier : si .git n'est pas dans le parent, tester ROOT_PATH lui-même
        if (!is_dir($gitRoot . '/.git') && is_dir($projectRoot . '/.git')) {
            $gitRoot = $projectRoot;
        }
        $gitDir     = $gitRoot . '/.git';
        $gitObjects = $gitDir  . '/objects';

        // Chmod récursif via exec (fonctionne si www-data possède les fichiers)
        @exec("find " . escapeshellarg($gitDir) . " -type d -exec chmod 777 {} + 2>&1");
        @exec("find " . escapeshellarg($gitDir) . " -type f -exec chmod 666 {} + 2>&1");
        @exec("git -C " . escapeshellarg($gitRoot) . " config core.sharedRepository world 2>&1");
        // Rendre les fichiers app lisibles par git
        @exec("find " . escapeshellarg($projectRoot) . " -type f -exec chmod a+r {} + 2>&1");

        // Utiliser gitRoot pour toutes les commandes git
        $projectRoot = $gitRoot;

        // Options git inline : contourne le problème gitconfig read-only dans Docker
        // -c safe.directory=*           → autorise git à travailler dans ce dossier (owner != user courant)
        // -c core.sharedRepository=world → les nouveaux objets sont accessibles par tous
        $gitOpts = "-c safe.directory=* -c core.sharedRepository=world";

        // Problème root vs www-data : git crée des sous-dossiers dans .git/objects
        // avec le umask du process (022) → pas inscriptibles ensuite.
        // Fix : chmod avant chaque commande + umask 0000 dans le shell.
        $pr = escapeshellarg($projectRoot);
        $gitCommands = [
            "find {$projectRoot}/.git/objects -type d -exec chmod 777 {} + 2>/dev/null; "
                . "find {$projectRoot}/.git/objects -type f -exec chmod 666 {} + 2>/dev/null; "
                . "umask 0000 && git $gitOpts -C $pr checkout main 2>&1",
            "umask 0000 && git $gitOpts -C $pr add -A 2>&1",
            "umask 0000 && git $gitOpts -C $pr commit --allow-empty -m " . escapeshellarg($commitMsg) . " 2>&1",
            "umask 0000 && git $gitOpts -C $pr push origin main 2>&1",
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

        ob_end_clean();
        restore_error_handler();
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

    public function resetStatus(): void
    {
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }

        file_put_contents($this->statusFile, json_encode([
            'status'   => 'idle',
            'progress' => 0,
            'ts'       => time()
        ]));

        $this->json(['success' => true]);
    }

}
