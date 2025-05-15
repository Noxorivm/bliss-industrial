<?php
// ... (inicio del archivo, auth_check, include config/database.php igual que antes) ...
require_once 'auth_check.php';
require_once '../config/database.php';

$cliente_id = $_GET['id'] ?? null;
$cliente = null;
$interacciones = [];
$error_message = '';

if (!$cliente_id || !filter_var($cliente_id, FILTER_VALIDATE_INT)) { /* ... (manejo ID inválido) ... */ exit; }

try {
    $pdo = getPDO();
    $stmtCliente = $pdo->prepare("SELECT * FROM " . DB_TABLE_CRM . " WHERE id = :id");
    $stmtCliente->bindParam(':id', $cliente_id, PDO::PARAM_INT);
    $stmtCliente->execute();
    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) { /* ... (manejo cliente no encontrado) ... */ exit; }

    // --- Obtener interacciones con TUS nombres de columna ---
    $stmtInteracciones = $pdo->prepare("SELECT id, cliente_id, usuario_crm_id, tipo_interaccion, fecha_interaccion, resumen_interaccion, proximo_paso, fecha_proximo_paso, creado_en FROM " . DB_TABLE_INTERACCIONES . " WHERE cliente_id = :cliente_id ORDER BY fecha_interaccion DESC, creado_en DESC");
    $stmtInteracciones->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
    $stmtInteracciones->execute();
    $interacciones = $stmtInteracciones->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) { /* ... (manejo error DB) ... */ }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- ... (Head igual que antes) ... -->
