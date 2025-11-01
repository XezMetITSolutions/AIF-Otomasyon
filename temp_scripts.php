    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true
        });

        // Add system status styles
        const style = document.createElement('style');
        style.textContent = `
            .status-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px 0;
                border-bottom: 1px solid #f1f3f4;
            }
            .status-item:last-child {
                border-bottom: none;
            }
            .status-indicator {
                width: 12px;
                height: 12px;
                border-radius: 50%;
            }
            .status-indicator.success {
                background: #28a745;
                box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
            }
            .status-indicator.warning {
                background: #ffc107;
                box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
            }
            .status-indicator.danger {
                background: #dc3545;
                box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
            }
        `;
        document.head.appendChild(style);

        // Add smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';

        // Add loading animation
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
        });
    </script>
</body>
</html>

