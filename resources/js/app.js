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
