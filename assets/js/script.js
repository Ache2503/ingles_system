// Funcionalidad básica para el menú móvil
document.addEventListener('DOMContentLoaded', function() {
    // Menú móvil
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('nav ul');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            nav.classList.toggle('active');
        });
    }
    
    // Temporizador para el modo examen
    if (document.querySelector('.timer')) {
        let timeLeft = 1800; // 30 minutos en segundos
        const timer = setInterval(function() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            document.querySelector('.timer').textContent = 
                `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                alert('¡Tiempo terminado!');
                document.getElementById('quizForm').submit();
            } else {
                timeLeft--;
            }
        }, 1000);
    }
    
    // Validación de formularios
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredInputs = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    input.style.borderColor = 'red';
                    isValid = false;
                } else {
                    input.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor completa todos los campos requeridos.');
            }
        });
    });
});

// Mostrar/ocultar respuestas de ejercicios
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar respuestas
    document.querySelectorAll('.show-answer').forEach(button => {
        button.addEventListener('click', function() {
            const answer = this.nextElementSibling;
            answer.classList.toggle('show');
            this.textContent = answer.classList.contains('show') ? 
                'Ocultar respuesta' : 'Mostrar respuesta';
        });
    });
    
    // Navegación por pestañas (para temas complejos)
    const tabButtons = document.querySelectorAll('.tab-button');
    if (tabButtons.length > 0) {
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Ocultar todos los contenidos
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Desactivar todos los botones
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Activar el seleccionado
                document.getElementById(tabId).classList.add('active');
                this.classList.add('active');
            });
        });
    }
});