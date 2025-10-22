<?php
session_name('Flotillas');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ./inicioSesion.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="../img/icono2.png" type="image/x-icon">
    <title>Menú</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

    <style>
        .nav-link.active {
            background-color: #ccc !important;
            color: #000 !important;
        }

        .tabContent {
            display: none;
        }

        .tabContent:not(.d-none) {
            display: block;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            /* Ajuste para alineación */
            height: 100vh;
            /* Ocupa el 100% de la altura de la ventana */
        }

        iframe {
            width: 100%;
            height: 800px;
            border: none;
            overflow-x: hidden;
            /* Elimina el scroll horizontal */
            overflow-y: auto;
            /* Permite el scroll vertical si el contenido es más alto */
        }

        .nav-tabs-wrapper::-webkit-scrollbar {
            height: 6px;
        }

        .nav-tabs-wrapper::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .nav-tabs-wrapper::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>

</head>

<body>

    <div id="entradaAnimacion"
        style="position:fixed; top:0; left:0; width:100%; height:100%; background:white; display:flex; align-items:center; justify-content:center; z-index:9999;">
        <lottie-player src="https://assets1.lottiefiles.com/packages/lf20_49rdyysj.json" background="transparent"
            speed="1" style="width: 350px; height: 350px;" autoplay>
        </lottie-player>
    </div>

    <nav class="navbar navbar-dark bg-dark fixed-top" style="background-color: #081856!important;">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="../php/principal.php">
                <img src="../img/loguito2.png" alt="" height="45">
            </a>
            <div class="text-white d-none d-md-block">
                Bienvenido de nuevo <?php echo $_SESSION['usuario']; ?>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
                <i class="fas fa-user-circle fa-2x"></i>
            </button>

            <div class="offcanvas offcanvas-end bg-dark text-white" id="offcanvasNavbar"
                style="background-color: #081856!important;">
                <div class="offcanvas-header">
                    <span class="text-white font-size-lg"> <?php echo $_SESSION['usuario']; ?> </span>
                    <button type="button" class="btn-close btn-lg" style="background-color: white"
                        data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav mr-auto">
                        <!-- Opción para visualizar los videos del usuario -->
                        <?php if ($_SESSION['rol'] === 'TI'): ?>
                            <li class="nav-item">
                                <a class="btn btn-outline-info" href="../menu/usuarios.php">Usuarios</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <br><br>
                    <ul class="navbar-nav mr-auto">
                        <!-- Opción para cerrar sesión dentro del menú lateral -->
                        <li class="nav-item">
                            <a class="nav-link text-white" href="cerrarSesion.php">Cerrar Sesión</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content" style="padding-top: 65px;">
        <div class="container">

            <!-- Contenedor con scroll horizontal -->
            <div class="card p-3 mb-1">
                <div class="nav-tabs-wrapper" style="overflow-x: auto; white-space: nowrap;">
                    <ul class="nav nav-tabs d-flex flex-nowrap" id="menuTabs" style="min-width: max-content;">
                        <li class="nav-item">
                            <a class="nav-link text-center" data-target="actividadContent"
                                data-url="/flotillas/menu/carga.php">
                                <i class="fa fa-bus"></i><br> Carga
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-center" data-target="dataContent"
                                data-url="../menu/data.php">
                                <i class="bi bi-list-ul"></i><br> Data
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-center" data-target="mapContent" data-url="../menu/map.php">
                                <i class="bi bi-geo-alt"></i><br> Map
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-center" data-target="gasContent" data-url="/flotillas/menu/gas.php">
                                <i class="bi bi-fuel-pump"></i><br> Gas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-center" data-target="unidadContent" data-url="../menu/unidad.php">
                                <i class="bi bi-car-front-fill"></i><br> Unidades
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-center" data-target="graficaContent"
                                data-url="/flotillas/menu/graficas.php">
                                <i class="bi bi-bar-chart-line"></i><br> Gráfica
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-center" data-target="direccionContent"
                                data-url="/flotillas/menu/direccion.php">
                                <i class="bi bi-compass"></i><br> Dirección
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-center" data-target="pedidosContent"
                                data-url="/flotillas/menu/pedidos_cerrados.php">
                                <i class="bi bi-check-square"></i><br> Viajes Cerrados
                            </a>
                        </li>
                        <?php if ($_SESSION['rol'] === 'TI'): ?>
                            <li class="nav-item">
                                <a class="nav-link text-center" data-target="pedidosEliminadosContent"
                                    data-url="/flotillas/menu/pedidos_eliminados.php">
                                    <i class="bi bi-x-circle"></i><br> Pedidos Eliminados
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Contenido dinámico -->
            <div class="card p-3">
                <div id="actividadContent" class="tabContent">
                    <iframe id="iframeCarga" src=""></iframe>
                </div>
                <div id="dataContent" class="tabContent d-none"></div>
                <div id="graficaContent" class="tabContent d-none"></div>
                <div id="mapContent" class="tabContent d-none"></div>
                <div id="gasContent" class="tabContent d-none"></div>
                <div id="unidadContent" class="tabContent d-none"></div>
                <div id="direccionContent" class="tabContent d-none"></div>
                <div id="pedidosContent" class="tabContent d-none"></div>
                <div id="pedidosEliminadosContent" class="tabContent d-none"></div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const tabs = document.querySelectorAll("#menuTabs .nav-link");
            const contents = document.querySelectorAll(".tabContent");
            const iframeCarga = document.getElementById("iframeCarga");

            const showLoader = (element) => {
                element.innerHTML = '<div class="text-center p-3">Cargando...</div>';
            };

            const hideAllContents = () => {
                contents.forEach(content => content.classList.add("d-none"));
            };

            tabs.forEach(tab => {
                tab.addEventListener("click", function () {
                    // Cambiar pestaña activa
                    tabs.forEach(t => t.classList.remove("active"));
                    this.classList.add("active");

                    const targetId = this.dataset.target;
                    const url = this.dataset.url;
                    const targetContent = document.getElementById(targetId);

                    hideAllContents();
                    targetContent.classList.remove("d-none");

                    if (targetId === "actividadContent") {
                        if (iframeCarga.src !== location.origin + url) {
                            iframeCarga.src = url;
                        }
                    } else {
                        showLoader(targetContent);
                        fetch(url)
                            .then(res => {
                                if (!res.ok) throw new Error("Error al cargar");
                                return res.text();
                            })
                            .then(html => {
                                targetContent.innerHTML = html;

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
                                    if (targetId === "mapContent") {
                                        if (typeof google !== "undefined" && google.maps && typeof initMap === "function") {
                                            initMap();
                                        } else {
                                            const existingScript = document.querySelector("#gmaps-script");
                                            if (!existingScript) {
                                                const script = document.createElement("script");
                                                script.id = "gmaps-script";
                                                script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyDiZcKXugtBRzqpDQ0ax7Zzgt36rXEj1Lw&callback=initMap";
                                                script.async = true;
                                                script.defer = true;
                                                document.head.appendChild(script);
                                            }
                                        }
                                    }
                                }, 300);
                            })
                            .catch(() => {
                                targetContent.innerHTML = '<div class="text-danger">Error al cargar la sección.</div>';
                            });
                    }
                });
            });

            // ✅ Activar la pestaña marcada como 'active' en el HTML
            const defaultTab = Array.from(tabs).find(tab => tab.classList.contains("active")) || tabs[0];
            defaultTab.click();
        });
    </script>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const abrir = urlParams.get('abrir');
        if (abrir === 'data') {
            const tabs = document.querySelectorAll('#menuTabs .nav-link');
            tabs.forEach(tab => {
                if (tab.dataset.url === '../menu/data.php') {
                    tab.click();
                }
            });
        }
    </script>

    <script>
        window.addEventListener("DOMContentLoaded", () => {
            const hasVisited = sessionStorage.getItem('hasVisited');

            const anim = document.getElementById("entradaAnimacion");

            if (!hasVisited) {
                // Primera visita
                sessionStorage.setItem('hasVisited', 'true');
                setTimeout(() => {
                    if (anim) anim.style.display = "none";
                }, 2000); // Puedes ajustar la duración
            } else {
                // Ya ha visitado antes en esta sesión
                if (anim) anim.style.display = "none";
            }
        });
    </script>

    <!-- <script src="/flotillas/js/gas.js"></script> -->


</body>

<?php include '../css/footer.php'; ?>

</html>