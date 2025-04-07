<?php
/**
 * Plugin Name: Custom Booknetic API
 * Description: REST API with JWT and per-user API key support.
 * Version: 1.0
 * Author: Your Name
 */

defined('ABSPATH') || exit;

require_once plugin_dir_path(__FILE__) . 'includes/api-routes.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
