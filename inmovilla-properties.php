<?php
/**
 * Plugin Name: Inmovilla Properties
 * Plugin URI: https://github.com/tuempresa/inmovilla-properties
 * Version: 2.0.3-final-fix
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

// --- CONSTANTES GLOBALES ---
define('INMOVILLA_PROPERTIES_VERSION', '2.0.3-final-fix');
define('INMOVILLA_PROPERTIES_PLUGIN_FILE', __FILE__);
define('INMOVILLA_PROPERTIES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('INMOVILLA_PROPERTIES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('INMOVILLA_PROPERTIES_INCLUDES_DIR', INMOVILLA_PROPERTIES_PLUGIN_DIR . 'includes/');
define('INMOVILLA_PROPERTIES_TEMPLATES_DIR', INMOVILLA_PROPERTIES_PLUGIN_DIR . 'templates/');
define('INMOVILLA_PROPERTIES_ASSETS_URL', INMOVILLA_PROPERTIES_PLUGIN_URL . 'assets/');


// --- FUNCIONES DE UTILIDAD GLOBALES (CORREGIDO) ---
// Definimos esta función aquí para que esté disponible globalmente antes de que se carguen las clases.
if (!function_exists('inmovilla_get_setting')) {
    function inmovilla_get_setting($key, $default = null) {
        $settings = get_option('inmovilla_properties_options', array());

        // Compatibilidad con instalaciones previas que usaban otro nombre de opción
        if (empty($settings)) {
            $settings = get_option('inmovilla_properties_settings', array());
        }

        return isset($settings[$key]) ? $settings[$key] : $default;
    }
}


/**
 * Clase principal del plugin
 */
final class InmovillaProperties {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function includes() {
        require_once INMOVILLA_PROPERTIES_INCLUDES_DIR . 'class-inmovilla-api.php';
        require_once INMOVILLA_PROPERTIES_INCLUDES_DIR . 'class-inmovilla-cache.php';
        require_once INMOVILLA_PROPERTIES_INCLUDES_DIR . 'class-inmovilla-properties-manager.php';
        require_once INMOVILLA_PROPERTIES_INCLUDES_DIR . 'class-inmovilla-shortcodes.php';
        require_once INMOVILLA_PROPERTIES_INCLUDES_DIR . 'class-inmovilla-seo.php';
        require_once INMOVILLA_PROPERTIES_INCLUDES_DIR . 'class-inmovilla-sitemap.php';
        require_once INMOVILLA_PROPERTIES_INCLUDES_DIR . 'class-inmovilla-favorites.php';
        require_once INMOVILLA_PROPERTIES_INCLUDES_DIR . 'class-inmovilla-search.php';
        require_once INMOVILLA_PROPERTIES_PLUGIN_DIR . 'public/class-inmovilla-public.php';
        require_once INMOVILLA_PROPERTIES_PLUGIN_DIR . 'public/class-inmovilla-ajax.php';
        
        if (is_admin()) {
            require_once INMOVILLA_PROPERTIES_PLUGIN_DIR . 'admin/admin-settings.php';
        }
    }

    private function init_hooks() {
        register_activation_hook(INMOVILLA_PROPERTIES_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(INMOVILLA_PROPERTIES_PLUGIN_FILE, array($this, 'deactivate'));
        add_action('plugins_loaded', array($this, 'on_plugins_loaded'));
    }

    public function on_plugins_loaded() {
        load_plugin_textdomain('inmovilla-properties', false, dirname(plugin_basename(INMOVILLA_PROPERTIES_PLUGIN_FILE)) . '/languages/');
        $this->register_post_types_and_taxonomies();
        new Inmovilla_Properties_Manager();
        new InmovillaShortcodes();
        new InmovillaSEO();
        new InmovillaSitemap();
        new InmovillaFavorites();
        new InmovillaSearch();
        new Inmovilla_Public();
        new Inmovilla_Ajax();
        if (is_admin()) {
            new Inmovilla_Admin_Settings();
        }
    }

    public function activate() {
        $this->register_post_types_and_taxonomies();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
        wp_clear_scheduled_hook('inmovilla_sync_properties');
    }

    public function register_post_types_and_taxonomies() {
        add_action('init', function() {
            register_post_type('inmovilla_property', array(
                'labels' => array(
                    'name' => __('Propiedades', 'inmovilla-properties'), 
                    'singular_name' => __('Propiedad', 'inmovilla-properties'),
                    'menu_name' => __('Propiedades Inmovilla', 'inmovilla-properties'),
                ),
                'public' => true, 'publicly_queryable' => true, 'show_ui' => true, 'show_in_menu' => true,
                'menu_icon' => 'dashicons-admin-home', 'query_var' => true, 'rewrite' => array('slug' => 'propiedades'),
                'has_archive' => true, 'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'), 'show_in_rest' => true,
            ));
            register_taxonomy('property_type', 'inmovilla_property', array(
                'labels' => array('name' => __('Tipos de Propiedad', 'inmovilla-properties')), 'hierarchical' => true, 'public' => true,
                'show_ui' => true, 'rewrite' => array('slug' => 'tipo'), 'show_in_rest' => true,
            ));
            register_taxonomy('property_location', 'inmovilla_property', array(
                'labels' => array('name' => __('Ubicaciones', 'inmovilla-properties')), 'hierarchical' => true, 'public' => true,
                'show_ui' => true, 'rewrite' => array('slug' => 'ubicacion'), 'show_in_rest' => true,
            ));
        }, 0); // Prioridad 0 para asegurar que se ejecuta pronto
    }
}

/**
 * Función global para iniciar el plugin
 */
if (!function_exists('inmovilla_properties')) {
    function inmovilla_properties() {
        return InmovillaProperties::instance();
    }
}

// Arrancamos el plugin
inmovilla_properties();
