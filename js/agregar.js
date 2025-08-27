let map;
let markerCliente;
let markerRepartidor;
let directionsService;
let directionsRenderer;

let clienteCoords = { lat: 23.6345, lng: -102.5528 }; // Valor por defecto cliente
let repartidorCoords = null; // Se llenará con geolocalización

function initMap() {
    const ubicacionInput = document.getElementById('ubicacion');
    const direccionClienteInput = document.getElementById('location');
    const valorInicial = ubicacionInput.value.trim();

    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer();

    // Inicializar mapa centrado en ubicación del repartidor
    map = new google.maps.Map(document.getElementById('map'), {
        center: repartidorCoords || clienteCoords,
        zoom: 15
    });
    directionsRenderer.setMap(map);

    // Marcador repartidor (azul)
    if (repartidorCoords) {
        markerRepartidor = new google.maps.Marker({
            position: repartidorCoords,
            map: map,
            label: "Repartidor",
            icon: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png"
        });
    }

    // Si ya hay un valor inicial en el input, intentar ubicar cliente
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

            // Actualizar campos
            document.getElementById('ubicacion').value = `${coords.lat}, ${coords.lng}`;

            // Colocar marcador y calcular ruta
            colocarMarcadorClienteYCalcularRuta(coords, document.getElementById('location'));

        } else if (status === google.maps.GeocoderStatus.ZERO_RESULTS) {
            alert('No se pudo encontrar la dirección. Intenta de nuevo.');
        } else {
            alert('Error al geocodificar la dirección: ' + status);
        }
    });
}

function calcularRuta() {
    if (!clienteCoords || !clienteCoords.lat || !clienteCoords.lng) {
        console.warn("Coordenadas cliente inválidas.");
        return;
    }
    if (!repartidorCoords || !repartidorCoords.lat || !repartidorCoords.lng) {
        console.warn("Coordenadas repartidor inválidas.");
        return;
    }

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
        alert("Geolocalización no compatible.");
        repartidorCoords = null;
        initMap();
    }
}

document.addEventListener("DOMContentLoaded", function () {
    obtenerUbicacionRepartidor();

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

        // Si no son coordenadas, geocodificar dirección textual
        geocodeAddress(valor);
    });
});

document.getElementById('location_alterna').addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];

    if (selectedOption.value) {
        const lat = selectedOption.getAttribute('data-lat');
        const lng = selectedOption.getAttribute('data-lng');
        const direccion = selectedOption.getAttribute('data-direccion');

        document.getElementById('ubicacion').value = `${lat}, ${lng}`;
        document.getElementById('location').value = direccion;

        const coords = { lat: parseFloat(lat), lng: parseFloat(lng) };
        colocarMarcadorClienteYCalcularRuta(coords, document.getElementById('location'));
    }
});
