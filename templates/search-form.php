<?php
/**
 * Template del formulario de búsqueda
 *
 * @var array $atts - Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener configuración
$style = $atts['style'] ?? 'horizontal';
$fields = isset($atts['fields']) ? explode(',', $atts['fields']) : array('type', 'city', 'price', 'bedrooms');
$fields = array_map('trim', $fields);

// Obtener datos para los campos
$api = new Inmovilla_API();
$property_types = $api->get_property_types();
$cities = $api->get_cities();
?>

<form class="inmovilla-search-form inmovilla-search-" method="get" id="inmovilla-search-form">
    <div class="search-form-container">
        <?php if ($style === 'horizontal'): ?>
            <div class="search-fields-row">
        <?php endif; ?>
        
        <!-- Campo Tipo de Propiedad -->
        <?php if (in_array('type', $fields)): ?>
            <div class="search-field">
                <label for="property-type"><?php _e('Tipo', 'inmovilla-properties'); ?></label>
                <select name="type" id="property-type" class="form-control">
                    <option value=""><?php _e('Cualquier tipo', 'inmovilla-properties'); ?></option>
                    <?php foreach ($property_types as $type): ?>
                        <option value="<?php echo esc_attr($type['value']); ?>" 
                                <?php selected($_GET['type'] ?? '', $type['value']); ?>>
                            <?php echo esc_html($type['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <!-- Campo Ciudad -->
        <?php if (in_array('city', $fields)): ?>
            <div class="search-field">
                <label for="property-city"><?php _e('Ciudad', 'inmovilla-properties'); ?></label>
                <select name="city" id="property-city" class="form-control">
                    <option value=""><?php _e('Cualquier ciudad', 'inmovilla-properties'); ?></option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo esc_attr($city); ?>" 
                                <?php selected($_GET['city'] ?? '', $city); ?>>
                            <?php echo esc_html($city); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        
        <!-- Campo Precio -->
        <?php if (in_array('price', $fields)): ?>
            <div class="search-field search-field-price">
                <label><?php _e('Precio', 'inmovilla-properties'); ?></label>
                <div class="price-range">
                    <div class="price-min">
                        <input type="number" 
                               name="min_price" 
                               id="min-price" 
                               class="form-control" 
                               placeholder="<?php _e('Precio mín.', 'inmovilla-properties'); ?>"
                               value="<?php echo esc_attr($_GET['min_price'] ?? ''); ?>" />
                    </div>
                    <span class="price-separator">-</span>
                    <div class="price-max">
                        <input type="number" 
                               name="max_price" 
                               id="max-price" 
                               class="form-control" 
                               placeholder="<?php _e('Precio máx.', 'inmovilla-properties'); ?>"
                               value="<?php echo esc_attr($_GET['max_price'] ?? ''); ?>" />
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Campo Habitaciones -->
        <?php if (in_array('bedrooms', $fields)): ?>
            <div class="search-field">
                <label for="property-bedrooms"><?php _e('Habitaciones', 'inmovilla-properties'); ?></label>
                <select name="bedrooms" id="property-bedrooms" class="form-control">
                    <option value=""><?php _e('Cualquier número', 'inmovilla-properties'); ?></option>
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?php echo $i; ?>" 
                                <?php selected($_GET['bedrooms'] ?? '', $i); ?>>
                            <?php echo $i; ?> 
                            <?php echo ($i == 1) ? __('habitación', 'inmovilla-properties') : __('habitaciones', 'inmovilla-properties'); ?>
                        </option>
                    <?php endfor; ?>
                    <option value="7" <?php selected($_GET['bedrooms'] ?? '', '7'); ?>>
                        <?php _e('7+ habitaciones', 'inmovilla-properties'); ?>
                    </option>
                </select>
            </div>
        <?php endif; ?>
        
        <!-- Campo Baños -->
        <?php if (in_array('bathrooms', $fields)): ?>
            <div class="search-field">
                <label for="property-bathrooms"><?php _e('Baños', 'inmovilla-properties'); ?></label>
                <select name="bathrooms" id="property-bathrooms" class="form-control">
                    <option value=""><?php _e('Cualquier número', 'inmovilla-properties'); ?></option>
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <option value="<?php echo $i; ?>" 
                                <?php selected($_GET['bathrooms'] ?? '', $i); ?>>
                            <?php echo $i; ?> 
                            <?php echo ($i == 1) ? __('baño', 'inmovilla-properties') : __('baños', 'inmovilla-properties'); ?>
                        </option>
                    <?php endfor; ?>
                    <option value="5" <?php selected($_GET['bathrooms'] ?? '', '5'); ?>>
                        <?php _e('5+ baños', 'inmovilla-properties'); ?>
                    </option>
                </select>
            </div>
        <?php endif; ?>
        
        <!-- Botón de búsqueda -->
        <div class="search-field search-field-button">
            <button type="submit" class="btn btn-primary search-button">
                <i class="fas fa-search"></i>
                <span><?php _e('Buscar', 'inmovilla-properties'); ?></span>
            </button>
        </div>
        
        <?php if ($style === 'horizontal'): ?>
            </div>
        <?php endif; ?>
        
        <!-- Búsqueda avanzada (colapsar/expandir) -->
        <div class="advanced-search-toggle">
            <button type="button" class="btn btn-link" data-toggle="collapse" data-target="#advanced-search">
                <i class="fas fa-sliders-h"></i>
                <span><?php _e('Búsqueda avanzada', 'inmovilla-properties'); ?></span>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </button>
        </div>
        
        <div class="collapse advanced-search" id="advanced-search">
            <div class="advanced-fields">
                <!-- Superficie -->
                <div class="search-field">
                    <label><?php _e('Superficie (m²)', 'inmovilla-properties'); ?></label>
                    <div class="size-range">
                        <input type="number" 
                               name="min_size" 
                               class="form-control" 
                               placeholder="<?php _e('Mín.', 'inmovilla-properties'); ?>"
                               value="<?php echo esc_attr($_GET['min_size'] ?? ''); ?>" />
                        <span>-</span>
                        <input type="number" 
                               name="max_size" 
                               class="form-control" 
                               placeholder="<?php _e('Máx.', 'inmovilla-properties'); ?>"
                               value="<?php echo esc_attr($_GET['max_size'] ?? ''); ?>" />
                    </div>
                </div>
                
                <!-- Zona -->
                <div class="search-field">
                    <label for="property-zone"><?php _e('Zona', 'inmovilla-properties'); ?></label>
                    <input type="text" 
                           name="zone" 
                           id="property-zone" 
                           class="form-control" 
                           placeholder="<?php _e('Barrio o zona', 'inmovilla-properties'); ?>"
                           value="<?php echo esc_attr($_GET['zone'] ?? ''); ?>" />
                </div>
                
                <!-- Características especiales -->
                <div class="search-field search-field-features">
                    <label><?php _e('Características', 'inmovilla-properties'); ?></label>
                    <div class="features-checkboxes">
                        <label>
                            <input type="checkbox" name="features[]" value="parking" 
                                   <?php checked(in_array('parking', $_GET['features'] ?? array())); ?> />
                            <?php _e('Parking', 'inmovilla-properties'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="features[]" value="terrace" 
                                   <?php checked(in_array('terrace', $_GET['features'] ?? array())); ?> />
                            <?php _e('Terraza', 'inmovilla-properties'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="features[]" value="elevator" 
                                   <?php checked(in_array('elevator', $_GET['features'] ?? array())); ?> />
                            <?php _e('Ascensor', 'inmovilla-properties'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="features[]" value="garden" 
                                   <?php checked(in_array('garden', $_GET['features'] ?? array())); ?> />
                            <?php _e('Jardín', 'inmovilla-properties'); ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resultados de búsqueda -->
    <div class="search-results-summary" id="search-results-summary" style="display: none;"></div>
</form>

<!-- Loading overlay -->
<div class="search-loading" id="search-loading" style="display: none;">
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <span><?php _e('Buscando propiedades...', 'inmovilla-properties'); ?></span>
    </div>
</div>