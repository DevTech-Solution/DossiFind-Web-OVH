<?php
    // ------------------------------------------------------------ //
    // Fichier "order_setup_controller.php"
    // ------------------------------------------------------------ //

    // Active le typage strict PHP
    declare(strict_types=1);

    // Démarre la session pour gérer les données temporaires (erreurs, checkout)
    session_start();

    // Recuperation information si existe
    $checkout = $_SESSION['checkout'] ?? null;

    // Redirection vers le template "order_setup_view.php"
    require_once __DIR__ .'/../templates/order_setup_view.php';
    // ------------------------------------------------------------ //
?>