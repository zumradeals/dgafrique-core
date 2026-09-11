import '../css/member-space.css';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

Alpine.store('network', {
    online: navigator.onLine,

    init() {
        window.addEventListener('online', () => { this.online = true; });
        window.addEventListener('offline', () => { this.online = false; });
    },
});

Alpine.data('dgNavigation', () => ({
    panel: null,
    lastTrigger: null,
    pushedHistory: false,

    init() {
        this.handlePopState = () => {
            if (this.panel !== null) {
                this.close(true);
            }
        };

        window.addEventListener('popstate', this.handlePopState);
    },

    destroy() {
        window.removeEventListener('popstate', this.handlePopState);
        document.documentElement.classList.remove('dg-panel-open');
    },

    open(name, event) {
        this.panel = name;
        this.lastTrigger = event?.currentTarget ?? document.activeElement;
        document.documentElement.classList.add('dg-panel-open');

        if (!window.history.state?.dgNavigationPanel) {
            window.history.pushState({ dgNavigationPanel: name }, '');
            this.pushedHistory = true;
        }

        this.$nextTick(() => {
            this.panelElement()?.querySelector('[data-autofocus], a[href], button:not([disabled])')?.focus();
        });
    },

    close(fromHistory = false) {
        const trigger = this.lastTrigger;
        this.panel = null;
        document.documentElement.classList.remove('dg-panel-open');

        if (!fromHistory && this.pushedHistory && window.history.state?.dgNavigationPanel) {
            this.pushedHistory = false;
            window.history.back();
        } else {
            this.pushedHistory = false;
        }

        this.$nextTick(() => trigger?.focus());
    },

    panelElement() {
        return this.$root.querySelector('[data-navigation-panel="' + this.panel + '"]');
    },

    trapFocus(event) {
        const panel = this.panelElement();
        if (!panel) return;

        const focusable = [...panel.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')]
            .filter((element) => !element.hasAttribute('hidden'));

        if (focusable.length === 0) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
}));

// EVENT-001 — raccord de présentation uniquement. Le moteur et les droits restent CAP-068 côté serveur.
const connectZumraEventTab = () => {
    const match = window.location.pathname.match(/^\/zumra\/groupes\/([0-9a-f-]+)\/?$/i);
    if (!match) return;

    document.querySelectorAll('.dg-zumra-world-tabs a[href="#evenements"]').forEach((link) => {
        link.setAttribute('href', `/zumra/groupes/${match[1]}/evenements`);
    });
};

// MEMBERS-001 — raccord de présentation vers l’espace membres réel ; l’autorité reste côté serveur.
const connectZumraMembersTab = () => {
    const match = window.location.pathname.match(/^\/zumra\/groupes\/([0-9a-f-]+)\/?$/i);
    if (!match) return;

    document.querySelectorAll('.dg-zumra-world-tabs a[href="#membres"]').forEach((link) => {
        link.setAttribute('href', `/zumra/groupes/${match[1]}/membres`);
    });
};

// ZUMRA-ACTIVITY-001 — le mini Fil est une destination de lecture, jamais un moteur navigateur.
const connectZumraActivityTab = () => {
    const match = window.location.pathname.match(/^\/zumra\/groupes\/([0-9a-f-]+)\/?$/i);
    if (!match) return;

    const activityUrl = `/zumra/groupes/${match[1]}/activite`;
    const tabs = document.querySelector('.dg-zumra-world-tabs');
    const home = tabs?.querySelector('a[href="#accueil"]');

    if (tabs && home && !tabs.querySelector('a[data-zumra-activity]')) {
        const link = document.createElement('a');
        link.href = activityUrl;
        link.dataset.zumraActivity = 'true';
        link.textContent = '◉ Fil';
        home.insertAdjacentElement('afterend', link);
    }

    document.querySelectorAll('a[href="#activite"]').forEach((link) => {
        link.setAttribute('href', activityUrl);
    });
};

// ZUMRA-POLISH-001 — une seule navigation locale lisible sur tous les sous-espaces.
// Ce raccord ne décide d’aucun droit : chaque destination conserve son middleware et son autorité métier.
const connectZumraPolishNavigation = () => {
    const match = window.location.pathname.match(/^\/zumra\/groupes\/([0-9a-f-]+)\/(activite|membres|formation|discussion|evenements)\/?$/i);
    if (!match || document.querySelector('.dg-zumra-polish-tabs')) return;

    const [, group, section] = match;
    const items = [
        ['accueil', `/zumra/groupes/${group}`, '⌂ Accueil'],
        ['activite', `/zumra/groupes/${group}/activite`, '◉ Fil'],
        ['membres', `/zumra/groupes/${group}/membres`, '♙ Membres'],
        ['formation', `/zumra/groupes/${group}/formation`, '◈ Formation'],
        ['discussion', `/zumra/groupes/${group}/discussion`, '▢ Discussion'],
        ['evenements', `/zumra/groupes/${group}/evenements`, '▣ Événements'],
    ];

    const nav = document.createElement('nav');
    nav.className = 'dg-zumra-polish-tabs';
    nav.setAttribute('aria-label', 'Navigation dans la ZUMRA');

    items.forEach(([key, href, label]) => {
        const link = document.createElement('a');
        link.href = href;
        link.textContent = label;
        if (key === section) {
            link.classList.add('is-active');
            link.setAttribute('aria-current', 'page');
        }
        nav.appendChild(link);
    });

    const host = document.querySelector('.za-hero, .dg-zumra-members__hero, .dg-event-space__hero, .dg-zumra-discussion__hero, .dg-formation__hero');
    host?.insertAdjacentElement('afterend', nav);
};

document.addEventListener('DOMContentLoaded', connectZumraEventTab);
document.addEventListener('DOMContentLoaded', connectZumraMembersTab);
document.addEventListener('DOMContentLoaded', connectZumraActivityTab);
document.addEventListener('DOMContentLoaded', connectZumraPolishNavigation);

Livewire.start();
