<?php
// --- Incluir verificador de sesión al principio ---
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Auditorías BLISS</title>
    <meta name="robots" content="noindex, nofollow"> <!-- No indexar dashboard -->

    <!-- Favicons, Fonts -->
    <link href="../assets/img/favicon.png" rel="icon"> <!-- Ajustar ruta -->
    <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon"> <!-- Ajustar ruta -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS -->
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet"> <!-- Ajustar ruta -->
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet"> <!-- Ajustar ruta -->
    <link href="../assets/vendor/datatables/datatables.min.css" rel="stylesheet"> <!-- DataTables CSS -->

    <!-- Main & Dashboard CSS -->
    <link href="../assets/css/main.css" rel="stylesheet"> <!-- Estilos generales (opcional aquí) -->
    <link href="../assets/css/dashboard.css" rel="stylesheet"> <!-- Estilos específicos dashboard -->

</head>
<body class="dashboard-body">

    <!-- Header del Dashboard -->
    <header id="header" class="header dashboard-header fixed-top bg-light shadow-sm">
      <div class="container-fluid container-xl position-relative d-flex align-items-center">
        <a href="index.php" class="logo d-flex align-items-center me-auto">
          <img src="../assets/img/logo.jfif" alt="BLISS Logo"> <!-- Ajustar ruta -->
          <span class="ms-2 d-none d-sm-inline sitename-dashboard">Dashboard BLISS</span>
        </a>
         <div class="ms-auto d-flex align-items-center">
             <span class="me-3 text-muted small">Hola, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
             <a href="logout.php" class="btn btn-sm btn-outline-secondary">
                 <i class="bi bi-box-arrow-right me-1"></i> Cerrar Sesión
             </a>
         </div>
      </div>
    </header>

    <main class="dashboard-main">
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
                                    <!-- Añadir más th si seleccionaste más columnas en fetch_audits.php -->
                                     <th>Acciones</th> <!-- Columna para futuros botones (Ver/Editar/Borrar) -->
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargarán aquí por DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Simple -->
    <footer class="dashboard-footer text-center py-3">
        <div class="container">
            <small class="text-muted">© <?php echo date("Y"); ?> BLISS Industrial Services. Dashboard interno.</small>
        </div>
    </footer>


    <!-- Vendor JS Files -->
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script> <!-- Ajustar ruta -->
    <script src="../assets/vendor/jquery/jquery-3.7.1.min.js"></script> <!-- jQuery (necesario para DataTables) -->
    <script src="../assets/vendor/datatables/datatables.min.js"></script> <!-- DataTables JS -->

    <!-- Dashboard Init Script -->
    <script>
        $(document).ready(function() {
            $('#auditsTable').DataTable({
                ajax: {
                    url: '../api/fetch_audits.php', // Ruta al script PHP que devuelve JSON
                    dataSrc: 'data' // La clave que contiene el array de datos en el JSON
                },
                columns: [
                    { data: 'id' },
                    {
                        data: 'fecha_envio',
                        render: function (data, type, row) {
                            // Formatear fecha si es necesario
                            if (type === 'display' || type === 'filter') {
                                let date = new Date(data);
                                return date.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' }) + ' ' +
                                       date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                            }
                            return data;
                        }
                    },
                    { data: 'empresa_nombre' },
                    { data: 'contacto_nombre' },
                    { data: 'contacto_email' },
                    { data: 'contacto_telefono' },
                    { data: 'perfil_cargo' },
                    { data: 'empresa_empleados' },
                    { data: 'urgencia_nivel' },
                    { data: 'cuando_empezar' },
                     // Asegúrate que el número y orden de estas columnas coincida con tu <thead> y el JSON devuelto
                    {
                        data: null, // Columna de acciones no viene de la DB directamente
                        render: function (data, type, row) {
                            // Aquí irían los botones CRUD en el futuro
                            // Por ahora, un botón simple de ejemplo (o dejar vacío)
                            // return '<button class="btn btn-sm btn-info view-btn" data-id="' + row.id + '">Ver</button>';
                            return '<a href="#" class="btn btn-sm btn-outline-primary disabled" title="Ver Detalles (Próximamente)"><i class="bi bi-eye"></i></a>'; // Botón deshabilitado
                        },
                        orderable: false, // No permitir ordenar por esta columna
                        searchable: false // No permitir buscar en esta columna
                    }
                ],
                responsive: true, // Hacer tabla responsive
                language: { // Traducción DataTables
                    "decimal":        "",
                    "emptyTable":     "No hay auditorías registradas",
                    "info":           "Mostrando _START_ a _END_ de _TOTAL_ entradas",
                    "infoEmpty":      "Mostrando 0 a 0 de 0 entradas",
                    "infoFiltered":   "(filtrado de _MAX_ entradas totales)",
                    "infoPostFix":    "",
                    "thousands":      ".",
                    "lengthMenu":     "Mostrar _MENU_ entradas",
                    "loadingRecords": "Cargando...",
                    "processing":     "Procesando...",
                    "search":         "Buscar:",
                    "zeroRecords":    "No se encontraron coincidencias",
                    "paginate": {
                        "first":      "Primero",
                        "last":       "Último",
                        "next":       "Siguiente",
                        "previous":   "Anterior"
                    },
                    "aria": {
                        "sortAscending":  ": activar para ordenar columna ascendente",
                        "sortDescending": ": activar para ordenar columna descendente"
                    }
                },
                order: [[1, 'desc']] // Ordenar por la columna de fecha (índice 1) descendente por defecto
            });

            // --- Aquí añadirías listeners para botones Ver/Editar/Borrar en el futuro ---
            // $('#auditsTable tbody').on('click', '.view-btn', function() {
            //     var dataId = $(this).data('id');
            //     alert('Ver detalles del ID: ' + dataId); // Acción de ejemplo
            //     // Aquí abrirías un modal o irías a otra página con los detalles
            // });
        });
    </script>

</body>
</html>