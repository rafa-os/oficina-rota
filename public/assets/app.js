document.querySelectorAll('form[data-confirmar]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirmar)) event.preventDefault();
    });
});
document.querySelectorAll('input[name="placa"]').forEach((input) => {
    input.addEventListener('input', () => { input.value = input.value.toUpperCase(); });
});
