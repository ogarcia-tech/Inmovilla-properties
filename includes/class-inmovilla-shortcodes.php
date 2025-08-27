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
     * Shortcode para mostrar propiedades
     */
    public function properties_shortcode($atts) {

        $atts = shortcode_atts(array(
            'limit' => 12,
            'type' => '',
            'location' => '',
            'featured' => false,
        ), $atts, 'inmovilla_properties');

        ob_start();

        // Aquí iría la lógica para obtener y mostrar propiedades
        echo '<div class="inmovilla-properties-grid">';
        echo '<p>' . __('Lista de propiedades (pendiente de conexión API)', 'inmovilla-properties') . '</p>';
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Shortcode para buscador
     */
    public function search_shortcode($atts) {

        $atts = shortcode_atts(array(
            'fields' => 'type,location,price',
            'layout' => 'horizontal',
        ), $atts, 'inmovilla_search');

        ob_start();

        echo '<div class="inmovilla-search-form">';
        echo '<form method="get" action="' . home_url('/buscar-propiedades/') . '">';
        echo '<input type="text" name="search" placeholder="' . __('Buscar propiedades...', 'inmovilla-properties') . '" />';
        echo '<button type="submit">' . __('Buscar', 'inmovilla-properties') . '</button>';
        echo '</form>';
        echo '</div>';

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
