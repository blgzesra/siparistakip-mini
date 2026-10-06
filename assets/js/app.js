document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('[data-sidebar-toggle]');
    var sidebar = document.querySelector('[data-sidebar]');

    if (!toggle || !sidebar) {
        return;
    }

    toggle.addEventListener('click', function () {
        sidebar.classList.toggle('is-open');
    });
});
