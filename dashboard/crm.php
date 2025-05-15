<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar en producción) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

require_once 'auth_check.php'; // Verificar sesión (asumimos que está en /dashboard/)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM - Clientes | Dashboard BLISS</title>
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
    <link href="../assets/css/dashboard.css" rel="stylesheet"> <!-- Asegúrate que aquí están los estilos de .cta-principal -->

</head>
<body class="dashboard-body">

    <!-- Header del Dashboard (Desde Include) -->
    <?php include_once 'includes/header_dashboard.php'; // Ruta correcta si 'includes' está dentro de 'dashboard' ?>

    <main class="dashboard-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="dashboard-title mb-0">Gestión de Clientes (CRM)</h1>
                <button type="button" class="btn cta-principal btn-sm" data-bs-toggle="modal" data-bs-target="#addClientModal">
                    <i class="bi bi-plus-circle-fill me-2"></i>Añadir Nuevo Cliente
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Listado de Clientes</h5>
                    <p class="card-subtitle mb-3 text-muted small">Clientes y leads registrados. Usa la búsqueda para filtrar.</p>

                    <div class="table-responsive">
                        <table id="clientsTable" class="table table-striped table-bordered table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Empresa</th>
                                    <th>Contacto</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Origen</th>
                                    <th>Estado</th>
                                    <th>Creado</th>
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

    <!-- Footer Simple -->
    <footer class="dashboard-footer text-center py-3">
        <div class="container">
            <small class="text-muted">© <?php echo date("Y"); ?> BLISS Industrial Services. Dashboard interno.</small>
        </div>
    </footer>

    <!-- =============================================== -->
    <!-- === Modal para Añadir Nuevo Cliente === -->
    <!-- =============================================== -->
    <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addClientModalLabel">Añadir Nuevo Cliente</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="addClientForm">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="crm_nombre_empresa" class="form-label">Nombre Empresa *</label>
                        <input type="text" class="form-control" id="crm_nombre_empresa" name="nombre_empresa" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="crm_persona_contacto" class="form-label">Persona de Contacto</label>
                        <input type="text" class="form-control" id="crm_persona_contacto" name="persona_contacto">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="crm_email_contacto" class="form-label">Email Contacto</label>
                        <input type="email" class="form-control" id="crm_email_contacto" name="email_contacto">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="crm_telefono_contacto" class="form-label">Teléfono Contacto</label>
                        <input type="tel" class="form-control" id="crm_telefono_contacto" name="telefono_contacto">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="crm_origen_lead" class="form-label">Origen Lead</label>
                        <select class="form-select" id="crm_origen_lead" name="origen_lead">
                            <option value="Manual CRM" selected>Manual CRM</option>
                            <option value="Form Auditoría">Form Auditoría</option>
                            <option value="Form Contacto">Form Contacto</option>
                            <option value="Referido">Referido</option>
                            <option value="Evento">Evento</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="crm_estado_lead" class="form-label">Estado Lead</label>
                        <select class="form-select" id="crm_estado_lead" name="estado_lead">
                            <option value="Nuevo" selected>Nuevo</option>
                            <option value="Contactado">Contactado</option>
                            <option value="En Seguimiento">En Seguimiento</option>
                            <option value="Propuesta Enviada">Propuesta Enviada</option>
                            <option value="Negociación">Negociación</option>
                            <option value="Ganado">Ganado</option>
                            <option value="Perdido">Perdido</option>
                            <option value="Descartado">Descartado</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="crm_notas_generales_cliente" class="form-label">Notas Generales</label>
                    <textarea class="form-control" id="crm_notas_generales_cliente" name="notas_generales_cliente" rows="3"></textarea>
                </div>
                 <div id="addClientFormFeedback" class="mt-3"></div> <!-- Para mensajes de error/éxito -->
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn cta-principal">Guardar Cliente</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- === FIN Modal === -->


    <!-- Vendor JS Files -->
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/v/bs5/dt-2.0.5/r-3.0.2/datatables.min.js"></script>

    <!-- CRM Init Script -->
    <script>
        if (typeof jQuery === 'undefined') {
            console.error('ERROR CRÍTICO: jQuery no se ha cargado. El CRM no funcionará.');
            document.body.innerHTML = '<div class="alert alert-danger m-5 text-center">Error crítico: No se pudo cargar jQuery. El dashboard no funcionará.</div>';
        } else {
            $(document).ready(function() {
                console.log("jQuery cargado, inicializando DataTables para CRM...");
                let clientsDataTable;
                try {
                   clientsDataTable = $('#clientsTable').DataTable({
                        processing: true,
                        ajax: {
                            url: '../api/fetch_crm_clientes.php', // Script PHP para obtener clientes
                            type: 'POST', // O GET
                            dataSrc: 'data', // Clave en el JSON que contiene los datos
                            error: function (xhr, error, thrown) {
                                console.error("Error AJAX al cargar clientes:", xhr.status, xhr.statusText, error, thrown);
                                console.error("Respuesta del servidor:", xhr.responseText);
                                $('#clientsTable_processing').hide();
                                $('#clientsTable tbody').html('<tr><td colspan="9" class="text-center text-danger">Error al cargar los clientes. Revise la consola y los logs del servidor.</td></tr>');
                            }
                        },
                        columns: [
                            { data: 'id', className: 'text-center' },
                            { data: 'nombre_empresa' },
                            { data: 'persona_contacto' },
                            { data: 'email_contacto', render: function(data){ return data ? `<a href="mailto:${data}">${data}</a>` : ''; } },
                            { data: 'telefono_contacto', render: function(data){ return data ? `<a href="tel:${data}">${data}</a>` : ''; } },
                            { data: 'origen_lead' },
                            { data: 'estado_lead' },
                            { 
                                data: 'fecha_creacion', 
                                className: 'text-nowrap', 
                                render: function (data) { 
                                    if (!data) return ''; 
                                    try { 
                                        let date = new Date(data.replace(' ', 'T')+'Z'); 
                                        if (isNaN(date)) date = new Date(data); 
                                        if (isNaN(date)) return data; 
                                        return date.toLocaleDateString('es-ES', {day:'2-digit',month:'2-digit',year:'numeric'}); 
                                    } catch(e){ return data; }
                                }
                            },
                            {
                                data: null,
                                render: function (data, type, row) {
                                     return `<a href="crm_cliente_detalle.php?id=${row.id}" class="btn btn-sm btn-outline-primary" title="Ver/Editar Cliente y Añadir Interacciones"><i class="bi bi-pencil-square"></i></a>`;
                                     // O un botón para un modal de edición más simple si prefieres no cambiar de página
                                     // return `<button type="button" class="btn btn-sm btn-outline-success edit-client-btn" data-id="${row.id}" title="Editar Cliente"><i class="bi bi-pencil"></i></button>`;
                                },
                                orderable: false, searchable: false, className: 'text-center actions-column'
                            }
                        ],
                        responsive: true,
                        language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json" },
                        order: [[7, 'desc']] // Ordenar por fecha_creacion (índice 7) descendente
                    });
                } catch (e) {
                     console.error("Error inicializando DataTables para Clientes:", e);
                     $('#clientsTable tbody').html('<tr><td colspan="9" class="text-center text-danger">Error al inicializar la tabla de clientes.</td></tr>');
                }

                // Manejar envío del formulario para añadir cliente
                $('#addClientForm').on('submit', function(e) {
                    e.preventDefault();
                    const feedbackDiv = $('#addClientFormFeedback');
                    feedbackDiv.html('').removeClass('alert alert-danger alert-success');
                    
                    const submitButton = $(this).find('button[type="submit"]');
                    const originalButtonText = submitButton.html();
                    submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...').prop('disabled', true);


                    // Recolectar datos del formulario
                    const form = $(this);
                    const clientData = {};
                    form.find('input, select, textarea').each(function() {
                        const input = $(this);
                        const name = input.attr('name');
                        let value = input.val();

                        if (name) {
                            if (input.is(':checkbox')) {
                                clientData[name] = input.is(':checked') ? value : '';
                            } else if (input.is(':radio')) {
                                if (input.is(':checked')) {
                                    clientData[name] = value;
                                }
                            } else {
                                clientData[name] = value || '';
                            }
                        }
                    });

                    console.log("Datos a enviar (CRM):", clientData);

                    $.ajax({
                        url: '../api/crear_crm_cliente.php', // Script PHP para crear cliente
                        type: 'POST',
                        data: JSON.stringify(clientData),
                        contentType: 'application/json; charset=utf-8',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                feedbackDiv.html('Cliente añadido con éxito' + (response.new_client_id ? ` (ID: ${response.new_client_id})` : '')).addClass('alert alert-success').fadeIn();
                                setTimeout(() => {
                                    $('#addClientModal').modal('hide');
                                    $('#addClientForm')[0].reset();
                                    feedbackDiv.fadeOut().html('').removeClass('alert alert-success');
                                }, 2000); // Cerrar modal y resetear después de 2 segundos

                                if (clientsDataTable) {
                                    clientsDataTable.ajax.reload(); // Recargar la tabla
                                }
                            } else {
                                feedbackDiv.html('Error: ' + (response.message || 'No se pudo añadir el cliente.')).addClass('alert alert-danger').fadeIn();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Error AJAX al crear cliente:", xhr.responseText);
                            feedbackDiv.html('Error en el servidor al añadir cliente. Detalles: ' + (xhr.responseText || error)).addClass('alert alert-danger').fadeIn();
                        },
                        complete: function() {
                            submitButton.html(originalButtonText).prop('disabled', false);
                        }
                    });
                });

                // Limpiar feedback del modal cuando se cierra
                $('#addClientModal').on('hidden.bs.modal', function () {
                    $('#addClientFormFeedback').html('').removeClass('alert alert-danger alert-success').hide();
                    $('#addClientForm')[0].reset();
                });

            }); // Fin $(document).ready
        } // Fin else (jQuery definido)
    </script>

</body>
</html>