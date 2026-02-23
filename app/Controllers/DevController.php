<?php

namespace Controllers;

class DevController
{
    private function progressFile(): string
    {
        return ROOT_PATH . '/Storage/dev_backup_progress.json';
    }

    private function backupDir(): string
    {
        return ROOT_PATH . '/Storage/backups';
    }

    private function backupZip(): string
    {
        return $this->backupDir() . '/backup-dev.zip';
    }

    private function tempZip(): string
    {
        return sys_get_temp_dir() . '/backup-dev.zip';
    }

    private function safeUnlink(string $path): void
    {
        // ✅ Vérification is_writable() évite l'erreur "Permission denied"
        //    sans avoir besoin du @ (qui était masqué par le set_error_handler global)
        if (file_exists($path) && is_writable($path)) {
            unlink($path);
        }
    }

    private function json(array $data, int $code = 200): void
    {
        while (ob_get_level() > 0) ob_end_clean();

        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function checkAccess(): void
    {
        $env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'prod';

        if ($env !== 'dev') {
            $this->json(['success' => false, 'error' => 'Interdit hors DEV'], 403);
        }

        if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            $this->json(['success' => false, 'error' => 'Accès refusé'], 403);
        }
    }

    private function setProgress(int $value): void
    {
        file_put_contents($this->progressFile(), json_encode([
            'progress' => $value,
            'error'    => null
        ]));
    }

    public function start(): void
    {
        $this->checkAccess();

        try {

            if (!is_dir($this->backupDir())) {
                mkdir($this->backupDir(), 0775, true);
            }

            $this->safeUnlink($this->progressFile());
            $this->safeUnlink($this->backupZip());
            $this->safeUnlink($this->tempZip());

            $this->setProgress(0);

            $this->runBackup();

            $this->json([
                'success' => true,
                'message' => 'Backup terminé'
            ]);

        } catch (\Throwable $e) {

            $this->setProgress(0);

            $this->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    private function runBackup(): void
    {
        $sourceDir = ROOT_PATH;
        $zipPath   = $this->tempZip();

        $this->setProgress(10);

        /* ===== SQL DUMP ===== */

        $dbHost = getenv('DB_HOST');
        $dbUser = getenv('DB_USER');
        $dbPass = getenv('DB_PASSWORD');
        $dbName = getenv('DB_NAME');

        $sqlFile   = sys_get_temp_dir() . '/database_dump.sql';
        $errorFile = sys_get_temp_dir() . '/database_dump.err';

        // ✅ stderr séparé du fichier SQL → on peut lire le vrai message d'erreur
        // ✅ Mot de passe passé via MYSQL_PWD → pas exposé dans `ps aux`
        // ✅ --skip-ssl : le client mysqldump (MariaDB 11.x) tente SSL par défaut
        //    mais le serveur MariaDB 10.11 n'est pas configuré TLS → connexion refusée
        $cmd = sprintf(
            'MYSQL_PWD=%s mysqldump --skip-ssl -h %s -u %s %s > %s 2> %s',
            escapeshellarg($dbPass),
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            escapeshellarg($dbName),
            escapeshellarg($sqlFile),
            escapeshellarg($errorFile)
        );

        exec($cmd, $output, $code);

        if ($code !== 0) {
            $errorMsg = file_exists($errorFile)
                ? trim(file_get_contents($errorFile))
                : 'Erreur inconnue (fichier stderr absent)';

            @unlink($errorFile);

            throw new \RuntimeException('Erreur mysqldump : ' . $errorMsg);
        }

        @unlink($errorFile);

        $this->setProgress(40);

        /* ===== ZIP FILES ===== */

        $zip = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Impossible de créer le ZIP');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {

            if ($file->isDir()) continue;

            $filePath     = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourceDir) + 1);

            if (str_starts_with($relativePath, 'Storage/backups')) continue;

            $zip->addFile($filePath, $relativePath);
        }

        $zip->addFile($sqlFile, 'database_dump.sql');

        $zip->close();

        unlink($sqlFile);

        /* ===== MOVE ZIP TO STORAGE ===== */

        rename($zipPath, $this->backupZip());

        $this->setProgress(100);
    }

    public function progress(): void
    {
        $this->checkAccess();

        if (!file_exists($this->progressFile())) {
            $this->json(['success' => true, 'progress' => 0]);
        }

        $data = json_decode(file_get_contents($this->progressFile()), true);

        $this->json([
            'success'  => true,
            'progress' => $data['progress'] ?? 0
        ]);
    }

    public function download(): void
    {
        $this->checkAccess();

        $zipPath = $this->backupZip();

        if (!file_exists($zipPath)) {
            $this->json(['success' => false, 'error' => 'Fichier introuvable'], 404);
        }

        while (ob_get_level() > 0) ob_end_clean();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="backup-dev.zip"');
        header('Content-Length: ' . filesize($zipPath));

        readfile($zipPath);

        $this->safeUnlink($zipPath);
        $this->safeUnlink($this->progressFile());

        exit;
    }
}