<?php
session_start();
session_destroy();
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CliniCloud - Sistema Clínico</title>
        <link rel="stylesheet" media="screen, print" href="assets/css/bootstrap.css">
        <link href="assets/css/smartapp.css" rel="stylesheet">
        <style>
            :root {
                --login-gradient-start: #2a7dbf;
                --login-gradient-end: #5085a6;
                --login-shadow: 0 20px 60px rgba(42, 125, 191, 0.15);
            }

            body {
                min-height: 100vh;
                background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
                background-attachment: fixed;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }

            .login-wrapper {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem 1rem;
                position: relative;
                overflow: hidden;
            }

            .login-wrapper::before {
                content: '';
                position: absolute;
                width: 200%;
                height: 200%;
                background: radial-gradient(circle, rgba(42, 125, 191, 0.03) 1px, transparent 1px);
                background-size: 50px 50px;
                animation: moveBackground 20s linear infinite;
                opacity: 1;
            }

            @keyframes moveBackground {
                0% { transform: translate(0, 0); }
                100% { transform: translate(50px, 50px); }
            }

            .login-card {
                background: #ffffff;
                border-radius: 1.5rem;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.05);
                border: 1px solid rgba(0, 0, 0, 0.08);
                padding: 3rem;
                width: 100%;
                max-width: 420px;
                position: relative;
                z-index: 1;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }

            .login-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.08);
            }

            .login-header {
                text-align: center;
                margin-bottom: 2.5rem;
            }

            .login-logo {
                max-width: 280px;
                height: auto;
                margin-bottom: 1.5rem;
                filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
            }

            .login-title {
                color: var(--bs-primary);
                font-size: 1.75rem;
                font-weight: 600;
                margin-bottom: 0.5rem;
                letter-spacing: -0.5px;
            }

            .login-subtitle {
                color: var(--bs-secondary);
                font-size: 0.95rem;
                font-weight: 400;
            }

            .form-label {
                color: var(--bs-dark);
                font-weight: 500;
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
            }

            .form-control {
                border: 2px solid var(--bs-gray-300);
                border-radius: 0.75rem;
                padding: 0.75rem 1rem;
                font-size: 0.95rem;
                transition: all 0.3s ease;
                background-color: var(--bs-white);
            }

            .form-control:focus {
                border-color: var(--bs-primary);
                box-shadow: 0 0 0 0.2rem rgba(42, 125, 191, 0.15);
                background-color: var(--bs-white);
            }

            .input-group {
                position: relative;
            }

            .btn-login {
                background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-teal) 100%);
                border: none;
                border-radius: 0.75rem;
                padding: 0.875rem 1.5rem;
                font-size: 1rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(42, 125, 191, 0.3);
            }

            .btn-login:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(42, 125, 191, 0.4);
                background: linear-gradient(135deg, var(--bs-teal) 0%, var(--bs-primary) 100%);
            }

            .btn-login:active {
                transform: translateY(0);
            }

            .btn-login:disabled {
                opacity: 0.7;
                cursor: not-allowed;
                transform: none;
            }

            .alert {
                border-radius: 0.75rem;
                border: none;
                font-size: 0.9rem;
            }

            .spinner-border {
                width: 1.5rem;
                height: 1.5rem;
                border-width: 0.2em;
            }

            .navbar-brand {
                padding: 1rem 0;
            }

            .navbar-brand img {
                height: 120px;
                width: auto;
            }

            /* Efecto decorativo */
            .decorative-circle {
                position: absolute;
                border-radius: 50%;
                background: linear-gradient(135deg, rgba(42, 125, 191, 0.08) 0%, rgba(80, 133, 166, 0.05) 100%);
                z-index: 0;
            }

            .circle-1 {
                width: 400px;
                height: 400px;
                top: -200px;
                right: -200px;
            }

            .circle-2 {
                width: 300px;
                height: 300px;
                bottom: -150px;
                left: -150px;
            }

            /* Responsive */
            @media (max-width: 576px) {
                .login-card {
                    padding: 2rem 1.5rem;
                    border-radius: 1.25rem;
                }

                .login-title {
                    font-size: 1.5rem;
                }

                .login-logo {
                    max-width: 220px;
                }
            }
        </style>
    </head>
    <body>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light position-absolute w-100 py-3" style="z-index: 1000; top: 0; background: transparent;">
            <div class="container">
                <a class="" href="#">
                    <img src="assets/img/CliniCloud_horizontal.png" alt="CliniCloud Logo" class="login-logo">
                </a>
            </div>
        </nav>

        <!-- Login Section -->
        <section class="login-wrapper">
            <div class="decorative-circle circle-1"></div>
            <div class="decorative-circle circle-2"></div>
            
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                        <div class="login-card">
                            <div class="login-header">
                                <h1 class="login-title">Bienvenido</h1>
                                <p class="login-subtitle">Ingrese sus credenciales para acceder al sistema</p>
                            </div>

                            <form id="loginForm">
                                <div class="mb-4">
                                    <label for="usuario" class="form-label">Usuario</label>
                                    <input 
                                        type="text" 
                                        class="form-control form-control-lg" 
                                        id="usuario" 
                                        placeholder="Ingrese su usuario"
                                        required
                                        autocomplete="username"
                                    >
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input 
                                        type="password" 
                                        class="form-control form-control-lg" 
                                        id="password" 
                                        placeholder="Ingrese su contraseña"
                                        required
                                        autocomplete="current-password"
                                    >
                                </div>

                                <div class="d-grid mb-3">
                                    <button type="button" class="btn btn-primary btn-lg btn-login text-white" id="btnIniciarSesion">
                                        Iniciar Sesión
                                    </button>
                                </div>

                                <div id="mensaje" class="text-center"></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Scripts -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="assets/scripts/jquery-3.7.1.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#usuario').focus();

                // Evento click en el botón de iniciar sesión
                $('#btnIniciarSesion').click(function() {
                    validarYEnviar();
                });

                // Enter en input de usuario pasa el foco a password
                $('#usuario').on('keyup', function(e) {
                    if (e.keyCode === 13) {
                        $('#password').focus();
                    }
                });

                // Enter en input de password intenta iniciar sesión
                $('#password').on('keyup', function(e) {
                    if (e.keyCode === 13) {
                        validarYEnviar();
                    }
                });

                // Función de validación y envío de datos
                function validarYEnviar() {
                    const usuario = $('#usuario').val().trim();
                    const password = $('#password').val().trim();

                    if (usuario === '' || password === '') {
                        $('#mensaje').html('<div class="alert alert-danger mb-0" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>Por favor ingrese usuario y contraseña</div>');
                        return;
                    }

                    iniciarSesion(usuario, password);
                }

                // Función para enviar la solicitud vía fetch
                function iniciarSesion(usuario, password) {
                    const $btn = $('#btnIniciarSesion');
                    const $mensaje = $('#mensaje');

                    $btn.attr('disabled', true);
                    $btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Verificando...');
                    $mensaje.html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>');

                    fetch('registro.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            usuario: usuario,
                            password: password
                        })
                    })
                    .then(response => response.text())
                    .then(data => {
                        $btn.attr('disabled', false);
                        $btn.html('Iniciar Sesión');
                        
                        if (data === 'success') {
                            $mensaje.html('<div class="alert alert-success mb-0" role="alert"><i class="bi bi-check-circle-fill me-2"></i>Acceso concedido. Redirigiendo...</div>');
                            setTimeout(function() {
                                window.location.href = 'index.php';
                            }, 500);
                        } else if(data === 'cambioPass') {
                            window.location.href = 'cambioPass.php';
                        } else {
                            $mensaje.html('<div class="alert alert-danger mb-0" role="alert"><i class="bi bi-x-circle-fill me-2"></i>' + data + '</div>');
                        }
                    })
                    .catch(error => {
                        $btn.attr('disabled', false);
                        $btn.html('Iniciar Sesión');
                        $mensaje.html('<div class="alert alert-danger mb-0" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error en la conexión. Por favor intente nuevamente.</div>');
                        console.error('Error:', error);
                    });
                }
            });
        </script>
    </body>
</html>