<?php
/**
 * Template para listado de propiedades
 */

if (!defined('ABSPATH')) {
    exit;
}

$primary_color = get_option('inmovilla_primary_color', '#007cba');
$secondary_color = get_option('inmovilla_secondary_color', '#0073aa');
?>

<div class="inmovilla-properties-wrapper">
    <?php if ($atts['show_search'] === 'true'): ?>
        <div class="inmovilla-search-section">
            <?php echo do_shortcode('[inmovilla_search]'); ?>
        </div>
    <?php endif; ?>

    <div class="inmovilla-properties-list <?php echo esc_attr($atts['layout']); ?>">
        <?php if (!empty($properties['data'])): ?>
            <?php foreach ($properties['data'] as $property): ?>
                <div class="inmovilla-property-card">
                    <?php 
                    global $inmovilla_current_property;
                    $inmovilla_current_property = $property;

                    $template = locate_template('inmovilla/property-card.php');
                    if (!$template) {
                        $template = INMOVILLA_PLUGIN_PATH . 'templates/property-card.php';
                    }
                    include $template;
                    ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="inmovilla-no-results">
                <p><?php _e('No se encontraron propiedades que coincidan con los criterios de búsqueda.', 'inmovilla-properties'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($properties['pagination'])): ?>
        <div class="inmovilla-pagination">
            <?php echo $properties['pagination']; ?>
        </div>
    <?php endif; ?>
</div>

<style>
:root {
    --inmovilla-primary: <?php echo esc_attr($primary_color); ?>;
    --inmovilla-secondary: <?php echo esc_attr($secondary_color); ?>;
}
</style>
