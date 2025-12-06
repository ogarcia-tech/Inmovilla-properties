<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar la conexión con la API de Inmovilla
 */
class InmovillaAPI {

    private $agency_number;
    private $agency_suffix;
    private $api_password;
    private $language;
    private $api_url;
    /** @var InmovillaCache|null */
    private $cache;

    public function __construct() {
        $this->agency_number = (int) inmovilla_get_setting('agency_id');
        $this->agency_suffix = sanitize_text_field((string) inmovilla_get_setting('agency_suffix', ''));
        $this->api_password  = inmovilla_get_setting('api_password');
        $this->language      = (int) inmovilla_get_setting('language', 1);
        $this->api_url       = inmovilla_get_setting('api_base_url', 'https://apiweb.inmovilla.com/apiweb/apiweb.php');

        $this->cache = inmovilla_get_setting('cache_enabled', true)
            ? new InmovillaCache()
            : null;
    }

    /**
     * Realizar petición a la API usando numagencia y contraseña.
     */
    public function request($endpoint, $params = array()) {

        $mock = $this->get_mock_properties($endpoint);
        if (false !== $mock) {
            return $mock;
        }

        if (!$this->has_credentials()) {
            return new WP_Error('missing_credentials', __('Número de agencia o contraseña no configurados', 'inmovilla-properties'));
        }

        switch ($endpoint) {
            case 'properties':
            case 'properties/search':
                return $this->fetch_properties($params);
            case 'cities':
            case 'locations/cities':
                return $this->fetch_cities();
            case 'test':
                return $this->test_connection();
            default:
                if (strpos($endpoint, 'properties/') === 0) {
                    $property_id = (int) str_replace('properties/', '', $endpoint);
                    return $this->fetch_property($property_id);
                }

                return new WP_Error('unsupported_endpoint', sprintf(__('Endpoint no soportado: %s', 'inmovilla-properties'), $endpoint));
        }
    }

    /**
     * Generar clave única para el caché.
     *
     * @param string $endpoint
     * @param array  $params
     * @return string
     */
    private function get_cache_key($endpoint, $params = array()) {
        if (empty($params)) {
            return $endpoint;
        }

        return $endpoint . ':' . md5(wp_json_encode($params));
    }

    /**
     * Leer respuesta cacheada si existe.
     *
     * @param string $cache_key
     * @return mixed
     */
    private function get_cached_response($cache_key) {
        if (!$this->cache) {
            return false;
        }

        return $this->cache->get($cache_key);
    }

    /**
     * Almacenar respuesta en caché.
     *
     * @param string $cache_key
     * @param mixed  $data
     * @return void
     */
    private function set_cached_response($cache_key, $data) {
        if (!$this->cache) {
            return;
        }

        $this->cache->set($cache_key, $data);
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
        $params = array(
            'limit' => 1,
            'page'  => 1,
        );

        return $this->fetch_properties($params);
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
        // No existe endpoint directo en la API clásica, pero podría derivarse de lostipos en el futuro.
        return new WP_Error('unsupported', __('La API de Inmovilla no ofrece tipos de propiedad en este modo', 'inmovilla-properties'));
    }

    /**
     * Obtener listado de ciudades.
     *
     * @return array|WP_Error
     */
    public function get_cities() {
        $response = $this->request('cities');

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
        $response = $this->get_properties(array('limit' => 20, 'page' => 1));

        if (is_wp_error($response)) {
            return $response;
        }

        if (!is_array($response)) {
            return new WP_Error('invalid_response', __('Respuesta inválida de la API', 'inmovilla-properties'));
        }

        return $response;
    }

    /**
     * Validar que hay credenciales configuradas.
     */
    private function has_credentials() {
        return !empty($this->agency_number) && !empty($this->api_password);
    }

