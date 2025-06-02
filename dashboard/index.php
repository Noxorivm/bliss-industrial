<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar en producción) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

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
    <!-- DataTables CSS desde CDN -->
    <link href="https://cdn.datatables.net/v/bs5/dt-2.0.5/r-3.0.2/datatables.min.css" rel="stylesheet">

    <!-- Main & Dashboard CSS -->
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet"> <!-- Aquí deben estar los estilos del modal también -->

</head>
<body class="dashboard-body">

    <?php include_once 'includes/header_dashboard.php'; ?>

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
                                    <th>Fecha</th>
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
                            <tbody>
                                <!-- DataTables cargará los datos aquí -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="dashboard-footer text-center py-3">
        <div class="container">
            <small class="text-muted">© <?php echo date("Y"); ?> BLISS Industrial Services. Dashboard interno.</small>
        </div>
    </footer>

    <!-- Modal de Detalles de Auditoría -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="detailsModalLabel">Detalles de Auditoría #<span id="modal-audit-id"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="detailsModalBody">
            Cargando detalles...
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
    <!-- FIN Modal de Detalles -->


    <!-- Vendor JS Files -->
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/v/bs5/dt-2.0.5/r-3.0.2/datatables.min.js"></script>


    <!-- Dashboard Init Script -->
    <script>
        if (typeof jQuery === 'undefined') {
            console.error('ERROR CRÍTICO: jQuery no se ha cargado. DataTables no funcionará.');
             document.body.innerHTML = '<div class="alert alert-danger m-5 text-center">Error crítico: No se pudo cargar jQuery. El dashboard no funcionará.</div>';
        } else {
            $(document).ready(function() {
                console.log("jQuery cargado, inicializando DataTables para Auditorías...");
                let auditsDataTable;

                try {
                   auditsDataTable = $('#auditsTable').DataTable({
                        processing: true,
                        ajax: {
                            url: '../api/fetch_audits.php',
                            type: 'POST', // O 'GET' si tu PHP lo maneja así
                            dataSrc: 'data',
                            error: function (xhr, error, thrown) {
                                console.error("Error AJAX al cargar datos para DataTables (Auditorías):", error, thrown);
                                console.error("Respuesta del servidor (Auditorías):", xhr.responseText);
                                $('#auditsTable_processing').hide();
                                $('#auditsTable tbody').html(
                                    '<tr><td colspan="11" class="text-center text-danger">Error al cargar los datos de auditorías ('+xhr.status+'). Revisa la consola (F12) y los logs del servidor PHP. <br><small>('+ (xhr.statusText || error || thrown) + ')</small></td></tr>'
                                );
                            }
                        },
                        columns: [
                            { data: 'id', className: 'text-center' },
                            {
                                data: 'fecha_envio', className: 'text-nowrap',
                                render: function (data, type, row) {
                                    if (type === 'display' || type === 'filter') {
                                        if (!data) return '';
                                        try { let date = new Date(data.replace(' ', 'T') + 'Z'); if (isNaN(date)) date = new Date(data); if (isNaN(date)) return data; return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: '2-digit' }) + ' ' + date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }); } catch (e) { return data; }
                                    } return data;
                                }
                            },
                            { data: 'empresa_nombre' },
                            { data: 'contacto_nombre' },
                            { data: 'contacto_email', render: function(data){ return data ? `<a href="mailto:${data}">${data}</a>` : ''; } },
                            { data: 'contacto_telefono', render: function(data){ return data ? `<a href="tel:${data}">${data}</a>` : ''; } },
                            { data: 'perfil_cargo', render: function(data, type, row) {
                                let display = data || '';
                                if (data === 'Otro' && row.cargo_otro_texto) { display = `${data} (${row.cargo_otro_texto})`;}
                                return display.length > 30 ? display.substring(0,27)+'...' : display;
                                }
                            },
                            { data: 'empresa_empleados' },
                            { data: 'urgencia_nivel' },
                            { data: 'cuando_empezar' },
                            {
                                data: null,
                                render: function (data, type, row) {
                                     return `<button class="btn btn-sm btn-outline-primary view-details-btn" data-audit-id="${row.id}" title="Ver Detalles Completos"><i class="bi bi-eye"></i></button>`;
                                },
                                orderable: false, searchable: false, className: 'text-center actions-column'
                            }
                        ],
                        responsive: true,
                        language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json" },
                        order: [[1, 'desc']]
                    });
                } catch (e) {
                     console.error("Error inicializando DataTables para Auditorías:", e);
                     $('#auditsTable tbody').html( '<tr><td colspan="11" class="text-center text-danger">Error al inicializar la tabla de auditorías. Revisa la consola (F12).</td></tr>' );
                }

                // Listener para el botón Ver Detalles (MODAL)
                $('#auditsTable tbody').on('click', '.view-details-btn', function() {
                    const auditId = $(this).data('audit-id');
                    if (auditId) {
                        openDetailsModal(auditId);
                    } else {
                        console.error("No se pudo obtener el ID de la auditoría para el modal.");
                        alert("Error: No se pudo cargar el detalle de la auditoría.");
                    }
                 });

            }); // Fin $(document).ready
        } // Fin else (jQuery definido)

        // === FUNCIÓN: Abrir Modal de Detalles para Auditoría ===
        function openDetailsModal(auditId) {
            const modalBody = document.getElementById('detailsModalBody');
            const modalTitleId = document.getElementById('modal-audit-id');
            const detailModalEl = document.getElementById('detailsModal');
            const detailModal = bootstrap.Modal.getInstance(detailModalEl) || new bootstrap.Modal(detailModalEl);

            if (!modalBody || !modalTitleId || !auditId) { console.error("Faltan elementos del modal o ID de auditoría."); alert("Error al intentar mostrar detalles."); return; }

            modalTitleId.textContent = auditId;
            modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-warning" role="status"><span class="visually-hidden">Cargando detalles...</span></div><p class="mt-2">Cargando detalles de la auditoría...</p></div>';
            detailModal.show();

            $.ajax({
                url: '../api/fetch_audit_full_details.php', // Script que devuelve todos los datos
                type: 'GET', data: { id: auditId }, dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        const data = response.data;
                        let contentHtml = '<dl class="row">';

                        function addDetailRow(label, value) {
                            let displayValue = '';
                            if (value !== null && value !== undefined && value !== '') {
                                if ((label.toLowerCase().includes('whatsapp')) && (value === 1 || value === '1' || value === true || String(value).toLowerCase() === 'sí')) value = 'Sí';
                                else if ((label.toLowerCase().includes('whatsapp')) && (value === 0 || value === '0' || value === false || String(value).toLowerCase() === 'no')) value = 'No';
                                displayValue = String(value).replace(/</g, "<").replace(/>/g, ">");
                            } else { displayValue = '<em class="text-muted">-- No especificado --</em>'; }
                            displayValue = displayValue.replace(/, /g, '<br>• ').replace(/\n/g, '<br>');
                            if(displayValue.startsWith('<br>• ')) displayValue = '• ' + displayValue.substring(6);
                            return `<dt class="col-sm-4 col-lg-3">${label}:</dt><dd class="col-sm-8 col-lg-9">${displayValue}</dd>`;
                        }
                        function ucwords(str) { return str.toLowerCase().replace(/\b[a-z]/g, function(letter) { return letter.toUpperCase(); }); }
                        function str_replace_js(search, replace, subject) { // Renombrada para evitar conflicto
                            let result = subject;
                            for (let i = 0; i < search.length; i++) { result = result.split(search[i]).join(replace[i]); }
                            return result;
                        }

                        // Sección Principal (de auditoria_bliss_main)
                        if (data.main) {
                            contentHtml += '<h6 class="detail-section-title">Información Principal</h6>';
                            contentHtml += addDetailRow('ID Auditoría', data.main.id);
                            contentHtml += addDetailRow('Fecha Envío', data.main.fecha_envio ? new Date(data.main.fecha_envio.replace(' ', 'T')+'Z').toLocaleString('es-ES', {dateStyle:'long', timeStyle:'short'}) : '--');
                            contentHtml += addDetailRow('Empresa', data.main.empresa_nombre);
                            contentHtml += addDetailRow('Contacto', data.main.contacto_nombre);
                            contentHtml += addDetailRow('Email', data.main.email_contacto); // Corregido: data.main.email_contacto
                            contentHtml += addDetailRow('Teléfono', data.main.contacto_telefono);
                            contentHtml += addDetailRow('Permiso WhatsApp', data.main.contacto_whatsapp);
                            contentHtml += addDetailRow('Cargo', data.main.perfil_cargo);
                            if(data.main.cargo_otro_texto) contentHtml += addDetailRow('Cargo (Otro)', data.main.cargo_otro_texto);
                            contentHtml += addDetailRow('Nº Empleados', data.main.empresa_empleados);
                            contentHtml += addDetailRow('Cuándo Empezar', data.main.cuando_empezar);
                            contentHtml += addDetailRow('Nivel Urgencia', data.main.urgencia_nivel);
                            contentHtml += addDetailRow('Recibir Informe Por', data.main.como_recibir);
                            contentHtml += addDetailRow('Recibir también por WhatsApp', data.main.recibir_whatsapp_informe);
                            contentHtml += addDetailRow('IP Origen', data.main.ip_address);
                        }

                        // Función para renderizar secciones departamentales en el modal
                        function renderSectionModal(title, sectionKey, sectionData) {
                            if (sectionData && Object.keys(sectionData).length > 0 && Object.values(sectionData).some(v => v !== null && v !== '')) {
                                contentHtml += `<hr><h6 class="detail-section-title">${title}</h6>`;
                                for (const key in sectionData) {
                                    if (sectionData.hasOwnProperty(key) && key !== 'id' && key !== 'auditoria_main_id') {
                                        let label = ucwords(str_replace_js(['dq_', 'dg_', 'dr_', 'dp_', 'dc_', 'dm_', 'dl_', 'di_', 'dprl_', 'daf_', 'dt_', 'dcomp_', 'ding_', 'dsat_', '_'],
                                                                         ['',    '',    '',    '',    '',    '',    '',    '',    '',     '',     '',     '',      '',      '',      ' '], key));
                                        label = label.trim();
                                        if (key.endsWith('_otro_texto')) {
                                            const original_key = key.replace('_otro_texto', '');
                                            const original_label_base = ucwords(str_replace_js(['dq_', 'dg_', 'dr_', 'dp_', 'dc_', 'dm_', 'dl_', 'di_', 'dprl_', 'daf_', 'dt_', 'dcomp_', 'ding_', 'dsat_', '_'], ['', '', '', '', '', '', '', '', '', '', '', '', '', '', ' '], original_key));
                                            label = original_label_base.trim() + " (Otro)";
                                        }
                                        contentHtml += addDetailRow(label, sectionData[key]);
                                    }
                                }
                            }
                        }

                        renderSectionModal("Diagnóstico Global Empresa", 'global', data.global);
                        renderSectionModal("Diagnóstico Calidad", 'calidad', data.calidad);
                        renderSectionModal("Diagnóstico RRHH", 'rrhh', data.rrhh);
                        renderSectionModal("Diagnóstico Producción", 'produccion', data.produccion);
                        renderSectionModal("Diagnóstico Comercial", 'comercial', data.comercial);
                        renderSectionModal("Diagnóstico Marketing", 'marketing', data.marketing);
                        renderSectionModal("Diagnóstico Logística", 'logistica', data.logistica);
                        renderSectionModal("Diagnóstico I+D", 'id_dpto', data.id_dpto);
                        renderSectionModal("Diagnóstico PRL", 'prl', data.prl);
                        renderSectionModal("Diagnóstico Admin./Finanzas", 'adminfin', data.adminfin);
                        renderSectionModal("Diagnóstico Transporte", 'transporte', data.transporte);
                        renderSectionModal("Diagnóstico Compras", 'compras', data.compras);
                        renderSectionModal("Diagnóstico Ingeniería", 'ingenieria', data.ingenieria);
                        renderSectionModal("Diagnóstico SAT", 'sat', data.sat);

                        contentHtml += '</dl>';
                        modalBody.innerHTML = contentHtml;
                    } else {
                        modalBody.innerHTML = '<div class="alert alert-danger">Error al cargar los detalles: ' + (response.message || 'No se recibieron datos.') + '</div>';
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error AJAX al obtener detalles de auditoría:", xhr.responseText);
                    modalBody.innerHTML = '<div class="alert alert-danger">Error de conexión al obtener detalles. Por favor, inténtalo de nuevo.</div>';
                }
            });
        }
        // ===============================================
    </script>

</body>
</html>