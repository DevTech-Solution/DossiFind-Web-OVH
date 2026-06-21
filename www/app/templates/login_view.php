<?php
// login_view.php
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>DossiFind - Connexion</title>
    </head>
    <body>
    <header>
        <?php require_once __DIR__ . '/../fragments/header.php'; ?>
    </header>
    <main>
        <h1>Connexion</h1>
        <p>Veuillez saisir l’adresse email utilisée lors de votre achat.</p>
        <form id="account-form" autocomplete="off">
            <div>
                <label for="email">Adresse email</label><br>
                <input type="email" id="email" name="email" required>
            </div>
            <!-- Zone dynamique -->
            <div id="password-zone"></div>
            <div id="form-message" style="color:red;margin-top:10px;"></div>
            <button type="submit" id="continue-btn">Continuer</button>
        </form>
    </main>
    <?php require __DIR__ . '/../fragments/footer.php'; ?>
    <script src="<?= JS_BASE_PATH ?>/assets/js/account.js"></script>
    </body>
</html>