<?php
    // account_delete_view.php
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>DossiFind - Supprimer le compte</title>
    </head>
    <body>
        <header>
            <!-- fragment header -->
            <?php require_once __DIR__ . '/../fragments/header.php';?>
        </header>
        <main>

            <h1>Supprimer mon compte</h1>
            <p>La suppression de votre compte est définitive.</p>

            <ul>
                <li>Vous perdrez définitivement l’accès à votre compte</li>
                <li>Toutes vos licences seront désactivées</li>
                <li>Tous les postes activés seront révoqués</li>
                <li>Vos factures resteront conservées (obligation légale)</li>
                <li>
                    L’adresse email associée à ce compte sera définitivement désactivée et
                    ne pourra plus être utilisée pour créer un compte ou effectuer un achat
                </li>
            </ul>

            <form method="post" action="index.php?page=account_delete_action">
                <button type="submit" name="confirm_delete" value="1">
                    Supprimer définitivement mon compte
                </button>
            </form>

            <p><a href="index.php?page=profile" title="Annuler et revenir à mon compte">Annuler et revenir à mon compte</a></p>
        </main>
        <?php require __DIR__ . '/../fragments/footer.php'; ?>
        <script src="<?= JS_BASE_PATH ?>/assets/js/account_delete.js"></script>
    </body>
</html>