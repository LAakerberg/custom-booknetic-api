<?php
/**
 * Plugin Name: Custom Booknetic API
 * Description: REST API with JWT and per-user API key support.
 * Version: 2.0
 * Author: LAA
 */

 defined('ABSPATH') || exit;

 register_activation_hook(__FILE__, 'booknetic_api_create_table');
 
 require_once plugin_dir_path(__FILE__) . 'includes/db-schema.php';
 require_once plugin_dir_path(__FILE__) . 'includes/api-routes.php';
 require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';