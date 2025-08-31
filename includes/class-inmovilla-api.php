<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar la conexión con la API de Inmovilla
 */
class InmovillaAPI {

    private $api_token;
    private $api_url;
    private $cache;

    public function __construct() {
        $this->api_token = inmovilla_get_setting('api_token');
        $this->api_url = inmovilla_get_setting('api_url', 'https://crm.inmovilla.com/api/');
        $this->cache = inmovilla_get_setting('cache_enabled', true);
    }

    /**
     * Realizar petición a la API
     */
    public function request($endpoint, $params = array()) {
        
        $mock = $this->get_mock_properties($endpoint);
        if (false !== $mock) {
            return $mock;
        }

        if (empty($this->api_token)) {
            return new WP_Error('no_token', __('Token de API no configurado', 'inmovilla-properties'));
        }

        $url = rtrim($this->api_url, '/') . '/' . ltrim($endpoint, '/');

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 60, // Timeout aumentado a 60 segundos
        );

        if (!empty($params)) {
            $args['body'] = json_encode($params);
            $response = wp_remote_post($url, $args);
        } else {
            $response = wp_remote_get($url, $args);
        }

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Error al decodificar respuesta de API', 'inmovilla-properties'));
        }

        return $data;
    }
    
    /**
     * Obtiene datos ficticios de propiedades si está habilitado.
     *
     * @param string $endpoint Endpoint solicitado.
     * @return array|false
     */
    private function get_mock_properties($endpoint) {
        if ($endpoint !== 'properties' || !apply_filters('inmovilla_mock_data', false)) {
            return false;
        }

        return $this->mock_properties_data();
    }

    /**
     * Datos ficticios de propiedades para evaluar el diseño.
     *
     * @return array
     */
    private function mock_properties_data() {
        return array(
            'data' => array(
                array(
                    'id' => 1,
                    'title' => 'Piso de Lujo en el Centro',
                    'price' => 450000,
                    'location' => array('city' => 'Madrid', 'district' => 'Salamanca'),
                    'description' => 'Un increíble piso con acabados de lujo, muy luminoso y en una de las mejores zonas de la ciudad.',
                    'bedrooms' => 3,
                    'bathrooms' => 2,
                    'size' => 120,
                    'featured' => true,
                    'images' => array(array('url' => 'https://via.placeholder.com/800x600.png?text=Propiedad+1')),
                    'reference' => 'REF-001',
                    'type' => 'Piso'
                ),
                array(
                    'id' => 2,
                    'title' => 'Chalet con Piscina y Jardín',
                    'price' => 780000,
                    'location' => array('city' => 'Barcelona', 'district' => 'Sarrià'),
                    'description' => 'Espectacular chalet con un gran jardín y piscina privada. Ideal para familias.',
                    'bedrooms' => 5,
                    'bathrooms' => 4,
                    'size' => 350,
                    'featured' => false,
                    'images' => array(array('url' => 'https://via.placeholder.com/800x600.png?text=Propiedad+2')),
                    'reference' => 'REF-002',
                    'type' => 'Chalet'
                ),
                array(
                    'id' => 3,
                    'title' => 'Ático con Vistas al Mar',
                    'price' => 620000,
                    'location' => array('city' => 'Valencia', 'district' => 'Playa de la Malvarrosa'),
                    'description' => 'Disfruta de unas vistas inmejorables desde la terraza de este maravilloso ático.',
                    'bedrooms' => 2,
                    'bathrooms' => 2,
                    'size' => 95,
                    'featured' => true,
                    'images' => array(array('url' => 'https://via.placeholder.com/800x600.png?text=Propiedad+3')),
                    'reference' => 'REF-003',
                    'type' => 'Ático'
                )
            ),
            'pagination' => '<a href="#">1</a><span class="current">2</span><a href="#">3</a>'
        );
    }

    /**
     * Obtener propiedades
     */
    public function get_properties($params = array()) {
        return $this->request('properties', $params);
    }

    /**
     * Obtener una propiedad específica
     */
    public function get_property($id) {
        return $this->request('properties/' . $id);
    }

    /**
     * Probar conexión
     */
    public function test_connection() {
        return $this->request('test');
    }

    /**
     * Buscar propiedades según parámetros.
     *
     * @param array $params Parámetros de búsqueda.
     * @return array|WP_Error
     */
    public function search_properties($params = array()) {
        $response = $this->request('properties/search', $params);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!is_array($response)) {
            return new WP_Error('invalid_response', __('Respuesta inválida de la API', 'inmovilla-properties'));
        }

        return $response;
    }

    /**
     * Obtener tipos de propiedad disponibles.
     *
     * @return array|WP_Error
     */
    public function get_property_types() {
        $response = $this->request('properties/types');

        if (is_wp_error($response)) {
            return $response;
        }

        if (!is_array($response)) {
            return new WP_Error('invalid_response', __('Respuesta inválida de la API', 'inmovilla-properties'));
        }

        return $response;
    }

    /**
     * Obtener listado de ciudades.
     *
     * @return array|WP_Error
     */
    public function get_cities() {
        $response = $this->request('locations/cities');

        if (is_wp_error($response)) {
            return $response;
        }

        if (!is_array($response)) {
            return new WP_Error('invalid_response', __('Respuesta inválida de la API', 'inmovilla-properties'));
        }

        return $response;
    }

    /**
     * Sincronizar todas las propiedades.
     *
     * Utilizado principalmente para peticiones AJAX.
     *
     * @return array|WP_Error
     */
    public function sync_all_properties() {
        $response = $this->request('properties/sync');

        if (is_wp_error($response)) {
            return $response;
        }

        if (!is_array($response)) {
            return new WP_Error('invalid_response', __('Respuesta inválida de la API', 'inmovilla-properties'));
        }

        return $response;
    }
}