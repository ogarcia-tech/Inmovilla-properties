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
                                    data-property-id="<?php echo esc_attr($property['id']); ?>">
                                📞 <?php _e('Contactar', 'inmovilla-properties'); ?>
                            </button>

                            <button class="inmovilla-btn inmovilla-btn-secondary inmovilla-favorite-btn"
                                    data-property-id="<?php echo esc_attr($property['id']); ?>">
                                ♡ <?php _e('Favorito', 'inmovilla-properties'); ?>
                            </button>

                            <button class="inmovilla-btn inmovilla-btn-outline inmovilla-share-btn">
                                🔗 <?php _e('Compartir', 'inmovilla-properties'); ?>
                            </button>

                            <?php if (!empty($property['property_pdf'])): ?>
                                <button class="inmovilla-btn inmovilla-btn-outline inmovilla-print-btn"
                                        data-pdf="<?php echo esc_url($property['property_pdf']); ?>">
                                    🖨 <?php _e('Imprimir', 'inmovilla-properties'); ?>
                                </button>
                            <?php endif; ?>

                            <button class="inmovilla-btn inmovilla-btn-outline inmovilla-send-btn"
                                    data-subject="<?php echo esc_attr($property['title'] ?? __('Propiedad', 'inmovilla-properties')); ?>"
                                    data-url="<?php echo esc_url($properties_manager->get_property_url($property)); ?>">
                                ✉ <?php _e('Enviar', 'inmovilla-properties'); ?>
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


                            <?php if (!empty($property['orientation'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Orientación:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['orientation']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['surface_useful'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Superficie útil:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['surface_useful']); ?> m²</span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['plot'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Parcela:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['plot']); ?> m²</span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['views'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Vistas:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['views']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['condition'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Estado:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['condition']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['double_bedrooms'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Dormitorios dobles:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['double_bedrooms']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['toilets'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Aseos:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['toilets']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['floors'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Plantas:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['floors']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['parking'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Parking:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['parking']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($property['air_conditioning']) && $property['air_conditioning'] !== ''): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Aire acondicionado:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value">
                                        <?php
                                        $ac = $property['air_conditioning'];
                                        echo ($ac === '1' || $ac === 1 || $ac === true) ? __('Sí', 'inmovilla-properties') :
                                            (($ac === '0' || $ac === 0 || $ac === false) ? __('No', 'inmovilla-properties') : esc_html($ac));
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['floor_type'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Tipo de suelo:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['floor_type']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($property['exterior_type'])): ?>
                                <div class="feature-item">
                                    <span class="feature-label"><?php _e('Tipo exterior:', 'inmovilla-properties'); ?></span>
                                    <span class="feature-value"><?php echo esc_html($property['exterior_type']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

                    <?php if (!empty($property['energy_rating']) || !empty($property['energy_consumption']) || !empty($property['co2_emissions'])): ?>
                        <div class="property-energy">
                            <h3><?php _e('Eficiencia energética', 'inmovilla-properties'); ?></h3>
                            <div class="energy-grid">
                                <?php if (!empty($property['energy_rating'])): ?>
                                    <div class="energy-item">
                                        <span class="energy-label"><?php _e('Certificación energética:', 'inmovilla-properties'); ?></span>
                                        <span class="energy-value"><?php echo esc_html($property['energy_rating']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($property['energy_consumption'])): ?>
                                    <div class="energy-item">
                                        <span class="energy-label"><?php _e('Consumo de energía:', 'inmovilla-properties'); ?></span>
                                        <span class="energy-value"><?php echo esc_html($property['energy_consumption']); ?> kWh/m²</span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($property['co2_emissions'])): ?>
                                    <div class="energy-item">
                                        <span class="energy-label"><?php _e('Emisiones CO₂:', 'inmovilla-properties'); ?></span>
                                        <span class="energy-value"><?php echo esc_html($property['co2_emissions']); ?> kg CO₂/m²</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($property['description'])): ?>
                        <div class="property-description">
                            <h3><?php _e('Descripción', 'inmovilla-properties'); ?></h3>
                            <div class="description-content">
                                <?php echo wp_kses_post($property['description']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php

                    $has_media = !empty($property['video_url']) || !empty($property['virtual_tour_url']) || !empty($property['property_pdf']);
                    if ($has_media): ?>
                        <div class="property-media">
                            <h3><?php _e('Medios', 'inmovilla-properties'); ?></h3>
                            <ul class="media-list">
                                <?php if (!empty($property['video_url'])): ?>
                                    <li><a href="<?php echo esc_url($property['video_url']); ?>" target="_blank" rel="noopener"><?php _e('Video', 'inmovilla-properties'); ?></a></li>
                                <?php endif; ?>
                                <?php if (!empty($property['virtual_tour_url'])): ?>
                                    <li><a href="<?php echo esc_url($property['virtual_tour_url']); ?>" target="_blank" rel="noopener"><?php _e('Tour virtual', 'inmovilla-properties'); ?></a></li>
                                <?php endif; ?>
                                <?php if (!empty($property['property_pdf'])): ?>
                                    <li><a href="<?php echo esc_url($property['property_pdf']); ?>" target="_blank" rel="noopener"><?php _e('PDF', 'inmovilla-properties'); ?></a></li>
                                <?php endif; ?>

                            </ul>
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

            <?php
            $agency_contact = inmovilla_get_setting('agency_contact');
            $legal_note = inmovilla_get_setting('legal_note');
            if ($agency_contact || $legal_note): ?>
                <div class="property-extra-info">
                    <?php if ($agency_contact): ?>
                        <div class="agency-contact">
                            <?php echo wp_kses_post($agency_contact); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($legal_note): ?>
                        <div class="legal-note">
                            <?php echo wp_kses_post($legal_note); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
