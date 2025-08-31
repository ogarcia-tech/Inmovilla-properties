<?php
/**
 * Template para tarjeta de propiedad individual
 * MEJORADO para traducciones y personalización
 */

if (!defined('ABSPATH')) {
    exit;
}

global $inmovilla_current_property;
$property = $inmovilla_current_property;

if (!$property) {
    return;
}

$properties_manager = new Inmovilla_Properties_Manager();
$property_url = $properties_manager->get_property_url($property);
$featured_image = $properties_manager->get_property_featured_image($property);
$price = !empty($property['price']) ? $properties_manager->format_price($property['price']) : '';
?>

<article class="inmovilla-card">
    <div class="inmovilla-card-image">
        <?php if ($featured_image): ?>
            <img src="<?php echo esc_url($featured_image); ?>" 
                 alt="<?php echo esc_attr($property['title'] ?? ''); ?>"
                 loading="lazy">
        <?php else: ?>
            <div class="inmovilla-card-no-image">
                <span><?php _e('Sin imagen', 'inmovilla-properties'); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($property['featured'])): ?>
            <span class="inmovilla-card-badge featured"><?php _e('Destacada', 'inmovilla-properties'); ?></span>
        <?php endif; ?>

        <?php if (!empty($property['status']) && $property['status'] !== 'active'): ?>
            <span class="inmovilla-card-badge status"><?php echo esc_html($property['status']); ?></span>
        <?php endif; ?>

        <button class="inmovilla-favorite-btn" data-property-id="<?php echo esc_attr($property['id']); ?>" title="<?php _e('Agregar a favoritos', 'inmovilla-properties'); ?>">
            <span class="heart-icon">♡</span>
        </button>
    </div>

    <div class="inmovilla-card-content">
        <div class="inmovilla-card-header">
            <?php if (!empty($property['type'])): ?>
                <span class="inmovilla-card-type"><?php echo esc_html($property['type']); ?></span>
            <?php endif; ?>

            <?php if ($price): ?>
                <span class="inmovilla-card-price"><?php echo esc_html($price); ?></span>
            <?php endif; ?>
        </div>

        <h3 class="inmovilla-card-title">
            <a href="<?php echo esc_url($property_url); ?>">
                <?php echo esc_html($property['title'] ?? __('Ver propiedad', 'inmovilla-properties')); ?>
            </a>
        </h3>

        <?php if (!empty($property['location'])): ?>
            <div class="inmovilla-card-location">
                <i class="inmovilla-icon-location"></i> <?php 
                $location_parts = array();
                if (!empty($property['location']['city'])) {
                    $location_parts[] = $property['location']['city'];
                }
                if (!empty($property['location']['district'])) {
                    $location_parts[] = $property['location']['district'];
                }
                echo esc_html(implode(', ', $location_parts));
                ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($property['description'])): ?>
            <div class="inmovilla-card-description">
                <?php echo wp_trim_words(strip_tags($property['description']), 15); ?>
            </div>
        <?php endif; ?>

        <div class="inmovilla-card-features">
            <?php if (!empty($property['bedrooms'])): ?>
                <span class="feature feature-bedrooms">
                    <i class="inmovilla-icon-bedrooms"></i> <?php printf(esc_html__('%s hab.', 'inmovilla-properties'), esc_html($property['bedrooms'])); ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($property['bathrooms'])): ?>
                <span class="feature feature-bathrooms">
                    <i class="inmovilla-icon-bathrooms"></i> <?php printf(esc_html__('%s baños', 'inmovilla-properties'), esc_html($property['bathrooms'])); ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($property['size'])): ?>
                <span class="feature feature-size">
                    <i class="inmovilla-icon-size"></i> <?php printf(esc_html__('%s m²', 'inmovilla-properties'), esc_html($property['size'])); ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="inmovilla-card-actions">
            <a href="<?php echo esc_url($property_url); ?>" class="inmovilla-btn inmovilla-btn-primary">
                <?php _e('Ver detalles', 'inmovilla-properties'); ?>
            </a>

            <button class="inmovilla-btn inmovilla-btn-secondary inmovilla-contact-btn" 
                    data-property-id="<?php echo esc_attr($property['id']); ?>">
                <?php _e('Contactar', 'inmovilla-properties'); ?>
            </button>
        </div>
    </div>
</article>