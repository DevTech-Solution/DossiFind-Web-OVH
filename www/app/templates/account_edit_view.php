<?php
    // account_edit_view.php
    // Variables attendues :
    // $user (email)
    // $purchases (liste des factures)
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>DossiFind - Mon compte</title>
    </head>
    <body>
        <header>
            <!-- fragment header -->
            <?php require_once __DIR__ . '/../fragments/header.php';?>
        </header>
    <main>
        <h1>Mon compte</h1>
        
        <!-- Changer mot de passe -->
        <section>

            <h2>Changer mon mot de passe</h2>

            <form id="form-password">
                <div>
                    <label for="current_password">Mot de passe actuel</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div>
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>

                <div>
                    <label for="confirm_password">Confirmation</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit">Mettre à jour le mot de passe</button>

                <!-- Message Password -->
                <div id="password-message" class="form-message"></div>

            </form>
        </section>

        <!-- Changer adresse mail -->
        <section>
            <h2>Changer mon adresse email</h2>

            <form id="form-email">
                <div>
                    <p><strong>Adresse mail actuelle :</strong><?= htmlspecialchars($user['email']) ?></p>
                    <p>Toute adresse email remplacée est définitivement désactivée.</p>
                    <p>Elle ne pourra plus être utilisée pour se connecter, créer un compte ou effectuer un achat ultérieur.</p>
                    <p>Assurez-vous d’utiliser une adresse email valide et durable avant de continuer.</p>
                    <input type="email" id="email" name="email" placeholder="Nouvelle adresse email" required>
                </div>
                <button type="submit">Mettre à jour l’adresse email</button>

                <!-- Message Mail -->
                <div id="mail-message" class="form-message-mail"></div>
            </form>
        </section>

        <!-- Mes factures -->
        <section>
            <h2>Mes achats</h2>
            <!-- Aucune facture a afficher -->
            <table border="1" cellpadding="4" cellspacing="0" width="60%">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>N° achat</th>
                        <th>Licence</th>
                        <th>Quantité</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($purchases)): ?>
                        <tr>
                            <td colspan="7" align="center">
                                Aucun achat enregistré.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($purchases as $purchase): ?>
                            <tr>
                                <td>
                                    <?= date('d/m/Y', strtotime($purchase['created_at'])) ?>
                                </td>

                                <td>
                                    <?= 'DF-' . str_pad((string)$purchase['id'], 6, '0', STR_PAD_LEFT) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($purchase['license_type']) ?>
                                </td>

                                <td>
                                    <?= (int)$purchase['license_quantity'] ?>
                                </td>

                                <td>
                                    <?= number_format((float)$purchase['price_total'], 2, ',', ' ') ?> €
                                </td>

                                <td>
                                    <?= htmlspecialchars($purchase['payment_status']) ?>
                                </td>

                                <td>
                                    <a href="index.php?page=invoice-print&purchase_id=<?= (int)$purchase['id'] ?>"title="Imprimer la facture" target="_blank">Imprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Suppression de compte -->
        <section>
            <a href="index.php?page=delete-account" title="Supprimer compte">
                Supprimer mon compte
            </a>
        </section>
    </main>
        <?php require __DIR__ . '/../fragments/footer.php'; ?>
        <script src="<?= JS_BASE_PATH ?>/assets/js/account_edit.js"></script>
    </body>
</html>