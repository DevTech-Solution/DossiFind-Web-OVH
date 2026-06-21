<?php
    // ------------------------------------------------------------ //
    // Fichier delete_controller.php
    // ------------------------------------------------------------ //

    declare(strict_types=1);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?page=profile');
        exit;
    }

    session_start();

    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit;
    }

    // Chargement PDO
    require_once __DIR__ . '/../config/database_pdo.php';

    // Chargement Mail
    require_once __DIR__ . '/../helpers/mailer.php';

    $userId = (int) $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // 1. Révoquer tous les devices
        $stmt = $pdo->prepare("
            UPDATE license_devices ld
            JOIN licenses l ON l.id = ld.license_id
            SET ld.revoked_at = NOW()
            WHERE l.user_id = :user_id
            AND ld.revoked_at IS NULL
        ");
        $stmt->execute(['user_id' => $userId]);

        // 2. Révoquer toutes les licences
        $stmt = $pdo->prepare("
            UPDATE licenses
            SET is_active = 0,
                revoked_at = NOW(),
                user_id = NULL
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);

        // 3. UPDATE users
        $stmt = $pdo->prepare('SELECT email FROM users WHERE id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $userEmail = $stmt->fetchColumn();

        if (!$userEmail) {
            throw new RuntimeException('Email utilisateur introuvable');
        }

        // 4. Anonymiser le compte utilisateur
        $stmt = $pdo->prepare("
            UPDATE users
            SET email = :email,
                password_hash = :password,
                is_active = 0,
                updated_at = NOW()
            WHERE id = :user_id
        ");

        $stmt->execute([
            'email'    => 'deleted_user_' . $userId . '@deleted.local',
            'password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            'user_id'  => $userId
        ]);

        $pdo->commit();

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    // 5. Envoi mail apres commit
    if ($userEmail) {
        sendAccountDeletionMail($userEmail);
    }

    // 6. Déconnexion complète
    $_SESSION = [];
    session_destroy();

    // 5. Redirection vers la page d’accueil (clé front controller)
    header('Location: index.php?page=index');
    exit;