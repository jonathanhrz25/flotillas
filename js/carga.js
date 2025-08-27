document.addEventListener("DOMContentLoaded", function () {
    let cajasPendientes = [];
    let cajasValidadas = new Set();
    let cajaActualValidando = null;
    let pedidoActualValidando = null; // Variable para el pedido actual
    let lastResults = [];
    let scanCooldown = false;
    let tipoEscaneoActual = ""; // Variable para saber qué se está escaneando (caja o pedido)

    function mostrarAlertaBootstrap(mensaje, tipo = "success", tiempo = 4000) {
        const contenedor = document.getElementById("alertas");
        if (!contenedor) return;

        const alerta = document.createElement("div");
        alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
        alerta.role = "alert";
        alerta.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

        contenedor.appendChild(alerta);

        setTimeout(() => {
            alerta.classList.remove("show");
            alerta.classList.add("hide");
            setTimeout(() => alerta.remove(), 500);
        }, tiempo);
    }

    // Función para mostrar notificaciones de caja validada
    function mostrarNotificacionCaja(caja) {
        const contenedor = document.getElementById("notificaciones");
        if (!contenedor) return;

        const alerta = document.createElement("div");
        alerta.className = "alert alert-success alert-dismissible fade show";
        alerta.role = "alert";
        alerta.innerHTML = `✅ Caja <strong>${caja}</strong> validada correctamente.`;

        const btnCerrar = document.createElement("button");
        btnCerrar.type = "button";
        btnCerrar.className = "btn-close";
        btnCerrar.setAttribute("data-bs-dismiss", "alert");
        btnCerrar.setAttribute("aria-label", "Close");

        alerta.appendChild(btnCerrar);
        contenedor.appendChild(alerta);

        setTimeout(() => {
            alerta.classList.remove("show");
            alerta.classList.add("hide");
            setTimeout(() => alerta.remove(), 500);
        }, 4000);
    }

    // Función para cargar la lista de cajas
    function cargarListaCajas(cajas) {
        const contenedor = document.getElementById("lista-cajas");
        if (!contenedor) {
            console.warn("No se encontró el contenedor lista-cajas");
            return;
        }
        contenedor.innerHTML = "";

        let progreso = document.getElementById("progreso-cajas");
        if (!progreso) {
            const p = document.createElement("p");
            p.id = "progreso-cajas";
            p.className = "mt-2 text-center text-muted";
            contenedor.parentElement.appendChild(p);
            progreso = p;
        }

        cajasPendientes = cajas;
        cajasValidadas.clear();
        cajaActualValidando = null;

        // Limpia el campo oculto al cargar nueva lista
        const listaCajasInput = document.getElementById('lista-cajas-input');
        if (listaCajasInput) listaCajasInput.value = '';

        cajas.forEach(caja => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "btn btn-outline-secondary caja-btn";
            btn.textContent = caja;
            btn.dataset.caja = caja;
            btn.addEventListener("click", () => abrirEscanerParaCaja(caja));
            contenedor.appendChild(btn);
        });

        // Ponemos foco en el primer botón (si existe)
        const primerBoton = contenedor.querySelector('button.caja-btn');
        if (primerBoton) primerBoton.focus();

        actualizarProgreso();
        const btnConfirmar = document.getElementById("btn-confirmar");
        if (btnConfirmar) btnConfirmar.disabled = true;
    }

    // Función para abrir el escáner para una caja específica
    function abrirEscanerParaCaja(caja) {
        if (cajaActualValidando !== null) return mostrarAlertaBootstrap("⚠️ Ya estás validando otra caja.", "warning");
        if (cajasValidadas.has(caja)) return mostrarAlertaBootstrap("⚠️ Esta caja ya fue validada.", "warning");

        cajaActualValidando = caja;
        tipoEscaneoActual = "caja";

        const modalElement = document.getElementById("qrModal");
        if (!modalElement) return mostrarAlertaBootstrap("❌ No se encontró el modal de escaneo.", "danger");
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    // Función para abrir el escáner para un pedido
    function abrirEscanerParaPedido() {
        if (pedidoActualValidando !== null) return mostrarAlertaBootstrap("⚠️ Ya has validado un pedido.", "warning");

        tipoEscaneoActual = "pedido";
        const modalElement = document.getElementById("qrModal");
        if (!modalElement) return mostrarAlertaBootstrap("❌ No se encontró el modal de escaneo.", "danger");
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }

    // Función para actualizar el progreso de cajas validadas
    function actualizarProgreso() {
        const progreso = document.getElementById("progreso-cajas");
        if (!progreso) return;
        progreso.innerText = `Validadas ${cajasValidadas.size} de ${cajasPendientes.length}`;
    }

    // Función para validar el código escaneado de la caja
    function validarCajaEscaneada(codigoEscaneado) {
        if (cajasValidadas.has(codigoEscaneado)) {
            alert("Esta caja ya fue validada.");
            return;
        }

        if (cajaActualValidando && codigoEscaneado === cajaActualValidando) {
            cajasValidadas.add(cajaActualValidando);

            // Actualiza el input hidden con la lista de cajas validadas en formato JSON
            const listaCajasInput = document.getElementById('lista-cajas-input');
            if (listaCajasInput) {
                listaCajasInput.value = JSON.stringify(Array.from(cajasValidadas));
            }

            const btns = document.querySelectorAll(".caja-btn");
            btns.forEach(btn => {
                if (btn.dataset.caja === cajaActualValidando) {
                    btn.classList.remove("btn-outline-secondary");
                    btn.classList.add("btn-success");
                    btn.innerHTML = `${cajaActualValidando} ✅`;
                    btn.disabled = true;
                }
            });

            mostrarNotificacionCaja(cajaActualValidando);

            const modalElement = document.getElementById("qrModal");
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
            }

            actualizarProgreso();

            if (cajasValidadas.size === cajasPendientes.length) {
                const btnConfirmar = document.getElementById("btn-confirmar");
                if (btnConfirmar) btnConfirmar.disabled = false;
            }

            cajaActualValidando = null; // resetea la caja actual después de validar
        } else {
            mostrarAlertaBootstrap("❌ Código incorrecto. Intenta escanear la caja seleccionada.", "danger");
        }
    }

    // Función para validar el código escaneado del pedido
    function validarPedidoEscaneado(codigoEscaneado) {
        const pedidoInput = document.getElementById("pedido");
        if (!pedidoInput) return;

        // Asignar el código escaneado al campo de pedido
        pedidoInput.value = codigoEscaneado;
        pedidoInput.dispatchEvent(new Event("input")); // Disparar lógica para cargar cajas

        // Cerrar el modal de escaneo
        const modalElement = document.getElementById("qrModal");
        if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
        }

        // Detener escáner y liberar cámara
        if (Quagga) {
            Quagga.offDetected();
            Quagga.stop();
            Quagga.initialized = false;

            const video = document.querySelector('#barcode-scanner video');
            if (video && video.srcObject) {
                video.srcObject.getTracks().forEach(track => track.stop());
                video.srcObject = null;
            }
        }

        lastResults = [];
        scanCooldown = false;
    }

    // Callback para Quagga.onDetected
    function onDetectedCallback(result, tipoEscaneo) {
        if (scanCooldown) return;
        const code = result.codeResult.code;
        const error = result.codeResult.error;

        if ((error && error > 0.25) || !isInsideOverlay(result)) {
            const overlay = document.getElementById("scanner-overlay");
            if (overlay) overlay.style.borderColor = "red";
            return;
        }

        lastResults.push(code);
        if (lastResults.length > 3) lastResults.shift();

        // Verifica si hay al menos 3 códigos iguales seguidos
        const occurrences = lastResults.filter(val => val === code).length;

        if (occurrences >= 3) {

            const overlay = document.getElementById("scanner-overlay");
            if (overlay) overlay.style.borderColor = "limegreen";
            scanCooldown = true;

            if (tipoEscaneo === "caja") {
                validarCajaEscaneada(code);
            } else if (tipoEscaneo === "pedido") {
                validarPedidoEscaneado(code);
            }

            setTimeout(() => {
                scanCooldown = false;
            }, 1000);
        }
    }

    // Función para iniciar el escáner con Quagga
    function iniciarEscaner(tipoEscaneo) {
        lastResults = [];
        scanCooldown = false;

        const overlay = document.getElementById("scanner-overlay");
        if (overlay) overlay.style.borderColor = "red";

        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector("#barcode-scanner"),
                constraints: { facingMode: "environment" }
            },
            decoder: {
                readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader"]
            },
            locate: true
        }, function (err) {
            if (err) {
                console.error(err);
                mostrarAlertaBootstrap("❌ No se pudo iniciar el lector.", "danger");
                return;
            }
            Quagga.start();
            Quagga.onDetected(result => onDetectedCallback(result, tipoEscaneo));
        });
    }

    // Función para verificar que el código escaneado esté dentro del área de escaneo
    function isInsideOverlay(result) {
        if (!result.box) return false;

        const overlay = document.getElementById("scanner-overlay");
        const video = document.querySelector('#barcode-scanner video') || document.querySelector('#barcode-scanner canvas');
        if (!overlay || !video) return false;

        const overlayRect = overlay.getBoundingClientRect();
        const videoRect = video.getBoundingClientRect();
        const center = {
            x: (result.box[0][0] + result.box[2][0]) / 2,
            y: (result.box[0][1] + result.box[2][1]) / 2
        };

        const scaleX = videoRect.width / (video.videoWidth || 640);
        const scaleY = videoRect.height / (video.videoHeight || 480);
        const domX = center.x * scaleX + videoRect.left;
        const domY = center.y * scaleY + videoRect.top;

        return (
            domX >= overlayRect.left &&
            domX <= overlayRect.right &&
            domY >= overlayRect.top &&
            domY <= overlayRect.bottom
        );
    }

    // Comienzo del código que escucha el input pedido
    const intervalo = setInterval(function () {
        const pedidoInput = document.getElementById("pedido");
        if (pedidoInput) {
            clearInterval(intervalo);
            pedidoInput.focus();

            // 🔄 CORREGIDO: ahora al hacer clic se abre el escáner como pedido
            pedidoInput.addEventListener("click", function () {
                abrirEscanerParaPedido(); // ✅ Abre el modal y asigna tipoEscaneoActual = "pedido"
            });

            const qrModal = document.getElementById("qrModal");
            const overlay = document.getElementById("scanner-overlay");

            document.addEventListener("keypress", function (e) {
                const tag = document.activeElement.tagName.toLowerCase();
                if (tag !== 'input' && tag !== 'textarea' && tag !== 'select') {
                    pedidoInput.focus();
                }
            });

            let debounceTimer;

            pedidoInput.addEventListener("input", function () {
                clearTimeout(debounceTimer);
                const pedido = this.value.trim();

                if (pedido.length === 0) {
                    const cajasInput = document.getElementById('cajas');
                    const listaCajas = document.getElementById('lista-cajas');
                    if (cajasInput) cajasInput.value = '';
                    if (listaCajas) listaCajas.innerHTML = '';
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`/flotillas/menu/buscar_cajas_pedido.php?pedido=${encodeURIComponent(pedido)}`)
                        .then(response => response.json())
                        .then(data => {
                            console.log("Respuesta buscar_cajas_pedido.php:", data);
                            if (data.cantidad !== undefined && Array.isArray(data.cajas)) {
                                const cajasInput = document.getElementById('cajas');
                                if (cajasInput) cajasInput.value = data.cantidad;
                                cargarListaCajas(data.cajas);
                            } else {
                                mostrarAlertaBootstrap(data.error || "❌ No se encontraron cajas.", "danger");
                                const cajasInput = document.getElementById('cajas');
                                const listaCajas = document.getElementById('lista-cajas');
                                if (cajasInput) cajasInput.value = '';
                                if (listaCajas) listaCajas.innerHTML = '';
                            }
                        })
                        .catch(err => {
                            console.error("❌ Error al obtener cajas:", err);
                            mostrarAlertaBootstrap("❌ Error al conectar con el servidor.", "danger");
                        });
                }, 300);
            });

            if (qrModal) {
                qrModal.addEventListener("shown.bs.modal", function () {
                    if (pedidoInput && pedidoInput.value.trim() !== "" && tipoEscaneoActual === "pedido") {
                        mostrarAlertaBootstrap("⚠️ Ya se escaneó un pedido. Bórralo primero si deseas cambiarlo.", "warning");
                        const modal = bootstrap.Modal.getInstance(qrModal);
                        if (modal) modal.hide();
                        return;
                    }

                    setTimeout(() => {
                        iniciarEscaner(tipoEscaneoActual);
                    }, 200);
                });

                qrModal.addEventListener("hidden.bs.modal", function () {
                    if (Quagga) {
                        Quagga.offDetected(onDetectedCallback);
                        Quagga.stop();

                        const video = document.querySelector('#barcode-scanner video');
                        if (video && video.srcObject) {
                            video.srcObject.getTracks().forEach(track => track.stop());
                            video.srcObject = null;
                        }
                    }

                    lastResults = [];
                    scanCooldown = false;

                    if (overlay) overlay.style.borderColor = "red";

                    cajaActualValidando = null;
                    pedidoActualValidando = null;

                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();

                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';

                    setTimeout(() => pedidoInput.focus(), 100);
                });
            }

            const fechaInput = document.getElementById('fecha');
            if (fechaInput) {
                const now = new Date();

                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');

                const formattedDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
                fechaInput.value = formattedDateTime;
            }
        }
    }, 100);


    // Función para borrar el pedido actual
    const btnBorrarPedido = document.getElementById("btn-borrar-pedido");
    if (btnBorrarPedido) {
        btnBorrarPedido.addEventListener("click", () => {
            const pedidoInput = document.getElementById("pedido");
            const cajasInput = document.getElementById("cajas");
            const listaCajas = document.getElementById("lista-cajas");

            if (confirm("¿Estás seguro que deseas borrar el pedido actual?")) {
                pedidoInput.value = "";
                if (cajasInput) cajasInput.value = "";
                if (listaCajas) listaCajas.innerHTML = "";

                cajasPendientes = [];
                cajasValidadas.clear();
                actualizarProgreso();

                const btnConfirmar = document.getElementById("btn-confirmar");
                if (btnConfirmar) btnConfirmar.disabled = true;
            }
        });
    }
});


