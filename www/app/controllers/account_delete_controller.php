<?php
    //-----------------------------------------------------//
    // Fichier account_delete_controller.php
    //-----------------------------------------------------//

    // Sécurité minimale
    declare(strict_types=1);

    // Session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Protection : utilisateur non connecté
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }

    // Redirection vers le template "account_delete_view.php"
    require_once __DIR__ .'/../templates/account_delete_view.php';
?>