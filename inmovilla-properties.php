<?php
/**
 * Plugin Name: Inmovilla Properties
 * Plugin URI: https://github.com/tuempresa/inmovilla-properties
 * Description: Plugin profesional para conectar WordPress con Inmovilla CRM. Incluye URLs SEO-friendly, búsquedas avanzadas, sistema de favoritos y panel de administración completo.
 * Version: 1.0.0
 * Author: Tu Empresa
 * Author URI: https://tuempresa.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: inmovilla-properties
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('INMOVILLA_PROPERTIES_VERSION', '1.0.0');
define('INMOVILLA_PROPERTIES_PLUGIN_FILE', __FILE__);
define('INMOVILLA_PROPERTIES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('INMOVILLA_PROPERTIES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('INMOVILLA_PROPERTIES_INCLUDES_DIR', INMOVILLA_PROPERTIES_PLUGIN_DIR . 'includes/');
define('INMOVILLA_PROPERTIES_TEMPLATES_DIR', INMOVILLA_PROPERTIES_PLUGIN_DIR . 'templates/');
define('INMOVILLA_PROPERTIES_ASSETS_URL', INMOVILLA_PROPERTIES_PLUGIN_URL . 'assets/');

/**
 * Clase principal del plugin
 */
class InmovillaProperties {

    /**
     * Instancia única del plugin
     */
    private static $instance = null;

    /**
     * Versión del plugin
     */
    public $version = INMOVILLA_PROPERTIES_VERSION;

    /**
     * Instancias de las clases del plugin
     */
    public $api = null;
    public $seo = null;
    public $admin = null;
    public $shortcodes = null;
    public $cache = null;
    public $search = null;
    public $favorites = null;
    public $sitemap = null;
    public $public = null;
    public $ajax = null;

    /**
     * Constructor privado (Singleton)
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Obtener instancia única del plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Cargar dependencias del plugin
     */
    private function load_dependencies() {

        // Archivos principales (verificar existencia antes de cargar)
        $required_files = array(
            'class-inmovilla-api.php',
            'class-inmovilla-seo.php',
            'class-inmovilla-admin.php',
            'class-inmovilla-shortcodes.php',
            'class-inmovilla-cache.php',
            'class-inmovilla-search.php',
            'class-inmovilla-favorites.php',
            'class-inmovilla-sitemap.php'
        );

        // Archivos opcionales (no críticos)
        $optional_files = array(
            'class-inmovilla-public.php',
            'class-inmovilla-ajax.php'
        );

        // Cargar archivos requeridos
        foreach ($required_files as $file) {
            $file_path = INMOVILLA_PROPERTIES_INCLUDES_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                // Log del error pero no detener la ejecución
                error_log("Inmovilla Properties: Archivo requerido no encontrado: " . $file);
            }
        }

