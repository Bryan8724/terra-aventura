<?php

namespace Controllers;

use Models\Quete;
use Core\Auth;
use Core\ApiAuth;
use Core\Response;
use PDO;

class QueteController
{
    private Quete $quete;

    public function __construct(private PDO $db)
    {
        $this->quete = new Quete($db);
    }

    public function index(): void
    {
        $isApi = str_starts_with(
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            '/api/'
        );

        /*
        |--------------------------------------------------------------------------
        | 🔐 AUTHENTIFICATION
        |--------------------------------------------------------------------------
        */
        if ($isApi) {
            $user = ApiAuth::requireAuth();
        } else {
            Auth::check();
            $user = $_SESSION['user'];
        }

        $isAdmin = (($user['role'] ?? '') === 'admin');

        /*
        |--------------------------------------------------------------------------
        | Base : toutes les quêtes / objets / parcours
        |--------------------------------------------------------------------------
        */
        $rowsAll = $this->quete->getAllCompletes(null);

        /*
        |--------------------------------------------------------------------------
        | Parcours validés par l'utilisateur
        |--------------------------------------------------------------------------
        */
        $doneParcours = [];

        if (!$isAdmin) {
            $rowsDone = $this->quete->getAllCompletes((int)$user['id']);

            foreach ($rowsDone as $r) {
                if (!empty($r['parcours_id']) && !empty($r['objet_obtenu'])) {
                    $doneParcours[(int)$r['parcours_id']] = true;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Assemblage logique
        |--------------------------------------------------------------------------
        */
        $quetes = [];

        foreach ($rowsAll as $row) {

            $qid = (int)($row['quete_id'] ?? 0);
            if ($qid <= 0) {
                continue;
            }

            $quetes[$qid] ??= [
                'id'     => $qid,
                'nom'    => (string)($row['quete_nom'] ?? ''),
                'saison' => $row['saison'] ?? null,
                'objets' => [],
            ];

            if (!empty($row['objet_id'])) {

                $oid = (int)$row['objet_id'];

                $quetes[$qid]['objets'][$oid] ??= [
                    'id'       => $oid,
                    'nom'      => (string)($row['objet_nom'] ?? ''),
                    'parcours' => [],
                    'obtenu'   => false,
                ];

                if (!empty($row['parcours_id'])) {

                    $pid = (int)$row['parcours_id'];

                    $parcoursObtenu = (!$isAdmin && isset($doneParcours[$pid]));

                    if ($parcoursObtenu) {
                        $quetes[$qid]['objets'][$oid]['obtenu'] = true;
                    }

                    $quetes[$qid]['objets'][$oid]['parcours'][$pid] = [
                        'id'     => $pid,
                        'nom'    => (string)($row['parcours_nom'] ?? ''),
                        'ville'  => (string)($row['ville'] ?? ''),
                        'dep'    => (string)($row['departement'] ?? ''),
                        'logo'   => $row['poiz_logo'] ?? null,
                        'obtenu' => $parcoursObtenu,
                    ];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Normalisation finale
        |--------------------------------------------------------------------------
        */
        foreach ($quetes as &$q) {
            foreach ($q['objets'] as &$o) {
                $o['parcours'] = array_values($o['parcours']);
            }
            $q['objets'] = array_values($q['objets']);
        }

        $quetes = array_values($quetes);

        /*
        |--------------------------------------------------------------------------
        | 🔐 VERSION API
        |--------------------------------------------------------------------------
        */
        if ($isApi) {

            Response::json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id'       => $user['id'],
                        'username' => $user['username'],
                        'role'     => $user['role']
                    ],
                    'quetes' => $quetes
                ]
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 🌐 VERSION WEB
        |--------------------------------------------------------------------------
        */
        $title = 'Quêtes';

        ob_start();
        require VIEW_PATH . '/quetes/index.php';
        $content = ob_get_clean();

        require VIEW_PATH . '/partials/layout.php';
    }
}
