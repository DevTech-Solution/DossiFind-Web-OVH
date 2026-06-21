// ------------------------------------------------------------
// header.js
// Rôle : gestion du menu utilisateur (ouvrir / fermer)
// ------------------------------------------------------------

document.addEventListener('DOMContentLoaded', () => {

    const toggleButton = document.getElementById('user-menu-toggle');
    const dropdownMenu = document.getElementById('user-menu-dropdown');

    // Si le menu utilisateur n'existe pas (utilisateur non connecté)
    if (!toggleButton || !dropdownMenu) {
        return;
    }

    // Ouvre / ferme le menu au clic sur l’icône utilisateur
    toggleButton.addEventListener('click', (event) => {
        event.stopPropagation(); // empêche la fermeture immédiate
        dropdownMenu.toggleAttribute('hidden');
    });

    // Ferme le menu si clic ailleurs dans la page
    document.addEventListener('click', () => {
        if (!dropdownMenu.hasAttribute('hidden')) {
            dropdownMenu.setAttribute('hidden', '');
        }
    });

    // Empêche la fermeture quand on clique DANS le menu
    dropdownMenu.addEventListener('click', (event) => {
        event.stopPropagation();
    });

});