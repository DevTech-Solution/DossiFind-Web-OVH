<?php
    //-----------------------------------------------------//
    // Fichier login_controller.php
    //-----------------------------------------------------//

    // Sécurité minimale
    declare(strict_types=1);

    // Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Si l'utilisateur est déjà connecté → dashboard
    if (isset($_SESSION['user_id'])) {
        header('Location: index.php?page=dashboard');
        exit;
    }

    // Importe la vue login_view.php
    require_once __DIR__ .'/../templates/login_view.php';
    //-----------------------------------------------------//
?>