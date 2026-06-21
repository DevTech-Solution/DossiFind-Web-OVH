<?php
    // ------------------------------------------------------------ //
    // account_api.php
    // API JSON pour la vérification d'achat, création de compte
    // et connexion utilisateur (flux AJAX)
    // ------------------------------------------------------------ //

    declare(strict_types=1);

    // Réponse JSON uniquement
    header('Content-Type: application/json');

    try {

        // Sécurité basique : POST uniquement
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new RuntimeException('Méthode non autorisée');
        }

        // Connexion à la base de données
        require_once __DIR__ . '/../config/database_pdo.php';

        // Import security
        require_once __DIR__ .'/../helpers/security.php';

        // ----------------------------------------------------------------------------------------------- //
        // Connexion BDD non-joignable
        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('Connexion base de données non-joignable');
        }
        // ----------------------------------------------------------------------------------------------- //
        // Déclare une fonction qui rattache les achats existants à un utilisateur donné
        function attachPurchasesToUser(PDO $pdo, int $userId, string $email): void
        {
            $stmt = $pdo->prepare('UPDATE purchases SET user_id = :user_id WHERE email = :email');
            $stmt->execute([
                'user_id' => $userId,
                'email'   => $email
            ]);
        }

        // Déclare une fonction qui rattache les licences à un utilisateur via les achats associés
        function attachLicensesToUser(PDO $pdo, int $userId, string $email): void
        {
            $stmt = $pdo->prepare('UPDATE licenses l JOIN purchases p ON p.id = l.purchase_id SET l.user_id = :user_id WHERE p.email = :email');
            $stmt->execute([
                'user_id' => $userId,
                'email'   => $email
            ]);
        }

        // ------------------------------------------------------------ //
        // Données reçues
        // ------------------------------------------------------------ //
        $email = strtolower(trim($_POST['email'] ?? ''));
        $action = $_POST['action'] ?? 'check';

        // Validation minimale
        if (empty($email)) {
            throw new RuntimeException('Adresse email manquante');
        }

        // ------------------------------------------------------------ //
        // ACTION : CHECK
        // Vérifie si un achat existe pour cet email
        // ------------------------------------------------------------ //
        if ($action === 'check') {

            // Vérifie si un compte utilisateur existe
            $sqlUser = 'SELECT id FROM users WHERE email = :email LIMIT 1';
            $stmt = $pdo->prepare($sqlUser);
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                echo json_encode([
                    'status' => 'LOGIN'
                ]);
                exit;
            }

            // Vérifie si l'email a déjà été utilisé dans le passé
            $stmt = $pdo->prepare(
                'SELECT user_id FROM user_email_history WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $emailHistory = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($emailHistory) {
                echo json_encode([
                    'status' => 'EMAIL_OLD'
                ]);
                exit;
            }

            // Sinon seulement, vérifier les achats
            $sql = 'SELECT id FROM purchases WHERE email = :email LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
            $purchase = $stmt->fetch();

            if (!$purchase) {
                echo json_encode([
                    'status' => 'NO_PURCHASE'
                ]);
                exit;
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Email validé pour la suite du tunnel
            $_SESSION['auth_email'] = $email;

            echo json_encode([
                'status' => 'CREATE_ACCOUNT'
            ]);
            exit;
        }

        // ------------------------------------------------------------ //
        // ACTION : CREATE
        // Création d’un compte utilisateur
        // ------------------------------------------------------------ //

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($action === 'create') {

            // Vérifie que le tunnel est valide
            if (!isset($_SESSION['auth_email'])) {
                echo json_encode([
                    'error' => 'Session expirée. Veuillez recommencer.'
                ]);
                exit;
            }

            // Vérifie que l’email n’a pas changé
            if ($_SESSION['auth_email'] !== $email) {
                // Sécurité : on invalide la session
                unset($_SESSION['auth_email']);

                echo json_encode([
                    'error' => "Adresse email non valide pour cette étape. Veuillez recommencer."
                ]);
                exit;
            }

            // IMPORTANT : on force l’email serveur
            $email = $_SESSION['auth_email'];

            $password        = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            // Password vide
            if (empty($password) || empty($passwordConfirm)) {
                echo json_encode([
                    'error' => 'Mot de passe requis'
                ]);
                exit;
            }

            // Password non-identique
            if ($password !== $passwordConfirm) {
                echo json_encode([
                    'error' => 'Les mots de passe ne correspondent pas'
                ]);
                exit;
            }

            // Password robustesse -> helpers/security.php
            if (!isPasswordStrong($password)) {
                echo json_encode([
                    'error' => 'Le mot de passe doit contenir au moins 12 caractères, avec lettres, chiffres et caractères spéciaux.'
                ]);
                exit;
            }

            // Chiffre le password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $sql = 'INSERT INTO users (email, password_hash) VALUES (:email, :password_hash)';

                $stmt = $pdo->prepare($sql);
                $stmt->execute(['email' => $email,'password_hash' => $passwordHash]);
            } catch (Throwable $e) {
                echo json_encode([
                    'error' => 'Compte déjà existant'
                ]);
                exit;
            }

            $userId = (int) $pdo->lastInsertId();

            // Historise l'email utilisateur (email brûlé définitivement)
            $stmt = $pdo->prepare(
                'INSERT INTO user_email_history (user_id, email) VALUES (:user_id, :email)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'email'   => $email
            ]);

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['user_id'] = $userId;

            // RATTACHEMENTS
            attachPurchasesToUser($pdo, $userId, $email);
            attachLicensesToUser($pdo, $userId, $email);

            // L’email ne doit plus jamais être réutilisé dans ce tunnel
            unset($_SESSION['auth_email']);


            echo json_encode([
                'status' => 'SUCCESS'
            ]);
            exit;
        }

        // ------------------------------------------------------------ //
        // ACTION : LOGIN
        // Connexion utilisateur
        // ------------------------------------------------------------ //
        if ($action === 'login') {


            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if (isset($_SESSION['auth_email']) && $_SESSION['auth_email'] !== $email) {
                unset($_SESSION['auth_email']);

                echo json_encode([
                    'error' => 'Session de connexion invalide.'
                ]);
                exit;
            }

            $password = $_POST['password'] ?? '';

            if (empty($password)) {
                echo json_encode(['error' => 'Mot de passe requis']);
                exit;
            }

            $sql = 'SELECT id, password_hash FROM users WHERE email = :email LIMIT 1';

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'email' => $email
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                echo json_encode([
                    'error' => 'Identifiants invalides'
                ]);
                exit;
            }

            // Démarrage de session ici (API autorisée à le faire)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $userId = (int) $user['id'];
            $_SESSION['user_id'] = (int) $user['id'];

            // RATTACHEMENTS LICENCES
            attachPurchasesToUser($pdo, $userId, $email);
            attachLicensesToUser($pdo, $userId, $email);

            echo json_encode([
                'status' => 'SUCCESS'
            ]);
            exit;
        }

        // ------------------------------------------------------------ //
        // Action inconnue
        // ------------------------------------------------------------ //
        echo json_encode([
            'error' => 'Action invalide'
        ]);
        exit;
    } catch (Throwable $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
    exit;
}