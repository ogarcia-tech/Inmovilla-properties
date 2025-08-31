<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para generar sitemaps
 */
class InmovillaSitemap {

    public function __construct() {
        add_action('init', array($this, 'setup_sitemap_rules'));
    }

    /**
     * Configurar reglas de sitemap
     */
    public function setup_sitemap_rules() {
        add_rewrite_rule(
            '^sitemap-propiedades\.xml$',
            'index.php?inmovilla_sitemap=1',
            'top'
        );

        add_filter('query_vars', function($vars) {
            $vars[] = 'inmovilla_sitemap';
            return $vars;
        });

        add_action('template_redirect', array($this, 'generate_sitemap'));
    }

    /**
     * Generar sitemap
     */
    public function generate_sitemap() {
        if (!get_query_var('inmovilla_sitemap')) {
            return;
        }

        header('Content-Type: application/xml; charset=utf-8');

        // Intenta obtener el sitemap desde la caché
        $sitemap = get_transient('inmovilla_sitemap');

        if (false === $sitemap) {
            $sitemap  = '<?xml version="1.0" encoding="UTF-8"?>';
            $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            $args = array(
                'post_type'      => 'inmovilla_property',
                'post_status'    => 'publish',
                'posts_per_page' => 200,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'fields'         => 'ids',
            );

            $query       = new WP_Query($args);
            $total_pages = $query->max_num_pages;

            for ($page = 1; $page <= $total_pages; $page++) {
                if ($page > 1) {
                    $args['paged'] = $page;
                    $query         = new WP_Query($args);
                }

                foreach ($query->posts as $post_id) {
                    $sitemap .= '<url>';
                    $sitemap .= '<loc>' . esc_url(get_permalink($post_id)) . '</loc>';
                    $sitemap .= '<lastmod>' . esc_html(get_post_modified_time('c', true, $post_id)) . '</lastmod>';
                    $sitemap .= '<changefreq>daily</changefreq>';
                    $sitemap .= '<priority>0.8</priority>';
                    $sitemap .= '</url>';
                }
            }

            wp_reset_postdata();

            $sitemap .= '</urlset>';

            // Cachea el resultado por 12 horas
            set_transient('inmovilla_sitemap', $sitemap, 12 * HOUR_IN_SECONDS);
        }

        echo $sitemap;
        exit;
    }
}