        // Cargar archivos opcionales
        foreach ($optional_files as $file) {
            $file_path = INMOVILLA_PROPERTIES_INCLUDES_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    /**
     * Inicializar hooks de WordPress
     */
    private function init_hooks() {

        // Hook de inicialización
        add_action('init', array($this, 'init'));

        // Hook de activación
        register_activation_hook(__FILE__, array($this, 'activate'));

        // Hook de desactivación  
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Cargar assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Cargar traducciones
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // AJAX hooks
        add_action('wp_ajax_inmovilla_search', array($this, 'handle_ajax_search'));
        add_action('wp_ajax_nopriv_inmovilla_search', array($this, 'handle_ajax_search'));
        add_action('wp_ajax_inmovilla_favorites', array($this, 'handle_ajax_favorites'));
        add_action('wp_ajax_nopriv_inmovilla_favorites', array($this, 'handle_ajax_favorites'));
    }

    /**
     * Inicializar componentes del plugin
     */
    private function init_components() {

        // Inicializar API (crítico)
        if (class_exists('InmovillaAPI')) {
            $this->api = new InmovillaAPI();
        }

        // Inicializar SEO (crítico para URLs)
        if (class_exists('InmovillaSEO')) {
            $this->seo = new InmovillaSEO();
        }

        // Inicializar Admin (crítico para configuración)
        if (class_exists('InmovillaAdmin')) {
            $this->admin = new InmovillaAdmin();
        }

        // Inicializar Shortcodes (crítico para mostrar propiedades)
        if (class_exists('InmovillaShortcodes')) {
            $this->shortcodes = new InmovillaShortcodes();
        }

        // Componentes opcionales
        if (class_exists('InmovillaCache')) {
            $this->cache = new InmovillaCache();
        }

        if (class_exists('InmovillaSearch')) {
            $this->search = new InmovillaSearch();
        }

        if (class_exists('InmovillaFavorites')) {
            $this->favorites = new InmovillaFavorites();
        }

        if (class_exists('InmovillaSitemap')) {
            $this->sitemap = new InmovillaSitemap();
        }

        if (class_exists('InmovillaPublic')) {
            $this->public = new InmovillaPublic();
        }

        if (class_exists('InmovillaAjax')) {
            $this->ajax = new InmovillaAjax();
        }
    }

    /**
     * Inicialización del plugin
     */
    public function init() {

        // Crear custom post type para propiedades
        $this->create_post_types();

        // Crear taxonomías
        $this->create_taxonomies();

        // Configurar rewrite rules
        if ($this->seo) {
            $this->seo->setup_rewrite_rules();
        }
    }

    /**
     * Crear custom post types
     */
    private function create_post_types() {

        // Custom Post Type: Propiedades
        register_post_type('inmovilla_property', array(
            'labels' => array(
                'name' => __('Propiedades', 'inmovilla-properties'),
                'singular_name' => __('Propiedad', 'inmovilla-properties'),
                'menu_name' => __('Propiedades', 'inmovilla-properties'),
                'add_new' => __('Añadir Nueva', 'inmovilla-properties'),
                'add_new_item' => __('Añadir Nueva Propiedad', 'inmovilla-properties'),
                'edit_item' => __('Editar Propiedad', 'inmovilla-properties'),
                'new_item' => __('Nueva Propiedad', 'inmovilla-properties'),
                'view_item' => __('Ver Propiedad', 'inmovilla-properties'),
                'search_items' => __('Buscar Propiedades', 'inmovilla-properties'),
                'not_found' => __('No se encontraron propiedades', 'inmovilla-properties'),
                'not_found_in_trash' => __('No hay propiedades en la papelera', 'inmovilla-properties'),
            ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => false, // Oculto del admin (se gestiona via API)
            'show_in_menu' => false,
            'query_var' => true,
            'rewrite' => array('slug' => 'propiedades'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'show_in_rest' => true,
        ));
    }

    /**
     * Crear taxonomías
     */
    private function create_taxonomies() {

        // Taxonomía: Tipo de Propiedad
        register_taxonomy('property_type', 'inmovilla_property', array(
            'labels' => array(
                'name' => __('Tipos de Propiedad', 'inmovilla-properties'),
                'singular_name' => __('Tipo de Propiedad', 'inmovilla-properties'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_ui' => false,
            'show_admin_column' => false,
            'query_var' => true,
            'rewrite' => array('slug' => 'tipo'),
        ));

        // Taxonomía: Ubicación
        register_taxonomy('property_location', 'inmovilla_property', array(
            'labels' => array(
                'name' => __('Ubicaciones', 'inmovilla-properties'),
                'singular_name' => __('Ubicación', 'inmovilla-properties'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_ui' => false,
            'show_admin_column' => false,
            'query_var' => true,
            'rewrite' => array('slug' => 'ubicacion'),
        ));
    }

    /**
     * Cargar assets públicos
     */
    public function enqueue_public_assets() {

        // CSS público
        $css_files = array(
            'inmovilla-public' => 'css/inmovilla-public.css',
            'inmovilla-responsive' => 'css/inmovilla-responsive.css'
        );

        foreach ($css_files as $handle => $file) {
            $file_path = INMOVILLA_PROPERTIES_PLUGIN_DIR . 'assets/' . $file;
            if (file_exists($file_path)) {
                wp_enqueue_style(
                    $handle,
                    INMOVILLA_PROPERTIES_ASSETS_URL . $file,
                    array(),
                    filemtime($file_path)
                );
            }
        }

        // JavaScript público
        $js_files = array(
            'inmovilla-public' => 'js/inmovilla-public.js',
            'inmovilla-search' => 'js/inmovilla-search.js',
            'inmovilla-favorites' => 'js/inmovilla-favorites.js'
        );

        foreach ($js_files as $handle => $file) {
            $file_path = INMOVILLA_PROPERTIES_PLUGIN_DIR . 'assets/' . $file;
            if (file_exists($file_path)) {
                wp_enqueue_script(
                    $handle,
                    INMOVILLA_PROPERTIES_ASSETS_URL . $file,
                    array('jquery'),
                    filemtime($file_path),
                    true
                );
            }
        }

        // Localizar script para AJAX
        wp_localize_script('inmovilla-public', 'inmovilla_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('inmovilla_nonce'),
            'strings' => array(
                'loading' => __('Cargando...', 'inmovilla-properties'),
                'error' => __('Error al cargar datos', 'inmovilla-properties'),
                'no_results' => __('No se encontraron resultados', 'inmovilla-properties'),
            )
        ));
    }

    /**
     * Cargar assets del admin
     */
    public function enqueue_admin_assets($hook) {

        // Solo cargar en páginas del plugin
        if (strpos($hook, 'inmovilla') === false && $hook !== 'settings_page_inmovilla-properties') {
            return;
        }

        // CSS admin
        $admin_css = INMOVILLA_PROPERTIES_PLUGIN_DIR . 'assets/css/inmovilla-admin.css';
        if (file_exists($admin_css)) {
            wp_enqueue_style(
                'inmovilla-admin',
                INMOVILLA_PROPERTIES_ASSETS_URL . 'css/inmovilla-admin.css',
                array(),
                filemtime($admin_css)
            );
        }

        // JavaScript admin
        $admin_js = INMOVILLA_PROPERTIES_PLUGIN_DIR . 'assets/js/inmovilla-admin.js';
        if (file_exists($admin_js)) {
            wp_enqueue_script(
                'inmovilla-admin',
                INMOVILLA_PROPERTIES_ASSETS_URL . 'js/inmovilla-admin.js',
                array('jquery', 'wp-color-picker'),
                filemtime($admin_js),
                true
            );
        }

        // Dependencias adicionales
        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Cargar traducciones
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'inmovilla-properties',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Manejar búsquedas AJAX
     */
    public function handle_ajax_search() {

        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'inmovilla_nonce')) {
            wp_die(__('Error de seguridad', 'inmovilla-properties'));
        }

        // Delegar al componente de búsqueda
        if ($this->search) {
            $this->search->handle_ajax_search();
        } else {
            wp_send_json_error(__('Componente de búsqueda no disponible', 'inmovilla-properties'));
        }
    }

    /**
     * Manejar favoritos AJAX
     */
    public function handle_ajax_favorites() {

        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'inmovilla_nonce')) {
            wp_die(__('Error de seguridad', 'inmovilla-properties'));
        }

        // Delegar al componente de favoritos
        if ($this->favorites) {
            $this->favorites->handle_ajax_favorites();
        } else {
            wp_send_json_error(__('Componente de favoritos no disponible', 'inmovilla-properties'));
        }
    }

    /**
     * Activación del plugin
     */
    public function activate() {

        // Crear tablas si es necesario
        $this->create_database_tables();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Configuración inicial
        if (!get_option('inmovilla_properties_version')) {
            add_option('inmovilla_properties_version', $this->version);
            add_option('inmovilla_properties_settings', $this->get_default_settings());
        }
    }

    /**
     * Desactivación del plugin
     */
    public function deactivate() {

        // Flush rewrite rules
        flush_rewrite_rules();

        // Limpiar cache si existe
        if ($this->cache) {
            $this->cache->clear_all_cache();
        }
    }

    /**
     * Crear tablas de base de datos
     */
    private function create_database_tables() {

        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla para estadísticas y logs
        $table_name = $wpdb->prefix . 'inmovilla_logs';

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            action varchar(100) NOT NULL,
            property_id varchar(50),
            user_ip varchar(45),
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            data longtext,
            PRIMARY KEY (id),
            KEY action (action),
            KEY property_id (property_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Configuración por defecto
     */
    private function get_default_settings() {
        return array(
            'api_token' => '',
            'api_url' => 'https://crm.inmovilla.com/api/',
            'cache_enabled' => true,
            'cache_duration' => 3600,
            'properties_per_page' => 12,
            'primary_color' => '#2196F3',
            'secondary_color' => '#FF5722',
            'enable_favorites' => true,
            'enable_search' => true,
            'enable_sitemap' => true,
            'google_maps_api_key' => '',
            'default_map_zoom' => 14,
        );
    }

    /**
     * Obtener configuración
     */
    public function get_setting($key, $default = null) {
        $settings = get_option('inmovilla_properties_settings', $this->get_default_settings());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    /**
     * Guardar configuración
     */
    public function update_setting($key, $value) {
        $settings = get_option('inmovilla_properties_settings', $this->get_default_settings());
        $settings[$key] = $value;
        return update_option('inmovilla_properties_settings', $settings);
    }
}

/**
 * Inicializar el plugin
 */
function inmovilla_properties() {
    return InmovillaProperties::get_instance();
}

// Inicializar cuando WordPress esté listo
add_action('plugins_loaded', 'inmovilla_properties');

/**
 * Funciones de utilidad globales
 */

/**
 * Obtener instancia del plugin
 */
function inmovilla_get_instance() {
    return inmovilla_properties();
}

/**
 * Obtener configuración
 */
function inmovilla_get_setting($key, $default = null) {
    return inmovilla_properties()->get_setting($key, $default);
}

/**
 * Obtener URL de assets
 */
function inmovilla_asset_url($file) {
    return INMOVILLA_PROPERTIES_ASSETS_URL . ltrim($file, '/');
}

/**
 * Obtener ruta de template
 */
function inmovilla_template_path($file) {
    return INMOVILLA_PROPERTIES_TEMPLATES_DIR . ltrim($file, '/');
}

/**
 * Verificar si el plugin está configurado
 */
function inmovilla_is_configured() {
    $token = inmovilla_get_setting('api_token');
    return !empty($token);
}
