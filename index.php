<?php
session_start();
if (!isset($_SESSION['idUsuario_clinicloud'])) {
    header('Location: login.php');
    exit();
}
$idUsuario = $_SESSION['idUsuario_clinicloud'];
$nombreUs = $_SESSION['nombreUs_clinicloud'];
$primerApUs = $_SESSION['primerApUs_clinicloud'];
$segundoApUs = $_SESSION['segundoApUs_clinicloud'];
$fechaNacUs = $_SESSION['fechaNacUs_clinicloud'];
$celularUs = $_SESSION['celularUs_clinicloud'];
$ciUs = $_SESSION['ciUs_clinicloud'];
$emailUs = $_SESSION['emailUs_clinicloud'];
$usuarioUs = $_SESSION['usuarioUs_clinicloud'];
$perfilUs = $_SESSION['perfilUs_clinicloud'];
//$agenciaUS = $_SESSION['agenciaUS_clinicloud'];


/*
Accesos disponibles

-Ventanilla virtual
-gestion de usuarios

*/

$ventanillaVirtual = false;
$creditos = false;
$configuracion = false;

switch ($perfilUs) {
    case 'ADMINISTRADOR':
        $ventanillaVirtual = true;
        $creditos = true;
        $configuracion = true;
        break;
    case 'MEDICO':
        $consultorios = true;
        $aranceles = true;
        break;
    case 'CAJERO':
        $ventanillaVirtual = true;
        break;
}

