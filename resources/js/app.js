const progressiveProfile = document.querySelector('[data-profile-steps]');

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
