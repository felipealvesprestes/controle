// Mobile navigation drawer: a store so the persisted top bar and sidebar share it across wire:navigate.
document.addEventListener('alpine:init', () => {
    window.Alpine.store('nav', { open: false });
});
