/*!
 * Inmovilla Properties - Search JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    window.InmovillaSearch = {

        init: function() {
            this.setupAdvancedSearch();
            this.setupQuickFilters();
            this.setupMapSearch();
            this.setupSavedSearches();
        },

        setupAdvancedSearch: function() {
            // Toggle filtros avanzados
            $('.inmovilla-advanced-toggle').on('click', function() {
                var $content = $('.inmovilla-advanced-filters');
                var $icon = $(this).find('i');

                $content.slideToggle(300);
                $icon.toggleClass('fa-chevron-down fa-chevron-up');
            });

            // Filtros dependientes
            $('#property-type').on('change', function() {
                var type = $(this).val();
                InmovillaSearch.updateSubtypeOptions(type);
            });

            // Rangos de precio con slider
            if ($('#price-range').length) {
                InmovillaSearch.initPriceSlider();
            }
        },

        setupQuickFilters: function() {
            $('.inmovilla-quick-filter').on('click', function() {
                var filterType = $(this).data('filter-type');
                var filterValue = $(this).data('filter-value');

                // Activar filtro visual
                $('.inmovilla-quick-filter[data-filter-type="' + filterType + '"]').removeClass('active');
                $(this).addClass('active');

                // Aplicar filtro
                $('select[name="' + filterType + '"]').val(filterValue).trigger('change');

                // Ejecutar búsqueda
                InmovillaSearch.performSearch();
            });
        },

        setupMapSearch: function() {
            if ($('#inmovilla-map').length) {
                InmovillaSearch.initMap();
            }
        },

        setupSavedSearches: function() {
            // Guardar búsqueda actual
            $('#save-search').on('click', function() {
                var searchData = InmovillaSearch.getSearchData();
                var searchName = prompt('Nombre para esta búsqueda:');

                if (searchName) {
                    InmovillaSearch.saveSearch(searchName, searchData);
                }
            });

            // Cargar búsqueda guardada
            $('.load-saved-search').on('click', function() {
                var searchId = $(this).data('search-id');
                InmovillaSearch.loadSavedSearch(searchId);
            });
        },

        initPriceSlider: function() {
            $('#price-range').slider({
                range: true,
                min: 0,
                max: 2000000,
                step: 10000,
                values: [0, 500000],
                slide: function(event, ui) {
                    $('#price-min').val(ui.values[0]);
                    $('#price-max').val(ui.values[1]);
                    $('#price-display').text(
                        InmovillaSearch.formatPrice(ui.values[0]) + ' - ' + 
                        InmovillaSearch.formatPrice(ui.values[1])
                    );
                },
                stop: function(event, ui) {
                    InmovillaSearch.performSearch();
                }
            });
        },

        initMap: function() {
            // Inicializar mapa (Google Maps o OpenStreetMap)
            var mapOptions = {
                center: { lat: 40.4168, lng: -3.7038 }, // Madrid
                zoom: 10
            };

            // Si Google Maps está disponible
            if (typeof google !== 'undefined') {
                var map = new google.maps.Map(document.getElementById('inmovilla-map'), mapOptions);
                InmovillaSearch.setupMapEvents(map);
            } else {
                // Fallback a OpenStreetMap con Leaflet
                InmovillaSearch.initLeafletMap();
            }
        },

        initLeafletMap: function() {
            if (typeof L !== 'undefined') {
                var map = L.map('inmovilla-map').setView([40.4168, -3.7038], 10);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                InmovillaSearch.setupLeafletEvents(map);
            }
        },

        updateSubtypeOptions: function(type) {
            var subtypes = {
                'vivienda': ['Piso', 'Casa', 'Chalet', 'Ático', 'Dúplex'],
                'local': ['Bajo Comercial', 'Oficina', 'Nave', 'Local'],
                'terreno': ['Urbano', 'Rústico', 'Industrial']
            };

            var $subtype = $('#property-subtype');
            $subtype.empty().append('<option value="">Subtipo</option>');

            if (subtypes[type]) {
                $.each(subtypes[type], function(i, subtype) {
                    $subtype.append('<option value="' + subtype + '">' + subtype + '</option>');
                });
            }
        },

        performSearch: function() {
            var searchData = InmovillaSearch.getSearchData();
            var $results = $('.inmovilla-properties-grid');

            $results.addClass('inmovilla-loading');

            // Simular búsqueda AJAX
            setTimeout(function() {
                $results.removeClass('inmovilla-loading');
                // Aquí se haría la llamada AJAX real
                console.log('Búsqueda realizada:', searchData);
            }, 1000);
        },

        getSearchData: function() {
            var data = {};

            $('.inmovilla-search-form input, .inmovilla-search-form select').each(function() {
                var name = $(this).attr('name');
                var value = $(this).val();

                if (name && value) {
                    data[name] = value;
                }
            });

            return data;
        },

        saveSearch: function(name, data) {
            var savedSearches = JSON.parse(localStorage.getItem('inmovilla_saved_searches') || '[]');

            savedSearches.push({
                id: Date.now(),
                name: name,
                data: data,
                created: new Date().toISOString()
            });

            localStorage.setItem('inmovilla_saved_searches', JSON.stringify(savedSearches));
            InmovillaSearch.updateSavedSearchesList();
        },

        loadSavedSearch: function(searchId) {
            var savedSearches = JSON.parse(localStorage.getItem('inmovilla_saved_searches') || '[]');
            var search = savedSearches.find(s => s.id == searchId);

            if (search) {
                $.each(search.data, function(name, value) {
                    $('[name="' + name + '"]').val(value);
                });

                InmovillaSearch.performSearch();
            }
        },

        updateSavedSearchesList: function() {
            var savedSearches = JSON.parse(localStorage.getItem('inmovilla_saved_searches') || '[]');
            var $list = $('.inmovilla-saved-searches-list');

            $list.empty();

            savedSearches.forEach(function(search) {
                var $item = $('<div class="saved-search-item">' +
                    '<span>' + search.name + '</span>' +
                    '<button class="load-saved-search" data-search-id="' + search.id + '">Cargar</button>' +
                    '</div>');

                $list.append($item);
            });
        },

        formatPrice: function(price) {
            return new Intl.NumberFormat('es-ES', {
                style: 'currency',
                currency: 'EUR',
                maximumFractionDigits: 0
            }).format(price);
        }
    };

    $(document).ready(function() {
        InmovillaSearch.init();
    });

})(jQuery);
