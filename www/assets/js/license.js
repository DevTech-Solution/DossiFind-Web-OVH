// ------------------------------------------------------------ //
// Fichier : License.js
// Rôle    : Gestion affichage des détails de licences (dashboard)
// ------------------------------------------------------------ //

// Attente du chargement complet du DOM avant d'exécuter le script
document.addEventListener('DOMContentLoaded', () => {

    // Bouton permettant d'afficher les licences monoposte
    const monoBtn  = document.querySelector('.js-show-mono');

    // Bouton permettant d'afficher les licences multiposte
    const multiBtn = document.querySelector('.js-show-multi');

    // Si le bouton monoposte existe dans le DOM
    if (monoBtn) {
        monoBtn.addEventListener('click', (e) => {
            // Empêche le comportement par défaut du lien/bouton
            e.preventDefault();

            // Affiche ou masque le détail des licences monoposte
            toggleDetails('mono');
        });
    }

    // Si le bouton multiposte existe dans le DOM
    if (multiBtn) {
        multiBtn.addEventListener('click', (e) => {
            // Empêche le comportement par défaut du lien/bouton
            e.preventDefault();

            // Affiche ou masque le détail des licences multiposte
            toggleDetails('multi');
        });
    }
});

// ----------------------------------------------------------------------------- //
// Affiche ou masque le détail des licences (mono ou multi)
// ----------------------------------------------------------------------------- //
function toggleDetails(type) {

    // Récupère le conteneur correspondant au type de licence
    const container = document.getElementById(`${type}-details`);

    // Si le conteneur n'existe pas, on arrête la fonction
    if (!container) {
        return;
    }

    // Si le conteneur est déjà visible, on le masque
    if (container.style.display === 'block') {
        container.style.display = 'none';
        return;
    }

    // Si les données ont déjà été chargées précédemment,
    // on se contente d'afficher le conteneur sans refaire un fetch
    if (container.dataset.loaded === 'true') {
        container.style.display = 'block';
        return;
    }

    // Affiche le conteneur avec un message de chargement
    container.style.display = 'block';
    container.innerHTML = '<p>Chargement...</p>';

    // Appel AJAX vers le contrôleur PHP pour récupérer les licences
    fetch(`index.php?page=license&action=list&type=${type}`, {
        method: 'GET',
        headers: {
            // Permet au contrôleur de détecter une requête AJAX
            'X-Requested-With': 'XMLHttpRequest'
        }
    })

    .then(response => response.json())
    .then(data => {

        // Si une erreur est retournée par l’API
        if (!data.success) {
            container.innerHTML = `<p style="color:red;">${data.error}</p>`;
            return;
        }

        // Génère le HTML des licences et l'injecte dans le conteneur
        container.innerHTML = buildLicenseList(data.licenses, type);

        // Marque le conteneur comme déjà chargé
        container.dataset.loaded = 'true';

        // Boutons
        bindDeviceNameSaveButtons();
        //bindEditDeviceNameButtons();
        bindManageDevicesButtons();
        bindRevokeDeviceButtons();
    })
    .catch(() => {
        // Gestion d'une erreur réseau ou serveur
        container.innerHTML = '<p style="color:red;">Erreur lors du chargement.</p>';
    });
}

