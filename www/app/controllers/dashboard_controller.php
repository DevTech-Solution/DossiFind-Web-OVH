<?php
    // ------------------------------------------------------------ //
    // Fichier dashboard_controller.php
    // ------------------------------------------------------------ //
    
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
    
    // ------------------------------------------------------------ //
    // Connexion à la base de données (PDO)
    // ------------------------------------------------------------ //
    require __DIR__ . '/../config/database_pdo.php';

    // ------------------------------------------------------------ //
    // Récupération des licences de l'utilisateur
    // ------------------------------------------------------------ //
    $userId = (int) $_SESSION['user_id'];

    $stmt = $pdo->prepare('
        SELECT
            l.id AS license_id,
            l.license_key,
            l.license_type,
            l.max_activations,
            l.is_active,
            l.created_at,
            COUNT(ld.id) AS used_activations
        FROM licenses l
        LEFT JOIN license_devices ld ON ld.license_id = l.id
        WHERE l.user_id = :user_id
        GROUP BY l.id
        ORDER BY l.created_at DESC
    ');

    $stmt->execute([
        'user_id' => $userId
    ]);

    $licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ------------------------------------------------------------ //
    // Variables préparées pour la vue
    // ------------------------------------------------------------ //
    $hasLicenses   = !empty($licenses);
    $totalLicenses = count($licenses);

    // Redirection vers le template "dashboard_view.php"
    require_once __DIR__ . '/../templates/dashboard_view.php';
    // ------------------------------------------------------------ //
?>