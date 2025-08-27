<?php
/**
 * Uninstall Script for Inmovilla Properties Plugin
 * 
 * This file runs when the plugin is deleted from WordPress.
 * It removes all plugin data, options, and database tables.
 * 
 * @package Inmovilla_Properties
 * @version 1.0.0
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove all plugin options from the database
 */
function inmovilla_remove_plugin_options() {
    // Delete plugin settings
    delete_option('inmovilla_api_token');
    delete_option('inmovilla_api_url');
    delete_option('inmovilla_settings');
    delete_option('inmovilla_colors');
    delete_option('inmovilla_seo_settings');
    delete_option('inmovilla_cache_settings');
    delete_option('inmovilla_search_settings');

    // Delete version info
    delete_option('inmovilla_plugin_version');
    delete_option('inmovilla_db_version');

    // Delete transients (cache)
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_inmovilla_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_inmovilla_%'");
}

/**
 * Remove custom post types and their data
 */
function inmovilla_remove_post_types() {
    global $wpdb;

    // Get all inmovilla properties
    $properties = get_posts(array(
        'post_type' => 'inmovilla_property',
        'numberposts' => -1,
        'post_status' => 'any'
    ));

    // Delete each property and its meta
    foreach ($properties as $property) {
        wp_delete_post($property->ID, true);
    }

    // Clean up any remaining meta
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'inmovilla_%'");
}

/**
 * Remove custom database tables
 */
function inmovilla_remove_custom_tables() {
    global $wpdb;

    // Drop custom tables if they exist
    $tables = array(
        $wpdb->prefix . 'inmovilla_cache',
        $wpdb->prefix . 'inmovilla_favorites',
        $wpdb->prefix . 'inmovilla_searches'
    );

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}

/**
 * Remove user meta data related to plugin
 */
function inmovilla_remove_user_meta() {
    global $wpdb;

    // Delete user meta for favorites and saved searches
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'inmovilla_%'");
}

/**
 * Clean up uploaded files and cache
 */
function inmovilla_cleanup_files() {
    $upload_dir = wp_upload_dir();
    $plugin_dir = $upload_dir['basedir'] . '/inmovilla-cache/';

    if (is_dir($plugin_dir)) {
        $files = glob($plugin_dir . '*');
        foreach($files as $file) {
            if(is_file($file)) {
                unlink($file);
            }
        }
        rmdir($plugin_dir);
    }
}

/**
 * Remove rewrite rules
 */
function inmovilla_remove_rewrite_rules() {
    // Flush rewrite rules to remove custom URLs
    flush_rewrite_rules();

    // Remove custom rewrite rules from database
    global $wp_rewrite;
    $wp_rewrite->init();
    $wp_rewrite->flush_rules();
}

/**
 * Main uninstall function
 */
function inmovilla_uninstall_plugin() {
    // Remove all plugin data
    inmovilla_remove_plugin_options();
    inmovilla_remove_post_types();
    inmovilla_remove_custom_tables();
    inmovilla_remove_user_meta();
    inmovilla_cleanup_files();
    inmovilla_remove_rewrite_rules();

    // Clear any remaining cache
    wp_cache_flush();

    // Log uninstall for debugging (optional)
    if (WP_DEBUG) {
        error_log('Inmovilla Properties Plugin: Successfully uninstalled and cleaned up all data');
    }
}

// Execute uninstall
inmovilla_uninstall_plugin();

?>