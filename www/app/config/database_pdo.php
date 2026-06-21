<?php
    // ---------------------------------------------//
    // Sécurité minimale
    // Active le typage strict de PHP
    declare(strict_types=1);
    // ---------------------------------------------//

    // Charger la configuration de la base de données
    // Le fichier database.php doit retourner un tableau de configuration
    // (type de connexion, hôte ou socket, nom de BDD, charset, identifiants…)
    $config = require __DIR__ . '/database.php';
    // ---------------------------------------------//

    // Construire le DSN (Data Source Name)
    // Le DSN indique à PDO comment se connecter à MySQL

    // Cas 1 : connexion via socket Unix (souvent en local ou sur certains hébergements)
    if ($config['type'] === 'socket') {
        $dsn = sprintf(
            'mysql:unix_socket=%s;dbname=%s;charset=%s',
            $config['socket'],   // Chemin du socket MySQL
            $config['dbname'],   // Nom de la base de données
            $config['charset']   // Jeu de caractères (ex: utf8mb4)
        );
    } else {
        // Cas 2 : connexion via host (IP ou nom de domaine)
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],     // Hôte MySQL (ex: localhost)
            $config['dbname'],   // Nom de la base de données
            $config['charset']   // Jeu de caractères
        );
    }

    // Tentative de connexion à la base de données
    try {
        $pdo = new PDO(
            $dsn,                    // DSN construit précédemment
            $config['user'],         // Nom d'utilisateur MySQL
            $config['password'],     // Mot de passe MySQL
            [
                // Active les exceptions en cas d'erreur SQL
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

                // Définit le mode de récupération par défaut en tableau associatif
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                // Désactive l'émulation des requêtes préparées
                // → utilise les vraies requêtes préparées MySQL (plus sûr)
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
    // En cas d'erreur PDO (ex : échec de connexion à la base de données)
    // encapsulation de l'exception PDO dans une RuntimeException
    // permet de centraliser la gestion des erreurs au niveau supérieur
        throw new RuntimeException('Database connection failed', 0, $e);
    }