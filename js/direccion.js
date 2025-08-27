let map;
let marker;
let geocoder;

// Función para inicializar el mapa con la ubicación actual del usuario
function initMap() {
    // Primero, intenta obtener la ubicación actual del usuario
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
            const userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };

            // Crear el mapa centrado en la ubicación actual del usuario
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: userLocation
            });

            geocoder = new google.maps.Geocoder();

            // Crear el marcador en la ubicación actual del usuario
            marker = new google.maps.Marker({
                map: map,
                position: userLocation,
                draggable: true, // Permitir mover el marcador
            });

            // Escuchar cuando el marcador se mueva
            google.maps.event.addListener(marker, "dragend", function () {
                const position = marker.getPosition();
                document.getElementById("lat").value = position.lat();
                document.getElementById("lng").value = position.lng();

                // Actualizar la dirección cuando se mueva el marcador
                obtenerDireccion(position.lat(), position.lng(), document.getElementById("ubicacion"));
            });

        }, function () {
            alert("No se pudo obtener la ubicación del usuario.");
            // Si no se puede obtener la ubicación, centramos el mapa en CDMX como fallback
            const fallbackLocation = { lat: 19.432608, lng: -99.133209 }; // Coordenadas de CDMX
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: fallbackLocation
            });

            geocoder = new google.maps.Geocoder();

            // Crear marcador en la ubicación de fallback (CDMX)
            marker = new google.maps.Marker({
                map: map,
                position: fallbackLocation,
                draggable: true,
            });

            google.maps.event.addListener(marker, "dragend", function () {
                const position = marker.getPosition();
                document.getElementById("lat").value = position.lat();
                document.getElementById("lng").value = position.lng();

                // Actualizar la dirección con la nueva ubicación
                obtenerDireccion(position.lat(), position.lng(), document.getElementById("ubicacion"));
            });
        });
    } else {
        alert("Geolocalización no soportada por este navegador.");
        // Si no soporta geolocalización, centramos el mapa en CDMX
        const fallbackLocation = { lat: 19.432608, lng: -99.133209 }; // Coordenadas de CDMX
        map = new google.maps.Map(document.getElementById("map"), {
            zoom: 15,
            center: fallbackLocation
        });

        geocoder = new google.maps.Geocoder();

        // Crear marcador en la ubicación de fallback (CDMX)
        marker = new google.maps.Marker({
            map: map,
            position: fallbackLocation,
            draggable: true,
        });

        google.maps.event.addListener(marker, "dragend", function () {
            const position = marker.getPosition();
            document.getElementById("lat").value = position.lat();
            document.getElementById("lng").value = position.lng();

            // Actualizar la dirección con la nueva ubicación
            obtenerDireccion(position.lat(), position.lng(), document.getElementById("ubicacion"));
        });
    }
}

// Función para obtener la dirección desde las coordenadas
function obtenerDireccion(lat, lng, inputField) {
    const latlng = new google.maps.LatLng(lat, lng);
    geocoder.geocode({ 'location': latlng }, function (results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
            if (results[0]) {
                inputField.value = results[0].formatted_address;  // Asignamos la dirección legible
            } else {
                inputField.value = 'Dirección no disponible';
            }
        } else {
            inputField.value = 'Dirección no disponible';
        }
    });
}

// Buscar la dirección al escribir en el input
document.getElementById("buscarUbicacion").addEventListener("click", function () {
    const address = document.getElementById("ubicacion").value;

    if (address) {
        geocoder.geocode({ address: address }, function (results, status) {
            if (status === "OK") {
                map.setCenter(results[0].geometry.location);
                marker.setPosition(results[0].geometry.location);

                document.getElementById("lat").value = results[0].geometry.location.lat();
                document.getElementById("lng").value = results[0].geometry.location.lng();
                document.getElementById("ubicacion").value = results[0].formatted_address;
            } else {
                alert("No se pudo encontrar la dirección: " + status);
            }
        });
    } else {
        alert("Por favor, ingresa una dirección.");
    }
});

// Ajustar el mapa cuando el modal se muestre
$('#modalAgregarDireccion').on('shown.bs.modal', function () {
    if (typeof map === "undefined") {
        initMap();  // Llamar a la función para inicializar el mapa si aún no está definido
    } else {
        google.maps.event.trigger(map, 'resize');  // Ajustar el tamaño del mapa
        const center = map.getCenter();  // Obtener el centro del mapa
        map.setCenter(center);  // Recentrar el mapa
    }
});

// Bloquear el envío del formulario y enviarlo con AJAX
document.querySelector('form').addEventListener('submit', function (e) {
    e.preventDefault();  // Evitar que el formulario se envíe de forma tradicional

    // Obtener el valor del campo 'cliente' y convertirlo a mayúsculas
    const cliente = document.getElementById('cliente').value.toUpperCase();
    document.getElementById('cliente').value = cliente;  // Asignar el valor en mayúsculas al campo

    const lat = document.getElementById('lat').value;
    const lng = document.getElementById('lng').value;
    const ubicacion = document.getElementById('ubicacion').value;

    if (!lat || !lng || !ubicacion || !cliente) {
        alert("Todos los campos son obligatorios.");
        return;  // Evita que el formulario se envíe si falta algún dato
    }

    const formData = new FormData(this);  // Crear objeto FormData con los datos del formulario

    fetch('../db/guardar_direccion.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);  // Mostrar mensaje de éxito
                window.location.href = data.redirect;  // Redirigir al usuario
            } else {
                alert(data.message);  // Mostrar mensaje de error
            }
        })
        .catch(error => {
            console.error('Error al guardar la dirección:', error);
            alert('Hubo un error al guardar la dirección.');
        });
});
