<?php
    // ------------------------------------------------------------ //
    // Fichier password_reset_controller.php
    // ------------------------------------------------------------ //

    // Sécurité minimale
    declare(strict_types=1);

    // Connexion BDD
    require_once __DIR__ . '/../config/database_pdo.php';

    // Sécurité
    require_once __DIR__ . '/../helpers/security.php';

    // Mail
    require_once __DIR__ . '/../helpers/mailer.php';

    // ------------------------------------------------------------ //
    //- Mode DEV : désactiver l'envoi des mails (Mailjet bloqué)
    //- Mettre en true la valeur quand ça fontionnera
    // ------------------------------------------------------------ //
    define('MAIL_ENABLED', true);
    // ------------------------------------------------------------ //

    $success_message = null;
    $error_message   = null;
    $show_password_form = false;
    $token = null;

    // ============================================================
    // GET : lien depuis l'email
    // ============================================================
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {

        $token = trim($_GET['token']);
        $token_hash = hash('sha256', $token);

        $stmt = $pdo->prepare("
            SELECT pr.id, pr.user_id
            FROM password_resets pr
            WHERE pr.token_hash = :token_hash
            AND pr.used_at IS NULL
            AND pr.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute(['token_hash' => $token_hash]);
        $reset = $stmt->fetch();

        if ($reset) {
            $show_password_form = true;
        } else {
            $error_message = "Le lien de réinitialisation est invalide ou expiré.";
        }
    }

    // ============================================================
    // POST : demande de reset OU nouveau mot de passe
    // ============================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // --------------------------------------------------------
        // 1) Demande de reset (email)
        // --------------------------------------------------------
        if (isset($_POST['email'])) {

            $email = trim($_POST['email']);

            $stmt = $pdo->prepare("
                SELECT id FROM users
                WHERE email = :email
                AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user) {

                // Invalider anciens tokens
                $pdo->prepare("
                    UPDATE password_resets
                    SET used_at = NOW()
                    WHERE user_id = :user_id
                    AND used_at IS NULL
                ")->execute(['user_id' => $user['id']]);

                // Génération token
                $token = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $token);

                $pdo->prepare("
                    INSERT INTO password_resets (user_id, token_hash, expires_at)
                    VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))
                ")->execute([
                    'user_id'    => $user['id'],
                    'token_hash' => $token_hash
                ]);

                // -------------------------------------------------------------------------------- //
                //- Envoi mail réinitialisation password
                if (MAIL_ENABLED) {
                    sendPasswordResetMail($email, $token);
                    $success_message =
                        'Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.' .
                        'Lien de test :' . 'http://127.0.0.1/dashboard/DossiFind-Web/www/index.php?page=password-reset&token=' . $token;
                // -------------------------------------------------------------------------------- //
                } else {
                    $resetLink = $token;
                    // DEV ONLY : afficher le lien à l’écran
                    $success_message = "Lien de réinitialisation NAS : http://127.0.0.1/dashboard/DossiFind-Web/www/index.php?page=password-reset&token=$resetLink";
                }
            }
        }

        // --------------------------------------------------------
        // 2) Nouveau mot de passe
        // --------------------------------------------------------
        if (
            isset($_POST['token'], $_POST['password'], $_POST['password_confirm'])
        ) {
            $token = trim($_POST['token']);
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];

            if ($password !== $password_confirm) {
                $error_message = "Les mots de passe ne correspondent pas.";
            } elseif (!isPasswordStrong($password)) {
            $error_message = "Le mot de passe doit contenir au moins 12 caractères, avec des lettres, des chiffres et un caractère spécial.";
            } else {

                $token_hash = hash('sha256', $token);

                $stmt = $pdo->prepare("
                    SELECT pr.id, pr.user_id
                    FROM password_resets pr
                    WHERE pr.token_hash = :token_hash
                    AND pr.used_at IS NULL
                    AND pr.expires_at > NOW()
                    LIMIT 1
                ");
                $stmt->execute(['token_hash' => $token_hash]);
                $reset = $stmt->fetch();

                if (!$reset) {
                    $error_message = "Le lien de réinitialisation est invalide ou expiré.";
                } else {

                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    // Update password
                    $pdo->prepare("
                        UPDATE users
                        SET password_hash = :password_hash,
                            updated_at = NOW()
                        WHERE id = :user_id
                    ")->execute([
                        'password_hash' => $password_hash,
                        'user_id'       => $reset['user_id']
                    ]);

                    // Marquer token utilisé
                    $pdo->prepare("
                        UPDATE password_resets
                        SET used_at = NOW()
                        WHERE id = :id
                    ")->execute(['id' => $reset['id']]);
                    
                    //-----------------------------------------------------//
                    //- Envoi mail validation changement password
                    if (MAIL_ENABLED) {
                        $stmt = $pdo->prepare("
                            SELECT email
                            FROM users
                            WHERE id = :id
                            LIMIT 1
                        ");
                        $stmt->execute(['id' => $reset['user_id']]);
                        $userEmail = $stmt->fetchColumn();

                        if ($userEmail) {
                            sendPasswordChangedMail($userEmail);
                        }
                    }
                    //-----------------------------------------------------//
                    header('Location: index.php?page=login&reset=success');
                    exit;
                }
            }
        }
    }

    // Redirection vers le template "password_view.php"
    require __DIR__ . '/../templates/password_view.php';
//-----------------------------------------------------//