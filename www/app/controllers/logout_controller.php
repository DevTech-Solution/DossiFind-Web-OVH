<?php
    // ------------------------------------------------------------ //
    // Fichier "logout_controller.php"
    // Contrôleur de déconnexion utilisateur
    // Supprime la session active puis redirige vers la page d’accueil
    // ------------------------------------------------------------ //

    // Démarre la session
    session_start();

    // Destruction complète de la session (déconnexion)
    session_destroy();

    // Redirection vers la page d’entrée du site
    header('Location: index.php');
    exit;
    // ------------------------------------------------------------ //
?>
