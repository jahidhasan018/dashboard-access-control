/**
 * Dashboard Access Control — Admin Scripts
 * Accordion, search, toggle switches, bulk actions.
 */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {

		/* ── Universal Role Selector for All Tabs ───────────────────────── */
		var rolePickers = document.querySelectorAll('.dac-role-selector');
		rolePickers.forEach(function (container) {
			var select = container.querySelector('select');
			if (!select) return;

			select.addEventListener('change', function () {
				if (select.value) {
					if (select.form) {
						select.form.submit();
					} else {
						var url = new URL(window.location.href);
						url.searchParams.set('role', select.value);
						url.searchParams.delete('saved');
						window.location.href = url.toString();
					}
				}
			});
		});

		/* ── Accordion Toggle ──────────────────────────────────────────── */
		var accordion = document.getElementById('dac-menu-accordion');
		if (!accordion) return;

		accordion.addEventListener('click', function (e) {
			var header = e.target.closest('.dac-accordion-header');
			if (!header) return;

			var item = header.closest('.dac-accordion-item');
			if (!item) return;

			// Don't toggle if clicking on the switch or label.
			if (e.target.closest('.dac-toggle')) return;

			item.classList.toggle('dac-open');
		});

		/* ── Expand / Collapse All ─────────────────────────────────────── */
		var expandAll  = document.getElementById('dac-expand-all');
		var collapseAll = document.getElementById('dac-collapse-all');

		if (expandAll) {
			expandAll.addEventListener('click', function () {
				accordion.querySelectorAll('.dac-accordion-item').forEach(function (item) {
					item.classList.add('dac-open');
				});
			});
		}

		if (collapseAll) {
			collapseAll.addEventListener('click', function () {
				accordion.querySelectorAll('.dac-accordion-item').forEach(function (item) {
					item.classList.remove('dac-open');
				});
			});
		}

		/* ── Hide All / Show All ───────────────────────────────────────── */
		var hideAll = document.getElementById('dac-hide-all');
		var showAll = document.getElementById('dac-show-all');

		if (hideAll) {
			hideAll.addEventListener('click', function () {
				accordion.querySelectorAll('.dac-toggle-input').forEach(function (input) {
					input.checked = true;
					updateToggleVisual(input);
				});
				updateStats();
			});
		}

		if (showAll) {
			showAll.addEventListener('click', function () {
				accordion.querySelectorAll('.dac-toggle-input').forEach(function (input) {
					input.checked = false;
					updateToggleVisual(input);
				});
				updateStats();
			});
		}

		function getI18n(key, fallback) {
			if (typeof dacI18n !== 'undefined' && dacI18n && dacI18n[key]) {
				return dacI18n[key];
			}
			return fallback;
		}

		/* ── Individual Toggle ─────────────────────────────────────────── */
		accordion.addEventListener('change', function (e) {
			if (!e.target.classList.contains('dac-toggle-input')) return;
			updateToggleVisual(e.target);
			updateParentCount(e.target);
			updateStats();
		});

		function updateToggleVisual(input) {
			var item = input.closest('.dac-accordion-item, .dac-child-item');
			if (!item) return;

			var badge = item.querySelector('.dac-badge');
			if (input.checked) {
				item.classList.add('dac-item-hidden');
				if (badge) {
					badge.className = 'dac-badge dac-badge-hidden';
					badge.textContent = getI18n('hidden', 'Hidden');
				}
			} else {
				item.classList.remove('dac-item-hidden');
				if (badge) {
					badge.className = 'dac-badge dac-badge-visible';
					badge.textContent = getI18n('visible', 'Visible');
				}
			}
		}

		function updateParentCount(input) {
			var parentItem = input.closest('.dac-accordion-item');
			if (!parentItem) return;

			var children = parentItem.querySelectorAll('.dac-child-item');
			if (children.length === 0) return;

			var total     = children.length;
			var hidden    = 0;
			var visible   = 0;

			children.forEach(function (child) {
				var toggle = child.querySelector('.dac-toggle-input');
				if (toggle && toggle.checked) {
					hidden++;
				} else {
					visible++;
				}
			});

			var countEl = parentItem.querySelector('.dac-child-count');
			if (countEl) {
				countEl.textContent = visible + '/' + total;
			}

			// If parent toggle is checked (hide), all children are effectively hidden.
			var parentToggle = parentItem.querySelector(':scope > .dac-accordion-header .dac-toggle-input');
			if (parentToggle && parentToggle.checked) {
				// Parent is hidden — show all hidden.
				if (countEl) countEl.textContent = '0/' + total;
			}
		}

		function updateStats() {
			var allInputs = accordion.querySelectorAll('.dac-toggle-input');
			var total     = allInputs.length;
			var hidden    = 0;

			allInputs.forEach(function (input) {
				if (input.checked) hidden++;
			});

			var shownEl  = document.querySelector('.dac-stat-shown .dac-stat-num');
			var hiddenEl = document.querySelector('.dac-stat-hidden .dac-stat-num');
			var totalEl  = document.querySelector('.dac-stat-total .dac-stat-num');

			if (shownEl)  shownEl.textContent  = total - hidden;
			if (hiddenEl) hiddenEl.textContent = hidden;
			if (totalEl)  totalEl.textContent  = total;
		}

		/* ── Search ────────────────────────────────────────────────────── */
		var searchInput = document.getElementById('dac-menu-search');
		if (!searchInput) return;

		var searchTimer;
		searchInput.addEventListener('input', function () {
			clearTimeout(searchTimer);
			searchTimer = setTimeout(function () {
				var query = searchInput.value.toLowerCase().trim();
				var items = accordion.querySelectorAll('.dac-accordion-item, .dac-child-item');

				items.forEach(function (item) {
					var label = (item.getAttribute('data-label') || '').toLowerCase();
					var slug  = (item.getAttribute('data-slug') || '').toLowerCase();

					if (!query || label.indexOf(query) !== -1 || slug.indexOf(query) !== -1) {
						item.classList.remove('dac-search-hidden');
					} else {
						item.classList.add('dac-search-hidden');
					}
				});

				// If searching, expand all parents that have visible children.
				if (query) {
					accordion.querySelectorAll('.dac-accordion-item').forEach(function (item) {
						var visibleChildren = item.querySelectorAll('.dac-child-item:not(.dac-search-hidden)');
						if (visibleChildren.length > 0 || item.classList.contains('dac-search-hidden') === false) {
							item.classList.add('dac-open');
						}
					});
				}
			}, 200);
		});

		// Clear search on Escape.
		searchInput.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				searchInput.value = '';
				searchInput.dispatchEvent(new Event('input'));
			}
		});

		/* ── Init stats on load ────────────────────────────────────────── */
		updateStats();
	});

	/* ── Color Picker Init (jQuery required) ─────────────────────────── */
	if (typeof jQuery !== 'undefined') {
		jQuery(document).ready(function ($) {
			$('.dac-color-picker').each(function () {
				if (!$(this).hasClass('wp-picker-container')) {
					$(this).wpColorPicker();
				}
			});
		});
	}
})();
