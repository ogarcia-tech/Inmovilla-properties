<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar búsquedas
 */
class InmovillaSearch {

    public function __construct() {
        // Constructor básico
    }

    /**
     * Manejar búsqueda AJAX
     */
    public function handle_ajax_search() {

        $search_term = sanitize_text_field($_POST['search'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? '');
        $location = sanitize_text_field($_POST['location'] ?? '');

        // Aquí iría la lógica de búsqueda real con la API
        $results = array(
            'success' => true,
            'data' => array(),
            'message' => __('Búsqueda completada (pendiente de implementar API)', 'inmovilla-properties')
        );

        wp_send_json($results);
    }
}
