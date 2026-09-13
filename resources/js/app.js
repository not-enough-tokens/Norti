// Cierra cualquier <details data-dropdown> abierto (p.ej. el menú de cuenta
// de la Top Bar) al hacer clic fuera o presionar Escape. Sin esto, <details>
// solo se cierra si vuelves a tocar el mismo summary.
document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-dropdown][open]').forEach((details) => {
        if (!details.contains(event.target)) {
            details.open = false;
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('details[data-dropdown][open]').forEach((details) => {
        details.open = false;
    });
});

// Botón "mostrar contraseña" (form.text-field): alterna el type del input
// entre password/text y swapea el ícono eye/eye-off.
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');

    if (!button) {
        return;
    }

    const input = document.getElementById(button.dataset.passwordToggle);

    if (!input) {
        return;
    }

    const willShow = input.type === 'password';
    input.type = willShow ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(willShow));
    button.setAttribute('aria-label', willShow ? 'Ocultar contraseña' : 'Mostrar contraseña');
    button.querySelector('[data-password-toggle-icon="show"]').hidden = willShow;
    button.querySelector('[data-password-toggle-icon="hide"]').hidden = !willShow;
});
