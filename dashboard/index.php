<?php
// ... (require_once 'auth_check.php'; igual que antes) ...
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Auditorías BLISS</title>
    <meta name="robots" content="noindex, nofollow">

    <!-- Favicons, Fonts -->
    <link href="../assets/img/favicon.png" rel="icon">
    <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS -->
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <!-- =============================================== -->
    <!-- === DataTables CSS - CARGA SIMPLIFICADA === -->
    <!-- =============================================== -->
    <link href="../assets/vendor/datatables/datatables.min.css" rel="stylesheet"> <!-- Solo el archivo principal minificado -->
    <!-- =============================================== -->

    <!-- Main & Dashboard CSS -->
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">

</head>
<body class="dashboard-body">

    <!-- === Header del Dashboard (Desde Include) === -->
    <?php include_once 'includes/header_dashboard.php'; ?>
    <!-- ========================================= -->

    <main class="dashboard-main">
        <!-- ... (contenido principal del dashboard igual que antes) ... -->
        <div class="container-fluid">
            <h1 class="dashboard-title">Auditorías Recibidas</h1>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Listado de Auditorías</h5>
                    <p class="card-subtitle mb-3 text-muted small">Resultados ordenados por fecha de envío (más recientes primero). Usa la búsqueda para filtrar.</p>
                    <div class="table-responsive">
                        <table id="auditsTable" class="table table-striped table-bordered table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha Envío</th>
                                    <th>Empresa</th>
                                    <th>Contacto</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Cargo</th>
                                    <th>Empleados</th>
                                    <th>Urgencia</th>
                                    <th>Inicio Deseado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Simple -->
    <footer class="dashboard-footer text-center py-3">
        <!-- ... (contenido del footer igual que antes) ... -->
        <div class="container">
            <small class="text-muted">® <?php echo date("Y"); ?> BLISS Industrial Services. Dashboard interno.</small>
        </div>
    </footer>

    <!-- Vendor JS Files -->
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery Cargado desde CDN -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <!-- =========================================================== -->
    <!-- === DataTables JS - CARGA SIMPLIFICADA === -->
    <!-- =========================================================== -->
    <script src="../assets/vendor/datatables/datatables.min.js"></script>
    <!-- =========================================================== -->

    <!-- Dashboard Init Script -->
    <script>
        // ... (script de inicialización de DataTables igual que antes) ...
         if (typeof jQuery === 'undefined') {
            console.error('ERROR: jQuery no se ha cargado. DataTables no funcionar�.');
             document.body.innerHTML = '<div class="alert alert-danger m-5">Error cr�tico: No se pudo cargar jQuery. Contacta al administrador.</div>';
        } else {
            $(document).ready(function() {
                console.log("jQuery cargado, inicializando DataTables...");
                try {
                    $('#auditsTable').DataTable({
                        processing: true,
                        ajax: {
                            url: '../api/fetch_audits.php', // Ruta RELATIVA
                            type: 'POST', // O GET
                            dataSrc: 'data',
                            error: function (xhr, error, thrown) {
                                console.error("Error cargando datos DataTables:", error, thrown);
                                console.error("Respuesta del servidor:", xhr.responseText);
                                $('#auditsTable_processing').hide();
                                $('#auditsTable tbody').html(
                                    '<tr><td colspan="11" class="text-center text-danger">Error al cargar los datos. Revisa la consola (F12) y los logs del servidor. <br><small>Detalle: ' + (xhr.statusText || error || thrown) + ' - ' + (xhr.responseText || '') +'</small></td></tr>'
                                );
                            }
                        },
                        columns: [
                            { data: 'id' }, { data: 'fecha_envio', render: function (data, type, row) { /* ... */ } },
                            { data: 'empresa_nombre' }, { data: 'contacto_nombre' }, { data: 'contacto_email' },
                            { data: 'contacto_telefono' }, { data: 'perfil_cargo' }, { data: 'empresa_empleados' },
                            { data: 'urgencia_nivel' }, { data: 'cuando_empezar' },
                            { data: null, render: function (data, type, row) { /* ... */ }, orderable: false, searchable: false }
                        ],
                        responsive: true,
                        language: { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json" },
                        order: [[1, 'desc']]
                    });
                } catch (e) {
                     console.error("Error inicializando DataTables:", e);
                     alert("Error al inicializar la tabla de datos.");
                }
                 $('#auditsTable tbody').on('click', '.view-btn:not(.disabled)', function() { /* ... */ });
            });
        }
    </script>

</body>
</html>