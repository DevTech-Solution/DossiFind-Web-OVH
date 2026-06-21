// ------------------------------------------------------------ //
//  Fichier account.js
// Gère le flux AJAX de vérification d’achat, création de compte et connexion utilisateur
// ------------------------------------------------------------ //

document.addEventListener('DOMContentLoaded', () => {

    // Références aux éléments du formulaire
    const form = document.getElementById('account-form');    
    const passwordZone = document.getElementById('password-zone');
    const messageBox = document.getElementById('form-message');
    const continueBtn = document.getElementById('continue-btn');

    // Interception de la soumission du formulaire
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Réinitialisation du message utilisateur
        messageBox.textContent = '';

        // ----------------------------------------------------------------------------------------------------- //
        // Détermination de l’action à partir du bouton cliqué
        // (check / create / login)
        const action = e.submitter?.dataset.action || 'check';

        const passwordInputs = form.querySelectorAll('input[name="password"], input[name="password_confirm"]');

        if (action === 'create' && passwordInputs.length < 2) {
            messageBox.textContent = 'Veuillez renseigner les champs requis.';
            return;
        }

        // Construction des données envoyées au contrôleur PHP
        const formData = new FormData(form);
        formData.append('action', action);

        // Appel AJAX vers account_api.php
        let response;
        let raw;
        let data;

        try {
            response = await fetch('index.php?page=account-api', {
                method: 'POST',
                body: formData
            });
        } catch (e) {
            messageBox.textContent = 'Erreur réseau. Veuillez réessayer.';
            return;
        }

        try {
            raw = await response.text();
            data = JSON.parse(raw);
        } catch (e) {
            console.error('Réponse serveur non JSON :', raw);
            messageBox.textContent = 'Erreur serveur. Veuillez réessayer.';
            return;
        }

        // Debug : affichage de la réponse serveur
        console.log(data);

        // Gestion des erreurs renvoyées par le serveur
        if (data.error) {
            messageBox.textContent = data.error;
            return;
        }

        // Gestion du flux applicatif selon le statut retourné
        switch (data.status) {

            case 'NO_PURCHASE':
                // Aucun achat associé à l’email
                messageBox.textContent = 'Aucun achat associé à cette adresse email.';
                passwordZone.innerHTML = '';
                continueBtn.style.display = 'inline-block';
                break;

            case 'CREATE_ACCOUNT':
                // Affichage du formulaire de création de compte
                continueBtn.style.display = 'none';
                passwordZone.innerHTML = `
                    <div>
                        <label>Mot de passe</label><br>
                        <input type="password" name="password" required>
                    </div>
                    <div>
                        <label>Confirmation du mot de passe</label><br>
                        <input type="password" name="password_confirm" required>
                    </div>
                    <button type="submit" data-action="create">Créer mon compte</button>
                `;
                break;

            case 'LOGIN':
                // Affichage du formulaire de connexion
                continueBtn.style.display = 'none';
                passwordZone.innerHTML = `
                    <div>
                        <label>Mot de passe</label><br>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" data-action="login">Se connecter</button>
                    <a href="index.php?page=password-reset">mot de passe oublié ?</a>
                `;
                break;

            case 'SUCCESS':
                // Authentification ou création réussie → redirection dashboard
                window.location.href = 'index.php?page=dashboard';
                break;

            case 'BLOCKED':
                // Blocage temporaire après trop de tentatives échouées
                messageBox.textContent = 'Trop de tentatives échouées. Réessayez plus tard.';
                break;

            case 'EMAIL_OLD':
                messageBox.textContent =
                    'Cette adresse email a été utilisée par le passé. ' +
                    'Veuillez saisir votre adresse email actuelle.';
                passwordZone.innerHTML = '';
                continueBtn.style.display = 'inline-block';
            break;
        }
    });
});