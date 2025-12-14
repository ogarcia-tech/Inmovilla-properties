<?php
/**
 * Página de configuración del plugin Inmovilla Properties
 */

if (!defined('ABSPATH')) {
    exit;
}

class Inmovilla_Admin_Settings {
    
    private $options;
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Inmovilla Properties', 'inmovilla-properties'),
            __('Inmovilla Properties', 'inmovilla-properties'),
            'manage_options',
            'inmovilla-properties',
            array($this, 'settings_page'),
            'dashicons-admin-home',
            30
        );
    }
    
    /**
     * Cargar scripts del admin
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'inmovilla-properties') !== false) {
            wp_enqueue_script('inmovilla-admin-js',
                INMOVILLA_PROPERTIES_ASSETS_URL . 'js/inmovilla-admin.js',
                array('jquery'),
                INMOVILLA_PROPERTIES_VERSION,
                true
            );

            wp_enqueue_style('inmovilla-admin-css',
                INMOVILLA_PROPERTIES_ASSETS_URL . 'css/inmovilla-admin.css',
                array(),
                INMOVILLA_PROPERTIES_VERSION
            );
            
            wp_localize_script('inmovilla-admin-js', 'inmovilla_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('inmovilla_admin_nonce')
            ));
        }
    }
    
    /**
     * Inicializar configuración
     */
    public function settings_init() {
        register_setting('inmovilla_properties_settings', 'inmovilla_properties_options');
    }
    
    /**
     * Página de configuración
     */
    public function settings_page() {
        $this->options = get_option('inmovilla_properties_options');
        include INMOVILLA_PROPERTIES_PLUGIN_DIR . 'admin/partials/settings-form.php';
    }
}