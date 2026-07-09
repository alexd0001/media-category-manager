(function () {
    'use strict';

    function setupTreeToggles() {
        document.querySelectorAll('.mcm-tree-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                var parent = button.closest('.mcm-tree-item');
                var children = parent ? parent.querySelector('.mcm-tree-children') : null;
                var expanded;

                if (!children) {
                    return;
                }

                expanded = button.getAttribute('aria-expanded') === 'true';
                button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                button.textContent = expanded ? '+' : '−';
                children.hidden = expanded;
            });
        });
    }

    function setupCategorySearch() {
        var input = document.getElementById('mcm-category-search');

        if (!input) {
            return;
        }

        input.addEventListener('input', function () {
            var term = input.value.toLowerCase().trim();

            document.querySelectorAll('.mcm-tree-link').forEach(function (link) {
                var item = link.closest('.mcm-tree-item');
                var label = link.textContent.toLowerCase();
                var matches = label.indexOf(term) !== -1;

                if (!item) {
                    return;
                }

                item.style.display = matches || term === '' ? '' : 'none';
            });
        });
    }

    function init() {
        setupTreeToggles();
        setupCategorySearch();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
