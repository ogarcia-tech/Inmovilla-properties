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

        // Sección API
        add_settings_section(
            'inmovilla_api_section',
            __('Configuración de API', 'inmovilla-properties'),
            array($this, 'api_section_callback'),
            'inmovilla_properties_settings'
        );

        add_settings_field(
            'agency_id',
            __('Número de Agencia', 'inmovilla-properties'),
            array($this, 'agency_id_callback'),
            'inmovilla_properties_settings',
            'inmovilla_api_section'
        );

        add_settings_field(
            'api_password',
            __('Contraseña API', 'inmovilla-properties'),
            array($this, 'api_password_callback'),
            'inmovilla_properties_settings',
            'inmovilla_api_section'
        );

        add_settings_field(
            'api_base_url',
            __('URL Base API', 'inmovilla-properties'),
            array($this, 'api_base_url_callback'),
            'inmovilla_properties_settings',
            'inmovilla_api_section'
        );

        add_settings_field(
            'xml_feed_url',
            __('URL del Feed XML', 'inmovilla-properties'),
            array($this, 'xml_feed_url_callback'),
            'inmovilla_properties_settings',
            'inmovilla_api_section'
        );

        // Sección SEO
        add_settings_section(
            'inmovilla_seo_section',
            __('Configuración SEO', 'inmovilla-properties'),
            array($this, 'seo_section_callback'),
            'inmovilla_properties_settings'
        );
        
        add_settings_field(
            'base_slug',
            __('Slug Base URL', 'inmovilla-properties'),
            array($this, 'base_slug_callback'),
            'inmovilla_properties_settings',
            'inmovilla_seo_section'
        );
    }
    
    /**
     * Página de configuración
     */
    public function settings_page() {
        $this->options = get_option('inmovilla_properties_options');
        include INMOVILLA_PROPERTIES_PLUGIN_DIR . 'admin/partials/settings-form.php';
    }

    /**
     * Callbacks de secciones
     */
    public function api_section_callback() {
        echo '<p>' . __('Configuración de conexión con la API de Inmovilla', 'inmovilla-properties') . '</p>';
    }

    public function seo_section_callback() {
        echo '<p>' . __('Configuración para optimización SEO', 'inmovilla-properties') . '</p>';
    }
    
    /**
     * Callbacks de campos
     */
    public function agency_id_callback() {
        $value = isset($this->options['agency_id']) ? intval($this->options['agency_id']) : '';
        printf(
            '<input type="number" id="agency_id" name="inmovilla_properties_options[agency_id]" value="%s" class="regular-text" />
            <p class="description">%s</p>',
            esc_attr($value),
            __('Número de agencia facilitado por Inmovilla (ej: 2)', 'inmovilla-properties')
        );
    }

    public function api_password_callback() {
        $value = isset($this->options['api_password']) ? $this->options['api_password'] : '';
        printf(
            '<input type="password" id="api_password" name="inmovilla_properties_options[api_password]" value="%s" class="regular-text" />
            <p class="description">%s</p>',
            esc_attr($value),
            __('Contraseña asociada a tu número de agencia', 'inmovilla-properties')
        );
    }

    public function api_base_url_callback() {
        $value = isset($this->options['api_base_url']) ? $this->options['api_base_url'] : 'https://apiweb.inmovilla.com/apiweb/apiweb.php';
        printf(
            '<input type="url" id="api_base_url" name="inmovilla_properties_options[api_base_url]" value="%s" class="regular-text" />
            <p class="description">%s</p>',
            esc_attr($value),
            __('Endpoint clásico de Inmovilla (param + json=1)', 'inmovilla-properties')
        );
    }

    public function xml_feed_url_callback() {
        $value = isset($this->options['xml_feed_url']) ? $this->options['xml_feed_url'] : '';
        printf(
            '<input type="url" id="xml_feed_url" name="inmovilla_properties_options[xml_feed_url]" value="%s" class="regular-text" placeholder="https://procesos.inmovilla.com/xml/..." />
            <p class="description">%s</p>',
            esc_attr($value),
            __('Introduce la URL completa del feed XML que Inmovilla genera para tu agencia.', 'inmovilla-properties')
        );
    }

    public function base_slug_callback() {
        $value = isset($this->options['base_slug']) ? $this->options['base_slug'] : 'propiedades';
        printf(
            '<input type="text" id="base_slug" name="inmovilla_properties_options[base_slug]" value="%s" class="regular-text" />
            <p class="description">%s</p>',
            esc_attr($value),
            __('Slug base para las URLs de propiedades (ej: /propiedades/mi-propiedad)', 'inmovilla-properties')
        );
    }
    
    /**
     * Test de conexión API
     */
    public function test_api_connection() {
        $api = new InmovillaAPI();
        $test = $api->test_connection();
        
        if ($test['success']) {
            return array(
                'status' => 'success',
                'message' => __('Conexión exitosa con la API de Inmovilla', 'inmovilla-properties'),
                'data' => $test['data']
            );
        } else {
            return array(
                'status' => 'error',
                'message' => $test['message']
            );
        }
    }
}