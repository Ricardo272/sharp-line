/**
 * Menu mobile.
 * Le script ne fait qu'une chose : changer aria-expanded sur le bouton.
 * Le CSS lit cet attribut pour ouvrir ou fermer le menu, et les lecteurs
 * d'écran l'annoncent (« Menu, réduit » / « Menu, développé »).
 * Sans JavaScript, le menu reste affiché en liste.
 */
{
    const bouton = document.querySelector('.nav-bouton');

    if (bouton) {
        const menu = document.getElementById(bouton.getAttribute('aria-controls'));
        const estOuvert = () => bouton.getAttribute('aria-expanded') === 'true';
        const basculer = (ouvrir) => bouton.setAttribute('aria-expanded', String(ouvrir));

        bouton.addEventListener('click', () => basculer(!estOuvert()));

        // Échap : ferme, et rend le focus au bouton pour ne pas perdre l'utilisateur clavier
        document.addEventListener('keydown', (evenement) => {
            if (evenement.key === 'Escape' && estOuvert()) {
                basculer(false);
                bouton.focus();
            }
        });

        // Clic sur un lien du menu : on ferme (utile pour les liens vers une ancre)
        menu.addEventListener('click', (evenement) => {
            if (evenement.target.closest('a')) {
                basculer(false);
            }
        });
    }
}
