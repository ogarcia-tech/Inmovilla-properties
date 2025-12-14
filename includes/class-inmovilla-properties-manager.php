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
     * Extrae códigos de video desde el nodo XML
     */
    private function extract_videos($node) {
        $videos = array();
        // Asumiendo una estructura de nodo <videos><codigo>YOUTUBE_ID</codigo></videos>
        if (!empty($node->videos) && isset($node->videos->codigo)) {
            foreach ($node->videos->codigo as $codigo) {
                $url = trim((string) $codigo);
                if (!empty($url)) {
                    $videos[] = $url;
                }
            }
        }
        return $videos;
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
     * Mapea los nodos del XML a un array estándar (EXPANDIDO)
     */
    private function parse_xml_node($node) {
        $reference = (string) ($node->referencia
            ?? $node->ref
            ?? '');

        $identifier = (string) ($node->codigo
            ?? $node->id
            ?? $node->cod_ofer
            ?? $reference);

        if (empty($identifier) && empty($reference)) {
            return null;
        }

        // Mapeo de precios
        $venta = (float) ($node->precio_venta
            ?? $node->precioinmo
            ?? 0);
        $alquiler = (float) ($node->precio_alquiler
            ?? $node->precioalq
            ?? 0);
        
        $images = $this->extract_images($node);
        $videos = $this->extract_videos($node);

        $title_parts = array();
        $property_type = (string) ($node->nbtipo // Usar nbtipo o tipo
            ?? $node->tipo
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
            
            // Precios y Operación
            'price_sale'   => $venta,
            'price_rent'   => $alquiler,
            'price_outlet' => (float) ($node->outlet ?? 0),
            'monthly_type' => (string) ($node->tipomensual ?? ''),
            'operation_key' => (int) ($node->keyacci ?? 0),

            // Ubicación (para taxonomías)
            'type'         => $property_type,
            'city'         => $city,
            'zone'         => (string) ($node->zona ?? ''),
            
            // Metros
            'm_cons'       => (float) ($node->superficie_construida ?? $node->m_cons ?? 0),
            'm_uties'      => (float) ($node->m_uties ?? 0),
            'm_parcela'    => (float) ($node->m_parcela ?? 0),
            'm_terraza'    => (float) ($node->m_terraza ?? 0),

            // Habitaciones y Baños
            'bedrooms'     => (int) max(
                (int) ($node->habitaciones ?? 0),
                (int) ($node->habdobles ?? 0)
            ),
            'bathrooms'    => (int) ($node->banos
                ?? $node->banyos
                ?? $node->aseos
                ?? 0),
            'total_rooms'  => (int) ($node->total_hab ?? 0),

            // Features (1 o 0)
            'feat_lift'    => (int) ($node->ascensor ?? 0),
            'feat_ac'      => (int) ($node->aire_con ?? 0),
            'feat_heating' => (int) ($node->calefacción ?? 0),
            'feat_pool_com' => (int) ($node->piscina_com ?? 0),
            'feat_pool_prop' => (int) ($node->piscina_prop ?? 0),
            'feat_exterior' => (int) ($node->todoext ?? 0),
            
            // Certificación Energética
            'energy_letter'=> (string) ($node->energialetra ?? ''),
            'energy_value' => (float) ($node->energiavalor ?? 0),
            'emission_letter'=> (string) ($node->emisionesletra ?? ''),
            'emission_value' => (float) ($node->emisionesvalor ?? 0),

            // Entorno y Distancia
            'distmar'      => (int) ($node->distmar ?? 0),
            'x_entorno'    => (int) ($node->x_entorno ?? 0),

            // Multimedia
            'images'       => $images,
            'video_codes'  => $videos,
            
            // Raw data
            'raw'          => json_decode(json_encode($node), true),
        );
    }

    /**
     * Crea o actualiza el Post en WordPress y mapea a los campos ACF (ACTUALIZADO)
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

        // --- Mapeo de Campos ACF (usando update_post_meta con tus claves ACF) ---

        // Clave interna del plugin (No tocar)
        update_post_meta($post_id, '_inmovilla_id', $data['id_inmovilla']); 

        // Identificación y Precios
        update_post_meta($post_id, 'inmovilla_ref', $data['reference']);
        update_post_meta($post_id, 'inmovilla_cod_ofer', $data['id_inmovilla']);
        update_post_meta($post_id, 'inmovilla_precioinmo', $data['price_sale']);
        update_post_meta($post_id, 'inmovilla_precioalq', $data['price_rent']);
        update_post_meta($post_id, 'inmovilla_outlet', $data['price_outlet']);
        update_post_meta($post_id, 'inmovilla_tipomensual', $data['monthly_type']);

        // Metros y Dimensiones
        update_post_meta($post_id, 'inmovilla_m_cons', $data['m_cons']);
        update_post_meta($post_id, 'inmovilla_m_uties', $data['m_uties']);
        update_post_meta($post_id, 'inmovilla_m_parcela', $data['m_parcela']);
        update_post_meta($post_id, 'inmovilla_m_terraza', $data['m_terraza']);

        // Habitaciones y Baños
        update_post_meta($post_id, 'inmovilla_total_hab', $data['total_rooms']);
        update_post_meta($post_id, 'inmovilla_banyos', $data['bathrooms']);
        
        // Características (1/0)
        update_post_meta($post_id, 'inmovilla_ascensor', $data['feat_lift']);
        update_post_meta($post_id, 'inmovilla_aire_con', $data['feat_ac']);
        update_post_meta($post_id, 'inmovilla_calefaccion', $data['feat_heating']);
        update_post_meta($post_id, 'inmovilla_piscina_com', $data['feat_pool_com']);
        update_post_meta($post_id, 'inmovilla_piscina_prop', $data['feat_pool_prop']);
        update_post_meta($post_id, 'inmovilla_todoext', $data['feat_exterior']);

        // Certificación Energética
        update_post_meta($post_id, 'inmovilla_energialetra', $data['energy_letter']);
        update_post_meta($post_id, 'inmovilla_energiavalor', $data['energy_value']);
        update_post_meta($post_id, 'inmovilla_emisionesletra', $data['emission_letter']);
        update_post_meta($post_id, 'inmovilla_emisionesvalor', $data['emission_value']);
        
        // Entorno y Distancia
        update_post_meta($post_id, 'inmovilla_distmar', $data['distmar']);
        update_post_meta($post_id, 'inmovilla_x_entorno', $data['x_entorno']);

        // Multimedia (solo los códigos/URLs)
        update_post_meta($post_id, 'inmovilla_gallery_urls', $data['images']);
        update_post_meta($post_id, 'inmovilla_video_codes', $data['video_codes']);
        // 1. URLs de Galería
if (!empty($data['images'])) {
    $gallery_rows = [];
    foreach ($data['images'] as $url) {
        // Formatea el array al estilo ACF: [ [ 'url' => '...url...' ], [ 'url' => '...url...' ] ]
        $gallery_rows[] = ['url' => $url];
    }
    // Usamos update_field para guardar el Repetidor correctamente
    update_field('inmovilla_gallery_urls', $gallery_rows, $post_id);
} else {
    // Asegura que el campo se vacíe si no hay imágenes
    update_field('inmovilla_gallery_urls', false, $post_id);
}


// 2. Códigos de Video
if (!empty($data['video_codes'])) {
    $video_rows = [];
    foreach ($data['video_codes'] as $code) {
        // Formatea el array al estilo ACF: [ [ 'code' => '...code...' ], [ 'code' => '...code...' ] ]
        $video_rows[] = ['code' => $code];
    }
    // Usamos update_field para guardar el Repetidor correctamente
    update_field('inmovilla_video_codes', $video_rows, $post_id);
} else {
    // Asegura que el campo se vacíe si no hay videos
    update_field('inmovilla_video_codes', false, $post_id);
}

        // Guardar datos crudos
        update_post_meta($post_id, 'raw_data', $data['raw']);


        // --- Mapeo de Taxonomías ---

        // 1. Tipo de Operación (keyacci a taxonomía 'operacion')
        if ($data['operation_key']) {
            // Usar el código (ej: 1, 2)
            wp_set_object_terms($post_id, $data['operation_key'], 'operacion', false);
        }
        
        // 2. Tipo de Propiedad (texto a taxonomía 'tipo_propiedad')
        if (!empty($data['type'])) {
            wp_set_object_terms($post_id, $data['type'], 'tipo_propiedad', false);
        }

        // 3. Ubicación (Ciudad y Zona)
        if (!empty($data['city'])) {
            wp_set_object_terms($post_id, $data['city'], 'ciudad', false);
        }
        
        if (!empty($data['zone'])) {
            wp_set_object_terms($post_id, $data['zone'], 'zona', false);
        }


        // --- Manejo de Imágenes (manteniendo la lógica existente para la destacada) ---
        if (!empty($data['images'])) {
            // La línea original que mantiene el array de URLs
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
