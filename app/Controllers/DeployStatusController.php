<?php

namespace Controllers;

class DeployStatusController
{
    private string $statusFile = '/srv/scripts/deploy-status.json';

    public function index(): void
    {
        // DEV uniquement
        if (getenv('APP_ENV') !== 'dev') {
            http_response_code(404);
            return;
        }

        // Admin uniquement
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            http_response_code(403);
            return;
        }

        header('Content-Type: application/json');

        if (!file_exists($this->statusFile)) {
            echo json_encode([
                'status' => 'idle',
                'progress' => 0
            ]);
            return;
        }

        $content = file_get_contents($this->statusFile);

        if (!$content) {
            echo json_encode([
                'status' => 'idle',
                'progress' => 0
            ]);
            return;
        }

        echo $content;
    }
}
