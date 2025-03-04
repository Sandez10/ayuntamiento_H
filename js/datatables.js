$(document).ready(function () {
    if ($('#generalTable').length) {
        $('#generalTable').DataTable({
            language: {
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_",
                infoFiltered: "(filtrado de _MAX_ registros)",
                paginate: { previous: "Anterior", next: "Siguiente" }
            },
            paging: true,
            lengthMenu: [[10, 15, 30, -1], [10, 15, 30, "Todos"]],
            responsive: true
        });
    }
});
        // JavaScript para ocultar el sidebar al desplazar horizontalmente
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            let isSidebarHidden = false;

            window.addEventListener('scroll', function () {
                if (window.scrollX > 50 && !isSidebarHidden) {
                    sidebar.classList.add('hidden');
                    mainContent.classList.add('expanded');
                    isSidebarHidden = true;
                } else if (window.scrollX <= 50 && isSidebarHidden) {
                    sidebar.classList.remove('hidden');
                    mainContent.classList.remove('expanded');
                    isSidebarHidden = false;
                }
            });
        });