    </div> <!-- End page-content -->
</main> <!-- End main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (sidebarToggle && sidebar && sidebarOverlay) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
        });
        
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
        });
    }
    
    // Sidebar Search Functionality
    const searchInput = document.getElementById('sidebarSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const menuItems = document.querySelectorAll('.sidebar-menu-item');
            
            menuItems.forEach(function(item) {
                const link = item.querySelector('.sidebar-menu-link');
                const text = link.textContent.toLowerCase();
                
                if (text.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});
</script>
</body>
</html>