document.addEventListener('DOMContentLoaded', function () {
    const formulario = document.getElementById('formularioGas');
    const modal = new bootstrap.Modal(document.getElementById('modalAgregarGas'));
    const alertaContainer = document.getElementById('alertaContainer');
    const tablaGas = document.getElementById('tablaGas');
    const gasContent = document.getElementById('gasContent');

    formulario.addEventListener('submit', function (e) {
        const precio = document.getElementById('precio_litro').value.trim();
        const litros = document.getElementById('cantidad_litros').value.trim();

        if (!precio || !litros) {
            const continuar = confirm("⚠️ No ingresaste datos de gasolina.\n¿Deseas continuar de todos modos?");
            if (!continuar) {
                e.preventDefault(); // Detiene el envío del formulario
            }
        }
    });
});

function previewImagen(event, previewId, eliminarBtnId) {
    const input = event.target;
    const file = input.files[0];

    if (file && file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const preview = document.getElementById(previewId);
            const eliminarBtn = document.getElementById(eliminarBtnId);

            preview.src = e.target.result;
            preview.classList.remove('d-none');
            eliminarBtn.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
}

function eliminarImagen(inputId, previewId, eliminarBtnId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const eliminarBtn = document.getElementById(eliminarBtnId);

    input.value = '';
    preview.src = '#';
    preview.classList.add('d-none');
    eliminarBtn.classList.add('d-none');
}

function establecerFechaActual() {
    const ahora = new Date();
    const offset = ahora.getTimezoneOffset();
    ahora.setMinutes(ahora.getMinutes() - offset);
    document.getElementById('fecha').value = ahora.toISOString().slice(0, 16);
}

function calcularTotal() {
    const precioInput = document.getElementById('precio_litro');
    const litrosInput = document.getElementById('cantidad_litros');
    const totalInput = document.getElementById('total_cargado');

    if (!precioInput || !litrosInput || !totalInput) return;

    const precio = parseFloat(precioInput.value);
    const litros = parseFloat(litrosInput.value);

    if (!isNaN(precio) && !isNaN(litros)) {
        const total = precio * litros;
        totalInput.value = total.toFixed(2);
    } else {
        totalInput.value = '';
    }
}

const modalElement = document.getElementById('modalAgregarGas');

modalElement.addEventListener('shown.bs.modal', () => {
    establecerFechaActual();

    const precioInput = document.getElementById('precio_litro');
    const litrosInput = document.getElementById('cantidad_litros');
    const totalInput = document.getElementById('total_cargado');

    precioInput.value = '';
    litrosInput.value = '';
    totalInput.value = '';

    precioInput.addEventListener('input', calcularTotal);
    litrosInput.addEventListener('input', calcularTotal);
});

modalElement.addEventListener('hidden.bs.modal', () => {
    const precioInput = document.getElementById('precio_litro');
    const litrosInput = document.getElementById('cantidad_litros');

    if (precioInput && litrosInput) {
        precioInput.removeEventListener('input', calcularTotal);
        litrosInput.removeEventListener('input', calcularTotal);
    }
});
