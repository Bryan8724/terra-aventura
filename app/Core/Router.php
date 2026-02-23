<?php

namespace Core;

use PDO;
use Throwable;

class Router
{
    private array $routes = [];
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /* =======================================================
       ROUTES
    ======================================================= */

    public function get(string $uri, array $action): void
    {
        $this->routes['GET'][$this->normalize($uri)] = $action;
    }

    public function post(string $uri, array $action): void
    {
        $this->routes['POST'][$this->normalize($uri)] = $action;
    }

    /* =======================================================
       DISPATCH
    ======================================================= */

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $rawUri = $_SERVER['REQUEST_URI'] ?? '/';

        $uri = parse_url($rawUri, PHP_URL_PATH) ?? '/';

        // Nettoyage si index.php est injecté par Nginx
        $uri = preg_replace('#^/index\.php#', '', $uri);

        $uri = $this->normalize($uri);

        // Gestion requêtes preflight (fetch / CORS)
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $action = $this->routes[$method][$uri] ?? null;

        if (!$action) {
            $this->handleNotFound($uri);
            return;
        }

        [$controllerName, $methodAction] = $action;
        $controllerClass = "Controllers\\$controllerName";

        try {

            if (!class_exists($controllerClass)) {
                throw new \RuntimeException("Controller $controllerClass introuvable");
            }

            $controller = $this->instantiateController($controllerClass);

            if (!method_exists($controller, $methodAction)) {
                throw new \RuntimeException(
                    "Méthode $methodAction introuvable dans $controllerClass"
                );
            }

            $controller->$methodAction();

        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /* =======================================================
       INSTANTIATION
    ======================================================= */

    private function instantiateController(string $class): object
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->getConstructor()) {
            return new $class();
        }

        $constructor = $reflection->getConstructor();
        $params      = $constructor->getParameters();

        if (
            count($params) === 1 &&
            $params[0]->getType()?->getName() === PDO::class
        ) {
            return new $class($this->db);
        }

        return new $class();
    }

    /* =======================================================
       NORMALIZE URI
    ======================================================= */

    private function normalize(string $uri): string
    {
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        return $uri ?: '/';
    }

    /* =======================================================
       404
    ======================================================= */

    private function handleNotFound(string $uri): void
    {
        http_response_code(404);

        if ($this->isApi($uri)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Route introuvable'
            ]);
            return;
        }

        echo '404 - Page not found';
    }

    /* =======================================================
       EXCEPTION HANDLER
    ======================================================= */

    private function handleException(Throwable $e): void
    {
        http_response_code(500);

        $env = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'prod';

        if ($env === 'dev') {

            echo "<pre style='background:#111;color:#ff6b6b;padding:20px'>";
            echo "💥 ERREUR SERVEUR\n\n";
            echo "Message : " . $e->getMessage() . "\n\n";
            echo "Fichier : " . $e->getFile() . "\n";
            echo "Ligne   : " . $e->getLine() . "\n\n";
            echo "Trace :\n" . $e->getTraceAsString();
            echo "</pre>";

            return;
        }

        echo '500 - Erreur serveur';
    }

    /* =======================================================
       API DETECTION
    ======================================================= */

    private function isApi(string $uri): bool
    {
        return str_starts_with($uri, '/api/');
    }
}