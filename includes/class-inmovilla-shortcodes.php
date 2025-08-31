<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar shortcodes
 */
class InmovillaShortcodes {

    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
    }

    /**
     * Registrar shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('inmovilla_properties', array($this, 'properties_shortcode'));
        add_shortcode('inmovilla_search', array($this, 'search_shortcode'));
        add_shortcode('inmovilla_featured', array($this, 'featured_shortcode'));
    }

    /**
     * Shortcode para mostrar propiedades.
     * CORREGIDO: Ahora utiliza WP_Query para obtener las propiedades desde la base de datos,
     * alineándose con la integración de Elementor.
     */
    public function properties_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => get_option('posts_per_page', 12), // Usar el ajuste de WordPress por defecto
            'type' => '',
            'location' => '',
            'featured' => false,
        ), $atts, 'inmovilla_properties');

        // Determinar la página actual para la paginación
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

        // Construir los argumentos para la consulta de WordPress
        $args = array(
            'post_type' => 'inmovilla_property',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'paged' => $paged,
        );

        // Añadir filtros a la consulta si se especifican en el shortcode
        $meta_query = array();
        if (!empty($atts['type'])) {
            $meta_query[] = array(
                'key' => 'property_type',
                'value' => sanitize_text_field($atts['type']),
                'compare' => '=',
            );
        }
        if (!empty($atts['location'])) {
            $meta_query[] = array(
                'key' => 'location_city',
                'value' => sanitize_text_field($atts['location']),
                'compare' => '=',
            );
        }
        if ($atts['featured']) {
            $meta_query[] = array(
                'key' => 'featured',
                'value' => '1', // Suponiendo que '1' o 'true' significa destacada
                'compare' => '=',
            );
        }

        if (count($meta_query) > 0) {
            $args['meta_query'] = $meta_query;
        }

        // Realizar la consulta a la base de datos
        $properties_query = new WP_Query($args);

        ob_start();

        if ($properties_query->have_posts()) {
            echo '<div class="inmovilla-properties-grid">';

            // Bucle para mostrar cada propiedad
            while ($properties_query->have_posts()) {
                $properties_query->the_post();
                
                // Hacemos el post actual accesible para la plantilla de la tarjeta
                // y cargamos los datos de Inmovilla para compatibilidad.
                global $post, $inmovilla_current_property;
                $inmovilla_current_property = get_post_meta($post->ID); // Cargamos todos los campos personalizados

                // Simulamos la estructura anterior para que la plantilla no se rompa
                $inmovilla_current_property['id'] = get_post_meta($post->ID, '_inmovilla_id', true);
                $inmovilla_current_property['title'] = get_the_title();
                $inmovilla_current_property['description'] = get_the_content();
                // etc... (puedes añadir más si la plantilla los necesita)

                // Cargamos la plantilla de la tarjeta
                $template_path = INMOVILLA_PROPERTIES_PLUGIN_DIR . 'templates/property-card.php';
                if (file_exists($template_path)) {
                    include $template_path;
                }
            }

            echo '</div>';

            // Generar paginación
            echo '<div class="inmovilla-pagination">';
            echo paginate_links(array(
                'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                'total' => $properties_query->max_num_pages,
                'current' => max(1, $paged),
                'format' => '?paged=%#%',
                'prev_text' => __('&laquo; Anterior'),
                'next_text' => __('Siguiente &raquo;'),
            ));
            echo '</div>';

        } else {
            echo '<p>' . __('No se encontraron propiedades que coincidan con los criterios de búsqueda.', 'inmovilla-properties') . '</p>';
        }

        // Restaurar datos originales del post
        wp_reset_postdata();
        unset($GLOBALS['inmovilla_current_property']);

        return ob_get_clean();
    }

    /**
     * Shortcode para buscador
     */
    public function search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'horizontal',
            'fields' => 'type,city,price,bedrooms',
        ), $atts, 'inmovilla_search');

        ob_start();
        $template_path = INMOVILLA_PROPERTIES_PLUGIN_DIR . 'templates/search-form.php';
        if (file_exists($template_path)) {
            include $template_path; 
        }
        return ob_get_clean();
    }

    /**
     * Shortcode para propiedades destacadas
     */
    public function featured_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 6,
        ), $atts, 'inmovilla_featured');

        return $this->properties_shortcode(array_merge($atts, array('featured' => true)));
    }
}