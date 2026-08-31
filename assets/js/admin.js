/**
 * Dashboard Access Control — Admin Scripts
 */
(function() {
	'use strict';

	// Role selector: update URL on change + click Load.
	document.addEventListener('DOMContentLoaded', function() {
		var roleSelect = document.getElementById('dac-role-select') || document.getElementById('dac-menu-role');
		var loadBtn    = document.getElementById('dac-load-role') || document.getElementById('dac-load-menu-role');

		if (roleSelect && loadBtn) {
			loadBtn.addEventListener('click', function(e) {
				e.preventDefault();
				var role = roleSelect.value;
				if (!role) {
					return;
				}
				var url = new URL(window.location.href);
				url.searchParams.set('role', role);
				window.location.href = url.toString();
			});

			// Also navigate on Enter key.
			roleSelect.addEventListener('keydown', function(e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					loadBtn.click();
				}
			});
		}
	});
})();
