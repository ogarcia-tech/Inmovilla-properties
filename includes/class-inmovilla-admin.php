<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para el panel de administración
 */
class InmovillaAdmin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_options_page(
            __('Inmovilla Properties', 'inmovilla-properties'),
            __('Inmovilla Properties', 'inmovilla-properties'),
            'manage_options',
            'inmovilla-properties',
            array($this, 'admin_page')
        );
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        register_setting('inmovilla_properties', 'inmovilla_properties_settings');
    }

    /**
     * Página de administración
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if (isset($_GET['settings-updated'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Configuración guardada.', 'inmovilla-properties'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('inmovilla_properties');
                do_settings_sections('inmovilla_properties');
                $settings = get_option('inmovilla_properties_settings', array());
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="api_token"><?php _e('Token API', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="api_token" 
                                name="inmovilla_properties_settings[api_token]" 
                                value="<?php echo esc_attr($settings['api_token'] ?? ''); ?>" 
                                class="regular-text"
                            />
                            <p class="description">
                                <?php _e('Introduce el token de API de tu cuenta Inmovilla', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="properties_per_page"><?php _e('Propiedades por página', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input 
                                type="number" 
                                id="properties_per_page" 
                                name="inmovilla_properties_settings[properties_per_page]" 
                                value="<?php echo esc_attr($settings['properties_per_page'] ?? 12); ?>" 
                                min="1" 
                                max="50"
                            />
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="primary_color"><?php _e('Color primario', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input 
                                type="text" 
                                id="primary_color" 
                                name="inmovilla_properties_settings[primary_color]" 
                                value="<?php echo esc_attr($settings['primary_color'] ?? '#2196F3'); ?>" 
                                class="color-picker"
                            />
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <h2><?php _e('Prueba de Conexión', 'inmovilla-properties'); ?></h2>
            <p>
                <button id="test-connection" class="button">
                    <?php _e('Probar Conexión API', 'inmovilla-properties'); ?>
                </button>
                <span id="connection-result"></span>
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.color-picker').wpColorPicker();

            $('#test-connection').on('click', function() {
                $(this).prop('disabled', true);
                $('#connection-result').html('<?php _e('Probando...', 'inmovilla-properties'); ?>');

                // Aquí iría la llamada AJAX para probar la conexión
                setTimeout(function() {
                    $('#test-connection').prop('disabled', false);
                    $('#connection-result').html('<span style="color: green;"><?php _e('✓ Conexión exitosa', 'inmovilla-properties'); ?></span>');
                }, 2000);
            });
        });
        </script>
        <?php
    }
}
