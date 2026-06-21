// ------------------------------------------------------------ //
// Fichier "licence_increment.js"
//-----------------------------------------------------------//
// Sélection des boutons radio pour le type de licence
const monoRadio   = document.querySelector('input[value="mono"]');
const multiRadio  = document.querySelector('input[value="multi"]');

// Sélection des boutons d’incrémentation / décrémentation
const decrementBtn = document.getElementById('decrement');
const incrementBtn = document.getElementById('increment');

// Champ de saisie de la quantité de licences
const quantityInput = document.getElementById('quantity_input');

// Champs cachés utilisés pour envoyer les données au formulaire (backend)
const hiddenType     = document.getElementById('license_type');
const hiddenQuantity = document.getElementById('quantity');
//-----------------------------------------------------------//
//
// Attente du chargement complet du DOM avant d’exécuter le script
document.addEventListener('DOMContentLoaded', () => {

    // Met à jour le champ caché de quantité avec la valeur actuelle
    function updateHiddenFields() {
        hiddenQuantity.value = quantityInput.value;
    }

    // Active le mode "mono" :
    // quantité forcée à 1
    // champ désactivé
    // type de licence défini à "mono"
    function setMonoMode() {
        quantityInput.value = 1;
        quantityInput.min = 1;
        quantityInput.disabled = true;

        hiddenType.value = 'mono';
        updateHiddenFields();
    }

    // Active le mode "multi" :
    // champ quantité activé (modifiable par l’utilisateur)
    // quantité minimale fixée à 2 (au moins deux licences)
    // si la valeur actuelle est inférieure à 2 (ex : retour depuis le mode mono),
    // la quantité est automatiquement forcée à 2
    // type de licence défini à "multi" pour l’envoi au backend

    function setMultiMode() {
        quantityInput.disabled = false;
        quantityInput.min = 2;

        // Si on vient du mono ou d'une valeur invalide, on force à 2
        if (parseInt(quantityInput.value, 10) < 2) {
            quantityInput.value = 2;
        }

        hiddenType.value = 'multi';
        updateHiddenFields();
    }

    // Initialisation par défaut au chargement de la page
    // (licence mono sélectionnée)
    // Initialisation en fonction de l’état réel du formulaire
    if (monoRadio.checked) {
        setMonoMode();
    } else if (multiRadio.checked) {
        setMultiMode();
    }

    // Gestion du changement vers le mode mono
    monoRadio.addEventListener('change', () => {
        if (monoRadio.checked) {
            setMonoMode();
        }
    });

    // Gestion du changement vers le mode multi
    multiRadio.addEventListener('change', () => {
        if (multiRadio.checked) {
            setMultiMode();
        }
    });

    // Bouton "+"
    // Incrémente la quantité uniquement si le champ n’est pas désactivé
    incrementBtn.addEventListener('click', () => {
        if (!quantityInput.disabled) {
            quantityInput.value = parseInt(quantityInput.value, 10) + 1;
            updateHiddenFields();
        }
    });

    // Bouton "-"
    // décrémente la quantité de licences
    // respecte la règle métier selon le type de licence :
    //   • mono → minimum 1 licence
    //   • multi → minimum 2 licences
    // aucune action si le champ quantité est désactivé
    // empêche toute valeur inférieure au minimum autorisé

    decrementBtn.addEventListener('click', () => {
        if (quantityInput.disabled) {
            return;
        }

        let minValue;

        if (monoRadio.checked) {
            minValue = 1;
        } else {
            minValue = 2;
        }

        const currentValue = parseInt(quantityInput.value, 10);

        if (currentValue > minValue) {
            quantityInput.value = currentValue - 1;
            updateHiddenFields();
        }
    });


    // Champ de saisie de la quantité (saisie manuelle clavier)
    // détermine dynamiquement la valeur minimale autorisée selon le type de licence :
    //   • mono → minimum 1 licence
    //   • multi → minimum 2 licences
    // empêche toute valeur invalide (non numérique)
    // empêche toute valeur inférieure au minimum autorisé
    // force automatiquement la valeur minimale si nécessaire
    // met à jour le champ caché pour garantir la cohérence des données envoyées

    quantityInput.addEventListener('input', () => {
        let minValue;

        if (monoRadio.checked) {
            minValue = 1;
        } else {
            minValue = 2;
        }

        if (quantityInput.value < minValue || isNaN(quantityInput.value)) {
            quantityInput.value = minValue;
        }

        updateHiddenFields();
    });
});
//-----------------------------------------------------------//