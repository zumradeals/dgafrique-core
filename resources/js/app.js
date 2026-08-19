const agirSheet = document.querySelector('[data-dg-agir-sheet]');
if (agirSheet) {
    const open = () => { agirSheet.hidden = false; };
    const close = () => { agirSheet.hidden = true; };

    document.querySelectorAll('[data-dg-agir-open]').forEach((button) => button.addEventListener('click', open));
    agirSheet.querySelectorAll('[data-dg-agir-close]').forEach((button) => button.addEventListener('click', close));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close();
    });
}

// Menu mobile de la landing (docs/design/DESIGN-INVARIANTS.md, addendum du 19/08/2026).
const landingMenuSheet = document.querySelector('[data-dg-menu-sheet]');
if (landingMenuSheet) {
    const open = () => { landingMenuSheet.hidden = false; };
    const close = () => { landingMenuSheet.hidden = true; };

    document.querySelectorAll('[data-dg-menu-open]').forEach((button) => button.addEventListener('click', open));
    landingMenuSheet.querySelectorAll('[data-dg-menu-close]').forEach((el) => el.addEventListener('click', close));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close();
    });
}

// Filtre des exemples « en ce moment » sur la landing — purement local, ne simule aucune
// recherche serveur (les cartes filtrées sont déjà toutes présentes dans la page).
const momentFilters = [...document.querySelectorAll('[data-dg-moment-filter]')];
const momentCards = [...document.querySelectorAll('[data-dg-moment]')];
if (momentFilters.length && momentCards.length) {
    momentFilters.forEach((button) => button.addEventListener('click', () => {
        const type = button.dataset.dgMomentFilter;

        momentFilters.forEach((btn) => btn.setAttribute('aria-pressed', String(btn === button)));
        momentCards.forEach((card) => {
            card.hidden = type !== 'tout' && card.dataset.dgMoment !== type;
        });
    }));
}

const progressiveProfile = document.querySelector('[data-profile-steps]');

const zumraQrTargets = [...document.querySelectorAll('[data-zumra-qr]')];
if (zumraQrTargets.length) {
    import('qrcode').then(({ default: QRCode }) => {
        zumraQrTargets.forEach((canvas) => QRCode.toCanvas(canvas, canvas.dataset.zumraQr, {
            width: 190,
            margin: 1,
            errorCorrectionLevel: 'M',
            color: { dark: '#061f35', light: '#ffffff' },
        }));
    }).catch(() => {
        zumraQrTargets.forEach((canvas) => canvas.replaceWith(document.createTextNode('QR temporairement indisponible')));
    });
}

if (progressiveProfile) {
    const stages = [...progressiveProfile.querySelectorAll('[data-profile-step]')];
    const targets = [...progressiveProfile.querySelectorAll('[data-profile-step-target]')];

    const showStage = (requestedIndex, focus = true) => {
        const index = Math.max(0, Math.min(requestedIndex, stages.length - 1));

        stages.forEach((stage, stageIndex) => {
            stage.hidden = stageIndex !== index;
        });
        targets.forEach((target, targetIndex) => {
            target.classList.toggle('active', targetIndex === index);
            target.classList.toggle('visited', targetIndex < index);
            target.setAttribute('aria-current', targetIndex === index ? 'step' : 'false');
        });

        if (focus) {
            stages[index]?.querySelector('input, textarea, select, button:not([disabled])')?.focus({ preventScroll: true });
            progressiveProfile.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    targets.forEach((target) => target.addEventListener('click', () => showStage(Number(target.dataset.profileStepTarget))));
    stages.forEach((stage, index) => {
        stage.querySelector('[data-profile-next]')?.addEventListener('click', () => showStage(index + 1));
        stage.querySelector('[data-profile-previous]')?.addEventListener('click', () => showStage(index - 1));
    });

    showStage(Number(progressiveProfile.dataset.profileInitial || 0), false);
}
