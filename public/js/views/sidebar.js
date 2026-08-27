document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebarMenu');
    const toggle = document.getElementById('sidebarToggle');

    if (!sidebar || !toggle) return;

    const savedState = localStorage.getItem('lolitasSidebarCollapsed');
    const shouldCollapse = savedState === '1';

    if (shouldCollapse) {
        sidebar.classList.add('sidebar-collapsed');
        toggle.querySelector('i').classList.replace('fa-chevron-left', 'fa-chevron-right');
    }

    toggle.addEventListener('click', function () {
        const collapsed = sidebar.classList.toggle('sidebar-collapsed');
        const icon = this.querySelector('i');
        icon.classList.toggle('fa-chevron-left', !collapsed);
        icon.classList.toggle('fa-chevron-right', collapsed);
        localStorage.setItem('lolitasSidebarCollapsed', collapsed ? '1' : '0');
    });
});