?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light" class="root-text-sm">
    <head>
        <meta charset="utf-8">
        <title>CliniCloud V2</title>
        <meta name="description" content="Page Description">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, maximum-scale=5">
        <!-- Standard favicon for browsers -->
        <link rel="icon" href="assets/assets/img/favicon-32x32.png" type="image/png" sizes="32x32">
        <link rel="icon" href="assets/assets/img/favicon-16x16.png" type="image/png" sizes="16x16">
        <!-- Apple Touch Icon (iOS) -->
        <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png" sizes="180x180">
        <!-- Android/Chrome (Progressive Web App) -->
        <link rel="icon" href="assets/img/favicon-192x192.png" type="image/png" sizes="192x192">
        <!-- Call App Mode on ios devices -->
        <meta name="mobile-web-app-capable" content="yes">
        <!-- Remove Tap Highlight on Windows Phone IE -->
        <meta name="msapplication-tap-highlight" content="no">
        <!-- Vendor css -->
        <link rel="stylesheet" media="screen, print" href="assets/css/bootstrap.css">
        <link rel="stylesheet" media="screen, print" href="assets/css/waves.css">
        <!-- Base css -->
        <link rel="stylesheet" media="screen, print" href="assets/css/smartapp.css">
        <link rel="stylesheet" media="screen, print" href="assets/css/sa-icons.css">
        <link rel="stylesheet" media="screen, print" href="assets/css/fontawesome.css">
        <link id="dynamic-style" rel="stylesheet" href="assets/css/fa-solid.css">
        <!-- Theme Style -->
        <link id="theme-style" rel="stylesheet" media="screen, print">
        <!-- Page specific CSS -->
        <link rel="stylesheet" media="screen, print" href="assets/css/night-owl.min.css">
        <link rel="stylesheet" media="screen, print" href="assets/css/toastr.min.css">
        <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

        <link href="assets/css/dropify.css" rel="stylesheet">


        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.css">

        <link rel="stylesheet" media="screen, print" href="assets/css/apexcharts.css">

        <style>
            .ace_log-error    { color: #ff5555; font-weight: bold; }  /* rojo */
            .ace_log-success  { color: #50fa7b; font-weight: bold; }  /* verde */
            .ace_log-info     { color: #8be9fd; font-weight: bold; }  /* celeste */

            .btn-excel{
                background-color: #107C41;
                color: #ffffff;
            }
            .btn-excel:hover{
                background-color: #107C41;
                color: #ffffff;
                border-color: #107C41;
            }

            /* Fix para scroll en menu mobile */
            @media (max-width: 991.98px) {
                /* Asegurar que el sidebar tenga altura completa y scroll */
                .app-sidebar {
                    position: fixed;
                    top: 0;
                    left: 0;
                    bottom: 0;
                    width: 280px;
                    z-index: 1050;
                    transform: translateX(-100%);
                    transition: transform 0.3s ease;
                    display: flex;
                    flex-direction: column;
                    background: var(--bs-body-bg);
                }
                
                .app-mobile-menu-open .app-sidebar {
                    transform: translateX(0);
                }
                
                /* El nav debe poder hacer scroll */
                .app-sidebar .primary-nav {
                    flex: 1 1 auto;
                    overflow-y: auto !important;
                    overflow-x: hidden;
                    -webkit-overflow-scrolling: touch !important;
                    overscroll-behavior: contain;
                    height: auto !important;
                }
                
                /* Asegurar que el logo no se desplace */
                .app-sidebar .app-logo {
                    flex: 0 0 auto;
                }
                
                /* Asegurar que el footer no se desplace */
                .app-sidebar .nav-footer {
                    flex: 0 0 auto;
                }
            }
        </style>

    </head>
    <body class="">
        <script>
            //hint: place this right after 'body' tag for instantaneous loading
            'use strict';
            var htmlRoot = document.getElementsByTagName('HTML')[0],
                //save states
                savePanelStateEnabled = true,
                //mobile operator on
                mobileOperator = function()
                {
                    // Check user agent
                    const userAgent = navigator.userAgent.toLowerCase();
                    const isMobileUserAgent = /iphone|ipad|ipod|android|blackberry|mini|windows\sce|palm/i.test(userAgent);
                    // Check for touch support
                    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
                    // Check screen size (optional)
                    const isSmallScreen = window.innerWidth <= 992; // Adjust the breakpoint as needed
                    // Return true if any of the conditions are met
                    return isMobileUserAgent || isTouchDevice || isSmallScreen;
                },
                //filter
                filterClass = function(t, e)
                {
                    return String(t).split(/[^\w-]+/).filter(function(t)
                    {
                        return e.test(t)
                    }).join(' ')
                },
                //load
                loadSettings = function()
                {
                    var t = localStorage.getItem('layoutSettings') || '',
                        e = t ? JSON.parse(t) :
                        {};
                    // Load theme setting
                    var savedTheme = e.theme || 'light';
                    htmlRoot.setAttribute('data-bs-theme', savedTheme);
                    // Load theme style CSS file only if one was saved
                    var themeStyle = e.themeStyle || '';
                    if (themeStyle)
                    {
                        loadThemeStyle(themeStyle);
                    }
                    return Object.assign(
                    {
                        htmlRoot: '',
                        theme: savedTheme,
                        themeStyle: themeStyle
                    }, e)
                },
                //save
                saveSettings = function()
                {
                    layoutSettings.htmlRoot = filterClass(htmlRoot.className, /^(set)-/i);
                    layoutSettings.theme = htmlRoot.getAttribute('data-bs-theme') || 'light';
                    // Save theme style CSS path
                    var themeStyleElement = document.getElementById('theme-style');
                    if (themeStyleElement)
                    {
                        layoutSettings.themeStyle = themeStyleElement.getAttribute('href');
                    }
                    else
                    {
                        layoutSettings.themeStyle = '';
                    }
                    localStorage.setItem("layoutSettings", JSON.stringify(layoutSettings));
                    savingIndicator();
                },
                //reset
                resetSettings = function()
                {
                    localStorage.setItem("layoutSettings", "");
                    // reset data-bs-theme
                    htmlRoot.setAttribute('data-bs-theme', 'light');
                    // reset theme style element if it exists
                    document.getElementById('theme-style').setAttribute('href', '');
                    // refresh page
                    window.location.reload();
                },
                //load theme style
                loadThemeStyle = function(themeStyle)
                {
                    // Get existing theme style if it exists
                    var existingThemeStyle = document.getElementById('theme-style');
                    if (existingThemeStyle)
                    {
                        // Update existing theme style's href
                        existingThemeStyle.href = themeStyle || '';
                    }
                    else if (themeStyle)
                    {
                        // Create new theme style element if none exists and themeStyle is provided
                        var linkElement = document.createElement('link');
                        linkElement.id = 'theme-style';
                        linkElement.rel = 'stylesheet';
                        linkElement.href = themeStyle;
                        document.head.appendChild(linkElement);
                    }
                },
                //get page id
                getPageIdentifier = function()
                {
                    return window.location.pathname.split('/').pop() || 'index.html';
                },
                //save panel state
                savePanelState = function()
                {
                    if (!savePanelStateEnabled) return;
                    var state = [];
                    var columns = document.querySelectorAll('.main-content > .row > [class^="col-"]');
                    columns.forEach(function(column, columnIndex)
                    {
                        var panels = column.querySelectorAll('.panel');
                        panels.forEach(function(panel, position)
                        {
                            var panelHeader = panel.querySelector('.panel-hdr');
                            // Save panel classes excluding 'panel' and 'panel-fullscreen'
                            var panelClasses = panel.className.split(' ').filter(function(cls)
                            {
                                return cls !== 'panel' && cls !== 'panel-fullscreen';
                            }).join(' ');
                            // Save header classes excluding 'panel-hdr'
                            var headerClasses = panelHeader ? panelHeader.className.split(' ').filter(function(cls)
                            {
                                return cls !== 'panel-hdr';
                            }).join(' ') : '';
                            state.push(
                            {
                                id: panel.id,
                                column: columnIndex,
                                position: position, // Save position within column
                                classes: panelClasses,
                                headerClasses: headerClasses
                            });
                        });
                    });
                    var pageId = getPageIdentifier();
                    var allStates = JSON.parse(localStorage.getItem('allPanelStates') || '{}');
                    allStates[pageId] = state;
                    localStorage.setItem('allPanelStates', JSON.stringify(allStates));
                    savingIndicator();
                },
                loadPanelState = function()
                {
                    var pageId = getPageIdentifier();
                    var allStates = JSON.parse(localStorage.getItem('allPanelStates') || '{}');
                    var savedState = allStates[pageId];
                    if (!savedState) return;
                    // Use same selector as save function
                    var columns = Array.from(document.querySelectorAll('.main-content > .row > [class^="col-"]'));
                    // Store all existing panels in a map before removing them
                    var panelMap = {};
                    columns.forEach(function(column)
                    {
                        var existingPanels = Array.from(column.querySelectorAll('.panel'));
                        existingPanels.forEach(function(panel)
                        {
                            panelMap[panel.id] = panel;
                            panel.remove();
                        });
                    });
                    // Sort state by column and position
                    savedState.sort(function(a, b)
                    {
                        if (a.column === b.column)
                        {
                            return a.position - b.position;
                        }
                        return a.column - b.column;
                    });
                    // Reinsert panels in correct order
                    savedState.forEach(function(item)
                    {
                        var panel = panelMap[item.id];
                        if (panel && columns[item.column])
                        {
                            // Update panel classes
                            panel.className = 'panel ' + (item.classes || '');
                            // Update header classes
                            var panelHeader = panel.querySelector('.panel-hdr');
                            if (panelHeader && item.headerClasses)
                            {
                                panelHeader.className = 'panel-hdr ' + item.headerClasses;
                            }
                            // Append to correct column
                            columns[item.column].appendChild(panel);
                        }
                    });
                },
                //reset panel state
                resetPanelState = function()
                {
                    var pageId = getPageIdentifier();
                    var allStates = JSON.parse(localStorage.getItem('allPanelStates') || '{}');
                    delete allStates[pageId];
                    localStorage.setItem('allPanelStates', JSON.stringify(allStates));
                    //refresh page
                    window.location.reload();
                },
                savingIndicator = function()
                {
                    // Create or get the indicator element
                    let indicator = document.getElementById('saving-indicator');
                    if (!indicator)
                    {
                        indicator = document.createElement('div');
                        indicator.id = 'saving-indicator';
                        document.body.appendChild(indicator);
                    }
                    // Show saving animation
                    //indicator.textContent = '';
                    indicator.className = 'saving-indicator spinner-border show';
                    // After a brief delay, show success and hide
                    setTimeout(() =>
                    {
                        //indicator.textContent = '';
                        indicator.className = 'saving-indicator spinner-border show success';
                        setTimeout(() =>
                        {
                            indicator.className = 'saving-indicator spinner-border success';
                        }, 500);
                    }, 300);
                },
                //load page layout settings
                layoutSettings = loadSettings();
            layoutSettings.htmlRoot && (htmlRoot.className = layoutSettings.htmlRoot);
            //load panel settings is triggered just before <script> tag    
        </script>
        <div class="app-wrap">
            <header class="app-header">
                <!-- Collapse icon -->
                <div class="d-flex flex-grow-1 w-100 me-auto align-items-center">
                    <!-- App logo -->
                    <div class="app-logo flex-shrink-0" data-prefix="v2.3" data-action="playsound" data-soundpath="media/sound/" data-soundfile="sawhisp.mp3">
                        <img src="assets/img/CliniCloud_horizontal.png" alt="logo" style="max-width:150px; max-height:60px; height:auto; display:block; object-fit:contain;">
                        <!-- please check docs on how to update this logo different dimensions -->
                        <!-- <svg class="custom-logo">
                            <use href="assets/img/app-logo.svg#custom-logo"></use> 
                        </svg> -->
                        <!-- <div class="logo-backdrop">
                            <div class="blobs">
                                <svg viewbox="0 0 1200 1200">
                                    <g class="blob blob-1">
                                        <path />
                                    </g>
                                    <g class="blob blob-2">
                                        <path />
                                    </g>
                                    <g class="blob blob-3">
                                        <path />
                                    </g>
                                    <g class="blob blob-4">
                                        <path />
                                    </g>
                                    <g class="blob blob-1 alt">
                                        <path />
                                    </g>
                                    <g class="blob blob-2 alt">
                                        <path />
                                    </g>
                                    <g class="blob blob-3 alt">
                                        <path />
                                    </g>
                                    <g class="blob blob-4 alt">
                                        <path />
                                    </g>
                                </svg>
                            </div>
                        </div> -->
                    </div>
                    <!-- Mobile menu -->
                    <div class="mobile-menu-icon me-2 d-flex d-sm-flex d-md-flex d-lg-none flex-shrink-0" data-action="toggle-swap" data-toggleclass="app-mobile-menu-open" aria-label="Toggle Mobile Menu">
                        <svg class="sa-icon">
                            <use href="assets/img/sprite.svg#menu"></use>
                        </svg>
                    </div>
                    <!-- Collapse icon -->
                    <button type="button" class="collapse-icon me-3 d-none d-lg-inline-flex d-xl-inline-flex d-xxl-inline-flex" data-action="toggle" data-class="set-nav-minified" aria-label="Toggle Navigation Size">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 5 8">
                            <polygon fill="#878787" points="4.5,1 3.8,0.2 0,4 3.8,7.8 4.5,7 1.5,4" />
                        </svg>
                    </button>
                    
                </div>
                
                <!-- Theme modes -->
                <button type="button" class="btn btn-system" data-action="toggle-theme" aria-label="Toggle Dark Mode" aria-pressed="false">
                    <svg class="sa-icon sa-icon-2x">
                        <use href="assets/img/sprite.svg#circle"></use>
                    </svg>
                </button>
                <!-- Notifications -->
                <!-- <button type="button" class="btn btn-system dropdown-toggle no-arrow" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open Notifications">
                    <span class="badge badge-icon pos-top pos-end">5</span>
                    <svg class="sa-icon sa-icon-2x">
                        <use href="assets/img/sprite.svg#bell"></use>
                    </svg>
                </button> -->
                <!-- Notifications dropdown -->
                <div class="dropdown-menu dropdown-menu-animated dropdown-xl dropdown-menu-end p-0">
                    <div class="notification-header rounded-top mb-2">
                        <h4 class="m-0"> 0 Nuevas <small class="mb-0 opacity-80">Notificaciones del Usuario</small>
                        </h4>
                    </div>
                    <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                        <li class="nav-item d-none">
                            <a class="nav-link active" data-bs-toggle="tab" href="assets/#tab-default" role="tab" aria-selected="true">Hidden</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-4 fs-md fw-500" data-bs-toggle="tab" href="assets/#tab-messages" role="tab" aria-selected="false">Mensajes</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-4 fs-md fw-500" data-bs-toggle="tab" href="assets/#tab-feeds" role="tab" aria-selected="false">Noticias</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-4 fs-md fw-500" data-bs-toggle="tab" href="assets/#tab-events" role="tab" aria-selected="false">Eventos</a>
                        </li>
                    </ul>
                    
                    <div class="py-2 px-3 d-block rounded-bottom text-end border-light border-bottom-0 border-end-0 border-start-0">
                        <a href="assets/#" class="fs-xs fw-500 ms-auto">view all notifications</a>
                    </div>
                </div>
                <!-- Profile -->
                <button type="button" data-bs-toggle="dropdown" title="drlantern@gotbootstrap.com" class="btn-system bg-transparent d-flex flex-shrink-0 align-items-center justify-content-center" aria-label="Open Profile Dropdown">
                    <img src="assets/img/user-ico.png" class="profile-image profile-image-md rounded-circle" alt="Sunny A.">
                </button>
                <!-- Profile dropdown -->
                <div class="dropdown-menu dropdown-menu-animated">
                    <div class="notification-header rounded-top mb-2">
                        <div class="d-flex flex-row align-items-center mt-1 mb-1 color-white">
                            <span class="status status-success d-inline-block me-2">
                                <img src="assets/img/user-ico.png" class="profile-image rounded-circle" alt="Sunny A.">
                            </span>
                            <div class="info-card-text">
                                <div class="fs-lg text-truncate text-truncate-lg"><?php echo $usuarioUs ?></div>
                                <span class="text-truncate text-truncate-md opacity-80 fs-sm"><?php echo $emailUs ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown-divider m-0"></div>
                    <a href="assets/#" class="dropdown-item" data-action="toggle-swap" data-toggleclass="open" data-target="aside.js-drawer-settings">
                        <span data-i18n="drpdwn.settings">Mi Perfil</span>
                    </a>
                    <div class="dropdown-divider m-0"></div>
                    <a href="assets/#" class="dropdown-item" data-action="toggle-swap" data-toggleclass="open" data-target="aside.js-drawer-settings">
                        <span data-i18n="drpdwn.settings">Cambio de Contraseña</span>
                    </a>
                    
                    <div class="dropdown-divider m-0"></div>
                    <a class="dropdown-item py-3 fw-500 d-flex justify-content-between" href="login.php">
                        <span class="text-danger" data-i18n="drpdwn.page-logout">Cerrar Sesion</span>
                        <!-- <span class="d-block text-truncate text-truncate-sm">@sunnyahmed</span> -->
                    </a>
                </div>
            </header>
            <aside class="app-sidebar d-flex flex-column">
                <div class="app-logo flex-shrink-0" data-prefix="v1.1" data-action="playsound" data-soundpath="media/sound/" data-soundfile="sawhisp.mp3">
                    <img src="assets/img/api_new_logo_border.png" alt="logo" style="max-width:150px; max-height:60px; height:auto; display:block; object-fit:contain;">
                    <!-- please check docs on how to update this logo different dimensions -->
                    <!-- <svg class="custom-logo">
                        <use href="assets/img/app-logo.svg#custom-logo"></use>
                    </svg> -->
                    <div class="logo-backdrop">
                        <div class="blobs">
                            <svg viewbox="0 0 1200 1200">
                                <g class="blob blob-1">
                                    <path />
                                </g>
                                <g class="blob blob-2">
                                    <path />
                                </g>
                                <g class="blob blob-3">
                                    <path />
                                </g>
                                <g class="blob blob-4">
                                    <path />
                                </g>
                                <g class="blob blob-1 alt">
                                    <path />
                                </g>
                                <g class="blob blob-2 alt">
                                    <path />
                                </g>
                                <g class="blob blob-3 alt">
                                    <path />
                                </g>
                                <g class="blob blob-4 alt">
                                    <path />
                                </g>
                            </svg>
                        </div>
                    </div>
                </div>
                <nav id="js-primary-nav" class="primary-nav flex-grow-1 custom-scroll">
                    <ul id="js-nav-menu" class="nav-menu">
                        <?php
                            $perfilUs = $_SESSION['perfilUs_clinicloud'];
                            echo "<li class='nav-title'><span>Inicio</span></li>";
                            echo "<li class='nav-item active' id='modulos/dashboard/dashboard.php'>";
                                echo "<a href='#'>";
                                    echo "<i class='fas fa-home'></i>";
                                    echo "<span class='nav-link-text'>Dashboard</span>";
                                echo "</a>";
                            echo "</li>";

                            echo "<li class='nav-title'><span>Recepcion</span></li>";
                            echo "<li class='nav-item' id='modulos/reportes/reportes.php'>";
                                echo "<a href='#'>";
                                    echo "<i class='fas fa-cash-register'></i>";
                                    echo "<span class='nav-link-text'>Caja</span>";
                                echo "</a>";
                            echo "</li>";
                            echo "<li class='nav-item' id='modulos/pacientes/pacientes.php'>";
                                echo "<a href='#'>";
                                    echo "<i class='fas fa-user-injured'></i>";
                                    echo "<span class='nav-link-text'>Pacientes</span>";
                                echo "</a>";
                            echo "</li>";



                            echo "<li class='nav-title'><span>Atencion Medica</span></li>";
                            // echo "<li class='nav-item' id='modulos/atencion_medica/medicina_general.php'>";
                            //     echo "<a href='#'>";
                            //         echo "<i class='fas fa-stethoscope'></i>";
                            //         echo "<span class='nav-link-text'>Medicina General</span>";
                            //     echo "</a>";
                            // echo "</li>";
                            // echo "<li class='nav-item' id='modulos/atencion_medica/pediatria.php'>";
                            //     echo "<a href='#'>";
                            //         echo "<i class='fas fa-baby'></i>";
                            //         echo "<span class='nav-link-text'>Pediatría</span>";
                            //     echo "</a>";
                            // echo "</li>";
                            // echo "<li class='nav-item' id='modulos/atencion_medica/traumatologia.php'>";
                            //     echo "<a href='#'>";
                            //         echo "<i class='fas fa-bone'></i>";
                            //         echo "<span class='nav-link-text'>Traumatología</span>";
                            //     echo "</a>";
                            // echo "</li>";
                            // echo "<li class='nav-item' id='modulos/atencion_medica/ginecologia.php'>";
                            //     echo "<a href='#'>";
                            //         echo "<i class='fas fa-female'></i>";
                            //         echo "<span class='nav-link-text'>Ginecología</span>";
                            //     echo "</a>";
                            // echo "</li>";
                            // echo "<li class='nav-item' id='modulos/atencion_medica/cardiologia.php'>";
                            //     echo "<a href='#'>";
                            //         echo "<i class='fas fa-heartbeat'></i>";
                            //         echo "<span class='nav-link-text'>Cardiología</span>";
                            //     echo "</a>";
                            // echo "</li>";
                            // echo "<li class='nav-item' id='modulos/atencion_medica/dermatologia.php'>";
                            //     echo "<a href='#'>";
                            //         echo "<i class='fas fa-allergies'></i>";
                            //         echo "<span class='nav-link-text'>Dermatología</span>";
                            //     echo "</a>";
                            // echo "</li>";
                            echo "<li class='nav-item' id='modulos/atencion_medica/odontologia.php'>";
                                echo "<a href='#'>";
                                    echo "<i class='fas fa-tooth'></i>";
                                    echo "<span class='nav-link-text'>Odontología</span>";
                                echo "</a>";
                            echo "</li>";
                            // echo "<li class='nav-item' id='modulos/atencion_medica/rayosx.php'>";
                            //     echo "<a href='#'>";
                            //         echo "<i class='fas fa-x-ray'></i>";
                            //         echo "<span class='nav-link-text'>Rayos X</span>";
                            //     echo "</a>";
                            // echo "</li>";
                            
                            
                            if($perfilUs == "ADMINISTRADOR" || $perfilUs == "CONTADOR"){
                                
                                echo "<li class='nav-title'><span>Reportes</span></li>";
                                echo "<li class='nav-item' id='modulos/reportes/reportes.php'>";
                                    echo "<a href='#'>";
                                        echo "<i class='fas fa-chart-bar'></i>";
                                        echo "<span class='nav-link-text'>Reporte Estadistico</span>";
                                    echo "</a>";
                                echo "</li>";
                                echo "<li class='nav-item' id='modulos/reportes/reportes.php'>";
                                    echo "<a href='#'>";
                                        echo "<i class='fas fa-chart-pie'></i>";
                                        echo "<span class='nav-link-text'>Reporte Economico</span>";
                                    echo "</a>";
                                echo "</li>";

                            }
                            
                            if($perfilUs == "ADMINISTRADOR"){
                                echo "<li class='nav-title'><span>Configuración</span></li>";
                                echo "<li class='nav-item' id='modulos/consultorios/consultorios.php'>";
                                    echo "<a href='#'>";
                                        echo "<i class='fas fa-clinic-medical'></i>";
                                        echo "<span class='nav-link-text'>Consultorios</span>";
                                    echo "</a>";
                                echo "</li>";
                                echo "<li class='nav-item' id='modulos/aranceles/aranceles.php'>";
                                    echo "<a href='#'>";
                                        echo "<i class='fas fa-tags'></i>";
                                        echo "<span class='nav-link-text'>Aranceles</span>";
                                    echo "</a>";
                                echo "</li>";
                                echo "<li class='nav-item' id='modulos/usuarios/usuarios.php'>";
                                    echo "<a href='#'>";
                                        echo "<i class='fas fa-users-cog'></i>";
                                        echo "<span class='nav-link-text'>Usuarios</span>";
                                    echo "</a>";
                                echo "</li>";
                                echo "<li class='nav-title'><span>Sistema</span></li>";
                                echo "<li class='nav-item' id='modulos/parametros/parametros.php'>";
                                    echo "<a href='#'>";
                                        echo "<i class='fas fa-cogs'></i>";
                                        echo "<span class='nav-link-text'>Parámetros</span>";
                                    echo "</a>";
                                echo "</li>";
                                echo "<li class='nav-item' id='modulos/system_logs/system_logs.php'>";
                                    echo "<a href='#'>";
                                        echo "<i class='fas fa-file-alt'></i>";
                                        echo "<span class='nav-link-text'>Logs de Sistema</span>";
                                    echo "</a>";
                                echo "</li>";
                            }
                        ?>
                    </ul>
                        
                </nav>
                <div class="nav-footer">
                    <svg class="sa-icon sa-thin sa-icon-success">
                        <use href="assets/img/sprite.svg#wifi"></use>
                    </svg>
                </div>
            </aside>
            <main class="app-body">
                <div class="app-content">
                    <div class="content-wrapper" id="main-container">
                        <!-- <nav class="app-breadcrumb" aria-label="breadcrumb">
                            <ol class="breadcrumb ms-0">
                                <li class="breadcrumb-item">Design</li>
                                <li class="breadcrumb-item">Documentation</li>
                                <li class="breadcrumb-item active" aria-current="page">Core Plugins</li>
                            </ol>
                        </nav>
                        <div class="main-content">
                            <h1 class="subheader-title">Core Plugins</h1>
                            <div class="row">
                                <div class="order-2 order-xl-1 col-lg-12 col-xl-9">
                                </div>
                                <div class="order-1 order-xl-2 col-lg-12 col-xl-3 position-relative">
                                    <h3>Contents</h3>
                                    <ul>
                                    </ul>
                                </div>
                            </div>
                        </div> -->
                        
                    </div>
                </div>
                <footer class="app-footer">
                    <div class="app-footer-content flex-grow-1"> CliniCloud V2 &copy; 2025. GhostWise Software - Ing Adrian Gomez <a href="assets/#top" class="ms-auto hidden-mobile" aria-label="Back to top">
                            <svg class="sa-icon sa-thick sa-icon-primary">
                                <use href="assets/img/sprite.svg#arrow-up"></use>
                            </svg>
                        </a>
                    </div>
                </footer>
            </main>
            <!--we use js-* extension to indicate a hook for a script reference-->
            
            <div class="backdrop" data-action="toggle-swap" data-toggleclass="open" data-target="aside.js-app-drawer"></div>
            
            <div class="backdrop" data-action="toggle-swap" data-toggleclass="open" data-target="aside.js-drawer-settings"></div>
        </div>
        <div class="backdrop" data-action="toggle-swap" data-toggleclass="app-mobile-menu-open"></div>



        <!-- Modal Small -->
        <div class="modal fade" id="modal-sm" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content" id="modal-sm-content">
                </div>
            </div>
        </div>
        <!-- Modal Default -->
        <div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content" id="modal-content">
                </div>
            </div>
        </div>
        <!-- Modal Large -->
        <div class="modal fade" id="modal-lg" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content" id="modal-lg-content">
                </div>
            </div>
        </div>
        <!-- Modal XL -->
        <div class="modal fade" id="modal-xl" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content" id="modal-xl-content">
                </div>
            </div>
        </div>



        <!-- Running script immediately (before DOMContentLoaded) -->
        <script>
            loadPanelState();
        </script>
        <!-- Core scripts -->
        <script src="assets/scripts/smartApp.js"></script>
        <script src="assets/scripts/smartNavigation.js"></script>
        
        <script src="assets/scripts/thirdparty/bootstrap/bootstrap.bundle.js"></script>
        <!-- Dependable scripts -->
        <script src="assets/scripts/thirdparty/sortable/sortable.js"></script>
        <!-- Optional scripts -->
        <script src="assets/scripts/smartSlimscroll.js"></script>
        <script src="assets/scripts/thirdparty/wavejs/waves.js"></script>
        <!-- Page Specific scripts -->
        <script src="assets/scripts/highlight.min.js"></script>
        <script src="assets/scripts/go.min.js"></script>
        <script src="assets/scripts/jquery-3.7.1.min.js"></script>
        <script src="assets/loader.js"></script>
        <script src="assets/verifica_sesion.js"></script>
        <script src="assets/scripts/toastr.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.all.min.js"></script>
        <!-- Page Specific modules -->
        <!-- Run gobal scripts: after all other scripts are loaded -->

        <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.43.2/ace.js"></script>

        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>

        <!-- DataTables JS -->
        <script src="https://cdn.datatables.net/2.3.4/js/dataTables.js"></script>
        <script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap5.js"></script>

        <script src="assets/scripts/dropify.js"></script>



        <script>
            /* Initialize the navigation : smartNavigation.js */
            let nav;
            const navElement = document.querySelector('#js-primary-nav');
            if (navElement)
            {
                nav = new Navigation(navElement,
                {
                    accordion: true,
                    slideUpSpeed: 350,
                    slideDownSpeed: 470,
                    closedSign: '<i class="sa sa-chevron-down"></i>',
                    openedSign: '<i class="sa sa-chevron-up"></i>',
                    initClass: 'js-nav-built',
                    debug: false,
                    instanceId: `nav-${Date.now()}`,
                    maxDepth: 5,
                    sanitize: true,
                    animationTiming: 'easeOutExpo',
                    debounceTime: 0,
                    onError: error => console.error('Navigation error:', error)
                });
            }
            /* Waves Effect : waves.js */
            if (window.Waves)
            {
                Waves.attach('.btn:not(.js-waves-off):not(.btn-switch):not(.btn-panel):not(.btn-system):not([data-action="playsound"]), .js-waves-on', ['waves-themed']);
                Waves.init();
            }
            
            /* Initialize tooltips: bootstrap.bundle.js */
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl)
            {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
            /* Initialize popovers: bootstrap.bundle.js */
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
            var popoverList = popoverTriggerList.map(function(popoverTriggerEl)
            {
                return new bootstrap.Popover(popoverTriggerEl)
            })
            /* Set default dropdown behavior: bootstrap.bundle.js */
            bootstrap.Dropdown.Default.autoClose = 'outside';
            /* Inject additional scripts dynamically */
            document.addEventListener('DOMContentLoaded', function()
            {
                hljs.highlightAll();
            });
        </script>

        <script>
            //document ready function
            $(document).ready(function()
            {
                $("#main-container").html(loader);
                $("#main-container").load("modulos/dashboard/dashboard.php");

                $(".nav-item").on("click", function(){
                    $("#main-container").html(loader);
                    $(".nav-item").removeClass("active");
                    $(this).addClass("active");
                    //console.log($(this).attr("id"));
                    $("#main-container").load($(this).attr("id"));
                });

                toastr.options = {
                    "closeButton": false,
                    "debug": false,
                    "newestOnTop": false,
                    "progressBar": false,
                    "positionClass": "toast-bottom-right",
                    "preventDuplicates": false,
                    "onclick": null,
                    "showDuration": "300",
                    "hideDuration": "1000",
                    "timeOut": "5000",
                    "extendedTimeOut": "1000",
                    "showEasing": "swing",
                    "hideEasing": "linear",
                    "showMethod": "fadeIn",
                    "hideMethod": "fadeOut"
                }

            });
        </script>
    </body>
</html>