<?php
    // ------------------------------------------------------------ //
    // Fichier "payment_checkout_view.php"
    // Affichage des éléments transmis par payment_checkout_controller.php
    // + sélection du mode de paiement (PayPal uniquement pour le moment)
    // Envoyer montant sur Paypal ou (Banque = plus tard)
    // ------------------------------------------------------------ //
    // Sécurité : empêche l’accès direct à la page de paiement sans données de commande valides
    if (!isset($checkout)) {
        header('Location: index.php?page=order_setup');
        exit;
    }
    // ------------------------------------------------------------ //
    // Prix de référence servant de base pour calculer l’économie réalisée
    $basePrice = 19.90;
    //
    // Calcule le pourcentage d’économie par licence par rapport au prix de base 
    // (0 % s’il n’y a pas de remise)
    if ($checkout['unit_price'] < $basePrice) {
        $discountPercent = round((1 - ($checkout['unit_price'] / $basePrice)) * 100);
    } else {
        $discountPercent = 0;
    }
    // ------------------------------------------------------------ //
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>DossiFind - Finalisation de votre paiement</title>
    </head>
    <body>
    <header>
        <!-- Fragment header -->
        <?php require_once __DIR__ . '/../fragments/header.php'; ?>
    </header>
    <main>
        <h1>Choix du paiement</h1>

        <!-- Récapitulatif de la commande -->
        <section>
            <h2>Récapitulatif</h2>
            <ul>
                <li>
                    <strong>Type de licence :</strong>
                    <?= ($checkout['license_type'] === 'mono') ? 'Un seul ordinateur' : 'Plusieurs ordinateurs'; ?>
                </li>
                <li>
                    <strong>Nombre de licences :</strong>
                    <?= (int)$checkout['quantity']; ?>
                </li>
                <li>
                    <strong>Adresse e-mail :</strong>
                    <?= htmlspecialchars($checkout['email'], ENT_QUOTES, 'UTF-8'); ?>
                </li>
                <li>
                    <strong>Prix unitaire :</strong>
                    <?= number_format($checkout['unit_price'], 2, ',', ' '); ?> €
                </li>
                <!--------------------------------------------->
                <!-- Affichage economie realisee pourcentage -->
                <?php if ($discountPercent > 0): ?>
                    <p>Économie réalisée : <?= $discountPercent; ?> % par licence </p>
                <?php endif; ?>
                <!--------------------------------------------->
                <li>
                    <strong>Total à payer :</strong>
                    <?= number_format($checkout['total_price'], 2, ',', ' '); ?> €
                </li>
            </ul>
            <!-- Btn Modifier informations -->
            <form method="GET" action="index.php">
                <input type="hidden" name="page" value="order_setup">
                <button type="submit">Modifier vos informations</button>
            </form>
        </section>

        <section>
            <!-- CGV -->
            <a href="index.php?page=cgv" title="Conditions générales de vente">Conditions générales de vente</a>
        </section>

        <!-- Choix du mode de paiement -->
        <section>
            <h2>Mode de paiement</h2>
            <p>Vous devrez vous connecter avec l’adresse email utilisée pour cet achat afin d’accéder à vos licences.</p>
            <!-- Paiement via PayPal -->
            <div id="paypal-button-container"></div>
            <!-- Paiement via CB -->
            <!--
            <button disabled>
                Paiement par carte bancaire
            </button>
            -->
        </section>
    </main>
    <?php require __DIR__ . '/../fragments/footer.php'; ?>
        <script src="<?= JS_BASE_PATH ?>/assets/js/paypal-checkout.js"></script>
    </body>
</html>