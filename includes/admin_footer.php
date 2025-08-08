<?php
// Verificar si la sesión está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
        </main>

        <footer class="admin-footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-section">
                        <h4><i class="fas fa-tachometer-alt"></i> Panel de Administración</h4>
                        <ul class="footer-links">
                            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                            <li><a href="topics.php"><i class="fas fa-book"></i> Gestión de Temas</a></li>
                            <li><a href="questions.php"><i class="fas fa-question-circle"></i> Gestión de Preguntas</a></li>
                            <li><a href="users.php"><i class="fas fa-users"></i> Gestión de Usuarios</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h4><i class="fas fa-server"></i> Sistema</h4>
                        <ul class="footer-links">
                            <li><i class="fas fa-code-branch"></i> Versión: 1.0.0</li>
                            <li><i class="fab fa-php"></i> PHP: <?php echo phpversion(); ?></li>
                            <li><i class="fas fa-database"></i> Base de datos: MySQL</li>
                            <li><i class="fas fa-user-shield"></i> Usuario: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h4><i class="fas fa-life-ring"></i> Soporte</h4>
                        <ul class="footer-links">
                            <li><a href="mailto:soporte@ingles.com"><i class="fas fa-envelope"></i> Contactar Soporte</a></li>
                            <li><a href="/ingles/docs/" target="_blank"><i class="fas fa-book-open"></i> Documentación</a></li>
                            <li><a href="<?= nav_url('logout') ?>"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <p><i class="fas fa-copyright"></i> Sistema de Repaso de Inglés <?php echo date('Y'); ?> - Todos los derechos reservados</p>
                    <p class="server-time"><i class="fas fa-clock"></i> Hora del servidor: <?php echo date('d/m/Y H:i:s'); ?></p>
                </div>
            </div>
        </footer>

        <!-- Font Awesome para iconos -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        
        <!-- Toastify CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
        
        <!-- Scripts de administración -->
        <script src="/ingles/assets/js/admin.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
        
        <?php if (isset($_SESSION['message'])): ?>
            <script>
                // Mostrar notificación de mensaje flash
                Toastify({
                    text: "<?php echo addslashes($_SESSION['message']); ?>",
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                    stopOnFocus: true,
                    className: "toast-notification",
                    style: {
                        boxShadow: "0 4px 15px rgba(0, 0, 0, 0.2)",
                        borderRadius: "8px",
                        fontFamily: "'Inter', sans-serif"
                    }
                }).showToast();
            </script>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <style>
            :root {
                --footer-bg: #2c3e50;
                --footer-text: #ecf0f1;
                --footer-link: #bdc3c7;
                --footer-link-hover: #3498db;
                --footer-border: #34495e;
                --footer-accent: #3498db;
                --footer-bottom-bg: #1a252f;
            }
            
            .admin-footer {
                background: var(--footer-bg);
                color: var(--footer-text);
                padding: 3rem 0 0;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                margin-top: 3rem;
                border-top: 5px solid var(--footer-accent);
            }
            
            .admin-footer .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 2rem;
            }
            
            .footer-content {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 2rem;
                margin-bottom: 2rem;
            }
            
            .footer-section h4 {
                color: white;
                font-size: 1.2rem;
                margin-bottom: 1.5rem;
                padding-bottom: 0.75rem;
                border-bottom: 2px solid var(--footer-accent);
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            
            .footer-section h4 i {
                color: var(--footer-accent);
                font-size: 1.1rem;
            }
            
            .footer-links {
                list-style: none;
                padding: 0;
                margin: 0;
            }
            
            .footer-links li {
                margin-bottom: 0.8rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            
            .footer-links a {
                color: var(--footer-link);
                text-decoration: none;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }
            
            .footer-links a:hover {
                color: var(--footer-link-hover);
                transform: translateX(5px);
            }
            
            .footer-links a i {
                width: 20px;
                text-align: center;
            }
            
            .footer-links i {
                color: var(--footer-accent);
                font-size: 0.9rem;
                width: 20px;
                text-align: center;
            }
            
            .footer-bottom {
                background: var(--footer-bottom-bg);
                padding: 1.5rem 0;
                text-align: center;
                font-size: 0.9rem;
                border-top: 1px solid var(--footer-border);
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .footer-bottom p {
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
            }
            
            .footer-bottom i {
                color: var(--footer-accent);
            }
            
            .server-time {
                font-size: 0.85rem;
                color: var(--footer-link);
            }
            
            .toast-notification {
                font-weight: 500;
            }
            
            @media (max-width: 768px) {
                .footer-content {
                    grid-template-columns: 1fr;
                }
                
                .footer-section {
                    margin-bottom: 1.5rem;
                }
                
                .footer-bottom {
                    flex-direction: column;
                    text-align: center;
                }
            }
        </style>
    </body>
</html>