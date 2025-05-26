<?php
// Este archivo asume que auth_check.php ya ha sido incluido
// y la sesión verificada en el script que lo llama (ej. index.php, crm.php, catalogo.php).
// No incluye session_start() aquí, ya que debe estar antes de cualquier salida.

// Determinar la página actual para la clase 'active'
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header id="header" class="header dashboard-header fixed-top bg-light shadow-sm">
  <div class="container-fluid container-xl position-relative d-flex align-items-center">

    <a href="index.php" class="logo d-flex align-items-center me-auto">
      <img src="../assets/img/logo.jfif" alt="BLISS Industrial Services Logo">
      <span class="ms-2 d-none d-lg-inline sitename-dashboard">Dashboard BLISS</span>
    </a>

    <!-- Navegación del Dashboard -->
    <nav class="dashboard-nav mx-auto"> <!-- mx-auto para centrar si hay espacio, o ajustar con me-auto en logo y ms-auto en user-info -->
        <ul class="d-flex align-items-center list-unstyled mb-0">
            <li class="me-2 me-lg-3">
                <a href="index.php" class="nav-link px-2 <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart-line-fill me-1 d-none d-sm-inline"></i>
                    <span class="d-sm-inline">Auditorías</span>
                    <span class="d-inline d-sm-none" title="Auditorías"><i class="bi bi-bar-chart-line-fill"></i></span>
                </a>
            </li>
            <li class="me-2 me-lg-3">
                <a href="crm.php" class="nav-link px-2 <?php echo ($currentPage == 'crm.php' || $currentPage == 'crm_cliente_detalle.php') ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill me-1 d-none d-sm-inline"></i>
                    <span class="d-sm-inline">CRM</span>
                    <span class="d-inline d-sm-none" title="CRM"><i class="bi bi-people-fill"></i></span>
                </a>
            </li>
            <li>
                <a href="catalogo.php" class="nav-link px-2 <?php echo ($currentPage == 'catalogo.php') ? 'active' : ''; ?>">
                    <i class="bi bi-grid-1x2-fill me-1 d-none d-sm-inline"></i>
                    <span class="d-sm-inline">Catálogo</span>
                    <span class="d-inline d-sm-none" title="Catálogo"><i class="bi bi-grid-1x2-fill"></i></span>
                </a>
            </li>
            <!-- Añadir más enlaces al dashboard aquí si es necesario -->
        </ul>
    </nav>
    <!-- FIN Navegación del Dashboard -->

     <div class="d-flex align-items-center ms-auto"> <!-- ms-auto para empujar a la derecha -->
         <span class="me-3 text-muted small d-none d-md-inline">Hola, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
         <a href="logout.php" class="btn btn-sm btn-outline-secondary">
             <i class="bi bi-box-arrow-right me-1 d-none d-sm-inline"></i>
             <span class="d-sm-inline">Cerrar Sesión</span>
             <span class="d-inline d-sm-none" title="Cerrar Sesión"><i class="bi bi-box-arrow-right"></i></span>
         </a>
     </div>
  </div>
</header>