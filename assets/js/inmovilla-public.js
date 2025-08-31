/*!
 * Inmovilla Properties - Frontend JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Namespace para el plugin
    window.InmovillaPublic = {

        // Configuración inicial
        config: {
            ajaxUrl: inmovilla_ajax_object.ajax_url,
            nonce: inmovilla_ajax_object.nonce,
            loadingClass: 'inmovilla-loading',
            noResultsClass: 'inmovilla-no-results'
        },

        // Inicialización
        init: function() {
            this.setupEventListeners();
            this.initGallery();
            this.initFavorites();
            this.initSearch();
            this.initFilters();
            this.initLazyLoading();
        },

        // Configurar event listeners
        setupEventListeners: function() {
            var self = this;

            // Búsqueda en tiempo real
            $(document).on('input', '.inmovilla-search-input', function() {
                clearTimeout(self.searchTimeout);
                self.searchTimeout = setTimeout(function() {
                    self.performSearch();
                }, 500);
            });

            // Filtros
            $(document).on('change', '.inmovilla-filter-select', function() {
                self.performSearch();
            });

            // Paginación AJAX
            $(document).on('click', '.inmovilla-pagination a', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');
                self.loadPage(url);
            });

            // Toggle filtros avanzados
            $(document).on('click', '.inmovilla-filter-toggle', function() {
                $(this).next('.inmovilla-filter-content').slideToggle();
            });

            // Responsive menu
            $(document).on('click', '.inmovilla-mobile-menu-toggle', function() {
                $('.inmovilla-mobile-menu').slideToggle();
            });


            // Abrir formulario de contacto
            $(document).on('click', '.inmovilla-contact-btn', function(e) {
                e.preventDefault();
                var subject = $(this).data('subject') || '';
                $('#inmovilla-contact-subject').val(subject);
                $('#inmovilla-contact-modal').fadeIn();
            });

            // Cerrar formulario de contacto
            $(document).on('click', '.inmovilla-modal-close', function() {
                $('#inmovilla-contact-modal').fadeOut();
            });

            // Enviar formulario de contacto
            $(document).on('submit', '#inmovilla-contact-form', function(e) {
                e.preventDefault();
                var $form = $(this);
                var data = $form.serialize();
                data += '&action=inmovilla_send_contact&nonce=' + InmovillaPublic.config.nonce;
                $.post(InmovillaPublic.config.ajaxUrl, data, function(response) {
                    if (response.success) {
                        $form[0].reset();
                        $('#inmovilla-contact-modal').fadeOut();
                        InmovillaPublic.showNotification(response.data.message, 'success');
                    } else {
                        InmovillaPublic.showNotification(response.data.message, 'error');
                    }
                });

            });
        },

        // Inicializar galería de imágenes
        initGallery: function() {
            if ($('.inmovilla-property-gallery').length) {
                var currentIndex = 0;
                var images = $('.inmovilla-gallery-image');
                var dots = $('.inmovilla-gallery-dot');

                function showImage(index) {
                    images.removeClass('active').eq(index).addClass('active');
                    dots.removeClass('active').eq(index).addClass('active');
                }

                // Navegación con dots
                $(document).on('click', '.inmovilla-gallery-dot', function() {
                    currentIndex = $(this).index();
                    showImage(currentIndex);
                });

                // Auto-slide cada 5 segundos
                if (images.length > 1) {
                    setInterval(function() {
                        currentIndex = (currentIndex + 1) % images.length;
                        showImage(currentIndex);
                    }, 5000);
                }

                // Navegación con teclado
                $(document).on('keydown', function(e) {
                    if ($('.inmovilla-property-gallery:visible').length) {
                        if (e.keyCode === 37) { // Izquierda
                            currentIndex = (currentIndex - 1 + images.length) % images.length;
                            showImage(currentIndex);
                        } else if (e.keyCode === 39) { // Derecha
                            currentIndex = (currentIndex + 1) % images.length;
                            showImage(currentIndex);
                        }
                    }
                });
            }
        },

        // Sistema de favoritos
        initFavorites: function() {
            var self = this;

            $(document).on('click', '.inmovilla-favorite-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $btn = $(this);
                var propertyId = $btn.data('property-id');
                var action = $btn.hasClass('active') ? 'remove' : 'add';

                self.toggleFavorite(propertyId, action, $btn);
            });

            // Cargar favoritos existentes
            this.loadFavorites();
        },

        // Toggle favorito
        toggleFavorite: function(propertyId, action, $btn) {
            var favorites = this.getFavorites();

            if (action === 'add') {
                favorites.push(propertyId);
                $btn.addClass('active');
                this.showNotification('Propiedad añadida a favoritos', 'success');
            } else {
                favorites = favorites.filter(id => id !== propertyId);
                $btn.removeClass('active');
                this.showNotification('Propiedad eliminada de favoritos', 'info');
            }

            this.saveFavorites(favorites);

            // Actualizar contador
            $('.inmovilla-favorites-count').text(favorites.length);
        },

        // Obtener favoritos del localStorage
        getFavorites: function() {
            try {
                return JSON.parse(localStorage.getItem('inmovilla_favorites') || '[]');
            } catch (e) {
                return [];
            }
        },

        // Guardar favoritos en localStorage
        saveFavorites: function(favorites) {
            try {
                localStorage.setItem('inmovilla_favorites', JSON.stringify(favorites));
            } catch (e) {
                console.warn('No se pudieron guardar los favoritos');
            }
        },

        // Cargar favoritos existentes
        loadFavorites: function() {
            var favorites = this.getFavorites();

            favorites.forEach(function(propertyId) {
                $('[data-property-id="' + propertyId + '"]').addClass('active');
            });

            $('.inmovilla-favorites-count').text(favorites.length);
        },

        // Inicializar búsqueda
        initSearch: function() {
            // Autocompletado para ubicaciones
            if ($('.inmovilla-location-input').length) {
                this.setupLocationAutocomplete();
            }

            // Rangos de precio
            if ($('.inmovilla-price-range').length) {
                this.setupPriceRanges();
            }
        },

        // Configurar autocompletado de ubicaciones
        setupLocationAutocomplete: function() {
            // Aquí se implementaría la integración con API de mapas
            // Por ahora, usamos un sistema básico

            var locations = [
                'Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Zaragoza',
                'Málaga', 'Murcia', 'Palma', 'Las Palmas', 'Bilbao'
            ];

            $('.inmovilla-location-input').on('input', function() {
                var value = $(this).val().toLowerCase();
                var matches = locations.filter(loc => 
                    loc.toLowerCase().includes(value)
                );

                // Mostrar sugerencias (implementación básica)
                // En producción se integraría con Google Places API
            });
        },

        // Configurar rangos de precio
        setupPriceRanges: function() {
            $('.inmovilla-price-min, .inmovilla-price-max').on('input', function() {
                var min = parseInt($('.inmovilla-price-min').val()) || 0;
                var max = parseInt($('.inmovilla-price-max').val()) || 0;

                if (max > 0 && min > max) {
                    $(this).val('');
                    InmovillaPublic.showNotification('El precio mínimo no puede ser mayor al máximo', 'warning');
                }
            });
        },

        // Realizar búsqueda
        performSearch: function() {
            var $form = $('.inmovilla-search-form');
            var $results = $('.inmovilla-properties-grid');
            var formData = $form.serialize();

            // Mostrar loading
            $results.addClass(this.config.loadingClass);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'inmovilla_search_properties',
                    nonce: this.config.nonce,
                    search_data: formData
                },
                success: function(response) {
                    if (response.success) {
                        $results.html(response.data.html);
                        InmovillaPublic.updateUrl(response.data.url);
                        InmovillaPublic.loadFavorites(); // Recargar favoritos
                    } else {
                        InmovillaPublic.showNotification('Error en la búsqueda', 'error');
                    }
                },
                error: function() {
                    InmovillaPublic.showNotification('Error de conexión', 'error');
                },
                complete: function() {
                    $results.removeClass(InmovillaPublic.config.loadingClass);
                }
            });
        },

        // Cargar página (paginación AJAX)
        loadPage: function(url) {
            var $results = $('.inmovilla-properties-grid');

            $results.addClass(this.config.loadingClass);

            $.get(url, function(response) {
                var $newContent = $(response).find('.inmovilla-properties-grid');
                var $newPagination = $(response).find('.inmovilla-pagination');

                $results.html($newContent.html());
                $('.inmovilla-pagination').replaceWith($newPagination);

                InmovillaPublic.loadFavorites();
                InmovillaPublic.scrollToResults();
            }).fail(function() {
                InmovillaPublic.showNotification('Error al cargar la página', 'error');
            }).always(function() {
                $results.removeClass(InmovillaPublic.config.loadingClass);
            });
        },

        // Scroll a resultados
        scrollToResults: function() {
            $('html, body').animate({
                scrollTop: $('.inmovilla-properties-grid').offset().top - 100
            }, 300);
        },

        // Inicializar filtros
        initFilters: function() {
            // Limpiar filtros
            $(document).on('click', '.inmovilla-clear-filters', function() {
                $('.inmovilla-search-form')[0].reset();
                InmovillaPublic.performSearch();
            });

            // Filtros rápidos
            $(document).on('click', '.inmovilla-quick-filter', function() {
                var filterType = $(this).data('filter-type');
                var filterValue = $(this).data('filter-value');

                $('[name="' + filterType + '"]').val(filterValue);
                InmovillaPublic.performSearch();
            });
        },

        // Lazy loading de imágenes
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var image = entry.target;
                            image.src = image.dataset.src;
                            image.classList.remove('lazy');
                            imageObserver.unobserve(image);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(function(img) {
                    imageObserver.observe(img);
                });
            }
        },

        // Actualizar URL sin recargar página
        updateUrl: function(url) {
            if (history.pushState && url) {
                history.pushState(null, null, url);
            }
        },

        // Mostrar notificaciones
        showNotification: function(message, type) {
            type = type || 'info';

            var notification = $('<div class="inmovilla-notification inmovilla-notification-' + type + '">')
                .text(message)
                .appendTo('body');

            // Auto-hide después de 3 segundos
            setTimeout(function() {
                notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },

        // Utilidades
        utils: {
            // Formatear precio
            formatPrice: function(price) {
                return new Intl.NumberFormat('es-ES', {
                    style: 'currency',
                    currency: 'EUR'
                }).format(price);
            },

            // Debounce function
            debounce: function(func, wait) {
                var timeout;
                return function executedFunction() {
                    var context = this;
                    var args = arguments;
                    var later = function() {
                        timeout = null;
                        func.apply(context, args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        InmovillaPublic.init();
    });

})(jQuery);
