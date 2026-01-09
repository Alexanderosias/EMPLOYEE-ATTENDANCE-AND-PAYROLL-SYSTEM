// Sidebar collapse/expand toggle for employee pages
// - Toggles `sidebar-collapsed` class on <body>
// - Persists state in localStorage so it remembers per browser

(function () {
  const STORAGE_KEY = 'eaaps_employee_sidebar_collapsed';

  function applyInitialState() {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved === '1') {
        document.documentElement.classList.add('sidebar-collapsed');
      }
    } catch (e) {
      // Ignore storage errors
    }
  }

  function setupEmployeeMobileMenu() {
    try {
      var root = document.querySelector('body.employee-layout');
      if (!root) return;

      var menuBtn = root.querySelector('.header-menu-btn');
      var menu = root.querySelector('.employee-mobile-menu');
      if (!menuBtn || !menu) return;

      menuBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        root.classList.toggle('employee-mobile-menu-open');
      });

      document.addEventListener('click', function (e) {
        if (!root.classList.contains('employee-mobile-menu-open')) return;
        if (menu.contains(e.target) || menuBtn.contains(e.target)) return;
        root.classList.remove('employee-mobile-menu-open');
      });
    } catch (e) {
      // Ignore menu setup errors
    }
  }

  function toggleSidebar() {
    const root = document.documentElement || document.body;
    const collapsed = root.classList.toggle('sidebar-collapsed');
    try {
      localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
    } catch (e) {
      // Ignore storage errors
    }
  }

  function scrollActiveTabIntoView() {
    try {
      if (!window.matchMedia || !window.matchMedia('(max-width: 750px)').matches) {
        return; // only adjust on small screens
      }

      var nav = document.querySelector('.sidebar-nav');
      if (!nav) return;

      var active = nav.querySelector('li.active');
      if (!active) return;

      var navRect = nav.getBoundingClientRect();
      var activeRect = active.getBoundingClientRect();

      var currentScroll = nav.scrollLeft || 0;
      var offset = (activeRect.left - navRect.left) - (navRect.width - activeRect.width) / 2;
      var targetScroll = currentScroll + offset;
      if (targetScroll < 0) targetScroll = 0;

      nav.scrollLeft = targetScroll;
    } catch (e) {
      // Ignore alignment errors
    }
  }

  // Expose helper so pages can apply state early in <head>
  if (!window.eaapsSidebar) {
    window.eaapsSidebar = {};
  }
  window.eaapsSidebar.applyInitialState = applyInitialState;

  document.addEventListener('DOMContentLoaded', function () {
    applyInitialState();

    const btn = document.getElementById('sidebarToggle');
    if (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        toggleSidebar();
      });
    }

    scrollActiveTabIntoView();
    setupEmployeeMobileMenu();
  });
})();
