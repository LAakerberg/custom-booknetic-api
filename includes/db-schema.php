<?php

function booknetic_api_create_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'booknetic_api_keys';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partner_name VARCHAR(100) NOT NULL,
        api_key VARCHAR(255) NOT NULL,
        enabled TINYINT(1) DEFAULT 1,
        methods TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
