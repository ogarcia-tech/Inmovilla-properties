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

    public function sync_properties() {
        $this->sync_properties_from_xml();
    }

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

        $active_refs = array();

        foreach ($properties_nodes as $property_node) {
            $prop_data = $this->parse_xml_node($property_node);
            if ($prop_data) {
                $post_id = $this->create_or_update_property($prop_data);
                if ($post_id) {
                    $active_refs[] = sanitize_text_field($prop_data['reference']);
                }
            }
        }

        if (!empty($active_refs)) {
            $this->cleanup_old_properties($active_refs);
        }

        update_option('inmovilla_last_sync', current_time('mysql'));
    }

    private function extract_videos($node) {
        $videos = array();
        if (!empty($node->videos) && isset($node->videos->codigo)) {
            foreach ($node->videos->codigo as $codigo) {
                $url = trim((string) $codigo);
                if (!empty($url)) { $videos[] = $url; }
            }
        }
        return $videos;
    }

    private function extract_images($node) {
        $images = array();
        if (!empty($node->fotos) && isset($node->fotos->foto)) {
            foreach ($node->fotos->foto as $foto) {
                $url = trim((string) $foto);
                if (!empty($url)) { $images[] = $url; }
            }
        }
        if (empty($images)) {
            foreach ($node as $key => $value) {
                if (preg_match('/^foto\d+$/i', $key)) {
                    $url = trim((string) $value);
                    if (!empty($url)) { $images[] = $url; }
                }
            }
        }
        return $images;
    }

    private function parse_xml_node($node) {
        $reference = trim((string) ($node->referencia ?? $node->ref ?? ''));
        $identifier = (string) ($node->codigo ?? $node->id ?? $node->cod_ofer ?? $reference);

        if (empty($identifier) && empty($reference)) {
            return null;
        }

        $venta = (float) ($node->precio_venta ?? $node->precioinmo ?? 0);
        $alquiler = (float) ($node->precio_alquiler ?? $node->precioalq ?? 0);
        
        $images = $this->extract_images($node);
        $videos = $this->extract_videos($node);

        $property_type = (string) ($node->nbtipo ?? $node->tipo ?? $node->tipo_ofer ?? '');
        $city = (string) ($node->poblacion ?? $node->ciudad ?? '');

        return array(
            'id_inmovilla' => $identifier,
            'reference'    => $reference ?: $identifier,
            'title'        => (string) ($node->titulo1 ?? $node->titulo ?? $property_type . ' ' . $city),
            'description'  => (string) ($node->descripcion ?? $node->descrip1 ?? ''),
            
            // Precios y Operación
            'price_sale'   => $venta,
            'price_rent'   => $alquiler,
            'price_outlet' => (float) ($node->outlet ?? 0),
            'monthly_type' => (string) ($node->tipomensual ?? ''),
            'operation_key' => (int) ($node->keyacci ?? 0),
            'action'       => (string) ($node->accion ?? ''),

            // Ubicación y Taxonomías
            'type'         => $property_type,
            'city'         => $city,
            'zone'         => (string) ($node->zona ?? ''),
            'conservacion' => (string) ($node->conservacion ?? ''),
            
            // Metros, Dimensiones y Gastos
            'm_cons'       => (float) ($node->m_cons ?? 0),
            'm_uties'      => (float) ($node->m_uties ?? 0),
            'm_parcela'    => (float) ($node->m_parcela ?? 0),
            'm_terraza'    => (float) ($node->m_terraza ?? 0),
            'antiguedad'   => (int) ($node->antiguedad ?? 0),
            'gastos_com'   => (float) ($node->gastos_com ?? 0),
            'numplanta'    => (string) ($node->numplanta ?? ''),

            // Habitaciones y Baños
            'bedrooms'     => (int) ($node->habitaciones ?? 0),
            'bedrooms_double' => (int) ($node->habdobles ?? 0),
            'bathrooms'    => (int) ($node->banyos ?? 0),
            'aseos'        => (int) ($node->aseos ?? 0),
            'total_rooms'  => (int) ($node->total_hab ?? 0),

            // Features / Equipamiento (1 o 0)
            'feat_lift'    => (int) ($node->ascensor ?? 0),
            'feat_ac'      => (int) ($node->aire_con ?? 0),
            'feat_heating' => (int) ($node->calefaccion ?? 0),
            'feat_pool_com' => (int) ($node->piscina_com ?? 0),
            'feat_pool_prop' => (int) ($node->piscina_prop ?? 0),
            'feat_garage'  => (int) ($node->plaza_gara ?? 0),
            'feat_storage' => (int) ($node->trastero ?? 0),
            'feat_terrace' => (int) ($node->terraza ?? 0),
            'feat_balcony' => (int) ($node->balcon ?? 0),
            'feat_sea_views' => (int) ($node->vistasalmar ?? 0),
            'feat_blind_door' => (int) ($node->puerta_blin ?? 0),
            'feat_adapted' => (int) ($node->adaptadominus ?? 0),
            'feat_exterior' => (int) ($node->todoext ?? 0),
            'feat_alarm'   => (int) ($node->alarma ?? 0),
            'feat_fireplace' => (int) ($node->chimenea ?? 0),
            'feat_bbq'     => (int) ($node->barbacoa ?? 0),
            'feat_first_line' => (int) ($node->primera_line ?? 0),
            'feat_solarium' => (int) ($node->solarium ?? 0),
            'feat_basement' => (int) ($node->sotano ?? 0),
            
            // Geoposicionamiento
            'latitud'      => (string) ($node->latitud ?? ''),
            'altitud'      => (string) ($node->altitud ?? ''),
            
            // Certificación Energética
            'energy_letter'=> (string) ($node->energialetra ?? ''),
            'energy_value' => (float) ($node->energiavalor ?? 0),
            'emission_letter'=> (string) ($node->emisionesletra ?? ''),
            'emission_value' => (float) ($node->emisionesvalor ?? 0),

            // Otros
            'distmar'      => (int) ($node->distmar ?? 0),
            'destacado'    => (int) ($node->destacado ?? 0),
            'numpanos'     => (int) ($node->numpanos ?? 0),

            // Multimedia
            'images'       => $images,
            'video_codes'  => $videos,
            'raw'          => json_decode(json_encode($node), true),
        );
    }

    private function create_or_update_property($data) {
        $reference = sanitize_text_field($data['reference']);
        $existing = get_posts(array(
            'post_type'      => 'inmovilla_property',
            'meta_key'       => 'inmovilla_ref',
            'meta_value'     => $reference,
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'post_status'    => 'any',
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

        if (is_wp_error($post_id) || !$post_id) return false;

        // --- Mapeo de Campos ACF (update_post_meta) ---
        update_post_meta($post_id, '_inmovilla_id', $data['id_inmovilla']);
        update_post_meta($post_id, 'inmovilla_ref', $reference);
        update_post_meta($post_id, 'inmovilla_precioinmo', $data['price_sale']);
        update_post_meta($post_id, 'inmovilla_precioalq', $data['price_rent']);
        update_post_meta($post_id, 'inmovilla_outlet', $data['price_outlet']);
        update_post_meta($post_id, 'inmovilla_m_cons', $data['m_cons']);
        update_post_meta($post_id, 'inmovilla_m_uties', $data['m_uties']);
        update_post_meta($post_id, 'inmovilla_m_parcela', $data['m_parcela']);
        update_post_meta($post_id, 'inmovilla_m_terraza', $data['m_terraza']);
        update_post_meta($post_id, 'inmovilla_antiguedad', $data['antiguedad']);
        update_post_meta($post_id, 'inmovilla_gastos_com', $data['gastos_com']);
        update_post_meta($post_id, 'inmovilla_numplanta', $data['numplanta']);
        update_post_meta($post_id, 'inmovilla_total_hab', $data['total_rooms']);
        update_post_meta($post_id, 'inmovilla_habdobles', $data['bedrooms_double']);
        update_post_meta($post_id, 'inmovilla_banyos', $data['bathrooms']);
        update_post_meta($post_id, 'inmovilla_aseos', $data['aseos']);
        update_post_meta($post_id, 'inmovilla_latitud', $data['latitud']);
        update_post_meta($post_id, 'inmovilla_altitud', $data['altitud']);
        update_post_meta($post_id, 'inmovilla_distmar', $data['distmar']);
        update_post_meta($post_id, 'inmovilla_destacado', $data['destacado']);
        update_post_meta($post_id, 'inmovilla_numpanos', $data['numpanos']);

        // Features (Booleanos)
        update_post_meta($post_id, 'inmovilla_ascensor', $data['feat_lift']);
        update_post_meta($post_id, 'inmovilla_aire_con', $data['feat_ac']);
        update_post_meta($post_id, 'inmovilla_calefaccion', $data['feat_heating']);
        update_post_meta($post_id, 'inmovilla_piscina_com', $data['feat_pool_com']);
        update_post_meta($post_id, 'inmovilla_piscina_prop', $data['feat_pool_prop']);
        update_post_meta($post_id, 'inmovilla_plaza_gara', $data['feat_garage']);
        update_post_meta($post_id, 'inmovilla_trastero', $data['feat_storage']);
        update_post_meta($post_id, 'inmovilla_terraza', $data['feat_terrace']);
        update_post_meta($post_id, 'inmovilla_balcon', $data['feat_balcony']);
        update_post_meta($post_id, 'inmovilla_vistasalmar', $data['feat_sea_views']);
        update_post_meta($post_id, 'inmovilla_alarma', $data['feat_alarm']);
        update_post_meta($post_id, 'inmovilla_chimenea', $data['feat_fireplace']);
        update_post_meta($post_id, 'inmovilla_barbacoa', $data['feat_bbq']);
        update_post_meta($post_id, 'inmovilla_primera_line', $data['feat_first_line']);
        update_post_meta($post_id, 'inmovilla_solarium', $data['feat_solarium']);
        update_post_meta($post_id, 'inmovilla_sotano', $data['feat_basement']);

        // Certificación Energética
        update_post_meta($post_id, 'inmovilla_energialetra', $data['energy_letter']);
        update_post_meta($post_id, 'inmovilla_energiavalor', $data['energy_value']);

        // --- Mapeo de Repetidores ACF (Galería y Vídeo) ---
        if (!empty($data['images'])) {
            $gallery_rows = array();
            foreach ($data['images'] as $url) { $gallery_rows[] = array('url' => $url); }
            update_field('inmovilla_gallery_urls', $gallery_rows, $post_id);
        }
        if (!empty($data['video_codes'])) {
            $video_rows = array();
            foreach ($data['video_codes'] as $code) { $video_rows[] = array('code' => $code); }
            update_field('inmovilla_video_codes', $video_rows, $post_id);
        }

        // --- Taxonomías ---
        if (!empty($data['action'])) wp_set_object_terms($post_id, $data['action'], 'accion', false);
        if (!empty($data['type'])) wp_set_object_terms($post_id, $data['type'], 'tipo_propiedad', false);
        if (!empty($data['city'])) wp_set_object_terms($post_id, $data['city'], 'ciudad', false);
        if (!empty($data['zone'])) wp_set_object_terms($post_id, $data['zone'], 'zona', false);
        if (!empty($data['conservacion'])) wp_set_object_terms($post_id, $data['conservacion'], 'conservacion', false);

        // Imagen destacada
        if (!empty($data['images']) && !has_post_thumbnail($post_id)) {
            $this->upload_image_url($post_id, $data['images'][0]);
        }

        return $post_id;
    }

    private function upload_image_url($post_id, $url) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $tmp = download_url($url);
        if (is_wp_error($tmp)) return;
        $file_array = array('name' => basename($url), 'tmp_name' => $tmp);
        $id = media_handle_sideload($file_array, $post_id, get_the_title($post_id));
        if (!is_wp_error($id)) set_post_thumbnail($post_id, $id);
    }

    private function cleanup_old_properties($active_refs) {
        $all_posts = get_posts(array('post_type' => 'inmovilla_property', 'posts_per_page' => -1, 'fields' => 'ids'));
        foreach ($all_posts as $post_id) {
            $ref = get_post_meta($post_id, 'inmovilla_ref', true);
            if (!empty($ref) && !in_array($ref, $active_refs)) wp_delete_post($post_id, true);
        }
    }
}
