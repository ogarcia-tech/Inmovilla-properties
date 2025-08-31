<?php
/**
 * Manejador de peticiones AJAX para Inmovilla Properties
 */

if (!defined('ABSPATH')) {
    exit;
}

class Inmovilla_Ajax {
    
    public function __construct() {
        // AJAX para usuarios logueados y no logueados
        add_action('wp_ajax_inmovilla_load_properties', array($this, 'load_properties'));
        add_action('wp_ajax_nopriv_inmovilla_load_properties', array($this, 'load_properties'));
        
        add_action('wp_ajax_inmovilla_search_properties', array($this, 'search_properties'));
        add_action('wp_ajax_nopriv_inmovilla_search_properties', array($this, 'search_properties'));
        
        add_action('wp_ajax_inmovilla_load_more', array($this, 'load_more_properties'));
        add_action('wp_ajax_nopriv_inmovilla_load_more', array($this, 'load_more_properties'));
        
        add_action('wp_ajax_inmovilla_get_cities', array($this, 'get_cities'));
        add_action('wp_ajax_nopriv_inmovilla_get_cities', array($this, 'get_cities'));
        
        // AJAX solo para administradores
        add_action('wp_ajax_inmovilla_test_connection', array($this, 'test_connection'));
        add_action('wp_ajax_inmovilla_sync_properties', array($this, 'sync_properties'));
        add_action('wp_ajax_inmovilla_clear_cache', array($this, 'clear_cache'));
    }
    
    /**
     * Cargar propiedades via AJAX
     */
    public function load_properties() {
        check_ajax_referer('inmovilla_public_nonce', 'nonce');
        
        $page   = absint($_POST['page'] ?? 1);
        $limit  = absint($_POST['limit'] ?? 12);
        $filters = isset($_POST['filters']) ? (array) $_POST['filters'] : array();

        // Sanitizar filtros
        $filters = $this->sanitize_filters($filters);

        try {
            $api      = new InmovillaAPI();
            $response = $api->get_properties(array_merge($filters, array(
                'page'  => $page,
                'limit' => $limit,
            )));

            if (is_wp_error($response) || empty($response['data'])) {
                wp_send_json_error(array(
                    'message' => __('No se encontraron propiedades', 'inmovilla-properties'),
                ));
            }

            $properties = $response['data'];
            $html       = '';
            foreach ($properties as $property) {
                ob_start();

                include INMOVILLA_PROPERTIES_TEMPLATES_DIR . 'property-card.php';

                $html .= ob_get_clean();
            }

            wp_send_json_success(array(
                'html'     => $html,
                'total'    => count($properties),
                'page'     => $page,
                'has_more' => count($properties) === $limit,
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Error al cargar las propiedades', 'inmovilla-properties'),
                'error'   => $e->getMessage(),
            ));
        }
    }
    
    /**
     * Buscar propiedades via AJAX
     */
    public function search_properties() {
        check_ajax_referer('inmovilla_public_nonce', 'nonce');
        
        $search_data = isset($_POST['search']) ? (array) $_POST['search'] : array();
        $page        = absint($_POST['page'] ?? 1);
        $limit       = absint($_POST['limit'] ?? 12);

        // Sanitizar datos de búsqueda
        $search_params = $this->sanitize_search_data($search_data);

        try {
            $api      = new InmovillaAPI();
            $response = $api->get_properties(array_merge($search_params, array(
                'page'  => $page,
                'limit' => $limit,
            )));

            if (is_wp_error($response) || empty($response['data'])) {
                wp_send_json_success(array(
                    'html'     => '<div class="no-results"><p>' . __('No se encontraron propiedades que coincidan con tu búsqueda.', 'inmovilla-properties') . '</p></div>',
                    'total'    => 0,
                    'page'     => $page,
                    'has_more' => false,
                ));
            }

            $properties = $response['data'];
            $html       = '';
            foreach ($properties as $property) {
                ob_start();

                include INMOVILLA_PROPERTIES_TEMPLATES_DIR . 'property-card.php';

                $html .= ob_get_clean();
            }

            wp_send_json_success(array(
                'html'           => $html,
                'total'          => count($properties),
                'page'           => $page,
                'has_more'       => count($properties) === $limit,
                'search_summary' => $this->generate_search_summary($search_params, count($properties)),
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Error en la búsqueda', 'inmovilla-properties'),
                'error'   => $e->getMessage(),
            ));
        }
    }
    
    /**
     * Cargar más propiedades (paginación AJAX)
     */
    public function load_more_properties() {
        check_ajax_referer('inmovilla_public_nonce', 'nonce');
        
        $this->load_properties(); // Reutilizar la función load_properties
    }
    