// ----------------------------------------------------------------------------- //
// Génère le HTML affichant la liste des licences + Statut métier
// ----------------------------------------------------------------------------- //
function buildLicenseList(licenses) {

    // Si aucune licence n’est fournie, on retourne un message informatif
    if (!licenses.length) {
        return '<p>Aucune licence trouvée.</p>';
    }

    // Initialisation de la structure HTML de la liste
    let html = '<ol>';

    // Parcours de chaque licence reçue depuis l’API
    licenses.forEach(license => {

        // Libellé lisible du statut (affichage UI)
        let statusLabel = '';

        // ------------------------------------------------------------------------- //
        // Gestion du statut métier → traduction en texte compréhensible pour l’UI
        // ------------------------------------------------------------------------- //
        switch (license.status) {

            // Licence existante mais non activée
            case 'INACTIVE':
                statusLabel = 'Inactive';
                break;

            // Licence active mais sans aucune activation utilisée
            case 'ACTIVE_UNUSED':

                if (license.license_type === 'multi') {
                    statusLabel = `Disponible (0/${license.max_activations})`;
                } else {
                    statusLabel = 'Disponible';
                }

                break;

            // Licence partiellement utilisée
            // → on affiche le détail des activations
            // → on autorise la désaffectation
            case 'ACTIVE_PARTIAL':

                if (license.license_type === 'multi') {
                    statusLabel = `Utilisée (${license.used_activations}/${license.max_activations})`;
                } else {
                    statusLabel = 'Utilisée';
                }

                break;

            // Licence totalement utilisée (plus d’activations disponibles)
            case 'ACTIVE_FULL':

                if (license.license_type === 'multi') {
                    statusLabel = `Complète (${license.max_activations}/${license.max_activations})`;
                } else {
                    statusLabel = 'Complète';
                }

                break;

            // Cas de sécurité si un statut inconnu est reçu
            default:
                statusLabel = 'État inconnu';
        }

        // Construction du HTML pour une licence
        const activationLabel = formatActivationDate(license.last_activated_at);

        html += `
            <li style="margin-bottom:8px;">
                <p>
                    ${license.license_key} – <strong>${statusLabel}</strong><br>
                    <small style="color:#666;">
                        Dernière activation : ${escapeHtml(activationLabel)}
                    </small>
                </p>
        `;
        // Bouton gérer les postes (UNIQUEMENT pour les licences multi)
        if (license.license_type === 'multi') {
            html += `
                <button
                    type="button"
                    class="js-manage-devices"
                    data-license-id="${license.id}"
                    style="margin-top:6px;"
                >
                    Gérer les postes
                </button>
            `;
        }

        // Bouton désaffecter (LICENCE MONO)
        if (
            license.license_type === 'mono' &&
            license.devices &&
            license.devices.length === 1
        ) {
            const device = license.devices[0];

            html += `
                <button
                    type="button"
                    class="js-revoke-device"
                    data-device-id="${device.id}"
                    data-license-id="${license.id}"
                    style="margin-top:6px;"
                >
                    Désaffecter ce poste
                </button>
            `;
        }

        // ------------------------------------------------------------------------- //
        // Devices activés (nom du poste)
        // ------------------------------------------------------------------------- //
        if (license.devices && license.devices.length > 0) {

            html += `<div style="margin-left:16px; margin-top:8px;">`;

            license.devices.forEach(device => {

                const value = device.device_name ? device.device_name : '';

                html += `
                    <div style="margin-top:6px;" data-device-wrapper="${device.id}">
                        <strong>Poste :</strong>
                        <span
                            class="js-device-label"
                            data-device-id="${device.id}"
                        >
                            ${escapeHtml(value || '-')}
                        </span>

                        <input
                            type="text"
                            class="js-device-name"
                            data-device-id="${device.id}"
                            placeholder="Nom du poste"
                            style="margin-left:8px;"
                        />

                        <button
                            type="button"
                            class="js-save-device-name"
                            data-device-id="${device.id}"
                            style="margin-left:6px;"
                        >
                            Enregistrer
                        </button>
                    </div>
                `;
            });

            html += `</div>`;
        }

        // Fermeture de l’élément de liste
        html += '</li>';
    });

    // Fermeture de la liste HTML
    html += '</ol>';

    // Retour du HTML final prêt à être injecté dans le DOM
    return html;
}

// ----------------------------------------------------------------------------- //
// Attache les événements de sauvegarde du nom de poste aux boutons correspondants
// ----------------------------------------------------------------------------- //

