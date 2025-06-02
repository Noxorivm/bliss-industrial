<?php
// --- Habilitar Errores (SOLO PARA DEPURACIÓN - Comentar en producción) ---
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
// --- Fin Habilitar Errores ---

require_once 'auth_check.php'; // Verificar sesión
require_once '../config/database.php'; // Incluir configuración de base de datos y conexión PDO

$cliente_id = $_GET['id'] ?? null;
$cliente = null;
$interacciones = []; // Array para guardar las interacciones
$error_message = '';

if (!$cliente_id || !filter_var($cliente_id, FILTER_VALIDATE_INT)) {
    // Si no hay ID o no es un entero, redirigir a crm.php
    header('Location: crm.php?error=invalid_id');
    exit;
}

try {
    $pdo = getPDO(); // Obtener la instancia de PDO desde database.php

    // Obtener datos del cliente
    $stmtCliente = $pdo->prepare("SELECT * FROM " . DB_TABLE_CRM . " WHERE id = :id");
    $stmtCliente->bindParam(':id', $cliente_id, PDO::PARAM_INT);
    $stmtCliente->execute();
    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        // Cliente no encontrado, redirigir a crm.php
        header('Location: crm.php?error=not_found');
        exit;
    }

    // Obtener interacciones para este cliente (usando los nombres de columna de tu tabla)
    $stmtInteracciones = $pdo->prepare(
        "SELECT id, cliente_id, usuario_crm_id, tipo_interaccion, fecha_interaccion, resumen_interaccion, resultado_interaccion, proximo_paso, fecha_proximo_paso, creado_en
         FROM " . DB_TABLE_INTERACCIONES . "
         WHERE cliente_id = :cliente_id
         ORDER BY fecha_interaccion DESC, creado_en DESC"
    ); // Asegúrate que tu tabla tiene 'resultado_interaccion' si lo seleccionas
    $stmtInteracciones->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
    $stmtInteracciones->execute();
    $interacciones = $stmtInteracciones->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    error_log("Error fetching client details or interactions for ID {$cliente_id}: " . $e->getMessage());
    $error_message = "Error al cargar los datos del cliente o sus interacciones.";
    if (!$cliente) { // Si el cliente no se cargó, error crítico para esta página
        die("Error crítico al cargar datos del cliente. Revise los logs del servidor.");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Cliente #<?php echo htmlspecialchars($cliente['id'] ?? 'Error'); ?> | Dashboard BLISS</title>
    <meta name="robots" content="noindex, nofollow">

    <!-- Favicons, Fonts, Vendor CSS -->
    <link href="../assets/img/favicon.png" rel="icon">
    <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet"> <!-- Asegúrate que los estilos del modal y lista de interacciones están aquí -->
</head>
<body class="dashboard-body">

    <?php include_once 'includes/header_dashboard.php'; ?>

    <main class="dashboard-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="dashboard-title mb-0">
                    <a href="crm.php" class="text-decoration-none me-2" title="Volver al listado CRM"><i class="bi bi-arrow-left-circle"></i></a>
                    Detalle Cliente: <?php echo htmlspecialchars($cliente['nombre_empresa'] ?? 'Cliente no encontrado'); ?>
                </h1>
            </div>

            <?php if ($error_message && !$cliente): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <a href="crm.php" class="btn btn-primary">Volver al Listado CRM</a>
            <?php elseif ($cliente): ?>
                <!-- Formulario de Edición del Cliente -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Información del Cliente #<?php echo htmlspecialchars($cliente['id']); ?></h5>
                        <p class="card-subtitle mb-3 text-muted small">
                            Creado: <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($cliente['fecha_creacion'] ?? 'now'))); ?> |
                            Última Modificación: <?php
                                $fecha_mod_str = 'Nunca modificado';
                                if (isset($cliente['fecha_modificacion']) && $cliente['fecha_modificacion'] !== null) {
                                    if ($cliente['fecha_modificacion'] !== $cliente['fecha_creacion']) {
                                        $fecha_mod_str = htmlspecialchars(date("d/m/Y H:i", strtotime($cliente['fecha_modificacion'])));
                                    }
                                }
                                echo $fecha_mod_str;
                            ?>
                        </p>
                        <?php if ($error_message && $cliente): // Mostrar error de interacciones si ocurrió pero el cliente sí cargó ?>
                            <div class="alert alert-warning"><?php echo $error_message; ?> (Error cargando interacciones)</div>
                        <?php endif; ?>

                        <form id="editClientForm">
                            <input type="hidden" name="cliente_id" value="<?php echo htmlspecialchars($cliente['id']); ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="crm_nombre_empresa_edit" class="form-label">Nombre Empresa *</label>
                                    <input type="text" class="form-control" id="crm_nombre_empresa_edit" name="nombre_empresa" value="<?php echo htmlspecialchars($cliente['nombre_empresa']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="crm_persona_contacto_edit" class="form-label">Persona de Contacto</label>
                                    <input type="text" class="form-control" id="crm_persona_contacto_edit" name="persona_contacto" value="<?php echo htmlspecialchars($cliente['persona_contacto'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="crm_email_contacto_edit" class="form-label">Email Contacto</label>
                                    <input type="email" class="form-control" id="crm_email_contacto_edit" name="email_contacto" value="<?php echo htmlspecialchars($cliente['email_contacto'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="crm_telefono_contacto_edit" class="form-label">Teléfono Contacto</label>
                                    <input type="tel" class="form-control" id="crm_telefono_contacto_edit" name="telefono_contacto" value="<?php echo htmlspecialchars($cliente['telefono_contacto'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="crm_origen_lead_edit" class="form-label">Origen Lead</label>
                                    <select class="form-select" id="crm_origen_lead_edit" name="origen_lead">
                                        <?php $origenes = ['Manual CRM', 'Form Auditoría', 'Form Contacto', 'Referido', 'Evento', 'Otro']; ?>
                                        <?php foreach ($origenes as $origen): ?>
                                            <option value="<?php echo $origen; ?>" <?php echo (($cliente['origen_lead'] ?? '') == $origen) ? 'selected' : ''; ?>><?php echo $origen; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="crm_estado_lead_edit" class="form-label">Estado Lead</label>
                                    <select class="form-select" id="crm_estado_lead_edit" name="estado_lead">
                                        <?php $estados = ['Nuevo', 'Contactado', 'En Seguimiento', 'Propuesta Enviada', 'Negociación', 'Ganado', 'Perdido', 'Descartado']; ?>
                                        <?php foreach ($estados as $estado): ?>
                                            <option value="<?php echo $estado; ?>" <?php echo (($cliente['estado_lead'] ?? '') == $estado) ? 'selected' : ''; ?>><?php echo $estado; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="crm_notas_generales_cliente_edit" class="form-label">Notas Generales</label>
                                <textarea class="form-control" id="crm_notas_generales_cliente_edit" name="notas_generales_cliente" rows="4"><?php echo htmlspecialchars($cliente['notas_generales_cliente'] ?? ''); ?></textarea>
                            </div>

                            <div id="editClientFormFeedback" class="mt-3"></div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn cta-principal">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Historial de Interacciones -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0 d-flex justify-content-between align-items-center">
                            Historial de Interacciones
                            <button type="button" class="btn cta-principal btn-sm" data-bs-toggle="modal" data-bs-target="#addInteractionModal">
                                <i class="bi bi-plus-circle-fill me-1"></i> Nueva Interacción
                            </button>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($interacciones)): ?>
                            <p class="text-muted text-center mt-3">No hay interacciones registradas para este cliente.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush interaction-list mt-2">
                                <?php foreach ($interacciones as $interaccion): ?>
                                    <div class="list-group-item interaction-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 fw-bold">
                                                <i class="<?php
                                                    $tipo_lower = strtolower($interaccion['tipo_interaccion'] ?? '');
                                                    switch ($tipo_lower) {
                                                        case 'llamada': echo 'bi bi-telephone-fill'; break;
                                                        case 'email enviado': echo 'bi bi-envelope-arrow-up-fill'; break;
                                                        case 'email recibido': echo 'bi bi-envelope-arrow-down-fill'; break;
                                                        case 'reunión': case 'reunion': echo 'bi bi-calendar-event-fill'; break;
                                                        case 'whatsapp': echo 'bi bi-whatsapp'; break;
                                                        case 'nota interna': echo 'bi bi-sticky-fill'; break;
                                                        case 'formulario auditoría': echo 'bi bi-file-earmark-text-fill'; break;
                                                        default: echo 'bi bi-chat-dots-fill';
                                                    }
                                                ?> me-2 text-primary"></i>
                                                <?php echo htmlspecialchars($interaccion['tipo_interaccion'] ?? 'N/A'); ?>
                                            </h6>
                                            <small class="text-muted" title="<?php echo htmlspecialchars($interaccion['fecha_interaccion'] ?? ''); ?>">
                                                <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($interaccion['fecha_interaccion'] ?? 'now'))); ?>
                                            </small>
                                        </div>
                                        <p class="mb-1 interaction-description"><?php echo nl2br(htmlspecialchars($interaccion['resumen_interaccion'] ?? 'Sin descripción')); ?></p>
                                        <?php if (!empty($interaccion['resultado_interaccion'])): // Nombre de columna de tu tabla ?>
                                            <small class="d-block"><strong>Resultado:</strong> <?php echo htmlspecialchars($interaccion['resultado_interaccion']); ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($interaccion['proximo_paso'])): ?>
                                            <small class="d-block"><strong>Próximo Paso:</strong> <?php echo htmlspecialchars($interaccion['proximo_paso']); ?>
                                            <?php if (!empty($interaccion['fecha_proximo_paso']) && $interaccion['fecha_proximo_paso'] != '0000-00-00' && $interaccion['fecha_proximo_paso'] != null): ?>
                                                (para el <?php echo htmlspecialchars(date("d/m/Y", strtotime($interaccion['fecha_proximo_paso']))); ?>)
                                            <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                        <small class="text-muted d-block mt-1">
                                            Registrado por: <?php echo htmlspecialchars($interaccion['usuario_crm_id'] ?? 'Sistema'); ?>
                                            el <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($interaccion['creado_en'] ?? 'now'))); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- FIN Historial de Interacciones -->

            <?php else: ?>
                <div class="alert alert-warning">No se pudo cargar la información del cliente.</div>
                <a href="crm.php" class="btn btn-primary">Volver al Listado CRM</a>
            <?php endif; ?>
        </div>
    </main>

    <footer class="dashboard-footer text-center py-3">
        <div class="container">
            <small class="text-muted">© <?php echo date("Y"); ?> BLISS Industrial Services. Dashboard interno.</small>
        </div>
    </footer>

    <!-- Modal para Añadir Interacción -->
    <div class="modal fade" id="addInteractionModal" tabindex="-1" aria-labelledby="addInteractionModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addInteractionModalLabel">Añadir Nueva Interacción para <?php echo htmlspecialchars($cliente['nombre_empresa'] ?? 'Cliente'); ?></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="addInteractionForm">
            <input type="hidden" name="cliente_id_interaction" value="<?php echo htmlspecialchars($cliente_id); ?>">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="int_fecha_interaccion_display" class="form-label">Fecha y Hora Interacción</label>
                        <input type="text" class="form-control" id="int_fecha_interaccion_display" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="int_tipo_interaccion" class="form-label">Tipo de Interacción *</label>
                        <select class="form-select" id="int_tipo_interaccion" name="tipo_interaccion" required>
                            <option value="" disabled selected>Seleccionar tipo...</option>
                            <option value="Llamada">Llamada</option>
                            <option value="Email Enviado">Email Enviado</option>
                            <option value="Email Recibido">Email Recibido</option>
                            <option value="Reunión">Reunión</option>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="Formulario Auditoría">Formulario Auditoría</option>
                            <option value="Nota Interna">Nota Interna</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="int_descripcion" class="form-label">Resumen / Descripción *</label>
                    <textarea class="form-control" id="int_descripcion" name="descripcion" rows="4" required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="int_resultado_interaccion" class="form-label">Resultado de esta interacción (opcional)</label>
                        <input type="text" class="form-control" id="int_resultado_interaccion" name="resultado_interaccion" placeholder="Ej: Interesado, Enviar propuesta...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="int_fecha_proximo_paso" class="form-label">Fecha Próximo Paso (opcional)</label>
                        <input type="date" class="form-control" id="int_fecha_proximo_paso" name="fecha_proximo_paso">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="int_proximo_paso" class="form-label">Tarea Próximo Paso (opcional)</label>
                    <textarea class="form-control" id="int_proximo_paso" name="proximo_paso" rows="2" placeholder="Ej: Llamar el Lunes, Enviar catálogo..."></textarea>
                </div>
                 <div id="addInteractionFormFeedback" class="mt-3"></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn cta-principal">Guardar Interacción</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- FIN Modal Interacción -->


    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function() {
            // === Formulario EDITAR CLIENTE ===
            $('#editClientForm').on('submit', function(e) {
                e.preventDefault();
                const feedbackDiv = $('#editClientFormFeedback');
                feedbackDiv.html('').removeClass('alert alert-danger alert-success');
                const submitButton = $(this).find('button[type="submit"]');
                const originalButtonText = submitButton.html();
                submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...').prop('disabled', true);

                const formDataArray = $(this).serializeArray();
                let clientData = {};
                $.each(formDataArray, function(i, field){ clientData[field.name] = field.value || ''; });
                console.log("Datos a actualizar (Cliente):", clientData);

                $.ajax({
                    url: '../api/actualizar_crm_cliente.php',
                    type: 'POST', data: JSON.stringify(clientData), contentType: 'application/json; charset=utf-8', dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            feedbackDiv.html('Cliente actualizado con éxito: ' + (response.message || '')).addClass('alert alert-success').fadeIn();
                             // Actualizar dinámicamente el título de la página si el nombre de la empresa cambia
                            if (clientData.nombre_empresa) {
                                const pageTitleH1 = $('h1.dashboard-title');
                                if (pageTitleH1.length) {
                                    pageTitleH1.contents().filter(function() { return this.nodeType === 3; }).last().replaceWith(" Detalle Cliente: " + clientData.nombre_empresa);
                                }
                                $('#addInteractionModalLabel').text('Añadir Nueva Interacción para ' + clientData.nombre_empresa);
                            }
                            setTimeout(() => { feedbackDiv.fadeOut().html('').removeClass('alert alert-success alert-danger'); }, 3000);
                        } else {
                            feedbackDiv.html('Error al actualizar: ' + (response.message || 'No se pudo actualizar el cliente.')).addClass('alert alert-danger').fadeIn();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error AJAX al actualizar cliente:", xhr.responseText);
                        feedbackDiv.html('Error en el servidor al actualizar. Detalles: ' + (xhr.responseText || error)).addClass('alert alert-danger').fadeIn();
                    },
                    complete: function() {
                        submitButton.html(originalButtonText).prop('disabled', false);
                    }
                });
            });

            // === Formulario AÑADIR INTERACCIÓN ===
            $('#addInteractionForm').on('submit', function(e) {
                e.preventDefault();
                const feedbackDiv = $('#addInteractionFormFeedback');
                feedbackDiv.html('').removeClass('alert alert-danger alert-success');
                const submitButton = $(this).find('button[type="submit"]');
                const originalButtonText = submitButton.html();
                submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...').prop('disabled', true);

                const form = $(this);
                let interactionData = {};
                form.find('input, select, textarea').each(function() {
                    const input = $(this);
                    if (input.attr('name') && !input.prop('disabled')) {
                        interactionData[input.attr('name')] = input.val() || '';
                    }
                });
                console.log("Datos de interacción a enviar:", interactionData);

                $.ajax({
                    url: '../api/crear_crm_interaccion.php',
                    type: 'POST',
                    data: JSON.stringify(interactionData),
                    contentType: 'application/json; charset=utf-8',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            feedbackDiv.html('Interacción guardada con éxito.').addClass('alert alert-success').fadeIn();
                            setTimeout(() => {
                                $('#addInteractionModal').modal('hide');
                                location.reload();
                            }, 1500);
                        } else {
                            feedbackDiv.html('Error: ' + (response.message || 'No se pudo guardar la interacción.')).addClass('alert alert-danger').fadeIn();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error AJAX al crear interacción:", xhr.responseText);
                        feedbackDiv.html('Error en el servidor al guardar interacción. Detalles: ' + (xhr.responseText || error)).addClass('alert alert-danger').fadeIn();
                    },
                    complete: function() {
                        submitButton.html(originalButtonText).prop('disabled', false);
                    }
                });
            });

            function resetInteractionModal() {
                $('#addInteractionFormFeedback').html('').removeClass('alert alert-danger alert-success').hide();
                $('#addInteractionForm')[0].reset();
                const nowMadrid = new Date(new Date().toLocaleString("en-US", {timeZone: "Europe/Madrid"}));
                const year = nowMadrid.getFullYear();
                const month = (nowMadrid.getMonth() + 1).toString().padStart(2, '0');
                const day = nowMadrid.getDate().toString().padStart(2, '0');
                const hours = nowMadrid.getHours().toString().padStart(2, '0');
                const minutes = nowMadrid.getMinutes().toString().padStart(2, '0');
                $('#int_fecha_interaccion_display').val(`${day}/${month}/${year} ${hours}:${minutes}`);
            }

            $('#addInteractionModal').on('show.bs.modal', function () {
                resetInteractionModal();
            });
            $('#addInteractionModal').on('hidden.bs.modal', function () {
                resetInteractionModal();
            });
        });
    </script>
</body>
</html>