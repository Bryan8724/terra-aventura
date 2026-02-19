// ===============================
// MODAL CONFIRMATION OBJET
// ===============================

function openConfirmationModal(objetId) {
    const modal = document.getElementById('confirmationModal');
    const input = document.getElementById('objet_id');

    if (!modal || !input) return;

    input.value = objetId;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeConfirmationModal() {
    const modal = document.getElementById('confirmationModal');
    if (!modal) return;

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// ===============================
// GESTION DES FERMETURES
// ===============================

// Clic sur le fond (backdrop) → fermer
document.getElementById('confirmationModal')
    ?.addEventListener('click', (e) => {
        if (e.target.id === 'confirmationModal') {
            closeConfirmationModal();
        }
    });

// Touche ESC → fermer
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeConfirmationModal();
    }
});
