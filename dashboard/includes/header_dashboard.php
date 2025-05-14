<?php
// auth_check.php ya debería haber sido incluido por la página que carga este header
// y session_start() ya debería estar activo.
?>
<header id="header" class="header dashboard-header fixed-top bg-light shadow-sm">
  <div class="container-fluid container-xl position-relative d-flex align-items-center">
    <a href="index.php" class="logo d-flex align-items-center me-auto">
      <!-- Ruta relativa desde /dashboard/includes/ hasta /assets/ -->
      <img src="../assets/img/logo.jfif" alt="BLISS Logo">
      <span class="ms-2 d-none d-sm-inline sitename-dashboard">Dashboard BLISS</span>
    </a>

    <!-- === NUEVA NAVEGACIÓN DEL DASHBOARD === -->
    <nav class="dashboard-nav ms-auto me-3">
        <ul class="d-flex align-items-center list-unstyled mb-0">
            <li class="nav-item px-2">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''); ?>" href="index.php">
                    <i class="bi bi-clipboard-data-fill me-1"></i> Auditorías
                </a>
            </li>
            <li class="nav-item px-2">
                <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'crm.php' ? 'active' : ''); ?>" href="crm.php">
                    <i class="bi bi-people-fill me-1"></i> CRM
                </a>
            </li>
            <!-- Aquí puedes añadir más enlaces en el futuro -->
        </ul>
    </nav>
    <!-- === FIN NUEVA NAVEGACIÓN === -->

     <div class="d-flex align-items-center">
         <span class="me-3 text-muted small d-none d-md-inline">Hola, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
         <a href="logout.php" class="btn btn-sm btn-outline-secondary">
             <i class="bi bi-box-arrow-right me-1"></i> Cerrar Sesión
         </a>
     </div>
  </div>
</header>