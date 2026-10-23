// Ludex : outils communs du site. Le site n'a pas de base à lui : chaque page appelle l'API, comme n'importe quel client.

const Ludex = (() => {
    const CLE = 'ludex:jeton';

    // Le jeton vit dans le navigateur ; s'il est illisible (navigation privée stricte), on reste déconnecté
    const lire = () => { try { return localStorage.getItem(CLE); } catch { return null; } };
    const ecrire = (jeton) => { try { jeton ? localStorage.setItem(CLE, jeton) : localStorage.removeItem(CLE); } catch { /* rien à garder */ } };

    class ErreurApi extends Error {
        constructor(statut, corps) {
            super(corps?.detail || corps?.message || `Erreur ${statut}`);
            this.statut = statut;
            this.violations = corps?.violations ?? [];
        }
    }

    /**
     * Appelle l'API. jeton : celui passé, sinon celui de la session ; false pour un appel anonyme.
     * Un 401 avec le jeton de session le supprime (expiré) et renvoie vers la connexion.
     */
    async function api(chemin, { methode = 'GET', corps, jeton, patch = false } = {}) {
        const cle = jeton === false ? null : (jeton ?? lire());
        const entetes = { Accept: 'application/ld+json' };
        if (corps !== undefined) entetes['Content-Type'] = patch ? 'application/merge-patch+json' : 'application/json';
        if (cle) entetes.Authorization = `Bearer ${cle}`;

        const reponse = await fetch(chemin, { method: methode, headers: entetes, body: corps === undefined ? undefined : JSON.stringify(corps) });
        if (reponse.status === 204) return null;
        const donnees = await reponse.json().catch(() => null);
        if (!reponse.ok) {
            if (reponse.status === 401 && jeton === undefined && cle) {
                ecrire(null);
                if (location.pathname.startsWith('/compte')) location.href = '/connexion?expire=1';
            }
            throw new ErreurApi(reponse.status, donnees);
        }
        return donnees;
    }

    async function connexion(email, motDePasse) {
        const { token } = await api('/api/auth', { methode: 'POST', corps: { email, password: motDePasse }, jeton: false });
        ecrire(token);
        return token;
    }

    const deconnexion = () => { ecrire(null); location.href = '/'; };

    /** Le compte connecté, ou null */
    async function moi() {
        if (!lire()) return null;
        try { return await api('/api/account'); } catch { return null; }
    }

    const echappe = (texte) => String(texte ?? '').replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);
    const heures = (minutes) => `${(minutes / 60).toLocaleString('fr-FR', { maximumFractionDigits: 1 })} h`;
    const jour = (iso) => new Date(iso).toLocaleDateString('fr-FR');
    const mois = (iso) => new Date(iso).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
    const STATUTS = { backlog: 'À faire', playing: 'En cours', finished: 'Fini', abandoned: 'Abandonné' };

    /** Partage natif sur téléphone, copie dans le presse-papiers ailleurs */
    async function partager(texte) {
        try {
            if (navigator.share && matchMedia('(pointer: coarse)').matches) {
                await navigator.share({ text: texte });
                return 'partage';
            }
            await navigator.clipboard.writeText(texte);
            return 'copie';
        } catch {
            return 'echec';
        }
    }

    /** Les messages d'erreur d'un formulaire : le détail, ou chaque champ en faute */
    const messageErreur = (e) => (e.violations?.length ? e.violations.map((v) => v.message).join(' ') : e.message);

    // La pastille en haut à droite : « Se connecter », ou le nom du compte
    (async () => {
        const lien = document.getElementById('compte-lien');
        if (!lien) return;
        const compte = await moi();
        if (compte) {
            lien.href = '/compte';
            lien.classList.add('ok');
            lien.querySelector('span').textContent = compte.displayName || 'Mon compte';
        }
    })();

    return { api, connexion, deconnexion, moi, jeton: lire, garder: ecrire, echappe, heures, jour, mois, STATUTS, partager, messageErreur, ErreurApi };
})();
