<?php
/**
 * Template para ficha individual de propiedad
 */

if (!defined('ABSPATH')) {
    exit;
}

global $inmovilla_property;
$property = $inmovilla_property;

if (!$property) {
    echo '<p>' . __('Propiedad no encontrada.', 'inmovilla-properties') . '</p>';
    return;
}

$properties_manager = new Inmovilla_Properties_Manager();
$gallery = $properties_manager->get_property_gallery($property);
$price = !empty($property['price']) ? $properties_manager->format_price($property['price']) : '';

get_header(); ?>

<div class="inmovilla-single-property">
    <div class="inmovilla-property-header">
        <div class="container">
            <div class="property-breadcrumb">
                <a href="<?php echo home_url(); ?>"><?php _e('Inicio', 'inmovilla-properties'); ?></a>
                <span class="separator">›</span>
                <a href="<?php echo home_url('/propiedades/'); ?>"><?php _e('Propiedades', 'inmovilla-properties'); ?></a>
                <span class="separator">›</span>
                <span class="current"><?php echo esc_html($property['title'] ?? __('Propiedad', 'inmovilla-properties')); ?></span>
            </div>

            <h1 class="property-title"><?php echo esc_html($property['title'] ?? __('Propiedad', 'inmovilla-properties')); ?></h1>

            <div class="property-meta">
                <?php if (!empty($property['location'])): ?>
                    <span class="property-location">
                        📍 <?php echo esc_html($property['location']['city'] ?? ''); ?>
                        <?php if (!empty($property['location']['district'])): ?>
                            - <?php echo esc_html($property['location']['district']); ?>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>

                <?php if (!empty($property['reference'])): ?>
                    <span class="property-reference">
                        <?php _e('Ref:', 'inmovilla-properties'); ?> <?php echo esc_html($property['reference']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="inmovilla-property-content">
        <div class="container">
            <div class="property-main">
                <div class="property-gallery-section">
                    <?php if (!empty($gallery)): ?>
                        <div class="inmovilla-gallery">
                            <div class="gallery-main">
                                <img src="<?php echo esc_url($gallery[0]['url'] ?? ''); ?>" 
                                     alt="<?php echo esc_attr($property['title'] ?? ''); ?>"
                                     class="main-image">
                            </div>

                            <?php if (count($gallery) > 1): ?>
                                <div class="gallery-thumbs">
                                    <?php foreach ($gallery as $index => $image): ?>
                                        <img src="<?php echo esc_url($image['url']); ?>" 
                                             alt="<?php echo esc_attr($property['title'] ?? ''); ?>"
                                             class="thumb-image <?php echo $index === 0 ? 'active' : ''; ?>"
                                             data-index="<?php echo $index; ?>">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="property-details-section">
                    <div class="property-price-contact">
                        <?php if ($price): ?>
                            <div class="property-price"><?php echo esc_html($price); ?></div>
                        <?php endif; ?>

                        <div class="property-actions">
                            <button class="inmovilla-btn inmovilla-btn-primary inmovilla-contact-btn"
                                    data-property-id="<?php echo esc_attr($property['id']); ?>"
                                    data-subject="<?php esc_attr_e('Hacer una oferta', 'inmovilla-properties'); ?>">
                                💰 <?php _e('Hacer una oferta', 'inmovilla-properties'); ?>
                            </button>

                            <button class="inmovilla-btn inmovilla-btn-primary inmovilla-contact-btn"
                                    data-property-id="<?php echo esc_attr($property['id']); ?>"
                                    data-subject="<?php esc_attr_e('Solicitar cita', 'inmovilla-properties'); ?>">
                                📅 <?php _e('Solicitar cita', 'inmovilla-properties'); ?>
                            </button>

                            <button class="inmovilla-btn inmovilla-btn-primary inmovilla-contact-btn"
                                    data-property-id="<?php echo esc_attr($property['id']); ?>"
                                    data-subject="<?php esc_attr_e('Solicitar llamada', 'inmovilla-properties'); ?>">
                                📞 <?php _e('Solicitar llamada', 'inmovilla-properties'); ?>
                            </button>

                            <button class="inmovilla-btn inmovilla-btn-secondary inmovilla-favorite-btn"
                                    data-property-id="<?php echo esc_attr($property['id']); ?>">
                                ♡ <?php _e('Favorito', 'inmovilla-properties'); ?>
                            </button>

                            <button class="inmovilla-btn inmovilla-btn-outline inmovilla-share-btn">
                                🔗 <?php _e('Compartir', 'inmovilla-properties'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="property-features">
                        <h3><?php _e('Características principales', 'inmovilla-properties'); ?></h3>

                        <div class="features-grid">
                            <?php if (!empty($property['type'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Tipo:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['type']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['bedrooms'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Habitaciones:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['bedrooms']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['bathrooms'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Baños:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['bathrooms']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['size'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Superficie:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['size']); ?> m²</span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['year_built'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Año construcción:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['year_built']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($property['description'])): ?>
                        <div class="property-description">
                            <h3><?php _e('Descripción', 'inmovilla-properties'); ?></h3>
                            <div class="description-content">
                                <?php echo wp_kses_post($property['description']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($property['location']['lat']) && !empty($property['location']['lng'])): ?>
                        <div class="property-map">
                            <h3><?php _e('Ubicación', 'inmovilla-properties'); ?></h3>
                            <div id="inmovilla-map" data-lat="<?php echo esc_attr($property['location']['lat']); ?>" 
                                 data-lng="<?php echo esc_attr($property['location']['lng']); ?>">
                                <!-- Mapa se cargará aquí -->
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Propiedades relacionadas -->
            <div class="related-properties">
                <h3><?php _e('Propiedades similares', 'inmovilla-properties'); ?></h3>
                <?php echo do_shortcode('[inmovilla_properties limit="4" type="' . ($property['type'] ?? '') . '" location="' . ($property['location']['city'] ?? '') . '"]'); ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de contacto -->
<div id="inmovilla-contact-modal" class="inmovilla-contact-modal" style="display:none;">
    <div class="inmovilla-modal-content">
        <span class="inmovilla-modal-close">&times;</span>
        <form id="inmovilla-contact-form">
            <input type="hidden" name="property_id" value="<?php echo esc_attr($property['id']); ?>">
            <input type="hidden" name="subject" id="inmovilla-contact-subject" value="">

            <div class="form-group">
                <label for="inmovilla-name"><?php _e('Nombre', 'inmovilla-properties'); ?></label>
                <input type="text" id="inmovilla-name" name="name" required>
            </div>

            <div class="form-group">
                <label for="inmovilla-email"><?php _e('Email', 'inmovilla-properties'); ?></label>
                <input type="email" id="inmovilla-email" name="email" required>
            </div>

            <div class="form-group">
                <label for="inmovilla-phone"><?php _e('Teléfono', 'inmovilla-properties'); ?></label>
                <input type="text" id="inmovilla-phone" name="phone">
            </div>

            <div class="form-group">
                <label for="inmovilla-message"><?php _e('Mensaje', 'inmovilla-properties'); ?></label>
                <textarea id="inmovilla-message" name="message" required></textarea>
            </div>

            <div class="form-group">
                <label for="inmovilla-source"><?php _e('¿Cómo nos has conocido?', 'inmovilla-properties'); ?></label>
                <input type="text" id="inmovilla-source" name="source">
            </div>

            <div class="form-group">
                <label><input type="checkbox" name="privacy" required> <?php _e('Acepto la política de privacidad', 'inmovilla-properties'); ?></label>
            </div>

            <button type="submit" class="inmovilla-btn inmovilla-btn-primary"><?php _e('Enviar', 'inmovilla-properties'); ?></button>
        </form>
    </div>
</div>

<?php get_footer(); ?>
