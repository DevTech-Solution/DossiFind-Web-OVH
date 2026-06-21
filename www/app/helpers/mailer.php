<?php

    // Composer
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    // Chargement de l'autoloader de Composer 
    require_once __DIR__ . '/../../../vendor/autoload.php';

    // Fichier configuration
    $config = require __DIR__ . '/../config/mail.php';

    function getMailer(): PHPMailer
    {
        global $config;

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port       = $config['port'];

        // CRITIQUE
        $mail->Timeout = 10;
        $mail->SMTPKeepAlive = false;

        // ENCODAGE
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->isHTML(true);

        return $mail;
    }

    // --------------------------------------------------------------------- //
    //-- Mail Achat
    function sendPurchaseConfirmationMail(
        string $email,
        string $orderRef,
        float $amount
    ): void {
        try {
            $mail = getMailer();
            $mail->addAddress($email);

            $formattedOrderRef = 'DF-' . str_pad((string)$orderRef, 6, '0', STR_PAD_LEFT);

            $mail->Subject = 'Confirmation de votre commande DossiFind';

            $mail->Body = "
            <div style='font-family:Arial, sans-serif; font-size:14px; color:#333; max-width:600px; margin:auto'>

                <h1 style='text-align:center; margin-bottom:30px;'>DossiFind</h1>

                <p>Bonjour,</p>

                <p>Merci pour votre achat sur <strong>DossiFind</strong>. Votre commande a bien été prise en compte.</p>

                <div style='margin:20px 0; padding:15px; background:#f7f7f7; border-radius:4px'>
                    <p style='margin:0'><strong>Référence de commande :</strong> {$formattedOrderRef}</p>
                    <p style='margin:5px 0 0'><strong>Montant :</strong> " . number_format($amount, 2) . " €</p>
                </div>

                <p>Vous pouvez accéder à votre compte via le lien ci-dessous :</p>

                <p>
                    <a href='" . BASE_URL . "/login'
                    style='color:#0066cc; text-decoration:none; font-weight:bold'>
                        Accéder à mon compte
                    </a>
                </p>

                <hr style='border:none; border-top:1px solid #e0e0e0; margin:30px 0'>

                <p style='font-size:12px; color:#777'>L’équipe DossiFind</p>

            </div>
            ";

            $mail->AltBody =
                "Merci pour votre achat.\n" .
                "Commande : {$orderRef}\n" .
                "Montant : " . number_format($amount, 2) . " €\n" .
                BASE_URL . "/login";

            $mail->send();

        } catch (Exception $e) {
            error_log('MAIL ACHAT ERROR: ' . $e->getMessage());
        }
    }
    // --------------------------------------------------------------------- //
    //-- Mail Suppression compte
    function sendAccountDeletionMail(string $email): void
    {
    try {
        $mail = getMailer();
        $mail->addAddress($email);

        $mail->Subject = 'Confirmation de suppression de votre compte DossiFind';

        $mail->Body = "
        <div style='font-family:Arial, sans-serif; font-size:14px; color:#333; max-width:600px; margin:auto'>

            <h1 style='text-align:center; margin-bottom:30px;'>DossiFind</h1>

            <p>Bonjour,</p>

            <p>Nous vous confirmons que votre compte <strong>DossiFind</strong> a été supprimé avec succès.</p>

            <div style='margin:20px 0; padding:15px; background:#f7f7f7; border-radius:4px'>
                <p style='margin:0'>Toutes les licences associées ont été désactivées.</p>
                <p style='margin:5px 0 0'>Vos données personnelles ont été supprimées conformément à la réglementation en vigueur.</p>
            </div>

            <p>Si vous n’êtes pas à l’origine de cette action,veuillez nous contacter immédiatement.</p>

            <hr style='border:none; border-top:1px solid #e0e0e0; margin:30px 0'>

            <p style='font-size:12px; color:#777'>L’équipe DossiFind</p>

        </div>
        ";

        $mail->AltBody =
            "Votre compte DossiFind a été supprimé.\n\n" .
            "Toutes les licences associées ont été désactivées.\n\n" .
            "Si vous n’êtes pas à l’origine de cette action, contactez-nous.";

        $mail->send();

        } catch (Exception $e) {
            error_log('MAIL ACCOUNT DELETE ERROR: ' . $e->getMessage());
        }
    }

    // --------------------------------------------------------------------- //
    //-- Mail Reinitialisation Password
    function sendPasswordResetMail(string $email, string $token): void
    {
        try {
            $mail = getMailer();
            $mail->addAddress($email);

            $resetLink = BASE_URL . '/index.php?page=password-reset&token=' . urlencode($token);

            $mail->Subject = 'Réinitialisation de votre mot de passe DossiFind';

            $mail->Body = "
            <div style='font-family:Arial, sans-serif; font-size:14px; color:#333; max-width:600px; margin:auto'>

                <h1 style='text-align:center; margin-bottom:30px;'>DossiFind</h1>

                <p>Bonjour,</p>

                <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>

                <p>Pour choisir un nouveau mot de passe, cliquez sur le lien ci-dessous :</p>

                <p style='text-align:center; margin:30px 0'>
                    <a href='{$resetLink}'
                    style='display:inline-block; padding:12px 20px; background:#0066cc; color:#ffffff; text-decoration:none; border-radius:4px; font-weight:bold'>
                        Réinitialiser mon mot de passe
                    </a>
                </p>

                <p style='font-size:13px; color:#555'>
                    Ce lien est valable pendant <strong>1 heure</strong>.<br>
                    Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer cet email.
                </p>

                <hr style='border:none; border-top:1px solid #e0e0e0; margin:30px 0'>

                <p style='font-size:12px; color:#777'>L’équipe DossiFind</p>

            </div>
            ";

            $mail->AltBody =
                "Réinitialisation de votre mot de passe DossiFind\n\n" .
                "Pour choisir un nouveau mot de passe, ouvrez le lien suivant :\n" .
                $resetLink . "\n\n" .
                "Ce lien est valable pendant 1 heure.\n" .
                "Si vous n’êtes pas à l’origine de cette demande, ignorez cet email.";

            $mail->send();

        } catch (Exception $e) {
            error_log('MAIL PASSWORD RESET ERROR: ' . $e->getMessage());
        }
    }
    // --------------------------------------------------------------------- //
    //- Confirmation Mot de passe modif
    function sendPasswordChangedMail(string $email): void
    {
        try {
            $mail = getMailer();
            $mail->addAddress($email);

            $mail->Subject = 'Votre mot de passe a été modifié';

            $mail->Body = "
                <div style='font-family:Arial, sans-serif; font-size:14px; color:#333'>
                    <h1 style='text-align:center; margin-bottom:30px;'>DossiFind</h1>

                    <p>Bonjour,</p>

                    <p>Nous vous confirmons que votre mot de passe a été modifié avec succès.</p>

                    <p>Si vous n’êtes pas à l’origine de cette action, nous vous recommandons de modifier immédiatement votre mot de passe ou de nous contacter.</p>

                    <p style='font-size:12px; color:#777'>L’équipe DossiFind</p>
                </div>
            ";

            $mail->AltBody =
                "Votre mot de passe DossiFind a été modifié.\n\n" .
                "Si vous n’êtes pas à l’origine de cette action, veuillez nous contacter.";

            $mail->send();

        } catch (Exception $e) {
            error_log('MAIL PASSWORD CHANGED ERROR: ' . $e->getMessage());
        }
    }
    // --------------------------------------------------------------------- //