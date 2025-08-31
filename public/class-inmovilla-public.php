<?php
/**
 * Funciones públicas del plugin Inmovilla Properties
 */

if (!defined('ABSPATH')) {
    exit;
}

class Inmovilla_Public {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_custom_styles'));
        add_filter('body_class', array($this, 'add_body_class'));
        add_action('wp_footer', array($this, 'add_schema_markup'));
    }
    
    /**
     * Cargar scripts y estilos del frontend
     */
    public function enqueue_scripts() {
        // CSS Principal
        wp_enqueue_style('inmovilla-public', 
            INMOVILLA_PROPERTIES_PLUGIN_URL . 'assets/css/inmovilla-public.css', 
            array(), 
            INMOVILLA_PROPERTIES_VERSION
        );
        
        // CSS Responsive
        wp_enqueue_style('inmovilla-responsive', 
            INMOVILLA_PROPERTIES_PLUGIN_URL . 'assets/css/inmovilla-responsive.css', 
            array('inmovilla-public'), 
            INMOVILLA_PROPERTIES_VERSION
        );
        
        // JavaScript principal
        wp_enqueue_script('inmovilla-public', 
            INMOVILLA_PROPERTIES_PLUGIN_URL . 'assets/js/inmovilla-public.js', 
            array('jquery'), 
            INMOVILLA_PROPERTIES_VERSION, 
            true
        );
        
        // JavaScript para búsquedas
        wp_enqueue_script('inmovilla-search', 
            INMOVILLA_PROPERTIES_PLUGIN_URL . 'assets/js/inmovilla-search.js', 
            array('jquery', 'inmovilla-public'), 
            INMOVILLA_PROPERTIES_VERSION, 
            true
        );
        
        // Localización para JavaScript
        wp_localize_script('inmovilla-public', 'inmovilla_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('inmovilla_public_nonce'),
            'loading_text' => __('Cargando...', 'inmovilla-properties'),
            'error_text' => __('Error al cargar los datos', 'inmovilla-properties'),
            'no_results_text' => __('No se encontraron propiedades', 'inmovilla-properties'),
            'view_property_text' => __('Ver propiedad', 'inmovilla-properties')
        ));
    }
    
    /**
     * Añadir estilos personalizados desde la configuración
     */
    public function add_custom_styles() {
        $options = get_option('inmovilla_properties_options', array());
        $primary_color = $options['primary_color'] ?? '#2563eb';
        $secondary_color = $options['secondary_color'] ?? '#64748b';
        
        echo "<style type='text/css'>
            :root {
                --inmovilla-primary: {$primary_color};
                --inmovilla-secondary: {$secondary_color};
                --inmovilla-primary-hover: " . $this->darken_color($primary_color, 20) . ";
                --inmovilla-primary-light: " . $this->lighten_color($primary_color, 90) . ";
            }
            
            .inmovilla-properties .property-card .price {
                color: var(--inmovilla-primary);
            }
            
            .inmovilla-properties .btn-primary {
                background-color: var(--inmovilla-primary);
                border-color: var(--inmovilla-primary);
            }
            
            .inmovilla-properties .btn-primary:hover {
                background-color: var(--inmovilla-primary-hover);
                border-color: var(--inmovilla-primary-hover);
            }
            
            .inmovilla-search-form .form-control:focus {
                border-color: var(--inmovilla-primary);
                box-shadow: 0 0 0 0.2rem " . $this->hex_to_rgba($primary_color, 0.25) . ";
            }
        </style>";
    }
    
    /**
     * Añadir clases CSS al body
     */
    public function add_body_class($classes) {
        if ($this->is_inmovilla_page()) {
            $classes[] = 'inmovilla-page';
        }
        
        if (is_single() && get_post_type() === 'inmovilla_property') {
            $classes[] = 'single-inmovilla-property';
        }
        
        return $classes;
    }
    
    /**
     * Verificar si estamos en una página de Inmovilla
     */
    private function is_inmovilla_page() {
        global $wp_query;
        
        if (isset($wp_query->query_vars['inmovilla_properties'])) {
            return true;
        }
        
        if (isset($wp_query->query_vars['inmovilla_property'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Añadir schema markup para SEO
     */
    public function add_schema_markup() {
        if (!$this->is_inmovilla_page()) {
            return;
        }
        
        global $wp_query;
        
        if (isset($wp_query->query_vars['inmovilla_property'])) {
            $this->add_property_schema();
        }
        
        if (isset($wp_query->query_vars['inmovilla_properties'])) {
            $this->add_properties_list_schema();
        }
    }
    
    /**
     * Schema markup para propiedad individual
     */
    private function add_property_schema() {
        global $inmovilla_current_property;
        
        if (!$inmovilla_current_property) {
            return;
        }
        
        $property = $inmovilla_current_property;
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'RealEstateListing',
            'name' => $property['title'] ?? '',
            'description' => wp_strip_all_tags($property['description'] ?? ''),
            'url' => $this->get_current_url(),
            'price' => array(
                '@type' => 'PriceSpecification',
                'price' => $property['price'] ?? 0,
                'priceCurrency' => 'EUR'
            )
        );
        
        if (!empty($property['address'])) {
            $schema['address'] = array(
                '@type' => 'PostalAddress',
                'streetAddress' => $property['address'],
                'addressLocality' => $property['city'] ?? '',
                'addressCountry' => 'ES'
            );
        }
        
        if (!empty($property['bedrooms'])) {
            $schema['numberOfRooms'] = intval($property['bedrooms']);
        }
        
        if (!empty($property['bathrooms'])) {
            $schema['numberOfBathroomsTotal'] = intval($property['bathrooms']);
        }
        
        if (!empty($property['size'])) {
            $schema['floorSize'] = array(
                '@type' => 'QuantitativeValue',
                'value' => intval($property['size']),
                'unitText' => 'MTK'
            );
        }
        
        if (!empty($property['images'])) {
            $schema['image'] = array_map(function($img) {
                return $img['url'] ?? $img;
            }, array_slice($property['images'], 0, 5));
        }
        
        echo "<script type='application/ld+json'>" . json_encode($schema, JSON_UNESCAPED_SLASHES) . "</script>";
    }
    
    /**
     * Schema markup para listado de propiedades
     */
    private function add_properties_list_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => get_the_title() ?: __('Propiedades', 'inmovilla-properties'),
            'description' => __('Listado de propiedades inmobiliarias', 'inmovilla-properties'),
            'url' => $this->get_current_url()
        );
        
        echo "<script type='application/ld+json'>" . json_encode($schema, JSON_UNESCAPED_SLASHES) . "</script>";
    }
    
    /**
     * Formatear precio para mostrar
     */
    public function format_price($price) {
        if (empty($price) || !is_numeric($price)) {
            return __('Consultar precio', 'inmovilla-properties');
        }
        
        return number_format($price, 0, ',', '.') . ' €';
    }
    
    /**
     * Formatear superficie
     */
    public function format_size($size) {
        if (empty($size) || !is_numeric($size)) {
            return '';
        }
        
        return number_format($size, 0, ',', '.') . ' m²';
    }
    
    /**
     * Obtener URL actual
     */
    private function get_current_url() {
        return (is_ssl() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Oscurecer color hexadecimal
     */
    private function darken_color($hex, $percent) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Aclarar color hexadecimal
     */
    private function lighten_color($hex, $percent) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        $r = max(0, min(255, $r + ((255 - $r) * $percent / 100)));
        $g = max(0, min(255, $g + ((255 - $g) * $percent / 100)));
        $b = max(0, min(255, $b + ((255 - $b) * $percent / 100)));
        
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Convertir hex a rgba
     */
    private function hex_to_rgba($hex, $alpha = 1) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "rgba($r, $g, $b, $alpha)";
    }
}