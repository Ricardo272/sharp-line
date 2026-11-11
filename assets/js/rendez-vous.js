/**
 * Rendez-vous : la bande des jours défile pour montrer le jour choisi.
 * Sans JavaScript, la bande commence simplement au premier jour.
 */
document.querySelector('.jours [aria-current="date"]')
    ?.scrollIntoView({ block: 'nearest', inline: 'center' });
