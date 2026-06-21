<?php
    // compatibility_view.php
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>DossiFind - Compatibilité</title>
    </head>
    <body>
        <header>
            <!-- fragment header -->
            <?php require_once __DIR__ . '/../fragments/header.php';?>
        </header>
        <main>
            <h1>Page de compatibilité</h1>
            <!-- Btn select licence -->
            <div class="btn-buy">
                <a href="index.php?page=order_setup" title="Acheter maintenant">acheter maintenant</a>
            </div>
        </main>
        <?php require __DIR__ . '/../fragments/footer.php'; ?>
    </body>
</html>