function bindDeviceNameSaveButtons() {

    // Récupère tous les boutons "Enregistrer" associés au nom d’un poste
    const buttons = document.querySelectorAll('.js-save-device-name');

    // Parcourt chaque bouton trouvé
    buttons.forEach(button => {

        // Écoute le clic sur le bouton de sauvegarde
        button.addEventListener('click', () => {

            // Récupère l'identifiant du poste depuis l'attribut data-device-id
            const deviceId = button.dataset.deviceId;

            // Récupère le champ de saisie correspondant à ce poste
            const input = document.querySelector(
                `.js-device-name[data-device-id="${deviceId}"]`
            );

            // Sécurité : si le champ n'existe pas, on stoppe l'exécution
            if (!input) {
                return;
            }

            // Récupère et nettoie le nom du poste saisi par l'utilisateur
            const deviceName = input.value.trim();

            // Envoie la mise à jour du nom du poste au serveur via une requête AJAX
            fetch('index.php?page=license&action=updateDeviceName', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    device_id: deviceId,
                    device_name: deviceName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Erreur lors de l’enregistrement');
                    return;
                }

                const wrapper = input.closest('[data-device-wrapper]');

                // Cherche le label existant
                let label = wrapper.querySelector('.js-device-label');

                // S'il n'existe pas encore, on le crée
                if (!label) {
                    label = document.createElement('span');
                    label.className = 'js-device-label';
                    label.dataset.deviceId = deviceId;

                    wrapper.insertAdjacentHTML('afterbegin', '<strong>Poste :</strong> ');
                    wrapper.insertBefore(label, input);
                }

                // Mise à jour du texte affiché
                label.textContent = deviceName || '—';
                
                // Vide le champ après sauvegarde
                input.value = '';
            });
        });
    });
}

// ----------------------------------------------------------------------------- //
// Attache les événements aux boutons permettant de modifier le nom du poste
// ----------------------------------------------------------------------------- //
function bindEditDeviceNameButtons() {

    // Sélectionne tous les boutons "Modifier" présents dans le DOM
    const buttons = document.querySelectorAll('.js-edit-device-name');

    // Parcourt chaque bouton "Modifier"
    buttons.forEach(button => {

        // Écoute le clic sur le bouton
        button.addEventListener('click', () => {

            // Récupère l'identifiant du device depuis l'attribut data-device-id
            const deviceId = button.dataset.deviceId;

            // Récupère le conteneur associé à ce device
            // (zone qui contient l'affichage ou le formulaire d'édition)
            const wrapper = document.querySelector(
                `[data-device-wrapper="${deviceId}"]`
            );

            // Sécurité : si le conteneur n'existe pas, on arrête
            if (!wrapper) return;

            // Remplace le contenu du conteneur par un champ de saisie
            // permettant de modifier le nom du poste
            wrapper.innerHTML = `
                <input
                    type="text"
                    placeholder="Nom du poste"
                    data-device-id="${deviceId}"
                    class="js-device-name"
                />
                <button
                    type="button"
                    class="js-save-device-name"
                    data-device-id="${deviceId}"
                    style="margin-left:8px;"
                >
                    Enregistrer
                </button>
            `;

            // Ré-attache les événements de sauvegarde
            // car le DOM vient d'être reconstruit dynamiquement
            bindDeviceNameSaveButtons();
        });
    });
}

// ----------------------------------------------------------------------------- //
// Formate une date d’activation provenant de l’API pour l’affichage utilisateur
// ----------------------------------------------------------------------------- //
function formatActivationDate(dateString) {

    // Si aucune date n’est fournie (licence jamais activée)
    if (!dateString) {
        return 'Jamais activée';
    }

    // Conversion de la date SQL en format ISO compatible JavaScript
    // Remplacement de l’espace par 'T' pour permettre le parsing par Date()
    const date = new Date(dateString.replace(' ', 'T'));

    // Vérification de la validité de la date après parsing
    if (isNaN(date.getTime())) {
        return 'Date invalide';
    }

    // Retourne la date formatée en français :
    // - Date : JJ/MM/AAAA
    // - Heure : HH:MM
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }) + ' à ' + date.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// ----------------------------------------------------------------------------- //
// Gestion du clic sur "Gérer les postes"
// ----------------------------------------------------------------------------- //
function bindManageDevicesButtons() {

    const buttons = document.querySelectorAll('.js-manage-devices');

    buttons.forEach(button => {
        button.addEventListener('click', () => {

            const licenseId = button.dataset.licenseId;

            const li = button.closest('li');

            let panel = li.querySelector('.js-devices-panel');

            // Toggle
            if (panel) {
                panel.remove();
                return;
            }

            panel = document.createElement('div');
            panel.className = 'js-devices-panel';
            panel.dataset.licenseId = licenseId;
            panel.style.marginTop = '8px';
            panel.innerHTML = '<p>Chargement des postes...</p>';

            fetch('index.php?page=license&action=get_license_devices', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    license_id: licenseId
                })
            })
            .then(r => r.json())
            .then(data => {

                if (data.status !== 'SUCCESS') {
                    panel.innerHTML = '<p style="color:red;">Erreur de chargement des postes</p>';
                    return;
                }

                let html = `
                    <p class="js-devices-counter" data-max="${data.max_activations}">
                        <strong>Postes activés :</strong>
                        ${data.devices.length} / ${data.max_activations}
                    </p>
                `;

                if (data.devices.length === 0) {
                    html += '<p>Aucun poste activé.</p>';
                } else {

                    html += '<ul style="margin-top:6px;">';

                    data.devices.forEach(device => {

                        html += `
                            <li style="margin-bottom:8px;">
                                <strong>${escapeHtml(device.device_name || 'Poste sans nom')}</strong>
                                <small>
                                    Activé le : ${formatActivationDate(device.activated_at)}
                                </small>
                                <br>
                                <button
                                    type="button"
                                    class="js-revoke-device"
                                    data-device-id="${device.id}"
                                    style="margin-top:4px;"
                                >
                                    Désaffecter
                                </button>
                            </li>
                        `;
                    });

                    html += '</ul>';
                }

                panel.innerHTML = html;
                bindRevokeDeviceButtons();
            });

            li.appendChild(panel);
        });
    });
}

