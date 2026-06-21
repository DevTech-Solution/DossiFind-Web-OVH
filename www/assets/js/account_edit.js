// Fichier account_edit.js

document.addEventListener('DOMContentLoaded', () => {
// --------------------------------------------------------------------- //
// Affiche un message utilisateur dans un conteneur HTML
//
// Le conteneur DOIT déjà avoir sa classe de base
// (ex: form-message ou form-message-mail)
// --------------------------------------------------------------------- //
    function showMessage(container, message, type = 'error') {

        // Texte du message
        container.textContent = message;

        // Supprime uniquement les états
        container.classList.remove('error', 'success');
        
        // Ajoute l’état (error / success)
        container.classList.add(type);
    }

    // FORMULAIRE PASSWORD
    const passwordForm = document.getElementById('form-password');

    // Message DOM PASSWORD
    const messageBox = document.getElementById('password-message');

    if (passwordForm) {
        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(passwordForm);
            formData.append('action', 'password');

            let response;
            let result;

            // Requête réseau
            try {
                response = await fetch('index.php?page=account-update', {
                    method: 'POST',
                    body: formData
                });
            } catch (e) {
                showMessage(messageBox, 'Erreur réseau. Veuillez réessayer.');
                return;
            }

            // Parsing JSON
            try {
                result = await response.json();
            } catch (e) {
                showMessage(messageBox, 'Erreur serveur. Réponse invalide.');
                return;
            }

            // Erreurs métier / HTTP (Exceptions PHP fichier -> account_update_controller.php)
            if (!response.ok || !result.success) {
                showMessage(messageBox, result.message);
                return;
            }

            // Succès
            showMessage(messageBox, result.message, 'success');
            passwordForm.reset();
        });
    }

    // --------------------------------------------------------------------- //
    // FORMULAIRE EMAIL
    const emailForm = document.getElementById('form-email');

    // Message DOM EMAIL
    const messageBoxMail = document.getElementById('mail-message');

    if (emailForm) {
        emailForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(emailForm);
            formData.append('action', 'email');

            let response;
            let resultMail;

            // Requête réseau
            try {
                response = await fetch('index.php?page=account-update', {
                    method: 'POST',
                    body: formData
                });
            } catch (e) {
                showMessage(messageBoxMail, 'Erreur réseau. Veuillez réessayer.');
                return;
            }

            // Parsing JSON
            try {
                resultMail = await response.json();
            } catch (e) {
                showMessage(messageBoxMail, 'Erreur serveur. Réponse invalide.');
                return;
            }

            // Erreurs métier / HTTP
            if (!response.ok || !resultMail.success) {
                showMessage(messageBoxMail, resultMail.message);
                return;
            }

            // Succès
            showMessage(messageBoxMail, resultMail.message, 'success');
            emailForm.reset();
        });
    }
    // --------------------------------------------------------------------- //
});
