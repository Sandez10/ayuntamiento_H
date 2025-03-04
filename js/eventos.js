$(document).ready(function () {
    $('.btnEditar').click(function () {
        let usuario = JSON.parse($(this).data('usuario'));
        $('#usuarioId').val(usuario.usr);
        $('#usuario').val(usuario.usr);
        $('#area').val(usuario.dependenciaArea);
        $('#rol').val(usuario.rol);
        $('#modalUsuarioLabel').text('Editar Usuario');
    });

    $('#btnAgregarUsuario').click(function () {
        $('#modalUsuarioLabel').text('Agregar Usuario');
        $('#formUsuario')[0].reset();
    });
});
