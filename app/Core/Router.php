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

    public function get(string $uri, array $action): void
    {
        $this->routes['GET'][$this->normalize($uri)] = $action;
    }

    public function post(string $uri, array $action): void
    {
        $this->routes['POST'][$this->normalize($uri)] = $action;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = $this->normalize(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/'
        );

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

            $this->handleException($e, $uri);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Instanciation intelligente
    |--------------------------------------------------------------------------
    */
    private function instantiateController(string $class): object
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->getConstructor()) {
            return new $class();
        }

        $constructor = $reflection->getConstructor();
        $params      = $constructor->getParameters();

        if (count($params) === 1 && $params[0]->getType()?->getName() === PDO::class) {
            return new $class($this->db);
        }

        return new $class();
    }

    /*
    |--------------------------------------------------------------------------
    | Normalisation URI
    |--------------------------------------------------------------------------
    */
    private function normalize(string $uri): string
    {
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        return $uri ?: '/';
    }

    /*
    |--------------------------------------------------------------------------
    | 404 Gestion propre
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Gestion erreurs contrôleurs
    |--------------------------------------------------------------------------
    */
    private function handleException(Throwable $e, string $uri): void
    {
        http_response_code(500);

        if ($this->isApi($uri)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erreur serveur'
            ]);
            return;
        }

        echo '500 - Erreur serveur';
    }

    /*
    |--------------------------------------------------------------------------
    | Détection API
    |--------------------------------------------------------------------------
    */
    private function isApi(string $uri): bool
    {
        return str_starts_with($uri, '/api/');
    }
}
