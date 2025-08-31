<?php
/**
 * Gestión de propiedades desde Inmovilla
 * MODIFICADO: Ahora guarda las propiedades como posts con sus campos personalizados (post meta).
 */

if (!defined('ABSPATH')) {
    exit;
}

class Inmovilla_Properties_Manager {

    private $api;

    public function __construct() {
        $this->api = new InmovillaAPI();
        
        add_action('inmovilla_sync_properties', array($this, 'sync_properties'));

        if (!wp_next_scheduled('inmovilla_sync_properties')) {
            $settings = get_option('inmovilla_properties_settings', array());
            $interval = $settings['schedule_interval'] ?? 'hourly';
            wp_schedule_event(time(), $interval, 'inmovilla_sync_properties');
        }
        
        // Ya no controlamos los templates desde aquí, lo hará Elementor o el tema.
    }

    /**
     * Sincroniza las propiedades de la API con los posts de WordPress.
     */
    public function sync_properties() {
        $page = 1;
        $properties_per_page = 20; // Procesamos en lotes de 20 para no agotar la memoria
        $has_more_pages = true;
        $synced_ids = []; // Guardaremos los IDs de las propiedades sincronizadas

        while ($has_more_pages) {
            $response = $this->api->get_properties(array(
                'per_page' => $properties_per_page,
                'page' => $page
            ));

            if (is_wp_error($response) || empty($response['data'])) {
                $has_more_pages = false;
                break;
            }

            foreach ($response['data'] as $property_data) {
                $this->create_or_update_property($property_data);
                $synced_ids[] = $property_data['id']; // Guardamos el ID de Inmovilla
            }
            
            if (count($response['data']) < $properties_per_page) {
                $has_more_pages = false;
            } else {
                $page++;
            }
        }
        
        // Opcional: Ocultar propiedades que ya no están en la API
        $this->hide_old_properties($synced_ids);

        update_option('inmovilla_last_sync', current_time('mysql'));
    }

    /**
     * Crea o actualiza un post de tipo 'inmovilla_property'
     */
    private function create_or_update_property($data) {
        $inmovilla_id = $data['id'];

        // Buscamos si ya existe un post con este ID de Inmovilla
        $existing_post = get_posts(array(
            'post_type' => 'inmovilla_property',
            'meta_key' => '_inmovilla_id',
            'meta_value' => $inmovilla_id,
            'posts_per_page' => 1
        ));

        $post_data = array(
            'post_title' => wp_strip_all_tags($data['title'] ?? 'Propiedad sin título'),
            'post_content' => $data['description'] ?? '',
            'post_status' => 'publish',
            'post_type' => 'inmovilla_property',
        );

        if ($existing_post) {
            // Si existe, lo actualizamos
            $post_data['ID'] = $existing_post[0]->ID;
            wp_update_post($post_data);
            $post_id = $existing_post[0]->ID;
        } else {
            // Si no existe, lo creamos
            $post_id = wp_insert_post($post_data);
        }

        // Guardamos todos los datos de la propiedad como campos personalizados
        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_inmovilla_id', $inmovilla_id); // ID único de Inmovilla
            update_post_meta($post_id, 'price', $data['price'] ?? '');
            update_post_meta($post_id, 'reference', $data['reference'] ?? '');
            update_post_meta($post_id, 'bedrooms', $data['bedrooms'] ?? '');
            update_post_meta($post_id, 'bathrooms', $data['bathrooms'] ?? '');
            update_post_meta($post_id, 'size', $data['size'] ?? '');
            update_post_meta($post_id, 'featured', $data['featured'] ?? false);
            update_post_meta($post_id, 'property_type', $data['type'] ?? '');

            // Guardamos datos de ubicación
            if (!empty($data['location'])) {
                update_post_meta($post_id, 'location_city', $data['location']['city'] ?? '');
                update_post_meta($post_id, 'location_district', $data['location']['district'] ?? '');
            }
            
            // Guardamos la galería de imágenes (como un array)
            if (!empty($data['images'])) {
                update_post_meta($post_id, 'gallery_images', $data['images']);
                // Establecemos la primera imagen como imagen destacada del post
                set_post_thumbnail_from_url($post_id, $data['images'][0]['url']);
            }
        }
    }
    
    /**
     * Oculta (pone en borrador) las propiedades que ya no existen en la API de Inmovilla.
     */
    private function hide_old_properties($synced_ids) {
        $args = array(
            'post_type' => 'inmovilla_property',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_inmovilla_id',
                    'value' => $synced_ids,
                    'compare' => 'NOT IN'
                )
            )
        );
        $old_properties = get_posts($args);

        foreach($old_properties as $old_property) {
            $old_property->post_status = 'draft';
            wp_update_post($old_property);
        }
    }
}

/**
 * Función auxiliar para establecer la imagen destacada desde una URL.
 */
if (!function_exists('set_post_thumbnail_from_url')) {
    function set_post_thumbnail_from_url($post_id, $url) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Evitar duplicados
        if (get_page_by_title(basename($url), 'OBJECT', 'attachment')) {
            return;
        }

        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            return;
        }

        $file_array = array(
            'name' => basename($url),
            'tmp_name' => $tmp
        );

        $id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return;
        }

        set_post_thumbnail($post_id, $id);
    }
}