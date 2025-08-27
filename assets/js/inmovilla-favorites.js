/*!
 * Inmovilla Properties - Favorites JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    window.InmovillaFavorites = {

        storageKey: 'inmovilla_favorites',

        init: function() {
            this.loadFavorites();
            this.setupEventHandlers();
            this.updateCounter();
        },

        setupEventHandlers: function() {
            // Toggle favorito
            $(document).on('click', '.inmovilla-favorite-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var propertyId = $(this).data('property-id');
                InmovillaFavorites.toggleFavorite(propertyId, $(this));
            });

            // Limpiar todos los favoritos
            $(document).on('click', '.clear-all-favorites', function() {
                if (confirm('¿Seguro que quieres eliminar todos los favoritos?')) {
                    InmovillaFavorites.clearAll();
                }
            });

            // Compartir favoritos
            $(document).on('click', '.share-favorites', function() {
                InmovillaFavorites.shareFavorites();
            });
        },

        toggleFavorite: function(propertyId, $btn) {
            var favorites = this.getFavorites();
            var isActive = $btn.hasClass('active');

            if (isActive) {
                // Eliminar de favoritos
                favorites = favorites.filter(id => id !== propertyId);
                $btn.removeClass('active');
                this.showNotification('Eliminado de favoritos', 'info');
            } else {
                // Añadir a favoritos
                favorites.push(propertyId);
                $btn.addClass('active');
                this.showNotification('Añadido a favoritos', 'success');
            }

            this.saveFavorites(favorites);
            this.updateCounter();
            this.triggerUpdate(propertyId, !isActive);
        },

        getFavorites: function() {
            try {
                return JSON.parse(localStorage.getItem(this.storageKey) || '[]');
            } catch (e) {
                return [];
            }
        },

        saveFavorites: function(favorites) {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(favorites));
            } catch (e) {
                console.warn('No se pudieron guardar los favoritos');
            }
        },

        loadFavorites: function() {
            var favorites = this.getFavorites();

            favorites.forEach(function(propertyId) {
                $('.inmovilla-favorite-btn[data-property-id="' + propertyId + '"]').addClass('active');
            });
        },

        updateCounter: function() {
            var count = this.getFavorites().length;
            $('.inmovilla-favorites-count').text(count);

            // Mostrar/ocultar badge
            if (count > 0) {
                $('.inmovilla-favorites-badge').show();
            } else {
                $('.inmovilla-favorites-badge').hide();
            }
        },

        clearAll: function() {
            localStorage.removeItem(this.storageKey);
            $('.inmovilla-favorite-btn').removeClass('active');
            this.updateCounter();
            this.showNotification('Todos los favoritos han sido eliminados', 'info');
        },

        shareFavorites: function() {
            var favorites = this.getFavorites();

            if (favorites.length === 0) {
                this.showNotification('No tienes favoritos para compartir', 'warning');
                return;
            }

            // Crear URL con favoritos
            var shareUrl = window.location.origin + window.location.pathname + '?favorites=' + favorites.join(',');

            // Copiar al portapapeles
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareUrl).then(function() {
                    InmovillaFavorites.showNotification('URL copiada al portapapeles', 'success');
                });
            } else {
                // Fallback para navegadores antiguos
                var textArea = document.createElement('textarea');
                textArea.value = shareUrl;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);

                InmovillaFavorites.showNotification('URL copiada', 'success');
            }
        },

        loadSharedFavorites: function() {
            // Cargar favoritos desde URL si existen
            var urlParams = new URLSearchParams(window.location.search);
            var sharedFavorites = urlParams.get('favorites');

            if (sharedFavorites) {
                var favorites = sharedFavorites.split(',');
                this.saveFavorites(favorites);
                this.loadFavorites();
                this.updateCounter();

                this.showNotification('Favoritos compartidos cargados', 'success');
            }
        },

        exportFavorites: function() {
            var favorites = this.getFavorites();

            if (favorites.length === 0) {
                this.showNotification('No tienes favoritos para exportar', 'warning');
                return;
            }

            // Crear datos de exportación
            var exportData = {
                favorites: favorites,
                exported_at: new Date().toISOString(),
                count: favorites.length
            };

            // Descargar como JSON
            var dataStr = JSON.stringify(exportData, null, 2);
            var dataBlob = new Blob([dataStr], { type: 'application/json' });
            var url = URL.createObjectURL(dataBlob);

            var link = document.createElement('a');
            link.href = url;
            link.download = 'inmovilla-favoritos-' + new Date().toISOString().split('T')[0] + '.json';
            link.click();

            URL.revokeObjectURL(url);

            this.showNotification('Favoritos exportados', 'success');
        },

        importFavorites: function(file) {
            var reader = new FileReader();

            reader.onload = function(e) {
                try {
                    var data = JSON.parse(e.target.result);

                    if (data.favorites && Array.isArray(data.favorites)) {
                        InmovillaFavorites.saveFavorites(data.favorites);
                        InmovillaFavorites.loadFavorites();
                        InmovillaFavorites.updateCounter();

                        InmovillaFavorites.showNotification(
                            'Favoritos importados: ' + data.favorites.length + ' propiedades',
                            'success'
                        );
                    } else {
                        InmovillaFavorites.showNotification('Archivo de favoritos inválido', 'error');
                    }
                } catch (error) {
                    InmovillaFavorites.showNotification('Error al leer el archivo', 'error');
                }
            };

            reader.readAsText(file);
        },

        triggerUpdate: function(propertyId, isFavorite) {
            // Disparar evento personalizado para otros componentes
            $(document).trigger('inmovilla:favorite:changed', {
                propertyId: propertyId,
                isFavorite: isFavorite,
                totalFavorites: this.getFavorites().length
            });
        },

        showNotification: function(message, type) {
            var $notification = $('<div class="inmovilla-notification inmovilla-notification-' + type + '">')
                .text(message)
                .appendTo('body')
                .fadeIn();

            setTimeout(function() {
                $notification.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };

    $(document).ready(function() {
        InmovillaFavorites.init();
        InmovillaFavorites.loadSharedFavorites();
    });

})(jQuery);
