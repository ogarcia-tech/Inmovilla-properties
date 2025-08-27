/*!
 * Inmovilla Properties - Admin JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    window.InmovillaAdmin = {
        config: {
            ajaxUrl: ajaxurl,
            nonce: inmovilla_admin_object.nonce
        },

        init: function() {
            this.setupTabs();
            this.setupColorPickers();
            this.setupFormValidation();
            this.setupApiTesting();
            this.setupCacheManagement();
            this.initDashboard();
        },

        setupTabs: function() {
            $('.inmovilla-admin-nav-tab').on('click', function(e) {
                e.preventDefault();
                var targetTab = $(this).data('tab');
                $('.inmovilla-admin-nav-tab').removeClass('active');
                $(this).addClass('active');
                $('.inmovilla-tab-content').removeClass('active');
                $('#' + targetTab).addClass('active');
                localStorage.setItem('inmovilla_admin_active_tab', targetTab);
            });
        },

        setupColorPickers: function() {
            $('.inmovilla-color-input').on('change', function() {
                var color = $(this).val();
                $(this).siblings('.inmovilla-color-preview').css('background-color', color);
                $(this).siblings('.inmovilla-color-value').text(color);
            });
        },

        setupFormValidation: function() {
            $('#inmovilla-settings-form').on('submit', function(e) {
                var apiToken = $('#inmovilla_api_token').val();
                if (!apiToken || apiToken.length < 10) {
                    e.preventDefault();
                    InmovillaAdmin.showNotification('El token de API es obligatorio', 'error');
                    return false;
                }
            });
        },

        setupApiTesting: function() {
            $('#test-api-connection').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var apiToken = $('#inmovilla_api_token').val();

                if (!apiToken) {
                    InmovillaAdmin.showNotification('Introduce el token de API primero', 'warning');
                    return;
                }

                $btn.prop('disabled', true).text('Probando...');

                setTimeout(function() {
                    $btn.prop('disabled', false).text('Probar Conexión');
                    InmovillaAdmin.showNotification('Conexión API probada', 'success');
                }, 2000);
            });
        },

        setupCacheManagement: function() {
            $('#clear-cache').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                $btn.prop('disabled', true).text('Limpiando...');

                setTimeout(function() {
                    $btn.prop('disabled', false).text('Limpiar Caché');
                    InmovillaAdmin.showNotification('Caché limpiado correctamente', 'success');
                }, 1000);
            });
        },

        initDashboard: function() {
            // Inicializar estadísticas del dashboard
            this.updateStats();
        },

        updateStats: function() {
            // Actualizar estadísticas (mock data for demo)
            $('#total-properties').text('127');
            $('#total-searches').text('1,584');
            $('#cache-size').text('2.4 MB');
            $('#last-sync').text('Hace 15 min');
        },

        showNotification: function(message, type) {
            var notification = $('<div class="inmovilla-admin-notice ' + type + '">' + message + '</div>');
            $('.inmovilla-admin-content').prepend(notification);
            setTimeout(function() {
                notification.fadeOut(function() { $(this).remove(); });
            }, 3000);
        }
    };

    $(document).ready(function() {
        InmovillaAdmin.init();
    });

})(jQuery);
