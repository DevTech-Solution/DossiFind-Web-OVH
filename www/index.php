<?php
    // ------------------------------------------------------------ //
    // Fichier "Index.php"
    // ------------------------------------------------------------ //
    // Active le typage strict pour éviter que PHP soit trop permissif et force le respect des types déclarés
    declare(strict_types=1);

    // Récupère le chemin de base du script courant sans le slash final
    $BASE_PATH = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

    // Définit une "constante globale" pour réutiliser le chemin de base
    define('JS_BASE_PATH', $BASE_PATH);

    // Détection du protocole utilisé par le navigateur
    // Si la variable HTTPS existe et n’est pas "off" → on est en HTTPS
    // Sinon → on est en HTTP
    // Cela permet d’éviter de coder en dur http ou https
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    // Récupération du nom de domaine ou de l’hôte (serveur Apache => $_SERVER)
    // Exemples possibles :
    // 127.0.0.1
    // localhost
    // dossifind.com
    // www.dossifind.com
    $host = $_SERVER['HTTP_HOST'];

    // Récupération du chemin du script courant
    // $_SERVER['SCRIPT_NAME'] contient le chemin COMPLET du fichier appelé
    // Exemple : /dashboard/DossiFind-Web/www/index.php
    //
    // dirname() permet de remonter d’un niveau (le dossier)
    // rtrim() supprime le "/" final si présent
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

    // Construction de l’URL de base du projet
    // Exemple final possible :
    // https://dossifind.fr/dashboard/DossiFind-Web/www
    //
    // Cette constante servira partout dans le projet
    // pour générer des liens propres et portables
    define('BASE_URL', $scheme . '://' . $host . $scriptDir);
    
    // ------------------------------------------------------------ //
    // Gestion de la page demandée via le routage
    // ------------------------------------------------------------ //

    // Vérifie si un paramètre "page" est présent dans l’URL
    // Exemple : index.php?page=login
    if (!empty($_GET['page'])) {
        // Une page a été demandée explicitement
        // On récupère sa valeur
        $page = $_GET['page'];
    } else {
        // Aucune page précisée dans l’URL
        // On définit la page par défaut (page d’accueil)
        $page = 'index';
    }
    // ------------------------------------------------------------ //
    // Liste blanche des pages autorisées (whitelist des routes autorisées)
    $routes = [
        'index'                 => 'index_controller.php',
        'compatibility'         => 'compatibility_controller.php',
        'features'              => 'features_controller.php',
        'login'                 => 'login_controller.php',
        'logout'                => 'logout_controller.php',
        'dashboard'             => 'dashboard_controller.php',
        'account-api'           => 'account_api.php',
        'payment_checkout'      => 'payment_checkout_controller.php',
        'order_setup'           => 'order_setup_controller.php',
        'payment'               => 'payment_controller.php',
        'payment_result'        => 'payment_result_controller.php',
        'license'               => 'license_controller.php',
        'profile'               => 'account_edit_controller.php',
        'delete-account'        => 'account_delete_controller.php',
        'account_delete_action' => 'delete_controller.php',
        'account-update'        => 'account_update_controller.php',
        'invoice-print'         => 'invoice_print_controller.php',
        'cgv'                   => 'cgv_controller.php',
        'privacy'               => 'privacy_controller.php',
        'legal'                 => 'legal_controller.php',
        'password-reset'        => 'password_reset_controller.php',
    ];

    // Chemin vers les contrôleurs
    $controllersPath = __DIR__ . '/app/controllers/';

    // Routage
    if (array_key_exists($page, $routes)) {
        require $controllersPath . $routes[$page];
    } else {
        // Page inconnue → 404
        http_response_code(404);
        require $controllersPath . '404_controller.php';
    }
    // ------------------------------------------------------------ //
?>