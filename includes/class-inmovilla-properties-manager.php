<?php
/**
 * Gestión de propiedades mediante Sincronización XML (Versión Final Integrada)
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
        if (empty($xml_url)) return;

        $xml_response = wp_remote_get($xml_url, array('timeout' => 300));
        if (is_wp_error($xml_response)) return;

        $xml = simplexml_load_string(wp_remote_retrieve_body($xml_response));
        if ($xml === false) return;

        $properties_nodes = isset($xml->propiedad) ? $xml->propiedad : (isset($xml->inmueble) ? $xml->inmueble : array());
        
        $stats = ['nuevas' => 0, 'actualizadas' => 0];
        $active_refs = array();

        foreach ($properties_nodes as $node) {
            $prop_data = $this->parse_xml_node($node);
            if ($prop_data) {
                $result = $this->create_or_update_property($prop_data);
                if ($result) {
                    $active_refs[] = sanitize_text_field($prop_data['reference']);
                    if ($result['status'] === 'new') $stats['nuevas']++;
                    if ($result['status'] === 'updated') $stats['actualizadas']++;
                }
            }
        }

        $this->add_to_history($stats['nuevas'], $stats['actualizadas'], count($active_refs));
        if (!empty($active_refs)) {
            $this->cleanup_old_properties($active_refs);
        }
        update_option('inmovilla_last_sync', current_time('mysql'));
    }

    public function update_single_property_via_api($post_id) {
        $ref = get_post_meta($post_id, 'inmovilla_ref', true);
        $agencia = inmovilla_get_setting('numagencia', '2');
        $pass = inmovilla_get_setting('api_password', '82ku9xz2aw3');
        
        $api_url = "https://apiweb.inmovilla.com/apiweb/apiweb.php?numagencia=$agencia&password=$pass&idioma=1&tipo=propiedad&where=ref='$ref'";
        
        $response = wp_remote_get($api_url);
        if (is_wp_error($response)) return false;

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data[0])) return false;

        $item = $data[0];
        update_post_meta($post_id, 'inmovilla_precioinmo', $item['precioinmo']);
        update_post_meta($post_id, 'inmovilla_outlet', $item['outlet'] ?? 0);
        
        wp_update_post([
            'ID' => $post_id,
            'post_title' => sanitize_text_field($item['titulo1'] ?? get_the_title($post_id)),
            'post_content' => wp_kses_post($item['descrip1'] ?? get_the_content(null, false, $post_id))
        ]);

        return true;
    }

    private function add_to_history($nuevas, $actualizadas, $total) {
        $history = get_option('inmovilla_sync_history', []);
        array_unshift($history, [
            'fecha' => current_time('mysql'),
            'nuevas' => $nuevas,
            'actualizadas' => $actualizadas,
            'total' => $total
        ]);
        update_option('inmovilla_sync_history', array_slice($history, 0, 10));
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

        if (empty($identifier) && empty($reference)) return null;

        $property_type = (string) ($node->nbtipo ?? $node->tipo ?? $node->tipo_ofer ?? '');
        $city = (string) ($node->poblacion ?? $node->ciudad ?? '');

        return array(
            'id_inmovilla' => $identifier,
            'reference'    => $reference ?: $identifier,
            'title'        => (string) ($node->titulo1 ?? $node->titulo ?? $property_type . ' ' . $city),
            'description'  => (string) ($node->descripcion ?? $node->descrip1 ?? ''),
            'price_sale'   => (float) ($node->precio_venta ?? $node->precioinmo ?? 0),
            'price_rent'   => (float) ($node->precio_alquiler ?? $node->precioalq ?? 0),
            'price_outlet' => (float) ($node->outlet ?? 0),
            'monthly_type' => (string) ($node->tipomensual ?? ''),
            'operation_key'=> (int) ($node->keyacci ?? 0),
            'action'       => (string) ($node->accion ?? ''),
            'type'         => $property_type,
            'city'         => $city,
            'zone'         => (string) ($node->zona ?? ''),
            'conservacion' => (string) ($node->conservacion ?? ''),
            'm_cons'       => (float) ($node->m_cons ?? 0),
            'm_uties'      => (float) ($node->m_uties ?? 0),
            'm_parcela'    => (float) ($node->m_parcela ?? 0),
            'm_terraza'    => (float) ($node->m_terraza ?? 0),
            'antiguedad'   => (int) ($node->antiguedad ?? 0),
            'gastos_com'   => (float) ($node->gastos_com ?? 0),
            'numplanta'    => (string) ($node->numplanta ?? ''),
            'bedrooms'     => (int) ($node->habitaciones ?? 0),
            'bedrooms_double' => (int) ($node->habdobles ?? 0),
            'bathrooms'    => (int) ($node->banyos ?? 0),
            'aseos'        => (int) ($node->aseos ?? 0),
            'total_rooms'  => (int) ($node->total_hab ?? 0),
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
            'latitud'      => (string) ($node->latitud ?? ''),
            'altitud'      => (string) ($node->altitud ?? ''),
            'energy_letter'=> (string) ($node->energialetra ?? ''),
            'energy_value' => (float) ($node->energiavalor ?? 0),
            'emission_letter'=> (string) ($node->emisionesletra ?? ''),
            'emission_value' => (float) ($node->emisionesvalor ?? 0),
            'distmar'      => (int) ($node->distmar ?? 0),
            'destacado'    => (int) ($node->destacado ?? 0),
            'numpanos'     => (int) ($node->numpanos ?? 0),
            'images'       => $this->extract_images($node),
            'video_codes'  => $this->extract_videos($node),
            'raw'          => json_decode(json_encode($node), true),
            'provincia'      => (string) ($node->provincia ?? ''),
            'orientacion'    => (string) ($node->orientacion ?? ''),
            'tipo_agua'      => (string) ($node->tagua ?? ''),
            'tipo_calefac'   => (string) ($node->tcalefaccion ?? ''),
            'emisionesletra' => (string) ($node->emisionesletra ?? ''),
            'emisionesvalor' => (float) ($node->emisionesvalor ?? 0),
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

        $status = 'new';
        if (!empty($existing)) {
            $post_args['ID'] = $existing[0];
            wp_update_post($post_args);
            $post_id = $existing[0];
            $status = 'updated';
        } else {
            $post_id = wp_insert_post($post_args);
        }

        if (is_wp_error($post_id) || !$post_id) return false;

        // Metadatos Pro
        update_post_meta($post_id, 'inmovilla_ref', $reference);
        update_post_meta($post_id, 'inmovilla_precioinmo', $data['price_sale']);
        update_post_meta($post_id, 'inmovilla_precioalq', $data['price_rent']);
        update_post_meta($post_id, 'inmovilla_m_cons', $data['m_cons']);
        update_post_meta($post_id, 'inmovilla_m_uties', $data['m_uties']);
        update_post_meta($post_id, 'inmovilla_m_parcela', $data['m_parcela']);
        update_post_meta($post_id, 'inmovilla_total_hab', $data['total_rooms']);
        update_post_meta($post_id, 'inmovilla_banyos', $data['bathrooms']);
        update_post_meta($post_id, 'inmovilla_destacado', $data['destacado']);
        update_post_meta($post_id, 'inmovilla_latitud', $data['latitud']);
        update_post_meta($post_id, 'inmovilla_altitud', $data['altitud']);
        update_post_meta($post_id, 'inmovilla_provincia', $data['provincia']);
        update_post_meta($post_id, 'inmovilla_orientacion', $data['orientacion']);
        update_post_meta($post_id, 'inmovilla_tagua', $data['tipo_agua']);
        update_post_meta($post_id, 'inmovilla_tcalefaccion', $data['tipo_calefac']);
        update_post_meta($post_id, 'inmovilla_emisionesletra', $data['emisionesletra']);
        update_post_meta($post_id, 'inmovilla_emisionesvalor', $data['emisionesvalor']);
        
        // Características
        update_post_meta($post_id, 'inmovilla_ascensor', $data['feat_lift']);
        update_post_meta($post_id, 'inmovilla_aire_con', $data['feat_ac']);
        update_post_meta($post_id, 'inmovilla_piscina_com', $data['feat_pool_com']);
        update_post_meta($post_id, 'inmovilla_piscina_prop', $data['feat_pool_prop']);
        update_post_meta($post_id, 'inmovilla_plaza_gara', $data['feat_garage']);
        update_post_meta($post_id, 'inmovilla_vistasalmar', $data['feat_sea_views']);

        // ACF Repetidores
        if (!empty($data['images'])) {
            $gallery = array();
            foreach ($data['images'] as $url) { $gallery[] = array('url' => $url); }
            update_field('inmovilla_gallery_urls', $gallery, $post_id);
            if (!has_post_thumbnail($post_id)) $this->upload_image_url($post_id, $data['images'][0]);
        }
        if (!empty($data['video_codes'])) {
            $videos = array();
            foreach ($data['video_codes'] as $code) { $videos[] = array('code' => $code); }
            update_field('inmovilla_video_codes', $videos, $post_id);
        }

        // Taxonomías
        if (!empty($data['action'])) wp_set_object_terms($post_id, $data['action'], 'accion', false);
        if (!empty($data['type'])) wp_set_object_terms($post_id, $data['type'], 'tipo_propiedad', false);
        if (!empty($data['city'])) wp_set_object_terms($post_id, $data['city'], 'ciudad', false);
        if (!empty($data['zone'])) wp_set_object_terms($post_id, $data['zone'], 'zona', false);
        if (!empty($data['conservacion'])) wp_set_object_terms($post_id, $data['conservacion'], 'conservacion', false);

        return ['id' => $post_id, 'status' => $status];
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
