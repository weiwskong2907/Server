document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const adminLevelContainer = document.getElementById('admin-level-container');
    
    if (roleSelect && adminLevelContainer) {
        roleSelect.addEventListener('change', function() {
            if (this.value === 'admin') {
                adminLevelContainer.style.display = '';
            } else {
                adminLevelContainer.style.display = 'none';
            }
        });
    }
});