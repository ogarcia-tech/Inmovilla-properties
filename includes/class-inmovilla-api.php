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

        if (empty($this->api_token)) {
            return new WP_Error('no_token', __('Token de API no configurado', 'inmovilla-properties'));
        }

        $url = rtrim($this->api_url, '/') . '/' . ltrim($endpoint, '/');

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
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
}
