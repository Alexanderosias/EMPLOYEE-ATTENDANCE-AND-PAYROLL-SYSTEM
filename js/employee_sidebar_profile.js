(function () {
  async function loadSidebarProfile() {
    var avatarEl = document.getElementById('sidebarProfileAvatar');
    var nameEl = document.getElementById('sidebarProfileName');

    if (!avatarEl && !nameEl) {
      return;
    }

    try {
      var res = await fetch('../views/employee_profile_handler.php?action=get_profile', {
        credentials: 'same-origin'
      });
      if (!res.ok) {
        return;
      }
      var result = await res.json();
      if (!result || !result.success || !result.data) {
        return;
      }
      var d = result.data || {};

      if (avatarEl && d.avatar_path) {
        avatarEl.src = d.avatar_path;
      }

      if (nameEl) {
        var first = (d.first_name || '').trim();
        var last = (d.last_name || '').trim();
        var label = '';

        // Use full name on mobile (<= 750px), keep initial + last on larger screens
        var isMobile = false;
        try {
          if (window.matchMedia) {
            isMobile = window.matchMedia('(max-width: 750px)').matches;
          }
        } catch (e) {
          isMobile = false;
        }

        if (first || last) {
          if (isMobile) {
            label = (first + ' ' + last).trim() || first || last;
          } else {
            if (first) {
              var initial = first.charAt(0).toUpperCase();
              label = last ? initial + '. ' + last : initial + '.';
            } else {
              label = last;
            }
          }
        }

        if (label) {
          nameEl.textContent = label;
        }
      }
    } catch (e) {
      console.error('Failed to load sidebar profile', e);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadSidebarProfile);
  } else {
    loadSidebarProfile();
  }
})();