// ----------------------------------------------------------------------------- //
// Gestion de la désaffectation d’un poste
// ----------------------------------------------------------------------------- //
function bindRevokeDeviceButtons() {

    const buttons = document.querySelectorAll('.js-revoke-device');

    buttons.forEach(button => {
        button.addEventListener('click', () => {

            if (!confirm('Voulez-vous vraiment désaffecter ce poste ?')) {
                return;
            }

            const deviceId = button.dataset.deviceId;
            const li = button.closest('li');
            const panel = button.closest('.js-devices-panel');


            // multi = panel, mono = bouton
            const licenseId = panel?.dataset.licenseId || button.dataset.licenseId;

            fetch('index.php?page=license&action=revoke_device', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({device_id: deviceId, license_id: licenseId})
            })
            .then(r => r.json())
            .then(data => {

                if (data.status !== 'SUCCESS') {
                    alert(data.error || 'Erreur lors de la désaffectation');
                    return;
                }

                // Suppression visuelle du poste
                li.remove();

                if (panel) {
                    updateDevicesCounter(panel);
                }
            });
        });
    });
}

// ----------------------------------------------------------------------------- //
// Met à jour le compteur de postes (devices) activés dans un panneau donné
// ----------------------------------------------------------------------------- //
function updateDevicesCounter(panel) {

    // Récupère tous les éléments <li> du panneau
    // Chaque <li> représente un poste / device activé
    const items = panel.querySelectorAll('li');

    // Récupère l’élément qui affiche le compteur des devices
    const counter = panel.querySelector('.js-devices-counter');

    // Si le compteur n’existe pas dans le DOM, on stoppe la fonction
    if (!counter) return;

    // Récupère le nombre maximum de postes autorisés
    // Stocké dans l’attribut data-max du compteur
    const max = counter.dataset.max;

    // Nombre de postes actuellement utilisés
    // Correspond au nombre d’éléments <li>
    const used = items.length;

    // Met à jour le contenu HTML du compteur
    // Affiche : "Postes activés : X / Y"
    counter.innerHTML = `<strong>Postes activés :</strong>${used} / ${max}`;

    // Si aucun poste n’est activé
    // et que le message "Aucun poste activé" n’est pas déjà présent
    if (used === 0 && !panel.querySelector('.no-device')) {

        // Ajoute un message informatif dans le panneau
        panel.innerHTML += '<p class="no-device">Aucun poste activé.</p>';
    }
}

// ----------------------------------------------------------------------------- //
// Protège une chaîne de caractères contre l'injection HTML (XSS)
// Échappe les caractères spéciaux avant affichage dans le DOM
// ----------------------------------------------------------------------------- //

    function escapeHtml(str) {

        // Si la chaîne est vide, nulle ou indéfinie, on retourne une chaîne vi
        if (!str) return '';

        // Remplace les caractères HTML sensibles par leurs entités correspondantes
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

// ----------------------------------------------------------------------------- //