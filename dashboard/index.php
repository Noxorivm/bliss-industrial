<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

// --- Incluir verificador de sesión al principio ---
// Debe estar antes de CUALQUIER salida HTML
require_once 'auth_check.php'; // Se asume que auth_check.php está en el mismo directorio
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Auditorías BLISS</title>
    <meta name="robots" content="noindex, nofollow"> <!-- No indexar dashboard -->

    <!-- Favicons, Fonts -->
    <!-- Rutas corregidas para empezar con ../ para salir de /dashboard/ -->
    <link href="../assets/img/favicon.png" rel="icon">
    <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS -->
    <!-- Rutas corregidas para empezar con ../ -->
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS (Bootstrap 5 Styling) - VERIFICA NOMBRE EXACTO DEL ARCHIVO -->
    <link href="../assets/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- DataTables Responsive CSS (Opcional pero recomendado) - VERIFICA NOMBRE EXACTO DEL ARCHIVO -->
    <link href="../assets/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet">


    <!-- Main & Dashboard CSS -->
    <!-- Rutas corregidas para empezar con ../ -->
    <link href="../assets/css/main.css" rel="stylesheet"> <!-- Estilos generales (opcional aquí) -->
    <link href="../assets/css/dashboard.css" rel="stylesheet"> <!-- Estilos específicos dashboard -->

</head>
<body class="dashboard-body">

    <!-- Header del Dashboard -->
    <header id="header" class="header dashboard-header fixed-top bg-light shadow-sm">
      <div class="container-fluid container-xl position-relative d-flex align-items-center">
        <a href="index.php" class="logo d-flex align-items-center me-auto">
          <!-- Ruta corregida para empezar con ../ -->
          <img src="../assets/img/logo.jfif" alt="BLISS Logo">
          <span class="ms-2 d-none d-sm-inline sitename-dashboard">Dashboard BLISS</span>
        </a>
         <div class="ms-auto d-flex align-items-center">
             <span class="me-3 text-muted small">Hola, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
             <!-- Ruta correcta a logout.php (está en el mismo directorio) -->
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
    <!-- Rutas corregidas para empezar con ../ -->
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- =============================================== -->
    <!-- === jQuery Cargado desde CDN === -->
    <!-- =============================================== -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- =============================================== -->

    <!-- DataTables Core JS - VERIFICA NOMBRE EXACTO DEL ARCHIVO (ej: datatables.min.js o jquery.dataTables.min.js) -->
    <script src="../assets/vendor/datatables/datatables.min.js"></script>
    <!-- DataTables Bootstrap 5 Integration JS - VERIFICA NOMBRE EXACTO DEL ARCHIVO -->
    <script src="../assets/vendor/datatables/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Responsive JS (Opcional pero recomendado) - VERIFICA NOMBRE EXACTO DEL ARCHIVO -->
    <script src="../assets/vendor/datatables/dataTables.responsive.min.js"></script>
    <script src="../assets/vendor/datatables/responsive.bootstrap5.min.js"></script>


    <!-- Dashboard Init Script -->
    <script>
        // Asegurarse que el DOM está listo Y que jQuery ($) está definido
        if (typeof jQuery === 'undefined') {
            console.error('ERROR: jQuery no se ha cargado. DataTables no funcionará.');
            // Opcional: Mostrar un mensaje al usuario
             document.body.innerHTML = '<div class="alert alert-danger m-5">Error crítico: No se pudo cargar jQuery. Contacta al administrador.</div>';
        } else {
            $(document).ready(function() {
                console.log("jQuery cargado, inicializando DataTables..."); // Mensaje de depuración
                try { // Añadir try-catch alrededor de la inicialización de DataTables
                    $('#auditsTable').DataTable({
                        processing: true, // Muestra indicador de procesamiento
                        // serverSide: true, // Descomentar si tienes MUCHOS datos y quieres paginación en servidor
                        ajax: {
                            url: '../api/fetch_audits.php', // Ruta RELATIVA al script PHP
                            type: 'POST', // Usar POST si se implementa serverSide, GET si no
                            dataSrc: 'data', // La clave que contiene el array de datos en el JSON
                            error: function (xhr, error, thrown) { // Manejo de errores AJAX
                                console.error("Error cargando datos DataTables:", error, thrown);
                                console.error("Respuesta del servidor:", xhr.responseText); // Muestra la respuesta completa
                                $('#auditsTable_processing').hide(); // Ocultar "procesando"
                                // Mostrar error más descriptivo en la tabla
                                $('#auditsTable tbody').html(
                                    '<tr><td colspan="11" class="text-center text-danger">Error al cargar los datos. Revisa la consola (F12) y los logs del servidor. <br><small>Detalle: ' + (xhr.statusText || error || thrown) + ' - ' + (xhr.responseText || '') +'</small></td></tr>'
                                );
                            }
                        },
                        columns: [
                            { data: 'id' },
                            {
                                data: 'fecha_envio',
                                render: function (data, type, row) {
                                    if (type === 'display' || type === 'filter') {
                                        if (!data) return ''; // Manejar nulos
                                        try {
                                            // Intenta crear fecha, asumiendo formato SQL 'YYYY-MM-DD HH:MM:SS'
                                            let date = new Date(data.replace(' ', 'T') + 'Z'); // Añadir Z para interpretar como UTC
                                            if (isNaN(date)) { // Fallback si el formato es diferente
                                                 date = new Date(data);
                                            }
                                             if (isNaN(date)) return data; // Si sigue fallando, muestra original

                                            // Formatear a español local
                                            return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
                                                   date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                                        } catch (e) {
                                            console.warn("Error formateando fecha:", data, e);
                                            return data; // Devuelve dato original si hay error
                                         }
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
                                     return '<button class="btn btn-sm btn-outline-primary view-btn disabled" data-id="' + row.id + '" title="Ver Detalles (Próximamente)"><i class="bi bi-eye"></i></button>';
                                },
                                orderable: false,
                                searchable: false
                            }
                        ],
                        responsive: true, // Habilitar responsive
                        language: { // Traducción DataTables desde CDN
                            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
                        },
                        order: [[1, 'desc']] // Ordenar por fecha (índice 1) descendente
                    });
                } catch (e) {
                     console.error("Error inicializando DataTables:", e);
                     alert("Error al inicializar la tabla de datos.");
                }


                 // Listener para el botón de Ver (ejemplo futuro)
                 $('#auditsTable tbody').on('click', '.view-btn:not(.disabled)', function() {
                     var dataId = $(this).data('id');
                     alert('ID seleccionado para ver: ' + dataId + '\n(Funcionalidad pendiente)');
                     // window.location.href = 'view_audit.php?id=' + dataId;
                 });
            });
        }
    </script>

</body>
</html>