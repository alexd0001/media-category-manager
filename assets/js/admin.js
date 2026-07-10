(function ($, window) {
    'use strict';

    function getSidebarTemplate() {
        var template = document.getElementById('mcm-sidebar-template');

        if (!template) {
            return null;
        }

        return template.cloneNode(true);
    }

    function buildLayout() {
        var wrap = document.querySelector('#wpbody-content .wrap');
        var template = getSidebarTemplate();
        var layout;
        var content;

        if (!wrap || !template || wrap.querySelector('.mcm-layout')) {
            return;
        }

        template.hidden = false;
        template.id = '';

        layout = document.createElement('div');
        layout.className = 'mcm-layout';

        content = document.createElement('div');
        content.className = 'mcm-content';

        while (wrap.firstChild) {
            content.appendChild(wrap.firstChild);
        }

        layout.appendChild(template);
        layout.appendChild(content);
        wrap.appendChild(layout);
    }

    function patchAjaxRequests() {
        if (window.mcmAjaxPatched) {
            return;
        }

        window.mcmAjaxPatched = true;

        function appendFilterToString(data) {
            var nextData = data;

            if (window.mcmAdmin.currentFilter.category_id) {
                if (nextData.indexOf('mcm_category=') === -1) {
                    nextData += '&mcm_category=' + encodeURIComponent(window.mcmAdmin.currentFilter.category_id);
                }

                if (nextData.indexOf('query%5Bmcm_category%5D=') === -1 && nextData.indexOf('query[mcm_category]=') === -1) {
                    nextData += '&query[mcm_category]=' + encodeURIComponent(window.mcmAdmin.currentFilter.category_id);
                }
            }

            if (window.mcmAdmin.currentFilter.view) {
                if (nextData.indexOf('mcm_view=') === -1) {
                    nextData += '&mcm_view=' + encodeURIComponent(window.mcmAdmin.currentFilter.view);
                }

                if (nextData.indexOf('query%5Bmcm_view%5D=') === -1 && nextData.indexOf('query[mcm_view]=') === -1) {
                    nextData += '&query[mcm_view]=' + encodeURIComponent(window.mcmAdmin.currentFilter.view);
                }
            }

            return nextData;
        }

        $(document).ajaxSend(function (event, jqxhr, settings) {
            if (!settings || !settings.data || settings.data.indexOf('action=query-attachments') === -1) {
                return;
            }

            if (typeof settings.data === 'string') {
                settings.data = appendFilterToString(settings.data);
                return;
            }

            if (window.mcmAdmin.currentFilter.category_id) {
                settings.data.mcm_category = window.mcmAdmin.currentFilter.category_id;
                settings.data.query = settings.data.query || {};
                settings.data.query.mcm_category = window.mcmAdmin.currentFilter.category_id;
            }

            if (window.mcmAdmin.currentFilter.view) {
                settings.data.mcm_view = window.mcmAdmin.currentFilter.view;
                settings.data.query = settings.data.query || {};
                settings.data.query.mcm_view = window.mcmAdmin.currentFilter.view;
            }
        });
    }

    function addGridBadges() {
        if (!window.wp || !wp.media || !wp.media.view || !wp.media.view.Attachment) {
            return;
        }

        if (wp.media.view.Attachment.prototype.mcmPatched) {
            return;
        }

        var originalRender = wp.media.view.Attachment.prototype.render;

        wp.media.view.Attachment.prototype.render = function () {
            originalRender.apply(this, arguments);

            var categories = this.model && this.model.get('mcmCategories');
            var container = this.el.querySelector('.mcm-grid-badges');
            var html = '';

            if (!this.el) {
                return this;
            }

            if (!container) {
                container = document.createElement('div');
                container.className = 'mcm-grid-badges';
                this.el.appendChild(container);
            }

            if (categories && categories.length) {
                categories.forEach(function (name) {
                    html += '<span class="mcm-badge">' + name + '</span>';
                });
            } else {
                html = '<span class="mcm-badge mcm-badge-empty">' + window.mcmAdmin.strings.uncategorized + '</span>';
            }

            container.innerHTML = html;

            return this;
        };

        wp.media.view.Attachment.prototype.mcmPatched = true;
    }

    function setupQuickEdit() {
        if (typeof inlineEditPost === 'undefined' || inlineEditPost.mcmPatched) {
            return;
        }

        var originalEdit = inlineEditPost.edit;

        inlineEditPost.edit = function (id) {
            originalEdit.apply(this, arguments);

            var postId = 0;
            var row;
            var editRow;
            var termHolder;
            var termIds;

            if (typeof id === 'object') {
                postId = parseInt(this.getId(id), 10);
            } else {
                postId = parseInt(id, 10);
            }

            if (!postId) {
                return;
            }

            row = document.getElementById('post-' + postId);
            editRow = document.getElementById('edit-' + postId);

            if (!row || !editRow) {
                return;
            }

            termHolder = row.querySelector('.mcm-term-ids');
            termIds = termHolder ? (termHolder.dataset.termIds || '').split(',') : [];

            editRow.querySelectorAll('input[name="mcm_attachment_categories[]"]').forEach(function (checkbox) {
                checkbox.checked = termIds.indexOf(checkbox.value) !== -1;
            });
        };

        inlineEditPost.mcmPatched = true;
    }

    $(function () {
        buildLayout();
        patchAjaxRequests();
        addGridBadges();
        setupQuickEdit();
    });
})(jQuery, window);
