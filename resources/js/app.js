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

Livewire.start();
