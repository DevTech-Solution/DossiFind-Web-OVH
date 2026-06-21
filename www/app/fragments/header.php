<?php
    // fragment header
    
    // Verification de la session 
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isLoggedIn = isset($_SESSION['user_id']);
?>

<nav>
    <ul>
        <li>
            <a href="index.php?page=index" title="Accueil">accueil</a>
        </li>

        <li>
            <a href="index.php?page=compatibility" title="Acheter maintenant">acheter maintenant</a>
        </li>

        <li>
            <a href="index.php?page=features" title="Fonctionnalités">fonctionnalités</a>
        </li>

        <!-- Zone utilisateur -->
        <?php if (!$isLoggedIn): ?>

            <!-- Utilisateur NON connecté : icône connexion -->
            <li id="user-menu">
                <a href="index.php?page=login" title="Connexion" aria-label="Connexion">🏠</a>
            </li>

        <?php else: ?>
            <!-- Utilisateur connecté -->
            <li id="user-menu">
                <!-- Icône utilisateur -->
                <button type="button"
                        id="user-menu-toggle"
                        aria-label="Menu utilisateur">
                    🏠
                </button>

                <!-- Menu déroulant utilisateur -->
                <ul id="user-menu-dropdown" hidden>
                    <li>
                        <a href="index.php?page=dashboard" title="Mon dashboard">mon dashboard</a>
                    </li>
                    <li>
                        <a href="index.php?page=profile" title="Mon compte">mon compte</a>
                    </li>
                    <li>
                        <a href="index.php?page=logout" title="Déconnexion">déconnexion</a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Expose le chemin de base PHP à "JavaScript" pour construire les URLs dynamiquement -->
<!-- Portee Globale vers les fichiers JS (AJAX) -->
<script>
    window.BASE_PATH = "<?php echo JS_BASE_PATH; ?>";
</script>