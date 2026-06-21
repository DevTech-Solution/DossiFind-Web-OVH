<?php
    //-----------------------------------------------------//
    // Fichier account_edit_controller.php
    //-----------------------------------------------------//

    // Sécurité minimale : force le typage strict en PHP
    declare(strict_types=1);

    // Démarrage de la session si elle n’est pas déjà active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Protection : utilisateur obligatoirement connecté
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }

    // Connexion PDO
    require_once __DIR__ . '/../config/database_pdo.php';

    // ID utilisateur depuis la session
    $userId = (int) $_SESSION['user_id'];

    //-----------------------------------------------------//
    // RÉCUPÉRATION DE L’UTILISATEUR (OBLIGATOIRE POUR LA VUE)
    //-----------------------------------------------------//
    $stmt = $pdo->prepare('
        SELECT
            id,
            email
        FROM users
        WHERE id = :id
        LIMIT 1
    ');

    $stmt->execute(['id' => $userId]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Sécurité supplémentaire : utilisateur introuvable
    if (!$user) {
        // Session incohérente → déconnexion propre
        session_destroy();
        header('Location: index.php?page=login');
        exit;
    }

    //-----------------------------------------------------//
    // RÉCUPÉRATION DES ACHATS UTILISATEUR
    //-----------------------------------------------------//
    $stmt = $pdo->prepare('
        SELECT
            id,
            created_at,
            license_type,
            license_quantity,
            price_total,
            payment_status
        FROM purchases
        WHERE user_id = :user_id
        ORDER BY created_at DESC
    ');

    $stmt->execute(['user_id' => $userId]);

    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //-----------------------------------------------------//
    // CHARGEMENT DE LA VUE
    //-----------------------------------------------------//
    require_once __DIR__ . '/../templates/account_edit_view.php';
    //-----------------------------------------------------//
?>