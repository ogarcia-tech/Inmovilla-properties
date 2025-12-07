<?php
/**
 * Formulario de configuración del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Configuración Inmovilla Properties', 'inmovilla-properties'); ?></h1>

    <form id="inmovilla-settings-form" method="post" action="options.php">
        <?php
        settings_fields('inmovilla_properties_settings');
        ?>

        <div class="inmovilla-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#api-settings" class="nav-tab nav-tab-active inmovilla-admin-nav-tab active" data-tab="api-settings">
                    <i class="fas fa-plug"></i> <?php _e('API', 'inmovilla-properties'); ?>
                </a>
                <a href="#seo-settings" class="nav-tab inmovilla-admin-nav-tab" data-tab="seo-settings">
                    <i class="fas fa-search"></i> <?php _e('SEO', 'inmovilla-properties'); ?>
                </a>
            </nav>

            <!-- API Settings Tab -->
            <div id="api-settings" class="tab-content inmovilla-tab-content active">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="agency_id"><?php _e('Número de Agencia', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input type="number"
                                   id="agency_id"
                                   name="inmovilla_properties_options[agency_id]"
                                   value="<?php echo esc_attr($this->options['agency_id'] ?? ''); ?>"
                                   class="regular-text" />
                            <p class="description">
                                <?php _e('Número de agencia proporcionado por Inmovilla (ej: 2)', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="api_password"><?php _e('Contraseña API', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input type="password"
                                   id="api_password"
                                   name="inmovilla_properties_options[api_password]"
                                   value="<?php echo esc_attr($this->options['api_password'] ?? ''); ?>"
                                   class="regular-text" />
                            <p class="description">
                                <?php _e('Contraseña que acompaña al número de agencia', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="api_base_url"><?php _e('URL Base API', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input type="url"
                                   id="api_base_url"
                                   name="inmovilla_properties_options[api_base_url]"
                                   value="<?php echo esc_attr($this->options['api_base_url'] ?? 'https://apiweb.inmovilla.com/apiweb/apiweb.php'); ?>"
                                   class="regular-text" />
                            <p class="description">
                                <?php _e('Endpoint legacy usado por Inmovilla (no RESTful)', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="xml_feed_url"><?php _e('URL del Feed XML', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input type="url"
                                   id="xml_feed_url"
                                   name="inmovilla_properties_options[xml_feed_url]"
                                   value="<?php echo esc_attr($this->options['xml_feed_url'] ?? ''); ?>"
                                   class="regular-text"
                                   placeholder="https://procesos.inmovilla.com/xml/..." />
                            <p class="description">
                                <?php _e('Introduce la URL del archivo XML proporcionada por Inmovilla para la sincronización diaria.', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <div class="inmovilla-sync-actions">
                    <button type="button" id="inmovilla-sync-now" class="button button-primary">
                        <?php _e('Forzar importación ahora', 'inmovilla-properties'); ?>
                    </button>
                    <p id="inmovilla-sync-status" class="description">
                        <?php
                        $last_sync = get_option('inmovilla_last_sync');
                        if (!empty($last_sync)) {
                            printf(
                                /* translators: %s: formatted datetime */
                                __('Última sincronización completada: %s', 'inmovilla-properties'),
                                esc_html($last_sync)
                            );
                        } else {
                            _e('Aún no se ha ejecutado ninguna sincronización.', 'inmovilla-properties');
                        }
                        ?>
                    </p>
                </div>
            </div>

            <!-- SEO Settings Tab -->
            <div id="seo-settings" class="tab-content inmovilla-tab-content">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="base_slug"><?php _e('Slug Base URL', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="base_slug" 
                                   name="inmovilla_properties_options[base_slug]" 
                                   value="<?php echo esc_attr($this->options['base_slug'] ?? 'propiedades'); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php _e('Slug base para las URLs de propiedades (ej: /propiedades/mi-propiedad)', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div>

<script>
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    
    if (field.type === 'password') {
        field.type = 'text';
        button.textContent = '<?php _e('Ocultar', 'inmovilla-properties'); ?>';
    } else {
        field.type = 'password';
        button.textContent = '<?php _e('Mostrar', 'inmovilla-properties'); ?>';
    }
}

// Tabs functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.nav-tab');
    const contents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('nav-tab-active'));
            contents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('nav-tab-active');
            
            // Show corresponding content
            const target = this.getAttribute('href').substring(1);
            document.getElementById(target).classList.add('active');
        });
    });
});
</script>