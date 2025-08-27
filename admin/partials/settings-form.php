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
    
    <form method="post" action="options.php">
        <?php
        settings_fields('inmovilla_properties_settings');
        do_settings_sections('inmovilla_properties_settings');
        ?>
        
        <div class="inmovilla-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#api-settings" class="nav-tab nav-tab-active">
                    <i class="fas fa-plug"></i> <?php _e('API', 'inmovilla-properties'); ?>
                </a>
                <a href="#design-settings" class="nav-tab">
                    <i class="fas fa-paint-brush"></i> <?php _e('Diseño', 'inmovilla-properties'); ?>
                </a>
                <a href="#seo-settings" class="nav-tab">
                    <i class="fas fa-search"></i> <?php _e('SEO', 'inmovilla-properties'); ?>
                </a>
                <a href="#advanced-settings" class="nav-tab">
                    <i class="fas fa-cog"></i> <?php _e('Avanzado', 'inmovilla-properties'); ?>
                </a>
            </nav>
            
            <!-- API Settings Tab -->
            <div id="api-settings" class="tab-content active">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="api_token"><?php _e('Token API', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="api_token" 
                                   name="inmovilla_properties_options[api_token]" 
                                   value="<?php echo esc_attr($this->options['api_token'] ?? ''); ?>" 
                                   class="regular-text" 
                                   required />
                            <button type="button" class="button" onclick="togglePasswordVisibility('api_token')">
                                <?php _e('Mostrar', 'inmovilla-properties'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Token obtenido desde Inmovilla → Ajustes → Opciones → Token para API Rest', 'inmovilla-properties'); ?>
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
                                   value="<?php echo esc_attr($this->options['api_base_url'] ?? 'https://crm.inmovilla.com/api/v1/'); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php _e('URL base de la API de Inmovilla', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cache_duration"><?php _e('Duración del Caché', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <select id="cache_duration" name="inmovilla_properties_options[cache_duration]">
                                <option value="300" <?php selected($this->options['cache_duration'] ?? 900, 300); ?>>
                                    <?php _e('5 minutos', 'inmovilla-properties'); ?>
                                </option>
                                <option value="900" <?php selected($this->options['cache_duration'] ?? 900, 900); ?>>
                                    <?php _e('15 minutos', 'inmovilla-properties'); ?>
                                </option>
                                <option value="1800" <?php selected($this->options['cache_duration'] ?? 900, 1800); ?>>
                                    <?php _e('30 minutos', 'inmovilla-properties'); ?>
                                </option>
                                <option value="3600" <?php selected($this->options['cache_duration'] ?? 900, 3600); ?>>
                                    <?php _e('1 hora', 'inmovilla-properties'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('Tiempo que se mantienen los datos en caché para mejorar el rendimiento', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Design Settings Tab -->
            <div id="design-settings" class="tab-content">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="primary_color"><?php _e('Color Primario', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input type="color" 
                                   id="primary_color" 
                                   name="inmovilla_properties_options[primary_color]" 
                                   value="<?php echo esc_attr($this->options['primary_color'] ?? '#2563eb'); ?>" />
                            <p class="description">
                                <?php _e('Color principal utilizado en botones y enlaces', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="secondary_color"><?php _e('Color Secundario', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input type="color" 
                                   id="secondary_color" 
                                   name="inmovilla_properties_options[secondary_color]" 
                                   value="<?php echo esc_attr($this->options['secondary_color'] ?? '#64748b'); ?>" />
                            <p class="description">
                                <?php _e('Color secundario utilizado en textos y fondos', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="properties_per_page"><?php _e('Propiedades por Página', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="properties_per_page" 
                                   name="inmovilla_properties_options[properties_per_page]" 
                                   value="<?php echo esc_attr($this->options['properties_per_page'] ?? 12); ?>" 
                                   min="1" 
                                   max="50" 
                                   class="small-text" />
                            <p class="description">
                                <?php _e('Número de propiedades a mostrar por página en los listados', 'inmovilla-properties'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- SEO Settings Tab -->
            <div id="seo-settings" class="tab-content">
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
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_sitemap"><?php _e('Generar Sitemap', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="enable_sitemap" 
                                       name="inmovilla_properties_options[enable_sitemap]" 
                                       value="1" 
                                       <?php checked($this->options['enable_sitemap'] ?? 1, 1); ?> />
                                <?php _e('Generar sitemap automático para propiedades', 'inmovilla-properties'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Advanced Settings Tab -->
            <div id="advanced-settings" class="tab-content">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="debug_mode"><?php _e('Modo Debug', 'inmovilla-properties'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="debug_mode" 
                                       name="inmovilla_properties_options[debug_mode]" 
                                       value="1" 
                                       <?php checked($this->options['debug_mode'] ?? 0, 1); ?> />
                                <?php _e('Activar logs detallados para debugging', 'inmovilla-properties'); ?>
                            </label>
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