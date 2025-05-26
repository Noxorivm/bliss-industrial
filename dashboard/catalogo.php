<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$pdo = getPDO();
$servicios_unicos = [];
try {
    $stmt_servicios = $pdo->query("SELECT DISTINCT servicios_asociados FROM " . DB_TABLE_CATALOGO . " WHERE activo = 1 AND servicios_asociados IS NOT NULL AND servicios_asociados != ''");
    $raw_servicios = $stmt_servicios->fetchAll(PDO::FETCH_COLUMN);
    $temp_array = [];
    foreach ($raw_servicios as $serv_string) {
        $items = array_map('trim', explode(',', $serv_string));
        $temp_array = array_merge($temp_array, $items);
    }
    $servicios_unicos = array_values(array_unique(array_filter($temp_array)));
    sort($servicios_unicos);
} catch (\PDOException $e) {
    error_log("Error obteniendo servicios para filtro catálogo: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Automatizaciones | Dashboard BLISS</title>
    <meta name="robots" content="noindex, nofollow">
    <!-- Favicons, Fonts, Vendor CSS -->
    <link href="../assets/img/favicon.png" rel="icon">
    <link href="../assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/v/bs5/dt-2.0.5/r-3.0.2/datatables.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/catalogo.css" rel="stylesheet">
</head>
<body class="dashboard-body">

    <?php include_once 'includes/header_dashboard.php'; ?>

    <main class="dashboard-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="dashboard-title mb-0">Catálogo de Automatizaciones</h1>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-4">
                            <label for="catalogoSearch" class="form-label">Buscar por nombre o descripción:</label>
                            <input type="text" class="form-control form-control-sm" id="catalogoSearch" placeholder="Ej: Gmail, Excel, Facturas...">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label for="catalogoFilterServicio" class="form-label">Filtrar por Servicio:</label>
                            <select class="form-select form-select-sm" id="catalogoFilterServicio">
                                <option value="">Todos los Servicios</option>
                                <?php foreach ($servicios_unicos as $servicio): ?>
                                    <option value="<?php echo htmlspecialchars($servicio); ?>"><?php echo htmlspecialchars($servicio); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12 col-lg-2">
                            <button class="btn btn-outline-secondary btn-sm w-100" id="resetFiltersBtn"><i class="bi bi-x-lg"></i> Limpiar Filtros</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="catalogoContainer" class="row gy-4">
                <div class="col-12 text-center" id="catalogoLoading">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Cargando automatizaciones...</span>
                    </div>
                    <p class="mt-2">Cargando automatizaciones...</p>
                </div>
                <div class="col-12 text-center" id="catalogoNoResults" style="display: none;">
                    <p class="lead text-muted">No se encontraron automatizaciones que coincidan con tu búsqueda.</p>
                </div>
            </div>
        </div>
    </main>

    <footer class="dashboard-footer text-center py-3">
         <div class="container">
            <small class="text-muted">© <?php echo date("Y"); ?> BLISS Industrial Services. Dashboard interno.</small>
        </div>
    </footer>

    <div class="modal fade" id="automationDetailsModal" tabindex="-1" aria-labelledby="automationDetailsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="automationDetailsModalLabel">Detalles de Automatización</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="automationDetailsModalBody"></div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- DataTables JS no es necesario aquí si usamos tarjetas en lugar de tabla -->
    <script>
        // Asegurarse que el DOM está listo Y que jQuery ($) está definido
        if (typeof jQuery === 'undefined') {
            console.error('ERROR CRÍTICO: jQuery no se ha cargado. El catálogo no funcionará.');
            document.getElementById('catalogoContainer').innerHTML = '<div class="col-12"><div class="alert alert-danger text-center">Error crítico: No se pudo cargar jQuery.</div></div>';
        } else {
            $(document).ready(function() {
                console.log("jQuery cargado, inicializando catálogo...");
                let allAutomations = [];

                const catalogoContainer = $('#catalogoContainer');
                const catalogoLoading = $('#catalogoLoading');
                const catalogoNoResults = $('#catalogoNoResults');
                const searchInput = $('#catalogoSearch');
                const serviceFilter = $('#catalogoFilterServicio');
                const resetFiltersBtn = $('#resetFiltersBtn');

                function renderAutomations(automationsToRender) {
                    catalogoContainer.empty();
                    if (automationsToRender.length === 0) {
                        catalogoNoResults.show();
                        return;
                    }
                    catalogoNoResults.hide();

                    automationsToRender.forEach(auto => {
                        let shortDesc = auto.descripcion_corta || auto.descripcion_completa || 'Sin descripción corta.';
                        if (shortDesc.length > 100) {
                            shortDesc = shortDesc.substring(0, 97) + "...";
                        }

                        let servicesBadges = '';
                        if (auto.servicios_asociados) {
                            const servicesArray = auto.servicios_asociados.split(',').map(s => s.trim()).filter(s => s !== '');
                            servicesArray.forEach(service => {
                                servicesBadges += `<span class="badge bg-secondary me-1 mb-1">${service}</span> `;
                            });
                        }

                        const cardHtml = `
                            <div class="col-lg-4 col-md-6 d-flex align-items-stretch">
                                <div class="catalogo-item-trigger interactive-card h-100 w-100"
                                     data-id="${auto.id}"
                                     data-nombre="${auto.nombre_automatizacion}"
                                     data-desc-completa="${auto.descripcion_completa || ''}"
                                     data-precio="${auto.precio_estimado || 'Consultar'}"
                                     data-moneda="${auto.moneda_precio || 'EUR'}"
                                     data-servicios="${auto.servicios_asociados || ''}"
                                     data-imagen="${auto.imagen_url || ''}"> <!-- Añadido data-imagen -->
                                    <div class="prompt-card-icon"><i class="bi bi-gear-wide-connected"></i></div>
                                    <h4 class="interactive-title-card">${auto.nombre_automatizacion}</h4>
                                    <p class="small">${shortDesc}</p>
                                    <div class="mt-auto pt-2">
                                        ${servicesBadges}
                                        <p class="catalogo-price mt-2 mb-0">${auto.precio_estimado ? parseFloat(auto.precio_estimado).toLocaleString('es-ES', { style: 'currency', currency: auto.moneda_precio || 'EUR' }) : 'Consultar precio'}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                        catalogoContainer.append(cardHtml);
                    });
                }

                function fetchAndRenderAutomations() {
                    catalogoLoading.show();
                    catalogoNoResults.hide();
                    catalogoContainer.empty();

                    $.ajax({
                        url: '../api/fetch_catalogo.php',
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            catalogoLoading.hide();
                            if (response.success && response.data) {
                                allAutomations = response.data;
                                applyFilters();
                            } else {
                                catalogoNoResults.show();
                                console.error("Error del API catálogo:", response.message);
                                catalogoContainer.html('<div class="col-12"><div class="alert alert-danger">Error al cargar el catálogo: ' + (response.message || 'Respuesta inesperada.') + '</div></div>');
                            }
                        },
                        error: function(xhr, status, error) {
                            catalogoLoading.hide();
                            catalogoNoResults.show();
                            console.error("Error AJAX al cargar catálogo:", xhr.status, xhr.statusText, xhr.responseText);
                            catalogoContainer.html('<div class="col-12"><div class="alert alert-danger">Error de conexión al cargar el catálogo. Revise la consola.</div></div>');
                        }
                    });
                }

                function applyFilters() {
                    const searchTerm = searchInput.val().toLowerCase().trim();
                    const selectedService = serviceFilter.val().toLowerCase().trim();

                    const filteredAutomations = allAutomations.filter(auto => {
                        const nameMatch = auto.nombre_automatizacion.toLowerCase().includes(searchTerm);
                        const descMatch = (auto.descripcion_corta && auto.descripcion_corta.toLowerCase().includes(searchTerm)) ||
                                          (auto.descripcion_completa && auto.descripcion_completa.toLowerCase().includes(searchTerm));
                        const tagsMatch = auto.tags && auto.tags.toLowerCase().includes(searchTerm);

                        const serviceMatch = selectedService === '' || (auto.servicios_asociados && auto.servicios_asociados.toLowerCase().split(',').map(s=>s.trim()).includes(selectedService));

                        return (nameMatch || descMatch || tagsMatch) && serviceMatch;
                    });
                    renderAutomations(filteredAutomations);
                }

                searchInput.on('keyup input', applyFilters); // 'input' para que reaccione a borrar con la X
                serviceFilter.on('change', applyFilters);
                resetFiltersBtn.on('click', function() {
                    searchInput.val('');
                    serviceFilter.val('');
                    applyFilters();
                });

                catalogoContainer.on('click', '.catalogo-item-trigger', function() {
                    const card = $(this);
                    const modalTitle = $('#automationDetailsModalLabel');
                    const modalBody = $('#automationDetailsModalBody');

                    modalTitle.text(card.data('nombre') || 'Detalle de Automatización');
                    
                    let imageUrl = card.data('imagen');
                    let imageHtml = '';
                    if(imageUrl){
                        imageHtml = `<div class="text-center mb-3"><img src="${imageUrl}" alt="${card.data('nombre')}" class="img-fluid rounded" style="max-height: 200px;"></div>`;
                    }

                    let bodyContent = `
                        ${imageHtml}
                        <p><strong>Descripción Completa:</strong></p>
                        <p>${card.data('desc-completa') || 'No disponible.'}</p>
                        <hr>
                        <p><strong>Servicios Asociados:</strong> ${card.data('servicios') || 'No especificados.'}</p>
                        <hr>
                        <p><strong>Precio Estimado:</strong> ${card.data('precio') === 'Consultar' ? 'Consultar' : parseFloat(card.data('precio')).toLocaleString('es-ES', { style: 'currency', currency: card.data('moneda') || 'EUR' })}</p>
                    `;
                    modalBody.html(bodyContent);

                    const detailModalEl = document.getElementById('automationDetailsModal');
                    const detailModal = bootstrap.Modal.getInstance(detailModalEl) || new bootstrap.Modal(detailModalEl);
                    detailModal.show();
                });

                fetchAndRenderAutomations(); // Carga inicial
            }); // Cierre de $(document).ready(function()
        } // Cierre del else (jQuery definido)
    </script>

</body>
</html>