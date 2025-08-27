<?php
/**
 * Gestión de propiedades desde Inmovilla
 */

if (!defined('ABSPATH')) {
    exit;
}

class Inmovilla_Properties_Manager {

    private $api;
    private $cache;

    public function __construct() {
        $this->api = new Inmovilla_API();
        $this->cache = new Inmovilla_Cache();

        // Hooks
        add_action('init', array($this, 'init'));
        add_action('inmovilla_sync_properties', array($this, 'sync_properties'));

        // Programar sincronización automática
        if (!wp_next_scheduled('inmovilla_sync_properties')) {
            wp_schedule_event(time(), 'hourly', 'inmovilla_sync_properties');
        }
    }

    public function init() {
        // Template redirect para URLs personalizadas
        add_action('template_redirect', array($this, 'template_redirect'));
    }

    /**
     * Redirect para templates personalizados
     */
    public function template_redirect() {
        global $wp_query;

        if (get_query_var('inmovilla_property_slug')) {
            $this->load_single_property_template();
        } else if (get_query_var('inmovilla_search')) {
            $this->load_search_template();
        }
    }

    /**
     * Cargar template de propiedad individual
     */
    private function load_single_property_template() {
        $slug = get_query_var('inmovilla_property_slug');
        $property = $this->get_property_by_slug($slug);

        if (!$property) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        // Cargar template
        $template = locate_template('inmovilla/property-single.php');
        if (!$template) {
            $template = INMOVILLA_PLUGIN_PATH . 'templates/property-single.php';
        }

        global $inmovilla_property;
        $inmovilla_property = $property;

        include $template;
        exit;
    }

    /**
     * Cargar template de búsqueda
     */
    private function load_search_template() {
        $template = locate_template('inmovilla/property-search.php');
        if (!$template) {
            $template = INMOVILLA_PLUGIN_PATH . 'templates/property-search.php';
        }

        include $template;
        exit;
    }

    /**
     * Obtener propiedades con caché
     */
    public function get_properties($params = array()) {
        $cache_key = 'properties_' . md5(serialize($params));

        // Intentar obtener del caché
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Obtener de la API
        $response = $this->api->get_properties($params);

        if (is_wp_error($response)) {
            return $response;
        }

        // Guardar en caché
        $this->cache->set($cache_key, $response);

        return $response;
    }

    /**
     * Obtener propiedad por ID
     */
    public function get_property($property_id) {
        $cache_key = 'property_' . $property_id;

        // Intentar obtener del caché
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Obtener de la API
        $response = $this->api->get_property($property_id);

        if (is_wp_error($response)) {
            return $response;
        }

        // Guardar en caché
        $this->cache->set($cache_key, $response);

        return $response;
    }

    /**
     * Obtener propiedad por slug
     */
    public function get_property_by_slug($slug) {
        // Buscar en caché por slug
        $cache_key = 'property_slug_' . $slug;
        $cached = $this->cache->get($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Buscar en todas las propiedades (esto podría optimizarse)
        $properties = $this->get_properties(array('per_page' => 100));

        if (is_wp_error($properties) || empty($properties['data'])) {
            return false;
        }

        foreach ($properties['data'] as $property) {
            $property_slug = $this->generate_property_slug($property);
            if ($property_slug === $slug) {
                $this->cache->set($cache_key, $property);
                return $property;
            }
        }

        return false;
    }

    /**
     * Buscar propiedades
     */
    public function search_properties($search_params) {
        $cache_key = 'search_' . md5(serialize($search_params));

        // Intentar obtener del caché
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Buscar en la API
        $response = $this->api->search_properties($search_params);

        if (is_wp_error($response)) {
            return $response;
        }

        // Guardar en caché (tiempo más corto para búsquedas)
        $this->cache->set($cache_key, $response, 1800); // 30 minutos

        return $response;
    }

    /**
     * Generar slug SEO-friendly para propiedad
     */
    public function generate_property_slug($property) {
        $title = !empty($property['title']) ? $property['title'] : '';
        $location = !empty($property['location']['city']) ? $property['location']['city'] : '';
        $reference = !empty($property['reference']) ? $property['reference'] : $property['id'];

        $slug_parts = array();

        if ($title) {
            $slug_parts[] = sanitize_title($title);
        }

        if ($location) {
            $slug_parts[] = sanitize_title($location);
        }

        if ($reference) {
            $slug_parts[] = sanitize_title($reference);
        }

        return implode('-', array_filter($slug_parts));
    }

    /**
     * Generar URL SEO-friendly para propiedad
     */
    public function get_property_url($property) {
        $slug = $this->generate_property_slug($property);
        return home_url('/propiedad/' . $slug . '/');
    }

    /**
     * Sincronizar propiedades (para cron)
     */
    public function sync_properties() {
        $properties = $this->api->get_properties(array('per_page' => 100));

        if (is_wp_error($properties)) {
            error_log('Inmovilla Plugin: Error sincronizando propiedades - ' . $properties->get_error_message());
            return;
        }

        // Limpiar caché antiguo
        $this->cache->flush_expired();

        // Pre-cargar propiedades en caché
        if (!empty($properties['data'])) {
            foreach ($properties['data'] as $property) {
                $cache_key = 'property_' . $property['id'];
                $this->cache->set($cache_key, $property);

                // También guardar por slug
                $slug = $this->generate_property_slug($property);
                $slug_cache_key = 'property_slug_' . $slug;
                $this->cache->set($slug_cache_key, $property);
            }
        }

        update_option('inmovilla_last_sync', current_time('mysql'));
    }

    /**
     * Obtener tipos de propiedades
     */
    public function get_property_types() {
        $cache_key = 'property_types';

        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $response = $this->api->get_property_types();

        if (!is_wp_error($response)) {
            $this->cache->set($cache_key, $response, 86400); // 24 horas
        }

        return $response;
    }

    /**
     * Obtener ubicaciones
     */
    public function get_locations() {
        $cache_key = 'locations';

        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $response = $this->api->get_locations();

        if (!is_wp_error($response)) {
            $this->cache->set($cache_key, $response, 86400); // 24 horas
        }

        return $response;
    }

    /**
     * Formatear precio
     */
    public function format_price($price, $currency = '€') {
        if (!is_numeric($price)) {
            return $price;
        }

        return number_format($price, 0, ',', '.') . ' ' . $currency;
    }

    /**
     * Obtener imagen principal de propiedad
     */
    public function get_property_featured_image($property) {
        if (!empty($property['images']) && is_array($property['images'])) {
            return $property['images'][0]['url'] ?? '';
        }

        return '';
    }

    /**
     * Obtener galería de imágenes
     */
    public function get_property_gallery($property) {
        return !empty($property['images']) ? $property['images'] : array();
    }
}
?>