document.addEventListener("DOMContentLoaded", function () {
    const modalUsuario = document.getElementById("modalUsuario");
    const modalTitle = document.getElementById("modalUsuarioLabel");
    const usuarioId = document.getElementById("usuarioId");
    const usuario = document.getElementById("usuario");
    const rol = document.getElementById("rol");
    const passtemp = document.getElementById("passtemp");
    const correo = document.getElementById("correo");
    const area = document.getElementById("area");
    const clave_area = document.getElementById("clave_area");
    const clave_prog = document.getElementById("clave_prog");
    const formUsuario = document.getElementById("formUsuario");

    // Verificar si los elementos del DOM existen
    if (!modalUsuario || !formUsuario || !modalTitle || !usuarioId || !usuario || !rol || !passtemp || !correo || !area || !clave_area || !clave_prog) {
        console.error("Error: Algunos elementos del DOM no fueron encontrados.");
        return;
    }

    // Abrir modal y determinar si es agregar o editar
    modalUsuario.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;
        const action = button.getAttribute("data-action");
        const claveProgContainer = clave_prog.closest(".mb-3");

        if (action === "editar") {
            modalTitle.textContent = "Editar Usuario";
            let usuarioData;
            try {
                usuarioData = JSON.parse(button.getAttribute("data-usuario"));
            } catch (error) {
                console.error("Error al parsear data-usuario:", error);
                alert("Error al cargar los datos del usuario.");
                return;
            }
            cargarDatosUsuario(usuarioData);
            claveProgContainer.style.display = "none"; // Oculta Clave Programa al editar
            clave_area.removeAttribute("required");
            clave_prog.removeAttribute("required");
        } else {
            modalTitle.textContent = "Agregar Usuario";
            limpiarFormulario();
            claveProgContainer.style.display = "block"; // Muestra Clave Programa al agregar
            clave_area.setAttribute("required", "true");
            clave_prog.setAttribute("required", "true");
        }
    });

    // Envío de formulario con AJAX
formUsuario.addEventListener("submit", function (event) {
    event.preventDefault();

    let formData = new FormData(formUsuario);

    fetch("actualizar_usr.php", {
        method: "POST",
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
            return response.json(); // Si es JSON, procesarlo normalmente
        } else {
            return response.text().then(text => { throw new Error("Respuesta inesperada del servidor:\n" + text); });
        }
    })
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || "Error en la operación.");
        }
    })
    .catch(error => {
        console.error("Error en la petición:", error);
        alert("Hubo un problema con el servidor.");
    });
});


    // Función para cargar datos al editar
    function cargarDatosUsuario(usuarioData) {
        usuarioId.value = usuarioData.id || "";
        usuario.value = usuarioData.usr || "";
        rol.value = usuarioData.rol || "";
        passtemp.value = ""; // No cargar la contraseña por seguridad
        correo.value = usuarioData.correo || "";
        area.value = usuarioData.dependenciaArea || "";
        clave_area.value = usuarioData.clave_area || "";
        clave_prog.value = usuarioData.claveProgramaP || "";
    }

    // Función para limpiar el formulario
    function limpiarFormulario() {
        formUsuario.reset();
        usuarioId.value = ""; // Limpiar manualmente si es necesario
    }

    // Eliminar usuario con AJAX
    document.addEventListener("click", function (event) {
        if (event.target.matches(".btnEliminar")) {
            const idUsuario = event.target.getAttribute("data-usuario");
            if (confirm("¿Seguro que deseas eliminar este usuario?")) {
                fetch("actualizar_usr.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `delete=1&usuario_id=${idUsuario}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Error en la respuesta del servidor.");
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.success) {
                        alert(data.message);
                        event.target.closest("tr").remove(); // Elimina la fila sin recargar
                    } else {
                        alert(data?.message || "Error al eliminar usuario.");
                    }
                })
                .catch(error => {
                    console.error("Error al eliminar el usuario:", error);
                    alert("Hubo un problema al eliminar el usuario.");
                });
            }
        }
    });

    // Limpiar formulario al cerrar el modal solo si se estaba agregando un usuario
    modalUsuario.addEventListener("hidden.bs.modal", function () {
        if (modalTitle.textContent === "Agregar Usuario") {
            limpiarFormulario();
        }
    });
});