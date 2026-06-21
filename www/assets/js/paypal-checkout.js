// Fichier "paypal-checkout.js"
// Rôle    : Gérer le paiement PayPal (Sandbox / Live)
//-----------------------------------------------------------//
// Affiche le bouton PayPal
// Crée une commande via le serveur
// Ouvre la popup PayPal
// Capture le paiement via le serveur
//-----------------------------------------------------------//

(function () {
    // IIFE (Immediately Invoked Function Expression)
    // → évite de polluer l’espace global (scope isolé)
    'use strict';

    // ------------------------------------------------------------ //
    // CONFIGURATION
    // ------------------------------------------------------------ //

    // Client ID PayPal SANDBOX
    //-- → utilisé uniquement pour les tests (à remplacer en production)
    const PAYPAL_CLIENT_ID = 'AUZidZT29hA-KFaTbznRxGmtVf8iKiUze3Jw4tF1Od_9KQaji4pkr_bauaCY8_49VitONlc287Wu5DLU';

    // URL serveur
    // → CREATE_ORDER_URL : création de la commande côté serveur
    // → CAPTURE_ORDER_URL : capture du paiement après validation PayPal
    const CREATE_ORDER_URL  = 'index.php?page=payment&action=create-order';
    const CAPTURE_ORDER_URL = 'index.php?page=payment&action=capture-order';


    // Devise utilisée pour le paiement
    // → doit être strictement identique à celle utilisée côté PHP
    const CURRENCY = 'EUR';

    // ------------------------------------------------------------ //
    // CHARGEMENT DU SDK PAYPAL
    // ------------------------------------------------------------ //

    function loadPayPalSdk(callback) {
        // Si le SDK PayPal est déjà chargé
        // → on lance directement l’initialisation du bouton
        if (window.paypal) {
            callback();
            return;
        }

        // Création dynamique de la balise <script>
        const script = document.createElement('script');

        // URL officielle du SDK PayPal
        // → client-id : identifiant PayPal
        // → currency : devise affichée
        //
        // Avec bouton card bancaire Paypal
        //script.src = `https://www.paypal.com/sdk/js?client-id=${PAYPAL_CLIENT_ID}&currency=${CURRENCY}`;
        //
        // Sans bouton card bancaire Paypal
        script.src = `https://www.paypal.com/sdk/js?client-id=${PAYPAL_CLIENT_ID}&currency=${CURRENCY}&disable-funding=card`;

        // Chargement asynchrone pour ne pas bloquer la page
        script.async = true;

        // Une fois le SDK chargé, on initialise le bouton
        script.onload = callback;

        // Injection du script dans le <head>
        document.head.appendChild(script);
    }

    // ------------------------------------------------------------ //
    // INITIALISATION DU BOUTON PAYPAL
    // ------------------------------------------------------------ //

    function initPayPalButton() {
        // Vérification de l’existence du conteneur HTML
        if (!document.getElementById('paypal-button-container')) {
            console.error('Conteneur PayPal introuvable');
            return;
        }

        // Initialisation des boutons PayPal
        paypal.Buttons({

            // -------------------------//
            // Création de la commande  //
            // -------------------------//
            createOrder: async function () {
                // Appel serveur pour créer une commande PayPal
                const response = await fetch(CREATE_ORDER_URL, {
                    method: 'POST',
                });
                
                const data = await response.json();
                // Vérification de la présence de l’ID de commande
                if (!data.orderID) {
                    throw new Error('orderID manquant');
                }
                return data.orderID;
            },

            // -------------------------//
            // Paiement approuve        //
            // -------------------------//
            onApprove: async function (data) {
                // Une fois le paiement validé par l’utilisateur
                // → appel serveur pour capturer le paiement
                const result = await fetch(
                    CAPTURE_ORDER_URL + '&orderID=' + encodeURIComponent(data.orderID),
                    {
                        method: 'POST'
                    }
                );
                // Redirection après tentative de capture
                // (succès ou échec gérés côté serveur)
                if (result.status === 'COMPLETED') {
                    window.location.href = 'index.php?page=payment_result';
                } else {
                    window.location.href = 'index.php?page=payment_result';
                }
            },

            // -------------------------//
            // Erreur PayPal            //
            // -------------------------//
            onError: function (err) {
                // Gestion des erreurs techniques PayPal
                console.error('Erreur PayPal :', err);
            },

            // -------------------------//
            // Annulation utilisateur   //
            // -------------------------//
            onCancel: function () {
                // L’utilisateur a fermé ou annulé le paiement
                console.log('Paiement PayPal annulé');
            }

        })
        // Rendu du bouton dans le conteneur HTML
        .render('#paypal-button-container');
    }

    // -------------------------//
    // LANCEMENT GLOBAL
    // -------------------------//

    // Attente du chargement complet du DOM
    document.addEventListener('DOMContentLoaded', function () {
        // Chargement du SDK PayPal
        // → puis initialisation du bouton
        loadPayPalSdk(initPayPalButton);
    });
    
})();