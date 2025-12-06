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

        add_action('wp_ajax_inmovilla_send_contact', array($this, 'send_contact'));
        add_action('wp_ajax_nopriv_inmovilla_send_contact', array($this, 'send_contact'));

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
     * Enviar formulario de contacto
     */
    public function send_contact() {
        check_ajax_referer('inmovilla_public_nonce', 'nonce');

        $name    = sanitize_text_field($_POST['name'] ?? '');
        $email   = sanitize_email($_POST['email'] ?? '');
        $phone   = sanitize_text_field($_POST['phone'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $source  = sanitize_text_field($_POST['source'] ?? '');
        $property_id = absint($_POST['property_id'] ?? 0);
        $privacy = isset($_POST['privacy']);

        if (empty($name) || empty($email) || empty($message) || !$privacy) {
            wp_send_json_error(array(
                'message' => __('Por favor completa todos los campos obligatorios.', 'inmovilla-properties'),
            ));
        }

        $to = get_option('admin_email');
        $email_subject = sprintf(__('Solicitud: %s', 'inmovilla-properties'), $subject);
        $body = sprintf(
            __('Nombre: %1$s\nEmail: %2$s\nTeléfono: %3$s\nOrigen: %4$s\nID Propiedad: %5$d\nMensaje:\n%6$s', 'inmovilla-properties'),
            $name,
            $email,
            $phone,
            $source,
            $property_id,
            $message
        );

        $headers = array('Reply-To: ' . $name . ' <' . $email . '>');

        if (wp_mail($to, $email_subject, $body, $headers)) {
            wp_send_json_success(array(
                'message' => __('Solicitud enviada correctamente.', 'inmovilla-properties'),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Error al enviar la solicitud.', 'inmovilla-properties'),
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

            $api = new InmovillaAPI();
            $response = $api->test_connection();

            if (is_wp_error($response)) {

                wp_send_json_error(array(
                    'message' => $response->get_error_message()
                ));
            }

            wp_send_json_success(array(
                'message' => __('Conexión exitosa con la API de Inmovilla', 'inmovilla-properties'),
                'data' => $response
            ));

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
        
        if (isset($filters['type_id'])) {
            $clean_filters['type_id'] = absint($filters['type_id']);
        }

        if (isset($filters['city_id'])) {
            $clean_filters['city_id'] = absint($filters['city_id']);
        }

        if (isset($filters['zone_id'])) {
            $clean_filters['zone_id'] = absint($filters['zone_id']);
        }

        if (isset($filters['operation'])) {
            $clean_filters['operation'] = absint($filters['operation']);
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

        if (!empty($filters['reference'])) {
            $clean_filters['reference'] = substr(preg_replace('/[^A-Za-z0-9\-]/', '', sanitize_text_field($filters['reference'])), 0, 50);
        }

        if (!empty($filters['has_elevator'])) {
            $clean_filters['has_elevator'] = (bool) $filters['has_elevator'];
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
        $summary_parts = array();

        if (!empty($params['min_price']) || !empty($params['max_price'])) {
            $price_range = '';
            if (!empty($params['min_price'])) {
                $price_range .= number_format($params['min_price'], 0, ',', '.') . '€';
            }
            if (!empty($params['max_price'])) {
                $price_range .= ' - ' . number_format($params['max_price'], 0, ',', '.') . '€';
            }
            $summary_parts[] = $price_range;
        }

        if (!empty($params['bedrooms'])) {
            $summary_parts[] = sprintf(__('≥ %d hab.', 'inmovilla-properties'), (int) $params['bedrooms']);
        }

        if (!empty($params['bathrooms'])) {
            $summary_parts[] = sprintf(__('≥ %d baños', 'inmovilla-properties'), (int) $params['bathrooms']);
        }

        if (!empty($params['has_elevator'])) {
            $summary_parts[] = __('con ascensor', 'inmovilla-properties');
        }

        $summary = implode(' · ', $summary_parts);

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