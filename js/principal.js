document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('#menuTabs .nav-link');
    const contents = document.querySelectorAll('.tabContent');
    const iframeCarga = document.getElementById('iframeCarga');

    const showLoader = () => {
        const loader = document.createElement('div');
        loader.id = 'global-loader';
        loader.innerHTML = '<div class="text-center">Cargando...</div>';
        document.body.appendChild(loader);
    };

    const hideLoader = () => {
        const loader = document.getElementById('global-loader');
        if (loader) loader.remove();
    };

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const url = this.getAttribute('data-url');
            const targetContent = document.getElementById(targetId);

            // Activar la pestaña
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Ocultar todo
            contents.forEach(c => c.classList.add('d-none'));

            // Mostrar el contenedor correspondiente
            targetContent.classList.remove('d-none');
            targetContent.innerHTML = '';

            showLoader();

            // Si es el iframe (carga.php)
            if (targetId === 'actividadContent' && iframeCarga) {
                iframeCarga.src = url;
                iframeCarga.onload = () => hideLoader();
            } else {
                // Para el resto de secciones
                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error("Error de red: " + response.status);
                        return response.text();
                    })
                    .then(html => {
                        targetContent.innerHTML = html;

                        // Ejecutar scripts embebidos
                        const scripts = targetContent.querySelectorAll("script");
                        scripts.forEach(oldScript => {
                            const newScript = document.createElement("script");
                            if (oldScript.src) {
                                newScript.src = oldScript.src;
                            } else {
                                newScript.textContent = oldScript.textContent;
                            }
                            document.body.appendChild(newScript);
                        });

                        setTimeout(() => {
                            // Inicializar Google Maps si corresponde
                            if (targetId === "mapContent") {
                                if (typeof google !== "undefined" && google.maps && typeof initMap === "function") {
                                    initMap();
                                } else {
                                    const existingScript = document.querySelector("#gmaps-script");
                                    if (!existingScript) {
                                        const script = document.createElement("script");
                                        script.id = "gmaps-script";
                                        script.src = "https://maps.googleapis.com/maps/api/js?key=API_KEY&callback=initMap";
                                        script.async = true;
                                        script.defer = true;
                                        document.head.appendChild(script);
                                    }
                                }
                            }

                            if (typeof inicializarCarga === 'function') {
                                inicializarCarga();
                            }

                        }, 300);

                        hideLoader();
                    })
                    .catch(error => {
                        targetContent.innerHTML = `<div class="text-danger">Error al cargar el contenido: ${error.message}</div>`;
                        hideLoader();
                    });
            }
        });
    });
});

