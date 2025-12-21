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
        add_action('wp_ajax_inmovilla_sync_properties', array($this, 'ajax_sync_properties'));
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
    /**
     * Botón de actualización API individual
     */
    add_action('post_submitbox_misc_actions', 'inmovilla_add_api_update_button');
    function inmovilla_add_api_update_button($post) {
        if ($post->post_type !== 'inmovilla_property') return;
        ?>
        <div class="misc-pub-section">
            <button type="button" id="inmo-api-update" class="button button-secondary" data-post-id="<?php echo $post->ID; ?>">
                <span class="dashicons dashicons-update" style="vertical-align: text-bottom;"></span> Actualizar vía API
            </button>
            <script>
                jQuery('#inmo-api-update').on('click', function() {
                    const btn = jQuery(this);
                    if(!confirm('¿Actualizar datos críticos desde la API?')) return;
                    btn.prop('disabled', true).text('Procesando...');
                    jQuery.post(ajaxurl, {
                        action: 'inmo_update_single',
                        post_id: btn.data('post-id'),
                        nonce: '<?php echo wp_create_nonce("inmo_single_nonce"); ?>'
                    }, function(res) {
                        alert(res.success ? 'Propiedad actualizada con éxito.' : 'Error en la conexión API.');
                        location.reload();
                    });
                });
            </script>
        </div>
        <?php
    }
    
    add_action('wp_ajax_inmo_update_single', function() {
        check_ajax_referer('inmo_single_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error();
        $manager = new Inmovilla_Properties_Manager();
        $success = $manager->update_single_property_via_api($_POST['post_id']);
        $success ? wp_send_json_success() : wp_send_json_error();
    });

    /**
     * Ejecuta la sincronización manual desde AJAX
     */
    public function ajax_sync_properties() {
        check_ajax_referer('inmovilla_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('No tienes permisos para ejecutar la sincronización.', 'inmovilla-properties')
            ), 403);
        }

        $xml_feed_url = inmovilla_get_setting('xml_feed_url');

        if (empty($xml_feed_url)) {
            wp_send_json_error(array(
                'message' => __('Configura la URL del feed XML antes de sincronizar.', 'inmovilla-properties')
            ));
        }

        /**
         * Disparamos el hook principal de sincronización.
         * La clase Inmovilla_Properties_Manager se encarga de procesar el feed.
         */
        do_action('inmovilla_sync_properties');

        wp_send_json_success(array(
            'message' => __('Sincronización completada correctamente.', 'inmovilla-properties')
        ));
    }
}
