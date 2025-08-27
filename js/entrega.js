document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const pedido = urlParams.get("pedido");
    let cajasPendientes = [], cajasValidadas = new Set(), cajaActualValidando = null;

    const scannerModalEl = document.getElementById('qrModal');
    const scannerModal = new bootstrap.Modal(scannerModalEl);
    const scannerContainer = document.getElementById('barcode-scanner');
    const feedbackEl = document.getElementById('scanner-feedback');
    const btnGuardar = document.getElementById("btnGuardar");
    const selectEntregado = document.getElementById("entregado");

    if (btnGuardar) btnGuardar.disabled = true; // Deshabilitar al inicio

    function mostrarNotificacionCaja(caja) {
        const cont = document.getElementById("notificaciones");
        if (!cont) return;
        const d = document.createElement("div");
        d.className = "alert alert-success alert-dismissible fade show";
        d.role = "alert";
        d.innerHTML = `✅ Caja <strong>${caja}</strong> entregada. <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        cont.appendChild(d);
        setTimeout(() => d.remove(), 4000);
    }

    function actualizarProgreso() {
        const prog = document.getElementById("progreso-cajas");
        if (prog) prog.innerText = `Entregadas ${cajasValidadas.size} de ${cajasPendientes.length}`;
    }

    function verificarSiPuedeGuardar() {
        const estado = selectEntregado?.value;
        const todasCajasValidadas = cajasValidadas.size === cajasPendientes.length;

        if (
            (estado === "Pedido Entregado" && todasCajasValidadas) ||
            estado === "Cliente Ausente"
        ) {
            btnGuardar.disabled = false;
        } else {
            btnGuardar.disabled = true;
        }
    }

    function cargarListaCajas(cajas) {
        const cont = document.getElementById("lista-cajas");
        cont.innerHTML = "";
        cajasPendientes = cajas;
        cajasValidadas.clear();
        document.getElementById("lista-cajas-input").value = "";

        cajas.forEach(caja => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "btn btn-outline-primary me-2 mb-2 caja-btn";
            btn.textContent = caja;
            btn.dataset.caja = caja;

            btn.addEventListener("click", () => {
                if (cajasValidadas.has(caja)) return;
                cajaActualValidando = caja;
                feedbackEl.textContent = "";
                scannerModal.show();
                iniciarScanner(code => {
                    if (code === cajaActualValidando) {
                        cajasValidadas.add(cajaActualValidando);
                        btn.classList.replace("btn-outline-primary", "btn-success");
                        btn.innerHTML = `${cajaActualValidando} ✅`;
                        btn.disabled = true;
                        mostrarNotificacionCaja(cajaActualValidando);
                        document.getElementById("lista-cajas-input").value = JSON.stringify([...cajasValidadas]);
                        actualizarProgreso();
                        cerrarScanner();
                        scannerModal.hide();
                        verificarSiPuedeGuardar(); // Verifica después de validar una caja
                    } else {
                        feedbackEl.textContent = "❌ No coincide. Intenta de nuevo.";
                    }
                });
            });

            cont.appendChild(btn);
        });

        const p = document.createElement("p");
        p.id = "progreso-cajas";
        p.className = "mt-2 text-center text-muted";
        cont.parentElement.appendChild(p);
        actualizarProgreso();
    }

    function iniciarScanner(onDetected) {
        Quagga.init({
            inputStream: {
                type: "LiveStream",
                target: scannerContainer,
                constraints: { facingMode: "environment" }
            },
            locator: { patchSize: "medium", halfSample: true },
            decoder: { readers: ["code_128_reader", "ean_reader", "ean_8_reader"] }
        }, err => {
            if (err) {
                console.error(err);
                feedbackEl.textContent = "🔴 Error inicializando cámara.";
                return;
            }
            Quagga.start();
        });

        Quagga.onDetected(data => {
            const code = data.codeResult.code.trim();
            onDetected(code);
        });
    }

    function cerrarScanner() {
        Quagga.offDetected();
        try { Quagga.stop(); } catch { }
    }

    if (pedido) {
        fetch(`/flotillas/menu/buscar_cajas_pedido.php?pedido=${encodeURIComponent(pedido)}`)
            .then(r => r.json())
            .then(data => {
                if (data?.cajas?.length) {
                    document.getElementById("cajas").value = data.cantidad || data.cajas.length;
                    cargarListaCajas(data.cajas);
                } else alert("No se encontraron cajas para este pedido.");
            })
            .catch(e => {
                console.error(e);
                alert("Error de conexión al servidor.");
            });
    }

    scannerModalEl.addEventListener('hidden.bs.modal', cerrarScanner);

    // Verificación al cambiar el estado de entrega
    selectEntregado?.addEventListener("change", () => {
        verificarSiPuedeGuardar();
    });
});
