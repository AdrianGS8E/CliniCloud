function verificarSesion(data) {
    // Solo intentar analizar si comienza como JSON (opcional, pero ayuda a evitar errores con HTML)
    if (typeof data === "string" && data.trim().startsWith("{")) {
        try {
            const json = JSON.parse(data);

            // Solo actuar si se detecta explícitamente "sesion: cerrada"
            if (json.sesion && json.sesion === "cerrada") {
                alert("Tu sesión ha expirado. Serás redirigido al inicio de sesión.");
                window.location.href = "login.php";
                return false;
            }
        } catch (e) {
            // No hacer nada si falla el parseo, puede que sea HTML o texto normal
        }
    }

    return true;
}
