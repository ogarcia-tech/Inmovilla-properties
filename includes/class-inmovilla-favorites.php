<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar favoritos
 */
class InmovillaFavorites {

    public function __construct() {
        // Constructor básico
    }

    /**
     * Manejar favoritos AJAX
     */
    public function handle_ajax_favorites() {

        $property_id = sanitize_text_field($_POST['property_id'] ?? '');
        $action = sanitize_text_field($_POST['action_type'] ?? 'toggle');

        // Aquí iría la lógica de favoritos
        $results = array(
            'success' => true,
            'data' => array(
                'property_id' => $property_id,
                'is_favorite' => true
            ),
            'message' => __('Favorito actualizado', 'inmovilla-properties')
        );

        wp_send_json($results);
    }
}