// ==========================
// 🌍 Viajes
// ==========================
document.getElementById("btn-confirmar").addEventListener("click", async function (event) {
    event.preventDefault();

    const operador = document.getElementById("operador").value;
    const unidad = document.getElementById("unidad").value;
    const form = document.getElementById("formulario");

    if (!unidad || !operador) {
        alert("Faltan datos de unidad u operador.");
        return;
    }

    try {
        // Paso 1: Verificamos si hay un viaje abierto
        const viajeResponse = await fetch("/flotillas/db/get_viaje_id.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ unidad, operador })
        });

        const viajeData = await viajeResponse.json();

        if (viajeData.status === "existing") {
            const continuar = confirm(`${viajeData.message}\n\n¿Deseas continuar con ese viaje?\nPresiona Cancelar para cerrarlo y crear uno nuevo.`);

            if (!continuar) {
                // Paso 2: Cerrar viaje actual
                const cerrarResponse = await fetch("/flotillas/db/cerrar_viaje.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({ viaje_id: viajeData.viaje_id })
                });

                const cerrarData = await cerrarResponse.json();

                if (cerrarData.status !== "success") {
                    alert("❌ No se pudo cerrar el viaje: " + cerrarData.message);
                    return;
                }

                // Paso 3: Crear nuevo viaje
                const nuevoViaje = await fetch("/flotillas/db/get_viaje_id.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({ unidad, operador })
                });

                const nuevoViajeData = await nuevoViaje.json();
                if (nuevoViajeData.status !== "success") {
                    alert("❌ No se pudo crear nuevo viaje: " + nuevoViajeData.message);
                    return;
                }

                document.getElementById("viaje_id").value = nuevoViajeData.viaje_id;

            } else {
                // Continuar con el viaje actual
                document.getElementById("viaje_id").value = viajeData.viaje_id;
            }

        } else if (viajeData.status === "success") {
            document.getElementById("viaje_id").value = viajeData.viaje_id;
        } else {
            alert("❌ " + viajeData.message);
            return;
        }

        // Paso 4: Enviar formulario con el viaje_id ya resuelto
        const formData = new FormData(form);
        const response = await fetch("/flotillas/db/database_form_carga.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.status === "success") {
            alert("✅ " + result.message);
            window.location.href = result.redirect;
        } else {
            alert("❌ " + result.message);
        }

    } catch (err) {
        console.error("Error en el envío:", err);
        alert("❌ Ocurrió un error al procesar la solicitud.");
    }
});


