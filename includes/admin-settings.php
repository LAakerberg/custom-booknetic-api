<?php

add_action('admin_menu', function () {
    add_options_page(
        'Booknetic API Keys',
        'Booknetic API',
        'manage_options',
        'booknetic-api-admin',
        'booknetic_api_admin_page'
    );
});

function booknetic_api_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'booknetic_api_keys';

    if (!current_user_can('manage_options')) return;

    if (isset($_POST['booknetic_save_keys'])) {
        check_admin_referer('booknetic_save_keys');

        $partners     = $_POST['partner'] ?? [];
        $keys         = $_POST['api_key'] ?? [];
        $enabled_keys = $_POST['enabled'] ?? [];
        $method_keys  = $_POST['methods'] ?? [];
        $to_delete    = $_POST['partner_delete'] ?? [];

        // Delete selected keys
        foreach ($to_delete as $partner_name) {
            $wpdb->delete($table, ['partner_name' => sanitize_text_field($partner_name)]);
        }

        // Insert or update
        foreach ($partners as $i => $partner_name) {
            $partner_name = sanitize_text_field($partner_name);
            if (empty($partner_name) || in_array($partner_name, $to_delete)) continue;

            $key     = sanitize_text_field($keys[$i] ?? '');
            $enabled = isset($enabled_keys[$partner_name]) ? 1 : 0;
            $methods = isset($method_keys[$partner_name]) ? implode(',', array_map('sanitize_text_field', $method_keys[$partner_name])) : '';

            $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE partner_name = %s", $partner_name));

            if ($existing) {
                $wpdb->update(
                    $table,
                    ['api_key' => $key, 'enabled' => $enabled, 'methods' => $methods],
                    ['partner_name' => $partner_name]
                );
            } else {
                $wpdb->insert(
                    $table,
                    [
                        'partner_name' => $partner_name,
                        'api_key'      => $key,
                        'enabled'      => $enabled,
                        'methods'      => $methods,
                        'created_at'   => current_time('mysql'),
                    ]
                );
            }
        }

        echo '<div class="updated"><p>API keys saved.</p></div>';
    }

    // Fetch all keys from DB
    $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A);
    ?>
    <div class="wrap">
        <h1>Partner API Keys</h1>
        <form method="post">
            <?php wp_nonce_field('booknetic_save_keys'); ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Partner Name</th>
                        <th>API Key</th>
                        <th>Enabled</th>
                        <th>Allowed Methods</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): 
                        $partner = $row['partner_name'];
                        $methods = explode(',', $row['methods']);
                        $rand = wp_generate_password(32, false);
                    ?>
                        <tr>
                            <td><input type="text" name="partner[]" value="<?php echo esc_attr($partner); ?>" /></td>
                            <td>
                                <input type="text" name="api_key[]" value="<?php echo esc_attr($row['api_key']); ?>" size="30" />
                                <button type="button" class="button" onclick="this.previousElementSibling.value='<?php echo esc_js($rand); ?>'">Generate</button>
                            </td>
                            <td><input type="checkbox" name="enabled[<?php echo esc_attr($partner); ?>]" <?php checked($row['enabled']); ?> /></td>
                            <td>
                                <?php foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method): ?>
                                    <label><input type="checkbox" name="methods[<?php echo esc_attr($partner); ?>][]" value="<?php echo $method; ?>" <?php checked(in_array($method, $methods)); ?> /> <?php echo $method; ?></label><br>
                                <?php endforeach; ?>
                            </td>
                            <td><input type="checkbox" name="partner_delete[]" value="<?php echo esc_attr($partner); ?>"></td>
                        </tr>
                    <?php endforeach; ?>

                    <!-- Add new -->
                    <tr>
                        <td><input type="text" name="partner[]" /></td>
                        <td>
                            <input type="text" name="api_key[]" value="<?php echo esc_attr(wp_generate_password(32, false)); ?>" size="30" />
                        </td>
                        <td><input type="checkbox" name="enabled[new]" /></td>
                        <td>
                            <?php foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method): ?>
                                <label><input type="checkbox" name="methods[new][]" value="<?php echo $method; ?>" /> <?php echo $method; ?></label><br>
                            <?php endforeach; ?>
                        </td>
                        <td>—</td>
                    </tr>
                </tbody>
            </table>
            <p><input type="submit" name="booknetic_save_keys" class="button button-primary" value="Save Changes"></p>
        </form>
    </div>
    <?php
}
