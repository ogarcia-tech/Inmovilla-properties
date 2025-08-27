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

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Aquí iría la lógica para generar URLs de propiedades
        echo '<url>';
        echo '<loc>' . home_url('/propiedades/') . '</loc>';
        echo '<changefreq>daily</changefreq>';
        echo '<priority>1.0</priority>';
        echo '</url>';

        echo '</urlset>';
        exit;
    }
}