// ==========================
// Cliente
// ==========================

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('num_cliente').addEventListener('input', function () {
        const numCliente = this.value.trim();
        const clienteInput = document.getElementById('cliente');  // Campo de nombre del cliente

        if (numCliente.length === 0) {
            clienteInput.value = '';  // Limpiar campo si no hay número ingresado
            return;
        }

        // Llamada al backend para obtener nombre del cliente
        fetch(`buscar_cliente.php?num_cliente=${encodeURIComponent(numCliente)}`)
            .then(response => response.json())
            .then(data => {
                if (data.nombre) {
                    clienteInput.value = data.nombre;
                } else {
                    clienteInput.value = '';  // Si no se encuentra, limpiar
                }
            })
            .catch(error => {
                console.error('Error al buscar cliente:', error);
                clienteInput.value = '';  // En caso de error, limpiar
            });
    });
});



// ==========================
// 🌍 Mapa y ubicación cliente
// ==========================

let map;
let markerCliente;
let markerRepartidor;
let directionsService;
let directionsRenderer;

let clienteCoords = { lat: 23.6345, lng: -102.5528 };
let repartidorCoords = null;

function esperarGoogleMaps(callback) {
    if (typeof google !== "undefined" && google.maps) {
        callback();
    } else {
        setTimeout(() => esperarGoogleMaps(callback), 100);
    }
}

