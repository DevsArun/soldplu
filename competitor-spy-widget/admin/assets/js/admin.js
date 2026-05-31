/**
 * Competitor Spy Widget - Admin JavaScript
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    var CSWAdmin = {

        init: function () {
            this.initModals();
            this.initCompetitors();
            this.initPrices();
            this.initSettings();
            this.initOnboarding();
            this.initCharts();
            this.initAnalytics();
        },

        // ========== MODALS ==========
        initModals: function () {
            $(document).on('click', '.csw-modal-close, .csw-modal-cancel, .csw-modal-overlay', function () {
                $(this).closest('.csw-modal').hide();
            });
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape') { $('.csw-modal:visible').hide(); }
            });
        },

        showModal: function (modalId) {
            $('#' + modalId).show();
        },

        // ========== COMPETITORS ==========
        initCompetitors: function () {
            var self = this;

            $('#csw-add-competitor-btn, #csw-add-first-competitor').on('click', function () {
                self.resetCompetitorForm();
                self.showModal('csw-competitor-modal');
            });

            // Template buttons
            $(document).on('click', '.csw-template-btn', function () {
                var template = $(this).data('template');
                var templates = {
                    amazon: { name: 'Amazon', url: 'https://www.amazon.com' },
                    walmart: { name: 'Walmart', url: 'https://www.walmart.com' },
                    ebay: { name: 'eBay', url: 'https://www.ebay.com' },
                    target: { name: 'Target', url: 'https://www.target.com' },
                    bestbuy: { name: 'Best Buy', url: 'https://www.bestbuy.com' }
                };
                if (templates[template]) {
                    $('#csw-comp-name').val(templates[template].name);
                    $('#csw-comp-url').val(templates[template].url);
                    $('#csw-comp-logo').val(cswAdmin.pluginUrl + 'public/assets/images/competitors/' + template + '.svg');
                }
            });

            // API type toggle
            $('#csw-comp-api-type').on('change', function () {
                $('.csw-api-fields').toggle($(this).val() === 'api');
            });

            // Save competitor
            $('#csw-competitor-form').on('submit', function (e) {
                e.preventDefault();
                self.saveCompetitor();
            });

            // Edit competitor
            $(document).on('click', '.csw-edit-competitor', function () {
                var id = $(this).data('id');
                self.editCompetitor(id);
            });

            // Delete competitor
            $(document).on('click', '.csw-delete-competitor', function () {
                var id = $(this).data('id');
                if (confirm(cswAdmin.i18n.confirm_delete)) {
                    self.deleteCompetitor(id);
                }
            });

            // Toggle active
            $(document).on('change', '.csw-toggle-active', function () {
                var id = $(this).data('id');
                var active = $(this).is(':checked') ? 1 : 0;
                self.toggleCompetitor(id, active);
            });
        },

        resetCompetitorForm: function () {
            $('#csw-competitor-form')[0].reset();
            $('#csw-competitor-id').val(0);
            $('#csw-modal-title').text('Add Competitor');
            $('#csw-templates-section').show();
            $('.csw-api-fields').hide();
        },

        saveCompetitor: function () {
            var $btn = $('#csw-save-competitor-btn');
            $btn.find('.csw-btn-text').hide();
            $btn.find('.csw-btn-loading').show();

            $.ajax({
                url: cswAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'csw_save_competitor',
                    nonce: cswAdmin.nonce,
                    competitor_id: $('#csw-competitor-id').val(),
                    name: $('#csw-comp-name').val(),
                    website_url: $('#csw-comp-url').val(),
                    logo_url: $('#csw-comp-logo').val(),
                    api_type: $('#csw-comp-api-type').val(),
                    api_key: $('#csw-comp-api-key').val(),
                    api_endpoint: $('#csw-comp-api-endpoint').val(),
                    priority: $('#csw-comp-priority').val()
                },
                success: function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function () { alert(cswAdmin.i18n.error); },
                complete: function () {
                    $btn.find('.csw-btn-text').show();
                    $btn.find('.csw-btn-loading').hide();
                }
            });
        },

        editCompetitor: function (id) {
            var self = this;
            $.ajax({
                url: cswAdmin.restUrl + 'competitors/' + id,
                method: 'GET',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', cswAdmin.restNonce);
                },
                success: function (competitor) {
                    $('#csw-competitor-id').val(competitor.id);
                    $('#csw-comp-name').val(competitor.name);
                    $('#csw-comp-url').val(competitor.website_url);
                    $('#csw-comp-logo').val(competitor.logo_url);
                    $('#csw-comp-api-type').val(competitor.api_type);
                    $('#csw-comp-api-key').val(competitor.api_key);
                    $('#csw-comp-api-endpoint').val(competitor.api_endpoint);
                    $('#csw-comp-priority').val(competitor.priority);
                    $('#csw-modal-title').text('Edit Competitor');
                    $('#csw-templates-section').hide();
                    $('.csw-api-fields').toggle(competitor.api_type === 'api');
                    self.showModal('csw-competitor-modal');
                }
            });
        },

        deleteCompetitor: function (id) {
            $.ajax({
                url: cswAdmin.ajaxUrl,
                type: 'POST',
                data: { action: 'csw_delete_competitor', nonce: cswAdmin.nonce, competitor_id: id },
                success: function (response) {
                    if (response.success) { location.reload(); }
                    else { alert(response.data.message); }
                }
            });
        },

        toggleCompetitor: function (id, active) {
            $.ajax({
                url: cswAdmin.ajaxUrl,
                type: 'POST',
                data: { action: 'csw_toggle_competitor', nonce: cswAdmin.nonce, competitor_id: id, is_active: active }
            });
        },

        // ========== PRICES ==========
        initPrices: function () {
            var self = this;

            $('#csw-add-price-btn').on('click', function () {
                self.showModal('csw-price-modal');
            });

            $('#csw-import-btn').on('click', function () {
                self.showModal('csw-import-modal');
            });

            // Product search with Select2
            if ($.fn.select2) {
                $('.csw-product-search').select2({
                    ajax: {
                        url: cswAdmin.ajaxUrl,
                        dataType: 'json',
                        delay: 300,
                        data: function (params) {
                            return { action: 'csw_search_products', nonce: cswAdmin.nonce, term: params.term };
                        },
                        processResults: function (data) { return { results: data }; }
                    },
                    minimumInputLength: 2,
                    placeholder: cswAdmin.i18n.search_products
                });
            }

            // Save price
            $('#csw-price-form').on('submit', function (e) {
                e.preventDefault();
                self.savePrice();
            });

            // Delete price
            $(document).on('click', '.csw-delete-price', function () {
                var id = $(this).data('id');
                if (confirm(cswAdmin.i18n.confirm_delete)) {
                    self.deletePrice(id);
                }
            });

            // Import
            $('#csw-import-form').on('submit', function (e) {
                e.preventDefault();
                self.importPrices();
            });
        },

        savePrice: function () {
            var $form = $('#csw-price-form');
            $.ajax({
                url: cswAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'csw_save_price',
                    nonce: cswAdmin.nonce,
                    product_id: $('#csw-price-product').val(),
                    competitor_id: $('#csw-price-competitor').val(),
                    competitor_price: $('#csw-price-amount').val(),
                    competitor_url: $('#csw-price-url').val()
                },
                success: function (response) {
                    if (response.success) { location.reload(); }
                    else { alert(response.data.message); }
                },
                error: function () { alert(cswAdmin.i18n.error); }
            });
        },

        deletePrice: function (id) {
            $.ajax({
                url: cswAdmin.ajaxUrl,
                type: 'POST',
                data: { action: 'csw_delete_price', nonce: cswAdmin.nonce, price_id: id },
                success: function (response) {
                    if (response.success) { $('tr[data-price-id="' + id + '"]').fadeOut(); }
                    else { alert(response.data.message); }
                }
            });
        },

        importPrices: function () {
            var formData = new FormData($('#csw-import-form')[0]);
            formData.append('action', 'csw_bulk_import_prices');
            formData.append('nonce', cswAdmin.nonce);

            $.ajax({
                url: cswAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) { alert(response.data.message); location.reload(); }
                    else { alert(response.data.message); }
                }
            });
        },

        // ========== SETTINGS ==========
        initSettings: function () {
            var self = this;

            // Tab navigation
            $('.csw-nav-item').on('click', function (e) {
                e.preventDefault();
                var tab = $(this).data('tab');
                $('.csw-nav-item').removeClass('active');
                $(this).addClass('active');
                $('.csw-settings-tab').removeClass('active');
                $('#csw-tab-' + tab).addClass('active');
            });

            // Color picker sync
            $('.csw-color-input').on('input', function () {
                $(this).siblings('.csw-color-text').val($(this).val());
            });

            // Save settings
            $('#csw-settings-form').on('submit', function (e) {
                e.preventDefault();
                self.saveSettings();
            });

            // Flush cache
            $('#csw-flush-cache-btn').on('click', function () {
                $.ajax({
                    url: cswAdmin.ajaxUrl,
                    type: 'POST',
                    data: { action: 'csw_flush_cache', nonce: cswAdmin.nonce },
                    success: function (response) {
                        if (response.success) { alert(response.data.message); }
                    }
                });
            });
        },

        saveSettings: function () {
            var $btn = $('#csw-save-settings-btn');
            $btn.find('.csw-btn-text').hide();
            $btn.find('.csw-btn-loading').show();

            var settings = {};
            $('#csw-settings-form').find('input, select, textarea').each(function () {
                var $el = $(this);
                var name = $el.attr('name');
                if (!name) return;

                if ($el.is(':checkbox')) {
                    settings[name] = $el.is(':checked') ? '1' : '0';
                } else if ($el.is(':radio')) {
                    if ($el.is(':checked')) { settings[name] = $el.val(); }
                } else {
                    settings[name] = $el.val();
                }
            });

            // Handle excluded products
            if (settings.excluded_products_text) {
                settings.excluded_products = settings.excluded_products_text.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
                delete settings.excluded_products_text;
            }

            $.ajax({
                url: cswAdmin.ajaxUrl,
                type: 'POST',
                data: { action: 'csw_save_settings', nonce: cswAdmin.nonce, settings: settings },
                success: function (response) {
                    if (response.success) { self.showToast(cswAdmin.i18n.saved); }
                    else { alert(response.data.message); }
                },
                error: function () { alert(cswAdmin.i18n.error); },
                complete: function () {
                    $btn.find('.csw-btn-text').show();
                    $btn.find('.csw-btn-loading').hide();
                }
            });
        },

        showToast: function (message) {
            var $toast = $('<div class="csw-toast">' + message + '</div>');
            $('body').append($toast);
            setTimeout(function () { $toast.addClass('show'); }, 10);
            setTimeout(function () { $toast.removeClass('show'); setTimeout(function () { $toast.remove(); }, 300); }, 3000);
        },

        // ========== ONBOARDING ==========
        initOnboarding: function () {
            var currentStep = 0;
            var totalSteps = 4;

            $('.csw-next-step').on('click', function () {
                if (currentStep < totalSteps - 1) {
                    currentStep++;
                    updateStep();
                }
            });

            $('.csw-prev-step').on('click', function () {
                if (currentStep > 0) {
                    currentStep--;
                    updateStep();
                }
            });

            function updateStep() {
                $('.csw-onboarding-step').removeClass('active');
                $('[data-step="' + currentStep + '"]').addClass('active');
                $('.csw-progress-step').removeClass('active');
                for (var i = 0; i <= currentStep; i++) {
                    $('.csw-progress-step[data-step="' + i + '"]').addClass('active');
                }
                $('.csw-progress-fill').css('width', ((currentStep + 1) / totalSteps * 100) + '%');
            }

            // Complete onboarding
            $('#csw-complete-onboarding').on('click', function () {
                var competitors = [];
                $('input[name="onboarding_competitors[]"]:checked').each(function () {
                    competitors.push({ template: $(this).val() });
                });
                var customName = $('#csw-custom-comp-name').val();
                if (customName) {
                    competitors.push({ template: 'custom', name: customName, url: $('#csw-custom-comp-url').val() });
                }

                $.ajax({
                    url: cswAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'csw_complete_onboarding',
                        nonce: cswAdmin.nonce,
                        competitors: competitors,
                        position: $('input[name="onboarding_position"]:checked').val()
                    },
                    success: function (response) {
                        if (response.success) { window.location.href = response.data.redirect; }
                    }
                });
            });
        },

        // ========== CHARTS ==========
        initCharts: function () {
            if (typeof Chart === 'undefined') return;

            this.initPerformanceChart();
            this.initAnalyticsChart();
            this.initDeviceChart();
        },

        initPerformanceChart: function () {
            var canvas = document.getElementById('csw-performance-chart');
            if (!canvas) return;

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Impressions',
                        data: [0, 0, 0, 0, 0, 0, 0],
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Conversions',
                        data: [0, 0, 0, 0, 0, 0, 0],
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.05)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        },

        initAnalyticsChart: function () {
            var canvas = document.getElementById('csw-analytics-chart');
            if (!canvas || typeof cswAnalyticsData === 'undefined') return;

            var labels = [], impressions = [], conversions = [];
            if (cswAnalyticsData.daily_stats) {
                cswAnalyticsData.daily_stats.forEach(function (d) {
                    labels.push(d.date);
                    impressions.push(parseInt(d.impressions));
                    conversions.push(parseInt(d.conversions));
                });
            }

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Impressions',
                        data: impressions,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true, tension: 0.4, borderWidth: 2
                    }, {
                        label: 'Conversions',
                        data: conversions,
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true, tension: 0.4, borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        },

        initDeviceChart: function () {
            var canvas = document.getElementById('csw-device-chart');
            if (!canvas || typeof cswAnalyticsData === 'undefined') return;

            var data = [0, 0, 0];
            if (cswAnalyticsData.device_breakdown) {
                cswAnalyticsData.device_breakdown.forEach(function (d) {
                    if (d.device_type === 'desktop') data[0] = parseInt(d.count);
                    if (d.device_type === 'mobile') data[1] = parseInt(d.count);
                    if (d.device_type === 'tablet') data[2] = parseInt(d.count);
                });
            }

            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: ['Desktop', 'Mobile', 'Tablet'],
                    datasets: [{ data: data, backgroundColor: ['#3B82F6', '#10B981', '#8B5CF6'], borderWidth: 0 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    cutout: '70%'
                }
            });
        },

        // ========== ANALYTICS ==========
        initAnalytics: function () {
            $('#csw-export-analytics').on('click', function () {
                var period = new URLSearchParams(window.location.search).get('period') || '30days';
                $.ajax({
                    url: cswAdmin.ajaxUrl,
                    type: 'POST',
                    data: { action: 'csw_export_analytics', nonce: cswAdmin.nonce, period: period },
                    success: function (response) {
                        if (response.success) {
                            var blob = new Blob([response.data.csv], { type: 'text/csv' });
                            var url = window.URL.createObjectURL(blob);
                            var a = document.createElement('a');
                            a.href = url;
                            a.download = response.data.filename;
                            a.click();
                            window.URL.revokeObjectURL(url);
                        }
                    }
                });
            });
        }
    };

    $(document).ready(function () {
        CSWAdmin.init();
    });

})(jQuery);
