<?php
    // ----------------------------------------------------------------------------------------------------- //
    // account_update_controller.php
    // ----------------------------------------------------------------------------------------------------- //

    declare(strict_types=1);

    header('Content-Type: application/json; charset=utf-8');

    session_start();

    // Sécurité minimale
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Utilisateur non authentifié.'
        ]);
        exit;
    }

    // PDO $pdo attendu
    require_once __DIR__ . '/../config/database_pdo.php';

    $userId = (int) $_SESSION['user_id'];
    $action = $_POST['action'] ?? null;

    if (!$action) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Action manquante.'
        ]);
        exit;
    }
    // ----------------------------------------------------------------------------------------------------- //
    try {
        // CHANGEMENT MOT DE PASSE
        if ($action === 'password') {

            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Import security
            require_once __DIR__ .'/../helpers/security.php';

            // Password pas identique
            if ($newPassword !== $confirmPassword) {
                throw new Exception('Les mots de passe ne correspondent pas.');
            }

            // Robustesse du mot de passe
            if (!isPasswordStrong($newPassword)) {
                throw new Exception(
                    'Le mot de passe doit contenir au moins 12 caractères, une lettre, un chiffre et un caractère spécial.'
                );
            }

            // Récupération du hash actuel
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                throw new Exception('Mot de passe actuel incorrect.');
            }

            // Mise à jour du mot de passe
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');

            $stmt->execute([
                'hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'id'   => $userId
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Mot de passe mis à jour avec succès.'
            ]);
            exit;
        }
        // ----------------------------------------------------------------------------------------------------- //
        // CHANGEMENT EMAIL
        if ($action === 'email') {

            $email = trim($_POST['email'] ?? '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Adresse email invalide.');
            }

            // Vérifier unicité
            $stmt = $pdo->prepare(
                'SELECT id FROM users WHERE email = :email AND id != :id'
            );
            $stmt->execute([
                'email' => $email,
                'id'    => $userId
            ]);

            if ($stmt->fetch()) {
                throw new Exception('Cette adresse email est déjà utilisée.');
            }

            // Vérifier si l'email a déjà été utilisé par un autre utilisateur (historique)
            $stmt = $pdo->prepare(
                'SELECT user_id FROM user_email_history WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $emailHistory = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($emailHistory && (int) $emailHistory['user_id'] !== $userId) {
                throw new Exception('Cette adresse email a déjà été utilisée.');
            }

            // Mise à jour email
            $stmt = $pdo->prepare('UPDATE users SET email = :email, updated_at = NOW() WHERE id = :id');

            $stmt->execute([
                'email' => $email,
                'id'    => $userId
            ]);

            // Historiser le nouvel email utilisateur
            $stmt = $pdo->prepare(
                'INSERT IGNORE INTO user_email_history (user_id, email)
                VALUES (:user_id, :email)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'email'   => $email
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Adresse email mise à jour avec succès.'
            ]);
            exit;
        }

        // Action inconnue
        throw new Exception('Action invalide.');

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
    // ----------------------------------------------------------------------------------------------------- //