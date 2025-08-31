<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar favoritos
 */
class InmovillaFavorites {

    public function __construct() {
        add_action('wp_ajax_inmovilla_toggle_favorite', array($this, 'handle_ajax_favorites'));
        add_action('wp_ajax_nopriv_inmovilla_toggle_favorite', array($this, 'handle_ajax_favorites'));
    }

    /**
     * Manejar favoritos AJAX
     */
    public function handle_ajax_favorites() {
        check_ajax_referer('inmovilla_public_nonce', 'nonce');

        if (is_user_logged_in() && !current_user_can('read')) {
            wp_send_json_error(array(
                'message' => __('Permisos insuficientes', 'inmovilla-properties'),
            ));
        }

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
