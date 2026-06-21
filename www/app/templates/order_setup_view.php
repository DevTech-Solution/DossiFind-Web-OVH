<?php
    // ------------------------------------------------------------ //
    // Fichier "order_setup_view.php"
    // ------------------------------------------------------------ //
    // Récupère la commande en cours si elle existe
    $checkout = $_SESSION['checkout'] ?? null;
    // ------------------------------------------------------------ //
    //Récupère les messages d’erreur temporaires
    if (isset($_SESSION['errors'])) {
        $errors = $_SESSION['errors'];
        unset($_SESSION['errors']);
    } else {
        $errors = [];
    }
    // ------------------------------------------------------------ //
?>

<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>DossiFind - Selection licence</title>
    </head>
    <body>
        <header>
            <!-- fragment header -->
            <?php require_once __DIR__ . '/../fragments/header.php';?>
        </header>
        <main>
            <h1>Sélection de votre type de licence</h1>


            <!-- formulaire licence + mail -->
            <form method="POST" action="index.php?page=payment_checkout">
                <!-- Choix du type de licence -->
                <fieldset>
                    <legend>Type de licence</legend>
                    <label>
                    <input type="radio" name="license_type_choice" value="mono"
                        <?= ($checkout['license_type'] ?? 'mono') === 'mono' ? 'checked' : ''; ?>
                    >
                    Un seul ordinateur
                    </label>
                    <label>
                    <input type="radio" name="license_type_choice" value="multi"
                        <?= ($checkout['license_type'] ?? '') === 'multi' ? 'checked' : ''; ?>
                    >
                    Plusieurs ordinateurs
                    </label>
                </fieldset>

                <!-- Quantité -->
                <fieldset>
                    <legend>Nombre de licences</legend>
                    <!-- btn less -->
                    <button type="button" id="decrement">-</button>
                    <input type="number" id="quantity_input" value="<?= isset($checkout['quantity']) ? (int)$checkout['quantity'] : 1; ?>" min="1">
                    <!-- btn more -->
                    <button type="button" id="increment">+</button>
                </fieldset>

                <!-- Email -->
                <fieldset>
                    <legend>Adresse e-mail</legend>
                    <p>L’adresse email saisie ici devra être la même que celle utilisée pour vous connecter après l’achat.</p>
                    <input type="email" name="email" placeholder="email@exemple.com" required
                    value="<?= isset($checkout['email']) ? htmlspecialchars($checkout['email'], ENT_QUOTES, 'UTF-8') : ''; ?>">

                    <!-- Affichage des messages d'erreurs -->
                    <?php if (!empty($errors)): ?>
                        <div class="error-messages">
                            <?php foreach ($errors as $error): ?>
                                <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </fieldset>

                <!-- Champs envoyés au contrôleur -->
                <input type="hidden" name="license_type" id="license_type" value="<?= $checkout['license_type'] ?? 'mono'; ?>">
                <input type="hidden" name="quantity" id="quantity"value="<?= isset($checkout['quantity']) ? (int)$checkout['quantity'] : 1; ?>">

                <!-- btn send form -->
                <button type="submit">Continuer vers le paiement</button>
                
            </form>
        </main>
        <?php require __DIR__ . '/../fragments/footer.php'; ?>
        <script src="<?= JS_BASE_PATH ?>/assets/js/license_increment.js"></script>
    </body>
</html>