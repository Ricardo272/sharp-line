<?php
/**
 * Petites aides pour les formulaires : affichage accessible, lecture des
 * champs, jeton anti-robots, téléphone. Réutilisées par le rendez-vous,
 * puis par le tableau de bord.
 */

require_once __DIR__ . '/helpers.php';

const JETON_AGE_MIN = 3;     // secondes : plus rapide, c'est un robot
const JETON_AGE_MAX = 7200;  // secondes : au-delà, le formulaire a expiré

/**
 * Attributs ARIA d'un champ :
 *   aria-describedby  relie le champ à son aide et à son message d'erreur
 *                     (le lecteur d'écran les lit quand on arrive sur le champ)
 *   aria-invalid      signale le champ en erreur
 */
function attributsChamp(string $champ, array $erreurs, ?string $idAide = null): string
{
    $ids = [];
    if ($idAide !== null) {
        $ids[] = $idAide;
    }
    if (isset($erreurs[$champ])) {
        $ids[] = 'erreur-' . $champ;
    }

    $attributs = $ids ? ' aria-describedby="' . e(implode(' ', $ids)) . '"' : '';
    if (isset($erreurs[$champ])) {
        $attributs .= ' aria-invalid="true"';
    }

    return $attributs;
}

/**
 * Message d'erreur sous un champ (rien s'il n'y a pas d'erreur).
 */
function messageErreur(string $champ, array $erreurs): string
{
    if (!isset($erreurs[$champ])) {
        return '';
    }

    return '<p class="champ__erreur" id="erreur-' . e($champ) . '">' . e($erreurs[$champ]) . '</p>';
}

/**
 * Récupère les champs attendus d'un formulaire, toujours sous forme de texte nettoyé.
 * Un texte qui n'est pas de l'UTF-8 valide est ignoré (vidé) : la vérification
 * le signale alors comme manquant, au lieu de faire échouer l'enregistrement en base.
 */
function lireChamps(array $source, array $champs): array
{
    $valeurs = [];
    foreach ($champs as $champ) {
        $texte = is_string($source[$champ] ?? null) ? trim($source[$champ]) : '';
        $valeurs[$champ] = mb_check_encoding($texte, 'UTF-8') ? $texte : '';
    }
    return $valeurs;
}


/* ==========================================================================
   Jeton anti-robots (sans session ni cookie)
   Le jeton contient l'heure d'affichage du formulaire, signée avec la clé
   secrète : « 1762700000.3f9a… ». Sans la clé, impossible d'en fabriquer un,
   ni de modifier l'heure sans casser la signature.
   ========================================================================== */

/**
 * Signature HMAC d'un texte. $usage sépare les signatures : un jeton de
 * formulaire ne peut pas servir de lien de confirmation, et inversement.
 */
function signer(string $usage, string $texte): string
{
    return hash_hmac('sha256', $usage . '|' . $texte, configLocale()['secret']);
}

function creerJeton(): string
{
    $horodatage = (string) time();
    return $horodatage . '.' . signer('formulaire', $horodatage);
}

/**
 * Vérifie le jeton. Retourne 'ok', 'invalide' (falsifié ou expiré) ou 'trop_rapide'.
 */
function verifierJeton(string $jeton, ?int $maintenant = null): string
{
    $maintenant ??= time();
    $morceaux = explode('.', $jeton, 2);
    if (count($morceaux) !== 2 || !ctype_digit($morceaux[0])) {
        return 'invalide';
    }

    [$horodatage, $signature] = $morceaux;

    // hash_equals compare en temps constant : on ne peut pas deviner la
    // signature caractère par caractère en mesurant le temps de réponse.
    if (!hash_equals(signer('formulaire', $horodatage), $signature)) {
        return 'invalide';
    }

    $age = $maintenant - (int) $horodatage;
    if ($age < JETON_AGE_MIN) {
        return 'trop_rapide';
    }
    if ($age > JETON_AGE_MAX) {
        return 'invalide';
    }

    return 'ok';
}


/* ==========================================================================
   Prix (tableau de bord)
   ========================================================================== */

/**
 * « 18,50 », « 19 € », « 19.5 » → 18.5 / 19 / 19.5 ; null si invalide (ou 0, ou 1000 et plus).
 */
function lirePrix(string $saisie): ?float
{
    $nettoye = str_replace([',', '€', ' ', "\u{00A0}", "\u{202F}"], ['.', '', '', '', ''], trim($saisie));

    if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $nettoye) || (float) $nettoye <= 0) {
        return null;
    }

    return (float) $nettoye;
}

/**
 * Prix pour remplir un champ : 18.5 → « 18,50 » ; 19 → « 19 ».
 */
function prixPourChamp(float|string $prix): string
{
    $prix = (float) $prix;
    return floor($prix) == $prix ? (string) (int) $prix : number_format($prix, 2, ',', '');
}


/* ==========================================================================
   Téléphone
   ========================================================================== */

/**
 * « 06.12.34.56.78 », « +33 6 12 34 56 78 »… → « 0612345678 », ou null si invalide.
 * Enregistré sans espaces ; formaterTelephone() les remet à l'affichage.
 */
function normaliserTelephone(string $saisie): ?string
{
    $chiffres = preg_replace('/[\s.\-()]/', '', $saisie);
    $chiffres = preg_replace('/^(?:\+33|0033)/', '0', $chiffres);

    return preg_match('/^0[1-9]\d{8}$/', $chiffres) ? $chiffres : null;
}

/**
 * « 0612345678 » → « 06 12 34 56 78 ».
 */
function formaterTelephone(string $chiffres): string
{
    return implode(' ', str_split($chiffres, 2));
}
