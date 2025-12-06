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
                            <label for="agency_number"><?php _e('Número de agencia', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="agency_number"
                                name="inmovilla_properties_settings[agency_number]"
                                value="<?php echo esc_attr($settings['agency_number'] ?? ''); ?>"
                                class="small-text"
                                min="1"
                            />
                            <p class="description">
                                <?php _e('Clave numérica que proporciona Inmovilla (ej: 2)', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="agency_suffix"><?php _e('Sufijo de agencia (opcional)', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                id="agency_suffix"
                                name="inmovilla_properties_settings[agency_suffix]"
                                value="<?php echo esc_attr($settings['agency_suffix'] ?? ''); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php _e('Solo si tu número de agencia lleva sufijo, por ejemplo _84', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="api_password"><?php _e('Contraseña API', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input
                                type="password"
                                id="api_password"
                                name="inmovilla_properties_settings[api_password]"
                                value="<?php echo esc_attr($settings['api_password'] ?? ''); ?>"
                                class="regular-text"
                            />
                            <p class="description">
                                <?php _e('Contraseña proporcionada por Inmovilla para el servicio API (ej: 82ku9xz2aw3)', 'inmovilla-properties'); ?>
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
                            <label for="language"><?php _e('Idioma (ID)', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="language"
                                name="inmovilla_properties_settings[language]"
                                value="<?php echo esc_attr($settings['language'] ?? 1); ?>"
                                min="1"
                                max="18"
                                class="small-text"
                            />
                            <p class="description">
                                <?php _e('ID de idioma según la tabla de la API (1=Español, 2=Inglés, etc.)', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="schedule_interval"><?php _e('Intervalo de sincronización', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <select id="schedule_interval" name="inmovilla_properties_settings[schedule_interval]">
                                <?php
                                $schedules = wp_get_schedules();
                                $current_interval = $settings['schedule_interval'] ?? 'hourly';
                                foreach ($schedules as $key => $schedule) {
                                    echo '<option value="' . esc_attr($key) . '"' . selected($current_interval, $key, false) . '>' . esc_html($schedule['display']) . '</option>';
                                }
                                ?>
                            </select>
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

                    <tr>
                        <th scope="row">
                            <label for="agency_contact"><?php _e('Contacto de la agencia', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <textarea
                                id="agency_contact"
                                name="inmovilla_properties_settings[agency_contact]"
                                rows="3"
                                class="large-text"
                            ><?php echo esc_textarea($settings['agency_contact'] ?? ''); ?></textarea>
                            <p class="description">
                                <?php _e('Información de contacto que se mostrará en la ficha de propiedad.', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="legal_note"><?php _e('Nota legal', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <textarea
                                id="legal_note"
                                name="inmovilla_properties_settings[legal_note]"
                                rows="3"
                                class="large-text"
                            ><?php echo esc_textarea($settings['legal_note'] ?? ''); ?></textarea>
                            <p class="description">
                                <?php _e('Texto legal que aparecerá al final de la ficha de propiedad.', 'inmovilla-properties'); ?>
                            </p>
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
                const $button = $(this);
                const errorMsg = '<?php echo esc_js(__('Error de conexión', 'inmovilla-properties')); ?>';

                $button.prop('disabled', true);
                $('#connection-result').html('<?php _e('Probando...', 'inmovilla-properties'); ?>');

                $.post(ajaxurl, {
                    action: 'inmovilla_test_connection',
                    nonce: '<?php echo wp_create_nonce('inmovilla_admin_nonce'); ?>'
                }).done(function(response) {
                    $button.prop('disabled', false);

                    if (response.success) {
                        $('#connection-result').html('<span style="color: green;">' + response.data.message + '</span>');
                    } else {
                        const message = response.data && response.data.message ? response.data.message : errorMsg;
                        $('#connection-result').html('<span style="color: red;">' + message + '</span>');
                    }
                }).fail(function() {
                    $button.prop('disabled', false);
                    $('#connection-result').html('<span style="color: red;">' + errorMsg + '</span>');
                });
            });
        });
        </script>
        <?php
    }
}
