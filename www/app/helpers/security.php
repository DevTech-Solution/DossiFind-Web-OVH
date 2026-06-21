<?php
    declare(strict_types=1);

    // ------------------------------------------------------------ //
    // Vérifie la robustesse d’un mot de passe
    // Longueur minimale : 12 caractères
    // Contient au moins : lettres, chiffres et caractères spéciaux
    // ------------------------------------------------------------ //

    function isPasswordStrong(string $password): bool
    {
        if (strlen($password) < 12) {
            return false;
        }

        if (!preg_match('/[A-Za-z]/', $password)) {
            return false;
        }

        if (!preg_match('/\d/', $password)) {
            return false;
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }
    // ------------------------------------------------------------ //