<?php
/**
 * Dashboard del plugin Inmovilla Properties
 */

if (!defined('ABSPATH')) {
    exit;
}

// CORREGIDO: Llamadas a las clases con los nombres correctos
$api = new InmovillaAPI();
$stats = $api->get_stats();
$sitemap = new InmovillaSitemap();
$sitemap_stats = $sitemap->get_sitemap_stats();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Dashboard Inmovilla Properties', 'inmovilla-properties'); ?>
    </h1>
    
    <div class="inmovilla-dashboard-stats">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-home"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['total_properties'] ?? 0); ?></h3>
                    <p><?php _e('Propiedades Total', 'inmovilla-properties'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['active_properties'] ?? 0); ?></h3>
                    <p><?php _e('Propiedades Activas', 'inmovilla-properties'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($stats['last_sync'] ?? __('Nunca', 'inmovilla-properties')); ?></h3>
                    <p><?php _e('Última Sincronización', 'inmovilla-properties'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-sitemap"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($sitemap_stats['total_properties'] ?? 0); ?></h3>
                    <p><?php _e('URLs en Sitemap', 'inmovilla-properties'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="inmovilla-dashboard-content">
        <div class="dashboard-row">
            <div class="dashboard-col-6">
                <div class="postbox">
                    <h2 class="hndle">
                        <span><?php _e('Estado de la API', 'inmovilla-properties'); ?></span>
                    </h2>
                    <div class="inside">
                        <div id="api-status-container">
                            <button type="button" class="button button-primary" id="test-api-connection">
                                <?php _e('Probar Conexión', 'inmovilla-properties'); ?>
                            </button>
                            <div id="api-test-result"></div>
                        </div>
                        
                        <div class="api-info">
                            <h4><?php _e('Información de Configuración', 'inmovilla-properties'); ?></h4>
                            <?php $options = get_option('inmovilla_properties_options', array()); ?>
                            <ul>
                                <li><strong><?php _e('Número de agencia:', 'inmovilla-properties'); ?></strong>
                                    <?php echo !empty($options['agency_id']) ? '✅ ' . esc_html($options['agency_id']) : '❌ ' . __('No configurado', 'inmovilla-properties'); ?>
                                </li>
                                <li><strong><?php _e('Contraseña API:', 'inmovilla-properties'); ?></strong>
                                    <?php echo !empty($options['api_password']) ? '✅ ' . __('Definida', 'inmovilla-properties') : '❌ ' . __('No configurada', 'inmovilla-properties'); ?>
                                </li>
                                <li><strong><?php _e('URL Base:', 'inmovilla-properties'); ?></strong>
                                    <?php echo esc_html($options['api_base_url'] ?? 'No configurada'); ?>
                                </li>
                                <li><strong><?php _e('Caché activo:', 'inmovilla-properties'); ?></strong>
                                    <?php echo get_option('inmovilla_cache_enabled', true) ? '✅ Sí' : '❌ No'; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-col-6">
                <div class="postbox">
                    <h2 class="hndle">
                        <span><?php _e('Acciones Rápidas', 'inmovilla-properties'); ?></span>
                    </h2>
                    <div class="inside">
                        <div class="quick-actions">
                            <button type="button" class="button button-secondary" id="sync-properties">
                                <i class="fas fa-sync-alt"></i>
                                <?php _e('Sincronizar Propiedades', 'inmovilla-properties'); ?>
                            </button>
                            
                            <button type="button" class="button button-secondary" id="clear-cache">
                                <i class="fas fa-trash"></i>
                                <?php _e('Limpiar Caché', 'inmovilla-properties'); ?>
                            </button>
                            
                            <button type="button" class="button button-secondary" id="regenerate-sitemap">
                                <i class="fas fa-sitemap"></i>
                                <?php _e('Regenerar Sitemap', 'inmovilla-properties'); ?>
                            </button>
                            
                            <a href="<?php echo esc_url($sitemap_stats['sitemap_url'] ?? '#'); ?>" 
                               class="button button-secondary" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                                <?php _e('Ver Sitemap', 'inmovilla-properties'); ?>
                            </a>
                        </div>
                        
                        <div id="quick-actions-result"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="postbox">
            <h2 class="hndle">
                <span><?php _e('Propiedades Recientes', 'inmovilla-properties'); ?></span>
            </h2>
            <div class="inside">
                <div id="recent-properties">
                    <p><?php _e('Cargando propiedades recientes...', 'inmovilla-properties'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="postbox">
            <h2 class="hndle">
                <span><?php _e('Shortcodes Disponibles', 'inmovilla-properties'); ?></span>
            </h2>
            <div class="inside">
                <div class="shortcodes-list">
                    <div class="shortcode-item">
                        <code>[inmovilla_properties]</code>
                        <p><?php _e('Lista de propiedades con paginación', 'inmovilla-properties'); ?></p>
                    </div>
                    
                    <div class="shortcode-item">
                        <code>[inmovilla_search]</code>
                        <p><?php _e('Formulario de búsqueda avanzada', 'inmovilla-properties'); ?></p>
                    </div>
                    
                    <div class="shortcode-item">
                        <code>[inmovilla_featured]</code>
                        <p><?php _e('Propiedades destacadas', 'inmovilla-properties'); ?></p>
                    </div>
                    
                    <div class="shortcode-item">
                        <code>[inmovilla_favorites]</code>
                        <p><?php _e('Sistema de favoritos del usuario', 'inmovilla-properties'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>