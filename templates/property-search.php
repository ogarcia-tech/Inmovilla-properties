<?php
/**
 * Template para formulario de búsqueda
 */

if (!defined('ABSPATH')) {
    exit;
}

$property_types = !empty($property_types) && !is_wp_error($property_types) ? $property_types : array();
$locations = !empty($locations) && !is_wp_error($locations) ? $locations : array();
?>

<div class="inmovilla-search-form <?php echo esc_attr($atts['layout']); ?>">
    <form class="inmovilla-search" method="GET" action="<?php echo esc_url(home_url('/buscar-propiedades/')); ?>">
        <div class="search-row basic-search">
            <div class="search-field">
                <label for="search-type"><?php _e('Tipo:', 'inmovilla-properties'); ?></label>
                <select name="type" id="search-type">
                    <option value=""><?php _e('Cualquier tipo', 'inmovilla-properties'); ?></option>
                    <?php if (!empty($property_types['data'])): ?>
                        <?php foreach ($property_types['data'] as $type): ?>
                            <option value="<?php echo esc_attr($type['id'] ?? $type['name']); ?>">
                                <?php echo esc_html($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="search-field">
                <label for="search-location"><?php _e('Ubicación:', 'inmovilla-properties'); ?></label>
                <select name="location" id="search-location">
                    <option value=""><?php _e('Cualquier ubicación', 'inmovilla-properties'); ?></option>
                    <?php if (!empty($locations['data'])): ?>
                        <?php foreach ($locations['data'] as $location): ?>
                            <option value="<?php echo esc_attr($location['id'] ?? $location['name']); ?>">
                                <?php echo esc_html($location['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="search-field price-range">
                <label for="search-min-price"><?php _e('Precio:', 'inmovilla-properties'); ?></label>
                <div class="price-inputs">
                    <input type="number" name="min_price" id="search-min-price" placeholder="<?php _e('Mín.', 'inmovilla-properties'); ?>">
                    <span class="price-separator">-</span>
                    <input type="number" name="max_price" id="search-max-price" placeholder="<?php _e('Máx.', 'inmovilla-properties'); ?>">
                </div>
            </div>

            <div class="search-actions">
                <button type="submit" class="inmovilla-btn inmovilla-btn-primary">
                    🔍 <?php _e('Buscar', 'inmovilla-properties'); ?>
                </button>

                <?php if ($atts['show_advanced'] === 'true'): ?>
                    <button type="button" class="inmovilla-btn inmovilla-btn-link advanced-toggle">
                        <?php _e('Búsqueda avanzada', 'inmovilla-properties'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($atts['show_advanced'] === 'true'): ?>
            <div class="search-row advanced-search" style="display: none;">
                <div class="search-field">
                    <label for="search-bedrooms"><?php _e('Habitaciones:', 'inmovilla-properties'); ?></label>
                    <select name="bedrooms" id="search-bedrooms">
                        <option value=""><?php _e('Cualquiera', 'inmovilla-properties'); ?></option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                        <option value="4">4+</option>
                        <option value="5">5+</option>
                    </select>
                </div>

                <div class="search-field">
                    <label for="search-bathrooms"><?php _e('Baños:', 'inmovilla-properties'); ?></label>
                    <select name="bathrooms" id="search-bathrooms">
                        <option value=""><?php _e('Cualquiera', 'inmovilla-properties'); ?></option>
                        <option value="1">1+</option>
                        <option value="2">2+</option>
                        <option value="3">3+</option>
                    </select>
                </div>

                <div class="search-field size-range">
                    <label for="search-min-size"><?php _e('Superficie (m²):', 'inmovilla-properties'); ?></label>
                    <div class="size-inputs">
                        <input type="number" name="min_size" id="search-min-size" placeholder="<?php _e('Mín.', 'inmovilla-properties'); ?>">
                        <span class="size-separator">-</span>
                        <input type="number" name="max_size" id="search-max-size" placeholder="<?php _e('Máx.', 'inmovilla-properties'); ?>">
                    </div>
                </div>

                <div class="search-field">
                    <label>
                        <input type="checkbox" name="featured" value="1">
                        <?php _e('Solo destacadas', 'inmovilla-properties'); ?>
                    </label>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const advancedToggle = document.querySelector('.advanced-toggle');
    const advancedSearch = document.querySelector('.advanced-search');

    if (advancedToggle && advancedSearch) {
        advancedToggle.addEventListener('click', function() {
            const isVisible = advancedSearch.style.display !== 'none';
            advancedSearch.style.display = isVisible ? 'none' : 'block';
            this.textContent = isVisible ? 
                '<?php _e('Búsqueda avanzada', 'inmovilla-properties'); ?>' : 
                '<?php _e('Búsqueda básica', 'inmovilla-properties'); ?>';
        });
    }
});
</script>
