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
        
        // Registro del botón API individual e Historial
        add_action('post_submitbox_misc_actions', array($this, 'add_api_update_button'));
        add_action('wp_ajax_inmo_update_single', array($this, 'ajax_update_single_property'));
    }
    
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
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'inmovilla-properties') !== false || (isset(get_current_screen()->post_type) && get_current_screen()->post_type === 'inmovilla_property')) {
            wp_enqueue_script('inmovilla-admin-js',
                INMOVILLA_PROPERTIES_ASSETS_URL . 'js/inmovilla-admin.js',
                array('jquery'),
                INMOVILLA_PROPERTIES_VERSION,
                true
            );
            
            wp_localize_script('inmovilla-admin-js', 'inmovilla_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('inmovilla_admin_nonce'),
                'single_nonce' => wp_create_nonce('inmo_single_nonce')
            ));
        }
    }
    
    public function settings_init() {
        register_setting('inmovilla_properties_settings', 'inmovilla_properties_options');
    }
    
    public function settings_page() {
        $this->options = get_option('inmovilla_properties_options');
        include INMOVILLA_PROPERTIES_PLUGIN_DIR . 'admin/partials/settings-form.php';
        
        // Renderizar historial al final de los ajustes
        $this->render_sync_history();
    }

    private function render_sync_history() {
        $history = get_option('inmovilla_sync_history', []);
        ?>
        <div class="wrap" style="margin-top: 30px; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
            <h2><?php _e('Historial de Sincronización', 'inmovilla-properties'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Nuevas</th>
                        <th>Actualizadas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="4">No hay registros aún.</td></tr>
                    <?php else: foreach ($history as $log): ?>
                        <tr>
                            <td><?php echo $log['fecha']; ?></td>
                            <td><strong><?php echo $log['total']; ?></strong></td>
                            <td><span style="color:green; font-weight:bold;">+<?php echo $log['nuevas']; ?></span></td>
                            <td><span style="color:blue; font-weight:bold;">~<?php echo $log['actualizadas']; ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function add_api_update_button($post) {
        if ($post->post_type !== 'inmovilla_property') return;
        ?>
        <div class="misc-pub-section">
            <button type="button" id="inmo-api-update" class="button button-secondary" data-post-id="<?php echo $post->ID; ?>">
                <span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Actualizar vía API
            </button>
            <script>
                jQuery(document).ready(function($) {
                    $('#inmo-api-update').on('click', function() {
                        const btn = $(this);
                        if(!confirm('¿Actualizar datos desde la API?')) return;
                        btn.prop('disabled', true).text('Procesando...');
                        $.post(ajaxurl, {
                            action: 'inmo_update_single',
                            post_id: btn.data('post-id'),
                            nonce: '<?php echo wp_create_nonce("inmo_single_nonce"); ?>'
                        }, function(res) {
                            alert(res.success ? 'Actualizado correctamente' : 'Error en la conexión');
                            location.reload();
                        });
                    });
                });
            </script>
        </div>
        <?php
    }

    public function ajax_update_single_property() {
        check_ajax_referer('inmo_single_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_send_json_error();
        $manager = new Inmovilla_Properties_Manager();
        $success = $manager->update_single_property_via_api($_POST['post_id']);
        $success ? wp_send_json_success() : wp_send_json_error();
    }

    public function ajax_sync_properties() {
        check_ajax_referer('inmovilla_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();
        do_action('inmovilla_sync_properties');
        wp_send_json_success();
    }
}
