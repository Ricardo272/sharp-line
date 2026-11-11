/**
 * Galerie : agrandissement d'une photo au clic.
 * Chaque photo est enveloppée dans un bouton ; un clic ouvre la fenêtre
 * <dialog> avec l'image et sa légende. Échap ferme (géré par le navigateur),
 * un clic sur le voile aussi, et le focus revient sur la photo ouverte.
 */
{
    const dialogue = document.getElementById('agrandissement');
    const photos = document.querySelectorAll('.photo');

    if (dialogue && photos.length && typeof dialogue.showModal === 'function') {
        const image = document.createElement('img');
        image.className = 'agrandissement__image';
        image.alt = '';
        dialogue.querySelector('.agrandissement__contenu').prepend(image);
        const legende = dialogue.querySelector('.agrandissement__legende');
        let declencheur = null;

        photos.forEach((photo) => {
            const img = photo.querySelector('img');
            const texte = photo.querySelector('figcaption').textContent;

            // Le bouton est créé ici : sans JavaScript, pas de bouton inutile
            const bouton = document.createElement('button');
            bouton.type = 'button';
            bouton.className = 'photo__bouton';
            bouton.setAttribute('aria-label', `Agrandir : ${texte}`);
            img.replaceWith(bouton);
            bouton.append(img);

            bouton.addEventListener('click', () => {
                image.src = img.currentSrc || img.src;
                image.alt = img.alt;
                image.width = img.getAttribute('width');
                image.height = img.getAttribute('height');
                legende.textContent = texte; // textContent : aucun HTML injecté
                declencheur = bouton;
                dialogue.showModal();
            });
        });

        // Clic sur le voile : la cible est la fenêtre elle-même, pas son contenu
        dialogue.addEventListener('click', (evenement) => {
            if (evenement.target === dialogue) {
                dialogue.close();
            }
        });

        dialogue.addEventListener('close', () => declencheur?.focus());
    }
}
