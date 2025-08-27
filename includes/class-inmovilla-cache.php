<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar el sistema de caché
 */
class InmovillaCache {

    private $cache_group = 'inmovilla_properties';
    private $cache_duration;

    public function __construct() {
        $this->cache_duration = inmovilla_get_setting('cache_duration', 3600);
    }

    /**
     * Obtener datos del caché
     */
    public function get($key) {
        return get_transient($this->get_cache_key($key));
    }

    /**
     * Guardar datos en caché
     */
    public function set($key, $data, $expiration = null) {
        if ($expiration === null) {
            $expiration = $this->cache_duration;
        }

        return set_transient($this->get_cache_key($key), $data, $expiration);
    }

    /**
     * Eliminar datos del caché
     */
    public function delete($key) {
        return delete_transient($this->get_cache_key($key));
    }

    /**
     * Limpiar todo el caché
     */
    public function clear_all_cache() {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $this->cache_group . '_%'
            )
        );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . $this->cache_group . '_%'
            )
        );
    }

    /**
     * Obtener clave de caché
     */
    private function get_cache_key($key) {
        return $this->cache_group . '_' . md5($key);
    }
}
