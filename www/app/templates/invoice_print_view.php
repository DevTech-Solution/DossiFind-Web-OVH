<?php
    // Facture impression
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Facture <?= 'DF-' . str_pad((string)$invoice['id'], 6, '0', STR_PAD_LEFT) ?></title>
    </head>
    <body>
        <!-- Btn print -->
        <button onclick="window.print()">Imprimer</button>
        <h1>Facture</h1>
        
        <div class="invoice-block">
            <span class="label">Numéro :</span>
            <?= 'DF-' . str_pad((string)$invoice['id'], 6, '0', STR_PAD_LEFT) ?>
        </div>

        <div class="invoice-block">
            <span class="label">Date :</span>
            <?= date('d/m/Y', strtotime($invoice['created_at'])) ?>
        </div>

        <div class="invoice-block">
            <span class="label">Email :</span>
            <?= htmlspecialchars($invoice['email']) ?>
        </div>

        <div class="invoice-block">
            <span class="label">Licence :</span>
            <?= htmlspecialchars($invoice['license_type']) ?>
        </div>

        <div class="invoice-block">
            <span class="label">Quantité :</span>
            <?= (int)$invoice['license_quantity'] ?>
        </div>

        <div class="invoice-block">
            <span class="label">Total :</span>
            <?= number_format((float)$invoice['price_total'], 2, ',', ' ') ?> €
        </div>

        <div class="invoice-block">
            <span class="label">Statut :</span>
            <?= htmlspecialchars($invoice['payment_status']) ?>
        </div>

        <script>
            // Impression automatique (optionnel)
            window.onload = () => window.print();
        </script>

    </body>
</html>