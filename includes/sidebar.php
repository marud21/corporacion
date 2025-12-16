<?php
// Obtener la ruta del archivo actual
$currentFile = basename($_SERVER['SCRIPT_NAME']);
?>
<!-- Sidebar -->
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentFile == 'socios.php') ? 'active' : ''; ?>" href="/corve/socios.php">
                    <i class="fas fa-users me-2"></i>
                    Socios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-trophy me-2"></i>
                    Torneos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-users me-2"></i>
                    Equipos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-user-friends me-2"></i>
                    Jugadores
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentFile == 'pagos.php') ? 'active' : ''; ?>" href="/corve/views/socios/pagos.php">
                    <i class="fas fa-file-invoice-dollar me-2"></i>
                    Pagos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($currentFile == 'mensualidades.php') ? 'active' : ''; ?>" href="/corve/views/socios/mensualidades.php">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Mensualidades
                </a>
            </li>
            <li class="nav-item border-top mt-3 pt-3">
                <a class="nav-link <?php echo ($currentFile == 'tarifas.php') ? 'active' : ''; ?>" href="/corve/views/admin/tarifas.php">
                    <i class="fas fa-dollar-sign me-2"></i>
                    Tarifas
                </a>
            </li>
        </ul>
    </div>
</nav>
