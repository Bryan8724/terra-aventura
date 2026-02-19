<?php

namespace Controllers;

class DeployStatusController
{
    public function index(): void
    {
        $appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? '';

        if ($appEnv !== 'prod') {
            http_response_code(404);
            return;
        }

        $token = $_GET['token'] ?? '';
        $expectedToken = 'MON_TOKEN_SUPER_SECRET_LONG_ET_COMPLEXE';

        if ($token !== $expectedToken) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        header("Access-Control-Allow-Origin: https://dev.terra.bryanmargot19210.fr");
        header("Content-Type: application/json");

        $statusFile = '/srv/scripts/deploy-status.json';

        if (!file_exists($statusFile)) {
            echo json_encode([
                'status' => 'idle',
                'progress' => 0
            ]);
            return;
        }

        readfile($statusFile);
    }
}
