<?php
/**
 * Pestaña de ayuda del plugin
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="inmovilla-help-content">
    <div class="help-sections">
        <!-- Configuración Inicial -->
        <div class="help-section">
            <h3><i class="fas fa-rocket"></i> <?php _e('Configuración Inicial', 'inmovilla-properties'); ?></h3>
            <ol>
                <li><strong><?php _e('Obtener Token API:', 'inmovilla-properties'); ?></strong>
                    <ul>
                        <li><?php _e('Inicia sesión en tu cuenta de Inmovilla', 'inmovilla-properties'); ?></li>
                        <li><?php _e('Ve a Ajustes → Opciones', 'inmovilla-properties'); ?></li>
                        <li><?php _e('Copia el "Token para API Rest"', 'inmovilla-properties'); ?></li>
                        <li><?php _e('Pégalo en la configuración del plugin', 'inmovilla-properties'); ?></li>
                    </ul>
                </li>
                <li><strong><?php _e('Crear Páginas Necesarias:', 'inmovilla-properties'); ?></strong>
                    <ul>
                        <li><?php _e('Crea una página para el listado de propiedades', 'inmovilla-properties'); ?></li>
                        <li><?php _e('Añade el shortcode [inmovilla_properties]', 'inmovilla-properties'); ?></li>
                        <li><?php _e('Configura el slug en Ajustes → Enlaces permanentes', 'inmovilla-properties'); ?></li>
                    </ul>
                </li>
            </ol>
        </div>
        
        <!-- Shortcodes Disponibles -->
        <div class="help-section">
            <h3><i class="fas fa-code"></i> <?php _e('Shortcodes Disponibles', 'inmovilla-properties'); ?></h3>
            
            <div class="shortcode-help">
                <h4><code>[inmovilla_properties]</code></h4>
                <p><?php _e('Muestra un listado de propiedades con paginación y filtros.', 'inmovilla-properties'); ?></p>
                <p><strong><?php _e('Parámetros:', 'inmovilla-properties'); ?></strong></p>
                <ul>
                    <li><code>limit</code> - <?php _e('Número de propiedades por página (default: 12)', 'inmovilla-properties'); ?></li>
                    <li><code>columns</code> - <?php _e('Número de columnas (default: 3)', 'inmovilla-properties'); ?></li>
                    <li><code>type</code> - <?php _e('Tipo de propiedad (piso, casa, local, etc.)', 'inmovilla-properties'); ?></li>
                    <li><code>city</code> - <?php _e('Ciudad específica', 'inmovilla-properties'); ?></li>
                </ul>
                <p><strong><?php _e('Ejemplo:', 'inmovilla-properties'); ?></strong> <code>[inmovilla_properties limit="9" columns="3" type="piso"]</code></p>
            </div>
            
            <div class="shortcode-help">
                <h4><code>[inmovilla_search]</code></h4>
                <p><?php _e('Formulario de búsqueda avanzada de propiedades.', 'inmovilla-properties'); ?></p>
                <p><strong><?php _e('Parámetros:', 'inmovilla-properties'); ?></strong></p>
                <ul>
                    <li><code>style</code> - <?php _e('Estilo: "horizontal", "vertical" (default: horizontal)', 'inmovilla-properties'); ?></li>
                    <li><code>fields</code> - <?php _e('Campos a mostrar separados por coma', 'inmovilla-properties'); ?></li>
                </ul>
                <p><strong><?php _e('Ejemplo:', 'inmovilla-properties'); ?></strong> <code>[inmovilla_search style="vertical"]</code></p>
            </div>
            
            <div class="shortcode-help">
                <h4><code>[inmovilla_featured]</code></h4>
                <p><?php _e('Muestra propiedades destacadas.', 'inmovilla-properties'); ?></p>
                <p><strong><?php _e('Parámetros:', 'inmovilla-properties'); ?></strong></p>
                <ul>
                    <li><code>limit</code> - <?php _e('Número de propiedades (default: 6)', 'inmovilla-properties'); ?></li>
                    <li><code>columns</code> - <?php _e('Número de columnas (default: 3)', 'inmovilla-properties'); ?></li>
                </ul>
            </div>
            
            <div class="shortcode-help">
                <h4><code>[inmovilla_favorites]</code></h4>
                <p><?php _e('Sistema de favoritos para que los usuarios guarden propiedades.', 'inmovilla-properties'); ?></p>
            </div>
        </div>
        
        <!-- Solución de Problemas -->
        <div class="help-section">
            <h3><i class="fas fa-wrench"></i> <?php _e('Solución de Problemas', 'inmovilla-properties'); ?></h3>
            
            <div class="troubleshooting">
                <h4><?php _e('Las propiedades no se muestran:', 'inmovilla-properties'); ?></h4>
                <ul>
                    <li><?php _e('Verifica que el token API esté correctamente configurado', 'inmovilla-properties'); ?></li>
                    <li><?php _e('Prueba la conexión desde el Dashboard', 'inmovilla-properties'); ?></li>
                    <li><?php _e('Limpia el caché del plugin', 'inmovilla-properties'); ?></li>
                    <li><?php _e('Revisa que tu servidor tenga acceso a internet', 'inmovilla-properties'); ?></li>
                </ul>
                
                <h4><?php _e('URLs no funcionan (Error 404):', 'inmovilla-properties'); ?></h4>
                <ul>
                    <li><?php _e('Ve a Ajustes → Enlaces permanentes', 'inmovilla-properties'); ?></li>
                    <li><?php _e('Haz click en "Guardar cambios" sin modificar nada', 'inmovilla-properties'); ?></li>
                    <li><?php _e('Esto regenerará las reglas de reescritura', 'inmovilla-properties'); ?></li>
                </ul>
                
                <h4><?php _e('El diseño no se ve bien:', 'inmovilla-properties'); ?></h4>
                <ul>
                    <li><?php _e('Verifica que tu tema sea compatible con CSS moderno', 'inmovilla-properties'); ?></li>
                    <li><?php _e('Ajusta los colores en la configuración del plugin', 'inmovilla-properties'); ?></li>
                    <li><?php _e('Limpia el caché del navegador y plugins de caché', 'inmovilla-properties'); ?></li>
                </ul>
            </div>
        </div>
        
        <!-- Información de Soporte -->
        <div class="help-section">
            <h3><i class="fas fa-life-ring"></i> <?php _e('Soporte Técnico', 'inmovilla-properties'); ?></h3>
            <div class="support-info">
                <p><?php _e('Si necesitas ayuda adicional, proporciona la siguiente información:', 'inmovilla-properties'); ?></p>
                <ul>
                    <li><strong><?php _e('Versión del plugin:', 'inmovilla-properties'); ?></strong> <?php echo INMOVILLA_VERSION; ?></li>
                    <li><strong><?php _e('Versión de WordPress:', 'inmovilla-properties'); ?></strong> <?php echo get_bloginfo('version'); ?></li>
                    <li><strong><?php _e('Versión de PHP:', 'inmovilla-properties'); ?></strong> <?php echo PHP_VERSION; ?></li>
                    <li><strong><?php _e('Tema activo:', 'inmovilla-properties'); ?></strong> <?php echo wp_get_theme()->get('Name'); ?></li>
                </ul>
                
                <div class="debug-info">
                    <h4><?php _e('Información de Debug:', 'inmovilla-properties'); ?></h4>
                    <textarea readonly rows="10" cols="80"><?php
                        $debug_info = array(
                            'Plugin Version' => INMOVILLA_VERSION,
                            'WordPress Version' => get_bloginfo('version'),
                            'PHP Version' => PHP_VERSION,
                            'Active Theme' => wp_get_theme()->get('Name'),
                            'API Token Configured' => !empty(get_option('inmovilla_properties_options')['api_token']) ? 'Yes' : 'No',
                            'Cache Enabled' => get_option('inmovilla_cache_enabled', true) ? 'Yes' : 'No',
                            'Permalinks Structure' => get_option('permalink_structure'),
                            'Site URL' => site_url(),
                            'Home URL' => home_url()
                        );
                        
                        foreach ($debug_info as $key => $value) {
                            echo $key . ': ' . $value . "\n";
                        }
                    ?></textarea>
                    <p><em><?php _e('Copia esta información cuando contactes soporte', 'inmovilla-properties'); ?></em></p>
                </div>
            </div>
        </div>
    </div>
</div>