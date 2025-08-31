<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar búsquedas
 */
class InmovillaSearch {

    public function __construct() {
        add_action('wp_ajax_inmovilla_search', array($this, 'handle_ajax_search'));
        add_action('wp_ajax_nopriv_inmovilla_search', array($this, 'handle_ajax_search'));
    }

    /**
     * Manejar búsqueda AJAX
     */
    public function handle_ajax_search() {
        check_ajax_referer('inmovilla_public_nonce', 'nonce');

        if (is_user_logged_in() && !current_user_can('read')) {
            wp_send_json_error(array(
                'message' => __('Permisos insuficientes', 'inmovilla-properties'),
            ));
        }

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
