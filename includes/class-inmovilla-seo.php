<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para manejar SEO y URLs amigables
 */
class InmovillaSEO {

    public function __construct() {
        add_action('init', array($this, 'setup_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'template_redirect'));
    }

    /**
     * Configurar reglas de reescritura
     */
    public function setup_rewrite_rules() {

        // Regla para lista de propiedades
        add_rewrite_rule(
            '^propiedades/?$',
            'index.php?inmovilla_page=list',
            'top'
        );

        // Regla para propiedad individual
        add_rewrite_rule(
            '^propiedades/([^/]+)/?$',
            'index.php?inmovilla_page=single&property_slug=$matches[1]',
            'top'
        );

        // Regla para búsqueda
        add_rewrite_rule(
            '^buscar-propiedades/?$',
            'index.php?inmovilla_page=search',
            'top'
        );
    }

    /**
     * Añadir variables de consulta
     */
    public function add_query_vars($vars) {
        $vars[] = 'inmovilla_page';
        $vars[] = 'property_slug';
        return $vars;
    }

    /**
     * Redirección de plantillas
     */
    public function template_redirect() {

        $inmovilla_page = get_query_var('inmovilla_page');

        if (empty($inmovilla_page)) {
            return;
        }

        switch ($inmovilla_page) {
            case 'list':
                $this->load_property_list_template();
                break;
            case 'single':
                $this->load_property_single_template();
                break;
            case 'search':
                $this->load_property_search_template();
                break;
        }
    }

    /**
     * Cargar template de lista
     */
    private function load_property_list_template() {
        $template = locate_template('inmovilla-properties/property-list.php');
        if (!$template) {
            $template = INMOVILLA_PROPERTIES_TEMPLATES_DIR . 'property-list.php';
        }
        if (file_exists($template)) {
            include $template;
            exit;
        }
    }

    /**
     * Cargar template de propiedad individual
     */
    private function load_property_single_template() {
        $template = locate_template('inmovilla-properties/property-single.php');
        if (!$template) {
            $template = INMOVILLA_PROPERTIES_TEMPLATES_DIR . 'property-single.php';
        }
        if (file_exists($template)) {
            include $template;
            exit;
        }
    }

    /**
     * Cargar template de búsqueda
     */
    private function load_property_search_template() {
        $template = locate_template('inmovilla-properties/property-search.php');
        if (!$template) {
            $template = INMOVILLA_PROPERTIES_TEMPLATES_DIR . 'property-search.php';
        }
        if (file_exists($template)) {
            include $template;
            exit;
        }
    }
}