    /**
     * Obtener lista de ciudades disponibles
     */
    public function get_cities() {
        check_ajax_referer('inmovilla_public_nonce', 'nonce');
        
        try {
            $api    = new InmovillaAPI();
            $result = $api->request('cities');

            if (is_wp_error($result) || empty($result['data'])) {
                wp_send_json_error(array(
                    'message' => __('Error al obtener las ciudades', 'inmovilla-properties'),
                ));
            }

            wp_send_json_success(array(
                'cities' => $result['data'],
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Error al obtener las ciudades', 'inmovilla-properties'),
                'error'   => $e->getMessage(),
            ));
        }
    }
    
    /**
     * Probar conexión con API (solo admin)
     */
    public function test_connection() {
        check_ajax_referer('inmovilla_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permisos insuficientes', 'inmovilla-properties')
            ));
        }
        
        try {
            $api  = new InmovillaAPI();
            $test = $api->test_connection();
            
            if ($test['success']) {
                wp_send_json_success(array(
                    'message' => __('Conexión exitosa con la API de Inmovilla', 'inmovilla-properties'),
                    'data' => $test['data']
                ));
            } else {
                wp_send_json_error(array(
                    'message' => $test['message']
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Error de conexión', 'inmovilla-properties'),
                'error' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Sincronizar propiedades (solo admin)
     */
    public function sync_properties() {
        check_ajax_referer('inmovilla_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permisos insuficientes', 'inmovilla-properties')
            ));
        }
        
        try {
            $manager = new Inmovilla_Properties_Manager();
            $manager->sync_properties();

            wp_send_json_success(array(
                'message' => __('Sincronización completada.', 'inmovilla-properties'),
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Error durante la sincronización', 'inmovilla-properties'),
                'error'   => $e->getMessage(),
            ));
        }
    }
    
    /**
     * Limpiar caché (solo admin)
     */
    public function clear_cache() {
        check_ajax_referer('inmovilla_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Permisos insuficientes', 'inmovilla-properties')
            ));
        }
        
        try {
            $cache = new Inmovilla_Cache();
            $cache->clear_all_cache();
            
            wp_send_json_success(array(
                'message' => __('Caché limpiado correctamente', 'inmovilla-properties')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Error al limpiar el caché', 'inmovilla-properties')
            ));
        }
    }
    
    /**
     * Sanitizar filtros de propiedades
     */
    private function sanitize_filters($filters) {
        $clean_filters = array();
        
        if (isset($filters['type'])) {
            $clean_filters['type'] = sanitize_text_field($filters['type']);
        }
        
        if (isset($filters['city'])) {
            $clean_filters['city'] = sanitize_text_field($filters['city']);
        }
        
        if (isset($filters['min_price'])) {
            $clean_filters['min_price'] = intval($filters['min_price']);
        }
        
        if (isset($filters['max_price'])) {
            $clean_filters['max_price'] = intval($filters['max_price']);
        }
        
        if (isset($filters['bedrooms'])) {
            $clean_filters['bedrooms'] = intval($filters['bedrooms']);
        }
        
        if (isset($filters['bathrooms'])) {
            $clean_filters['bathrooms'] = intval($filters['bathrooms']);
        }
        
        return $clean_filters;
    }
    
    /**
     * Sanitizar datos de búsqueda
     */
    private function sanitize_search_data($search_data) {
        return $this->sanitize_filters($search_data); // Mismo proceso por ahora
    }
    
    /**
     * Generar resumen de búsqueda
     */
    private function generate_search_summary($params, $count) {
        $parts = array();
        
        if (!empty($params['type'])) {
            $parts[] = $params['type'];
        }
        
        if (!empty($params['city'])) {
            $parts[] = __('en', 'inmovilla-properties') . ' ' . $params['city'];
        }
        
        if (!empty($params['min_price']) || !empty($params['max_price'])) {
            $price_range = '';
            if (!empty($params['min_price'])) {
                $price_range .= number_format($params['min_price'], 0, ',', '.') . '€';
            }
            if (!empty($params['max_price'])) {
                $price_range .= ' - ' . number_format($params['max_price'], 0, ',', '.') . '€';
            }
            $parts[] = $price_range;
        }
        
        $summary = implode(' ', $parts);
        
        return sprintf(
            _n(
                '%d propiedad encontrada%s',
                '%d propiedades encontradas%s',
                $count,
                'inmovilla-properties'
            ),
            $count,
            $summary ? ' (' . $summary . ')' : ''
        );
    }
}