function obtenerUbicacionRepartidor() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
            repartidorCoords = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            initMap();
        }, function (error) {
            alert("No se pudo obtener la ubicación del repartidor: " + error.message);
            repartidorCoords = null;
            initMap();
        });
    } else {
        alert("Geolocalización no soportada.");
        repartidorCoords = null;
        initMap();
    }
}

function initMap() {
    const ubicacionInput = document.getElementById('ubicacion');
    const direccionClienteInput = document.getElementById('location');
    const valorInicial = ubicacionInput.value.trim();

    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer();

    map = new google.maps.Map(document.getElementById('map'), {
        center: repartidorCoords || clienteCoords,
        zoom: 15
    });
    directionsRenderer.setMap(map);

    if (repartidorCoords) {
        markerRepartidor = new google.maps.Marker({
            position: repartidorCoords,
            map: map,
            label: "Repartidor",
            icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
        });
    }

    if (valorInicial !== "") {
        if (valorInicial.includes(',')) {
            const [latStr, lngStr] = valorInicial.split(',').map(v => v.trim());
            const lat = parseFloat(latStr);
            const lng = parseFloat(lngStr);
            if (!isNaN(lat) && !isNaN(lng)) {
                clienteCoords = { lat, lng };
                colocarMarcadorClienteYCalcularRuta(clienteCoords, direccionClienteInput);
            }
        } else {
            geocodeAddress(valorInicial);
        }
    }
}

