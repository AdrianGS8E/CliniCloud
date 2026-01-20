<?php
session_start();

?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SmartCoop</title>
        <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"> -->
        <link rel="stylesheet" media="screen, print" href="assets/css/bootstrap.css">
        <link href="assets/css/smartapp.css" rel="stylesheet">
        <link href="assets/css/authentication.css" rel="stylesheet">
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark position-fixed w-100 py-3" style="z-index: 1000;">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <img src="assets/img/logo-dark.jpg" alt="logo" style="width: 100px;">
                </a>
                <!-- <div class="ms-auto d-flex gap-2">
                    <a href="login.html" class="btn btn-link text-white border-0 text-decoration-none">Ingreso</a>
                    <a href="register.html" class="btn btn-link text-white border-0 text-decoration-none">Register</a>
                </div> -->
            </div>
        </nav>
        <!-- Login Page -->
        <section class="hero-section position-relative overflow-hidden">
            <div class="container" style="position: relative; z-index: 1;">
                <div class="row justify-content-center">
                    <div class="col-11 col-md-8 col-lg-6 col-xl-4">
                        <div id="regular-login" class="login-card p-4 p-md-5 bg-dark bg-opacity-50 translucent-dark rounded-4">
                            <h2 class="text-center mb-4">Login</h2>
                            <p class="text-center text-white opacity-50 mb-4">Ingrese sus credenciales para acceder</p>
                            <form onsubmit="event.preventDefault();"></form>
                                <div class="mb-3">
                                    <label for="usuario" class="form-label">Usuario</label>
                                    <input type="usuario" class="form-control form-control-lg text-white bg-dark border-light border-opacity-25 bg-opacity-25" id="usuario" value="<?php echo $_SESSION['usuarioUs_clinicloud']; ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nueva Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-lg text-white bg-dark border-light border-opacity-25 bg-opacity-25" id="password" required="">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="passwordConfirm" class="form-label">Confirmar Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-lg text-white bg-dark border-light border-opacity-25 bg-opacity-25" id="passwordConfirm" required="">
                                    </div>
                                </div>
                                <div class="d-grid mb-3">
                                    <button type="button" class="btn btn-primary btn-lg bg-primary bg-opacity-75" id="btnCambiarPassword">Cambiar Contraseña</button>
                                </div>
                                <div class="d-grid mb-3" id="mensaje">
                                    
                                </div>
                                <!-- <div class="text-center mb-4">
                                    <a href="forgetpassword.html" class="text-decoration-none small text-white">Forgot Password?</a>
                                </div> -->
                                <!-- <div class="divider small text-white opacity-25">or</div>
                                <div class="d-grid mb-3">
                                    <button type="button" id="switchToToken" class="btn btn-dark bg-opacity-50 border-dark btn-lg"> Login Using Token </button>
                                </div> -->
                            </form>
                        </div>
                        
                    </div>
                </div>
            </div>
            <div id="net"></div>
        </section>
        <script src="scripts/smartApp.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r121/three.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.halo.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.waves.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.net.min.js"></script>
        <script src="assets/scripts/jquery-3.7.1.min.js"></script>
        <script>
            // VANTA.HALO(
            // {
            //     el: "#net",
            //     mouseControls: false,
            //     touchControls: false,
            //     gyroControls: false,
            //     color: 0xfd3995,
            //     size: 1.6,
            //     scale: 0.75,
            //     xOffset: 0.22,
            //     scaleMobile: 0.50,
            // });
            VANTA.WAVES({
                el: "#net",
                mouseControls: true,
                touchControls: true,
                gyroControls: false,
                minHeight: 200.00,
                minWidth: 200.00,
                scale: 1.00,
                scaleMobile: 0.50,
                color: 0x002B46,
                shininess: 50.00,
                waveHeight: 20.00,
                waveSpeed: 0.30,
                zoom: 1.00
            });
            const switchToTokenButton = document.querySelector('#switchToToken');
            const switchToRegularButton = document.querySelector('#switchToRegular');
            const regularLogin = document.querySelector('#regular-login');
            const tokenLogin = document.querySelector('#token-login');
            switchToTokenButton.addEventListener('click', function()
            {
                regularLogin.classList.add('d-none');
                tokenLogin.classList.remove('d-none');
            });
            switchToRegularButton.addEventListener('click', function()
            {
                tokenLogin.classList.add('d-none');
                regularLogin.classList.remove('d-none');
            });
        </script>

        <script>
            $(document).ready(function() {
    
                // Evento click en el botón de iniciar sesión
                $('#btnCambiarPassword').click(function() {
                    validarYEnviar();
                });

                // Función de validación y envío de datos
                function validarYEnviar() {
                    const usuario = $('#usuario').val();
                    const password = $('#password').val();
                    const passwordConfirm = $('#passwordConfirm').val();

                    if (usuario === '' || password === '' || passwordConfirm === '') {
                        $('#mensaje').html("<div class='alert alert-danger' role='alert'>Por favor ingrese usuario y contraseña!</div>");
                        return;
                    }

                    //comprobar que la contraseña tenga minimo 8 caracteres, numeros, mayusculas y minusculas
                    if (password.length < 8) {
                        $('#mensaje').html("<div class='alert alert-danger' role='alert'>La contraseña debe tener al menos 8 caracteres!</div>");
                        return;
                    }
                    //comprobar que la contraseña tenga al menos un numero
                    if (!/[0-9]/.test(password)) {
                        $('#mensaje').html("<div class='alert alert-danger' role='alert'>La contraseña debe tener al menos un numero!</div>");
                        return;
                    }
                    //comprobar que la contraseña tenga al menos una mayuscula
                    if (!/[A-Z]/.test(password)) {
                        $('#mensaje').html("<div class='alert alert-danger' role='alert'>La contraseña debe tener al menos una mayuscula!</div>");
                        return;
                    }
                    //comprobar que la contraseña tenga al menos una minuscula
                    if (!/[a-z]/.test(password)) {
                        $('#mensaje').html("<div class='alert alert-danger' role='alert'>La contraseña debe tener al menos una minuscula!</div>");
                        return;
                    }
                    //comprobar que la contraseña tenga al menos un caracter especial
                    if (!/[!@#$%^&*]/.test(password)) {
                        $('#mensaje').html("<div class='alert alert-danger' role='alert'>La contraseña debe tener al menos un caracter especial!</div>");
                        return;
                    }
                    //comprobar que la contraseña y la confirmacion sean iguales
                    if (password !== passwordConfirm) {
                        $('#mensaje').html("<div class='alert alert-danger' role='alert'>Las contraseñas no coinciden!</div>");
                        return;
                    }




                    cambiarPassword(usuario, password, passwordConfirm);
                }

                // Función para enviar la solicitud vía fetch
                function cambiarPassword(usuario, password, passwordConfirm) {

                    $('#btnCambiarPassword').attr('disabled', true);
                    $('#mensaje').html('<div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>');

                    fetch('fn_cambioPass.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            usuario: usuario,
                            password: password,
                            passwordConfirm: passwordConfirm
                        })
                    })
                    .then(response => response.text())
                    .then(data => {
                        $('#btnCambiarPassword').attr('disabled', false);
                        if (data === 'success') {
                            alert('Contraseña cambiada con éxito!');
                            window.location.href = 'login.php';
                        } 
                        else {
                            $('#mensaje').html("<div class='alert alert-danger' role='alert'>" + data + "</div>");
                        }

                    })
                    .catch(error => {
                        $('#btnIniciarSesion').attr('disabled', false);
                        $('#mensaje').html("<div class='alert alert-danger' role='alert'>Error en la conexión.</div>");
                        console.error('Error:', error);
                    });
                }

            });

            

            
        </script>
    </body>
</html>