<?php
    // ------------------------------------------------------------ //
    // Fichier password_view.php
    // ------------------------------------------------------------ //

?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>DossiFind - Réinitialisation mot de passe</title>
    </head>
    <body>
        <header>
            <!-- fragment header -->
            <?php require_once __DIR__ . '/../fragments/header.php';?>
        </header>
        <main>
            <h1>réinitialiser le mot de passe</h1>
            <p>saisissez votre email pour choisir un nouveau mot de passe.</p>

            <!-- Message -->
            <?php if (!empty($success_message)): ?>
                <p class="success"><?= htmlspecialchars($success_message) ?></p>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <p class="error"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>

            <!-- Formulaire mail -->
            <?php if (!$show_password_form): ?>
            <form method="post" action="index.php?page=password-reset">
                <label>Email</label><br>
                <input type="email" name="email" required>
                <br><br>
                <!-- Cta submit mail -->
                <button type="submit">Envoyer le lien</button>
            </form>
            <?php endif; ?>

            <!-- Formulaire password -->
            <?php if ($show_password_form): ?>
            <form method="post" action="index.php?page=password-reset">
                <input type="hidden" name="token" value="<?= htmlspecialchars((string) $token) ?>">
                <label>Nouveau mot de passe</label><br>
                <input type="password" name="password" required>
                <br><br>

                <label>Confirmer le mot de passe</label><br>
                <input type="password" name="password_confirm" required>
                <br><br>
                <!-- Cta change password -->
                <button type="submit">Changer le mot de passe</button>
            </form>
            <?php endif; ?>

            <!-- Cta-back-login -->
            <div class="cta-login">
                <a href="index.php?page=login">retour</a>
            </div>
        </main>
        <?php require __DIR__ . '/../fragments/footer.php'; ?>
    </body>
</html>