    /**
     * Construir la petición para la API clásica de Inmovilla.
     */
    private function call_api($processes, $json = true) {
        $cache_key = $this->get_cache_key('legacy_api', $processes);
        $cached    = $this->get_cached_response($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        $request_text = $this->build_request_text($processes);
        $encoded      = rawurlencode($request_text);

        $body = array(
            'param' => $encoded,
        );

        if ($json) {
            $body['json'] = 1;
        }

        $ip_data = $this->build_ip_data();
        if (!empty($ip_data)) {
            $body = array_merge($body, $ip_data);
        }

        $response = wp_remote_post($this->api_url, array(
            'body'    => $body,
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return new WP_Error('json_error', __('Error al decodificar respuesta de API', 'inmovilla-properties'));
        }

        $this->set_cached_response($cache_key, $data);

        return $data;
    }

    /**
     * Compone el texto solicitado por la API.
     */
    private function build_request_text($processes) {
        $base = sprintf(
            '%d%s;%s;%d;lostipos',
            $this->agency_number,
            $this->agency_suffix,
            $this->api_password,
            $this->language
        );

        foreach ($processes as $process) {
            $base .= ';' . implode(';', $process);
        }

        return $base;
    }

    /**
     * Aporta IP real para el firewall del proveedor.
     */
    private function build_ip_data() {
        $ip_data = array();

        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip_data['ia'] = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_data['ib'] = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        }

        return $ip_data;
    }

    /**
     * Construir consulta paginada.
     */
    private function fetch_properties($params) {
        $limit = isset($params['limit']) ? max(1, (int) $params['limit']) : (isset($params['per_page']) ? max(1, (int) $params['per_page']) : 12);
        $page  = isset($params['page']) ? max(1, (int) $params['page']) : 1;

        $start_position = (($page - 1) * $limit) + 1;
        $where_clause   = $this->build_where_clause($params);
        $order_clause   = 'fecha desc';

        $response = $this->call_api(array(
            array('paginacion', $start_position, $limit, $where_clause, $order_clause),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        if (empty($response['paginacion']) || !is_array($response['paginacion'])) {
            return new WP_Error('invalid_response', __('Respuesta inválida de la API', 'inmovilla-properties'));
        }

        $meta   = $response['paginacion'][0] ?? array();
        $items  = array_slice($response['paginacion'], 1);
        $mapped = array_map(array($this, 'map_property_summary'), $items);

        return array(
            'data'       => $mapped,
            'pagination' => $meta,
        );
    }

    /**
     * Obtener detalle de propiedad.
     */
    private function fetch_property($property_id) {
        if ($property_id <= 0) {
            return new WP_Error('invalid_property', __('ID de propiedad inválido', 'inmovilla-properties'));
        }

        $where = sprintf('ofertas.cod_ofer=%d', $property_id);

        $response = $this->call_api(array(
            array('ficha', 1, 1, $where, ''),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        if (empty($response['ficha'][1])) {
            return new WP_Error('not_found', __('Propiedad no encontrada en la API', 'inmovilla-properties'));
        }

        return $this->map_property_detail($response['ficha'][1], $response);
    }

    /**
     * Obtener ciudades disponibles.
     */
    private function fetch_cities() {
        $response = $this->call_api(array(
            array('ciudades', 1, 500, '', ''),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        if (empty($response['ciudades']) || !is_array($response['ciudades'])) {
            return new WP_Error('invalid_response', __('Respuesta inválida de la API', 'inmovilla-properties'));
        }

        return array('data' => array_slice($response['ciudades'], 1));
    }

    /**
     * Construir cláusula WHERE segura.
     */
    private function build_where_clause($params) {
        $conditions = array();

        if (!empty($params['type_id'])) {
            $conditions[] = sprintf('key_tipo=%d', (int) $params['type_id']);
        }

        if (!empty($params['city_id'])) {
            $conditions[] = sprintf('key_loca=%d', (int) $params['city_id']);
        }

        if (!empty($params['zone_id'])) {
            $conditions[] = sprintf('key_zona=%d', (int) $params['zone_id']);
        }

        if (!empty($params['operation'])) {
            $conditions[] = sprintf('keyacci=%d', (int) $params['operation']);
        }

        if (isset($params['min_price'])) {
            $conditions[] = sprintf('precioinmo >= %d', (int) $params['min_price']);
        }

        if (isset($params['max_price']) && (int) $params['max_price'] > 0) {
            $conditions[] = sprintf('precioinmo <= %d', (int) $params['max_price']);
        }

        if (!empty($params['reference'])) {
            $reference = $this->sanitize_reference($params['reference']);
            if ($reference !== '') {
                $conditions[] = sprintf("ref='%s'", $reference);
            }
        }

        if (isset($params['bedrooms']) && (int) $params['bedrooms'] > 0) {
            $conditions[] = sprintf('total_hab >= %d', (int) $params['bedrooms']);
        }

        if (isset($params['bathrooms']) && (int) $params['bathrooms'] > 0) {
            $conditions[] = sprintf('banyos >= %d', (int) $params['bathrooms']);
        }

        if (!empty($params['has_elevator'])) {
            $conditions[] = 'ascensor=1';
        }

        return implode(' and ', $conditions);
    }

    private function clean_text_filter($value) {
        $value = sanitize_text_field($value);
        $value = preg_replace('/[^\pL\pN\s\-]/u', '', $value);

        return trim($value);
    }

    private function sanitize_reference($reference) {
        $reference = sanitize_text_field($reference);
        $reference = preg_replace('/[^A-Za-z0-9\-]/', '', $reference);

        return substr($reference, 0, 50);
    }

    /**
     * Mapear propiedad resumida para listados.
     */
    private function map_property_summary($item) {
        $property_id = isset($item['cod_ofer']) ? (int) $item['cod_ofer'] : 0;

        $price = isset($item['precioinmo']) ? (float) $item['precioinmo'] : 0;
        if ($price <= 0 && isset($item['precioalq'])) {
            $price = (float) $item['precioalq'];
        }

        return array(
            'id'         => $property_id,
            'title'      => trim(($item['nbtipo'] ?? '') . ' ' . ($item['ciudad'] ?? '')),
            'price'      => $price,
            'location'   => array(
                'city'     => $item['ciudad'] ?? '',
                'district' => $item['zona'] ?? '',
            ),
            'description' => '',
            'bedrooms'   => isset($item['total_hab']) ? (int) $item['total_hab'] : 0,
            'bathrooms'  => isset($item['banyos']) ? (int) $item['banyos'] : 0,
            'size'       => isset($item['m_cons']) ? (float) $item['m_cons'] : 0,
            'featured'   => !empty($item['outlet']),
            'images'     => !empty($item['foto']) ? array(array('url' => $item['foto'])) : array(),
            'reference'  => $item['ref'] ?? '',
            'type'       => $item['nbtipo'] ?? '',
        );
    }

    /**
     * Mapear propiedad completa para la ficha.
     */
    private function map_property_detail($item, $response) {
        $property_id = isset($item['cod_ofer']) ? (int) $item['cod_ofer'] : 0;

        $description_data = $response['descripciones'][$property_id][$this->language] ?? array();
        $description      = $description_data['descrip'] ?? '';
        $title            = $description_data['titulo'] ?? ($item['nbtipo'] ?? __('Propiedad', 'inmovilla-properties'));

        $images = array();
        if (!empty($response['fotos'][$property_id]) && is_array($response['fotos'][$property_id])) {
            foreach ($response['fotos'][$property_id] as $url) {
                $images[] = array('url' => $url);
            }
        }

        $price = isset($item['precioinmo']) ? (float) $item['precioinmo'] : 0;
        if ($price <= 0 && isset($item['precioalq'])) {
            $price = (float) $item['precioalq'];
        }

        return array(
            'id'          => $property_id,
            'title'       => $title,
            'price'       => $price,
            'location'    => array(
                'city'     => $item['ciudad'] ?? '',
                'district' => $item['zona'] ?? '',
            ),
            'description' => $description,
            'bedrooms'    => isset($item['total_hab']) ? (int) $item['total_hab'] : 0,
            'bathrooms'   => isset($item['banyos']) ? (int) $item['banyos'] : 0,
            'size'        => isset($item['m_cons']) ? (float) $item['m_cons'] : 0,
            'featured'    => !empty($item['outlet']),
            'images'      => $images,
            'reference'   => $item['ref'] ?? '',
            'type'        => $item['nbtipo'] ?? '',
            'raw'         => $item,
        );
    }
}