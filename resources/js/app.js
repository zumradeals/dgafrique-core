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