function colocarMarcadorClienteYCalcularRuta(coords, direccionClienteInput) {
    if (!markerCliente) {
        markerCliente = new google.maps.Marker({
            position: coords,
            map: map,
            draggable: true,
            label: "Cliente",
            icon: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
        });

        google.maps.event.addListener(markerCliente, 'dragend', function (event) {
            clienteCoords = {
                lat: event.latLng.lat(),
                lng: event.latLng.lng()
            };
            document.getElementById('ubicacion').value = `${clienteCoords.lat}, ${clienteCoords.lng}`;
            obtenerDireccion(clienteCoords.lat, clienteCoords.lng, direccionClienteInput);
            calcularRuta();
        });
    } else {
        markerCliente.setPosition(coords);
        markerCliente.setMap(map);
    }

    map.setCenter(coords);
    map.setZoom(15);

    clienteCoords = coords;

    obtenerDireccion(clienteCoords.lat, clienteCoords.lng, direccionClienteInput);
    calcularRuta();
}

function geocodeAddress(address) {
    if (!address || address.trim() === "") {
        alert('Por favor, ingresa una dirección válida.');
        return;
    }

    const geocoder = new google.maps.Geocoder();

    geocoder.geocode({ address }, function (results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
            const location = results[0].geometry.location;
            const coords = { lat: location.lat(), lng: location.lng() };

            document.getElementById('ubicacion').value = `${coords.lat}, ${coords.lng}`;
            colocarMarcadorClienteYCalcularRuta(coords, document.getElementById('location'));
        } else {
            alert('Error al buscar dirección: ' + status);
        }
    });
}

