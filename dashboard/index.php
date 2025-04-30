<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

// --- Incluir verificador de sesión al principio ---
// Debe estar antes de CUALQUIER salida HTML
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
    <!-- DataTables CSS (Bootstrap 5 Styling) -->
    <link href="../assets/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet"> <!-- Ajustar ruta -->
    <!-- DataTables Responsive CSS (Opcional pero recomendado) -->
    <link href="../assets/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet"> <!-- Ajustar ruta -->


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
             <a href="logout.php" class="btn btn-sm btn-outline-secondary"> <!-- Ruta relativa correcta -->
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
                                    <th>Acciones</th> <!-- Columna para futuros botones -->
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables cargará los datos aquí -->
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
    <!-- jQuery (Necesario para DataTables) -->
    <script src="../assets/vendor/datatables/jquery-3.7.1.min.js"></script> <!-- Ajusta versión si es diferente y ruta -->
    <!-- DataTables Core JS -->
    <script src="../assets/vendor/datatables/jquery.dataTables.min.js"></script> <!-- Ajustar ruta -->
    <!-- DataTables Bootstrap 5 Integration JS -->
    <script src="../assets/vendor/datatables/dataTables.bootstrap5.min.js"></script> <!-- Ajustar ruta -->
    <!-- DataTables Responsive JS (Opcional pero recomendado) -->
    <script src="../assets/vendor/datatables/dataTables.responsive.min.js"></script> <!-- Ajustar ruta -->
    <script src="../assets/vendor/datatables/responsive.bootstrap5.min.js"></script> <!-- Ajustar ruta -->


    <!-- Dashboard Init Script -->
    <script>
        $(document).ready(function() {
            $('#auditsTable').DataTable({
                processing: true, // Muestra indicador de procesamiento
                // serverSide: true, // Descomentar si tienes MUCHOS datos y quieres paginación en servidor
                ajax: {
                    url: '../api/fetch_audits.php', // Ruta al script PHP que devuelve JSON
                    type: 'POST', // Usar POST si se implementa serverSide
                    dataSrc: 'data', // La clave que contiene el array de datos en el JSON
                    error: function (xhr, error, thrown) { // Manejo de errores AJAX
                        console.error("Error cargando datos DataTables:", xhr.responseText);
                        $('#auditsTable_processing').hide(); // Ocultar "procesando"
                        alert('Error al cargar los datos de auditoría. Revisa la consola.');
                    }
                },
                columns: [
                    { data: 'id' },
                    {
                        data: 'fecha_envio',
                        render: function (data, type, row) {
                            if (type === 'display' || type === 'filter') {
                                try {
                                    let date = new Date(data.replace(' ', 'T') + 'Z'); // Intenta parsear como UTC
                                    if (isNaN(date)) { // Si falla, intenta formato directo
                                         date = new Date(data);
                                    }
                                     if (isNaN(date)) return data; // Si sigue fallando, muestra original
                                    // Ajustar a zona horaria local si es necesario o mostrar UTC
                                    return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
                                           date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                                } catch (e) { return data; } // Fallback
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
                    {
                        data: null,
                        render: function (data, type, row) {
                            // Enlace simple para ver detalles (redirigiría a una página futura)
                            // return '<a href="view_audit.php?id=' + row.id + '" class="btn btn-sm btn-outline-primary" title="Ver Detalles"><i class="bi bi-eye"></i></a>';
                             return '<button class="btn btn-sm btn-outline-primary view-btn disabled" data-id="' + row.id + '" title="Ver Detalles (Próximamente)"><i class="bi bi-eye"></i></button>'; // Botón deshabilitado por ahora
                        },
                        orderable: false,
                        searchable: false
                    }
                ],
                responsive: true,
                language: { // Traducción DataTables
                    "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json" // Usar traducción externa
                },
                order: [[1, 'desc']] // Ordenar por fecha (índice 1) descendente
            });

             // Listener para el botón de Ver (ejemplo futuro)
             $('#auditsTable tbody').on('click', '.view-btn:not(.disabled)', function() {
                 var dataId = $(this).data('id');
                 alert('ID seleccionado para ver: ' + dataId + '\n(Funcionalidad pendiente)');
                 // window.location.href = 'view_audit.php?id=' + dataId;
             });
        });
    </script>

</body>
</html>