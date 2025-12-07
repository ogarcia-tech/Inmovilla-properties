<?php
/**
 * Gestión de propiedades mediante Sincronización XML
 */

if (!defined('ABSPATH')) {
    exit;
}

class Inmovilla_Properties_Manager {

    public function __construct() {
        add_action('inmovilla_sync_properties', array($this, 'sync_properties_from_xml'));

        if (!wp_next_scheduled('inmovilla_sync_properties')) {
            $settings = get_option('inmovilla_properties_options', array());
            $interval = $settings['schedule_interval'] ?? 'daily';
            wp_schedule_event(time(), $interval, 'inmovilla_sync_properties');
        }
    }

    /**
     * Método de compatibilidad: ejecuta la sincronización XML
     */
    public function sync_properties() {
        $this->sync_properties_from_xml();
    }

    /**
     * Función principal de sincronización XML
     */
    public function sync_properties_from_xml() {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $xml_url = inmovilla_get_setting('xml_feed_url');

        if (empty($xml_url)) {
            error_log('Inmovilla: No se ha configurado la URL del XML.');
            return;
        }

        $xml_response = wp_remote_get($xml_url, array('timeout' => 120));

        if (is_wp_error($xml_response)) {
            error_log('Inmovilla: Error descargando XML - ' . $xml_response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($xml_response);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);

        if ($xml === false) {
            error_log('Inmovilla: XML inválido o corrupto.');
            return;
        }

        $properties_nodes = array();

        if (isset($xml->propiedad)) {
            $properties_nodes = $xml->propiedad;
        } elseif (isset($xml->inmueble)) {
            $properties_nodes = $xml->inmueble;
        }

        $active_ids = array();

        foreach ($properties_nodes as $property_node) {
            $prop_data = $this->parse_xml_node($property_node);

            if ($prop_data) {
                $post_id = $this->create_or_update_property($prop_data);

                if ($post_id) {
                    $active_ids[] = $prop_data['id_inmovilla'];
                }
            }
        }

        if (!empty($active_ids)) {
            $this->cleanup_old_properties($active_ids);
        }

        update_option('inmovilla_last_sync', current_time('mysql'));
    }

    /**
     * Mapea los nodos del XML a un array estándar
     */
    private function parse_xml_node($node) {
        $reference = (string) ($node->referencia
            ?? $node->ref
            ?? '');

        $identifier = (string) ($node->codigo
            ?? $node->id
            ?? $reference);

        if (empty($identifier) && empty($reference)) {
            return null;
        }

        $venta = (float) ($node->precio_venta
            ?? $node->precioinmo
            ?? 0);
        $alquiler = (float) ($node->precio_alquiler
            ?? $node->precioalq
            ?? 0);
        $price = $venta > 0 ? $venta : $alquiler;

        $images = $this->extract_images($node);

        $title_parts = array();
        $property_type = (string) ($node->tipo
            ?? $node->tipo_ofer
            ?? '');

        if (!empty($property_type)) {
            $title_parts[] = $property_type;
        }
        $city = (string) ($node->poblacion
            ?? $node->ciudad
            ?? '');

        if (!empty($city)) {
            $title_parts[] = __('en', 'inmovilla-properties') . ' ' . $city;
        }

        $title_from_xml = (string) ($node->titulo1
            ?? $node->titulo
            ?? '');

        $title = !empty($title_from_xml)
            ? $title_from_xml
            : trim(implode(' ', $title_parts));
        if (empty($title)) {
            $title = sprintf(__('Propiedad %s', 'inmovilla-properties'), $reference ?: $identifier);
        }

        return array(
            'id_inmovilla' => $identifier,
            'reference'    => $reference ?: $identifier,
            'title'        => $title,
            'description'  => (string) ($node->descripcion
                ?? $node->descrip1
                ?? ''),
            'price'        => $price,
            'type'         => $property_type,
            'city'         => $city,
            'zone'         => (string) ($node->zona ?? ''),
            'bedrooms'     => (int) max(
                (int) ($node->habitaciones ?? 0),
                (int) ($node->habdobles ?? 0)
            ),
            'bathrooms'    => (int) ($node->banos
                ?? $node->banyos
                ?? $node->aseos
                ?? 0),
            'size'         => (float) ($node->superficie_construida
                ?? $node->m_cons
                ?? 0),
            'images'       => $images,
            'raw'          => json_decode(json_encode($node), true),
        );
    }

    /**
     * Extrae URLs de imágenes desde el nodo XML
     */
    private function extract_images($node) {
        $images = array();

        if (!empty($node->fotos) && isset($node->fotos->foto)) {
            foreach ($node->fotos->foto as $foto) {
                $url = trim((string) $foto);
                if (!empty($url)) {
                    $images[] = $url;
                }
            }
        }

        if (empty($images)) {
            foreach ($node as $key => $value) {
                if (preg_match('/^foto\d+$/i', $key)) {
                    $url = trim((string) $value);
                    if (!empty($url)) {
                        $images[] = $url;
                    }
                }
            }
        }

        return $images;
    }

    /**
     * Crea o actualiza el Post en WordPress
     */
    private function create_or_update_property($data) {
        $existing = get_posts(array(
            'post_type'      => 'inmovilla_property',
            'meta_key'       => '_inmovilla_id',
            'meta_value'     => $data['id_inmovilla'],
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ));

        $post_args = array(
            'post_title'   => sanitize_text_field($data['title']),
            'post_content' => wp_kses_post($data['description']),
            'post_status'  => 'publish',
            'post_type'    => 'inmovilla_property',
        );

        if (!empty($existing)) {
            $post_args['ID'] = $existing[0];
            $post_id = wp_update_post($post_args);
        } else {
            $post_id = wp_insert_post($post_args);
        }

        if (is_wp_error($post_id) || !$post_id) {
            return false;
        }

        update_post_meta($post_id, '_inmovilla_id', $data['id_inmovilla']);
        update_post_meta($post_id, 'reference', $data['reference']);
        update_post_meta($post_id, 'price', $data['price']);
        update_post_meta($post_id, 'property_type', $data['type']);
        update_post_meta($post_id, 'location_city', $data['city']);
        update_post_meta($post_id, 'location_zone', $data['zone']);
        update_post_meta($post_id, 'bedrooms', $data['bedrooms']);
        update_post_meta($post_id, 'bathrooms', $data['bathrooms']);
        update_post_meta($post_id, 'size', $data['size']);
        update_post_meta($post_id, 'raw_data', $data['raw']);

        if (!empty($data['type'])) {
            wp_set_object_terms($post_id, $data['type'], 'property_type');
        }

        if (!empty($data['city'])) {
            wp_set_object_terms($post_id, $data['city'], 'property_location');
        }

        if (!empty($data['images'])) {
            update_post_meta($post_id, 'gallery_images', $data['images']);

            if (!has_post_thumbnail($post_id)) {
                $this->upload_image_url($post_id, $data['images'][0]);
            }
        }

        return $post_id;
    }

    /**
     * Descargar y asignar imagen destacada
     */
    private function upload_image_url($post_id, $url) {
        if (empty($url)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $tmp = download_url($url);

        if (is_wp_error($tmp)) {
            return;
        }

        $file_array = array(
            'name'     => basename($url),
            'tmp_name' => $tmp,
        );

        $id = media_handle_sideload($file_array, $post_id, get_the_title($post_id));

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return;
        }

        set_post_thumbnail($post_id, $id);
    }

    /**
     * Eliminar propiedades que ya no están en el XML
     */
    private function cleanup_old_properties($active_ids) {
        $all_posts = get_posts(array(
            'post_type'      => 'inmovilla_property',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));

        foreach ($all_posts as $post_id) {
            $inmo_id = get_post_meta($post_id, '_inmovilla_id', true);
            if (!in_array($inmo_id, $active_ids, true)) {
                wp_delete_post($post_id, true);
            }
        }
    }

    public function format_price($price) {
        if (empty($price) || !is_numeric($price)) {
            return __('Consultar precio', 'inmovilla-properties');
        }

        return number_format((float) $price, 0, ',', '.') . ' €';
    }

    public function get_property_url($prop) {
        return get_permalink($prop['ID'] ?? $prop);
    }

    public function get_property_featured_image($prop) {
        return get_the_post_thumbnail_url($prop['ID'] ?? $prop, 'large');
    }
}

if (!function_exists('set_post_thumbnail_from_url')) {
    function set_post_thumbnail_from_url($post_id, $url) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        if (get_page_by_title(basename($url), 'OBJECT', 'attachment')) {
            return;
        }

        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            return;
        }

        $file_array = array(
            'name'     => basename($url),
            'tmp_name' => $tmp,
        );

        $id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return;
        }

        set_post_thumbnail($post_id, $id);
    }
}