</head>
<body class="dashboard-body">

    <?php include_once 'includes/header_dashboard.php'; ?>

    <main class="dashboard-main">
        <div class="container-fluid">
            <!-- ... (Título y Formulario de Edición del Cliente igual que antes) ... -->

                <!-- ================================================= -->
                <!-- === Historial de Interacciones (Adaptado) === -->
                <!-- ================================================= -->
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
                                                <i class="<?php /* ... (lógica de icono igual que antes) ... */
                                                    switch (strtolower($interaccion['tipo_interaccion'])) {
                                                        case 'llamada': echo 'bi bi-telephone-fill'; break;
                                                        case 'email enviado': echo 'bi bi-envelope-arrow-up-fill'; break; // Ajustar
                                                        case 'email recibido': echo 'bi bi-envelope-arrow-down-fill'; break; // Ajustar
                                                        case 'reunión': echo 'bi bi-calendar-event-fill'; break;
                                                        case 'whatsapp': echo 'bi bi-whatsapp'; break;
                                                        case 'nota interna': echo 'bi bi-sticky-fill'; break; // Ajustar
                                                        default: echo 'bi bi-chat-dots-fill';
                                                    }
                                                ?> me-2 text-primary"></i>
                                                <?php echo htmlspecialchars($interaccion['tipo_interaccion']); ?>
                                            </h6>
                                            <small class="text-muted" title="<?php echo htmlspecialchars($interaccion['fecha_interaccion']); ?>">
                                                <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($interaccion['fecha_interaccion']))); ?>
                                            </small>
                                        </div>
                                        <!-- Usar resumen_interaccion en lugar de descripcion -->
                                        <p class="mb-1 interaction-description"><?php echo nl2br(htmlspecialchars($interaccion['resumen_interaccion'])); ?></p>
                                        
                                        <!-- La columna 'resultado' de tu tabla parece ser mi 'proximo_paso' conceptualmente,
                                             y 'proximo_paso' de tu tabla podría ser una descripción de esa tarea.
                                             Ajusta las etiquetas según el significado real de tus columnas.
                                             Aquí asumo que 'proximo_paso' de tu tabla es la tarea y 'fecha_proximo_paso' es su fecha.
                                             La columna 'resultado' del ejemplo anterior no parece tener un equivalente directo en tu tabla.
                                        -->
                                        <?php if (!empty($interaccion['proximo_paso'])): ?>
                                            <small class="d-block"><strong>Próximo Paso:</strong> <?php echo htmlspecialchars($interaccion['proximo_paso']); ?>
                                            <?php if (!empty($interaccion['fecha_proximo_paso']) && $interaccion['fecha_proximo_paso'] != '0000-00-00'): ?>
                                                (para el <?php echo htmlspecialchars(date("d/m/Y", strtotime($interaccion['fecha_proximo_paso']))); ?>)
                                            <?php endif; ?>
                                            </small>
                                        <?php endif; ?>

                                        <!-- Usar usuario_crm_id y creado_en -->
                                        <small class="text-muted d-block mt-1">
                                            Registrado por: <?php echo htmlspecialchars($interaccion['usuario_crm_id'] ?? 'Sistema'); ?>
                                            el <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($interaccion['creado_en']))); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- === FIN Historial de Interacciones === -->

            <?php else: ?>
                <!-- ... (mensaje cliente no encontrado) ... -->
            <?php endif; ?>
        </div>
    </main>

    <!-- ... (Footer y Modal de Añadir Interacción igual que antes, PERO verifica los 'name' de los inputs del modal para que coincidan con lo que espera crear_crm_interaccion.php:
             ej. name="descripcion" para el textarea del resumen, name="proximo_paso" para el textarea de la tarea siguiente, etc.) ... -->
    
    <!-- Modal para Añadir Interacción (Asegúrate que los names de los inputs coincidan con las variables en crear_crm_interaccion.php) -->
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
                        <label for="int_fecha_interaccion" class="form-label">Fecha y Hora Interacción *</label>
                        <input type="datetime-local" class="form-control" id="int_fecha_interaccion" name="fecha_interaccion" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
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
                            <option value="Nota Interna">Nota Interna</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <!-- El 'name' de este textarea es 'descripcion' -->
                    <label for="int_descripcion" class="form-label">Resumen / Descripción *</label>
                    <textarea class="form-control" id="int_descripcion" name="descripcion" rows="4" required></textarea>
                </div>
                <div class="row">
                    <!-- El 'name' de este input es 'resultado', que en el PHP de ejemplo anterior no se usaba directamente.
                         Si quieres que 'resultado' del formulario se guarde en 'proximo_paso' de la DB,
                         la lógica en crear_crm_interaccion.php tendría que mapearlo o cambiar el 'name' aquí.
                         Por ahora, lo dejaré como 'resultado' y el script PHP lo ignora (o puedes añadirlo).
                         Si 'proximo_paso' del formulario se refiere a la TAREA, entonces está bien.
                    -->
                    <div class="col-md-6 mb-3">
                        <label for="int_resultado" class="form-label">Resultado de esta interacción (opcional)</label>
                        <input type="text" class="form-control" id="int_resultado" name="resultado_interaccion_actual" placeholder="Ej: Interesado, Enviar propuesta...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="int_fecha_proximo_paso" class="form-label">Fecha Próximo Paso (opcional)</label>
                        <input type="date" class="form-control" id="int_fecha_proximo_paso" name="fecha_proximo_paso">
                    </div>
                </div>
                <div class="mb-3">
                     <!-- El 'name' de este textarea es 'proximo_paso' -->
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


    <!-- ... (Scripts JS igual que antes, asegúrate que el AJAX de #addInteractionForm usa los 'name' correctos) ... -->
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function() {
            // === Formulario EDITAR CLIENTE (igual que antes) ===
            $('#editClientForm').on('submit', function(e) { /* ... (código igual que antes) ... */ });

            // === NUEVO: Formulario AÑADIR INTERACCIÓN ===
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
                    // Asegurarse de que el campo tiene un nombre y no está deshabilitado
                    if (input.attr('name') && !input.prop('disabled')) {
                        interactionData[input.attr('name')] = input.val() || '';
                    }
                });
                // 'cliente_id_interaction' se toma del input hidden que ya tiene el valor correcto.

                console.log("Datos de interacción a enviar:", interactionData);

                $.ajax({
                    url: '../api/crear_crm_interaccion.php', // Script PHP
                    type: 'POST',
                    data: JSON.stringify(interactionData),
                    contentType: 'application/json; charset=utf-8',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            feedbackDiv.html('Interacción guardada con éxito.').addClass('alert alert-success').fadeIn();
                            setTimeout(() => {
                                $('#addInteractionModal').modal('hide');
                                // No resetear el form aquí, se hace en 'hidden.bs.modal'
                                location.reload(); // Recargar la página para ver la nueva interacción
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
             // Limpiar feedback y resetear fecha del modal de interacción cuando se cierra
            $('#addInteractionModal').on('hidden.bs.modal', function () {
                $('#addInteractionFormFeedback').html('').removeClass('alert alert-danger alert-success').hide();
                $('#addInteractionForm')[0].reset();
                const now = new Date();
                const year = now.getFullYear();
                const month = (now.getMonth() + 1).toString().padStart(2, '0');
                const day = now.getDate().toString().padStart(2, '0');
                const hours = now.getHours().toString().padStart(2, '0');
                const minutes = now.getMinutes().toString().padStart(2, '0');
                $('#int_fecha_interaccion').val(`${year}-${month}-${day}T${hours}:${minutes}`);
            });
        });
    </script>
</body>
</html>