function obtenerDireccion(lat, lng, inputField) {
    const latlng = new google.maps.LatLng(lat, lng);
    const geocoder = new google.maps.Geocoder();
    geocoder.geocode({ 'location': latlng }, function (results, status) {
        if (status === google.maps.GeocoderStatus.OK && results[0]) {
            inputField.value = results[0].formatted_address;
        } else {
            inputField.value = 'Dirección no disponible';
        }
    });
}

function calcularRuta() {
    if (!clienteCoords || !repartidorCoords) return;

    const request = {
        origin: new google.maps.LatLng(repartidorCoords.lat, repartidorCoords.lng),
        destination: new google.maps.LatLng(clienteCoords.lat, clienteCoords.lng),
        travelMode: google.maps.TravelMode.DRIVING
    };

    directionsService.route(request, function (response, status) {
        if (status === google.maps.DirectionsStatus.OK) {
            directionsRenderer.setDirections(response);
            const distanciaTexto = response.routes[0].legs[0].distance.text;
            const distanciaNumerica = parseFloat(distanciaTexto.replace(",", ""));
            document.getElementById('km').value = distanciaNumerica.toFixed(2);
        } else {
            console.warn("No se pudo calcular la ruta: " + status);
            document.getElementById('km').value = '';
            directionsRenderer.set('directions', null);
        }
    });
}

// Iniciar todo cuando esté listo el DOM
document.addEventListener("DOMContentLoaded", function () {
    const ubicacionInput = document.getElementById('ubicacion');
    const locationInput = document.getElementById('location');

    ubicacionInput.addEventListener('input', function () {
        locationInput.value = '';
        clienteCoords = { lat: 23.6345, lng: -102.5528 };
        if (map && markerCliente) {
            map.setCenter(clienteCoords);
            map.setZoom(6);
            markerCliente.setPosition(clienteCoords);
            directionsRenderer.set('directions', null);
            document.getElementById('km').value = '';
        }
    });

    document.getElementById('buscarUbicacion').addEventListener('click', function () {
        const valor = ubicacionInput.value.trim();

        if (valor === "") {
            clienteCoords = { lat: 23.6345, lng: -102.5528 };
            if (map && markerCliente) {
                map.setCenter(clienteCoords);
                map.setZoom(6);
                markerCliente.setPosition(clienteCoords);
                directionsRenderer.set('directions', null);
                document.getElementById('km').value = '';
                locationInput.value = '';
            }
            return;
        }

        if (valor.includes(',')) {
            const [latStr, lngStr] = valor.split(',').map(v => v.trim());
            const lat = parseFloat(latStr);
            const lng = parseFloat(lngStr);
            if (!isNaN(lat) && !isNaN(lng)) {
                const coords = { lat, lng };
                colocarMarcadorClienteYCalcularRuta(coords, locationInput);
                return;
            }
        }

        geocodeAddress(valor);
    });

    // Esperar a que Google Maps esté cargado antes de iniciar
    esperarGoogleMaps(() => {
        obtenerUbicacionRepartidor();
    });
});
