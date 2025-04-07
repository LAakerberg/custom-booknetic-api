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
    if (!current_user_can('manage_options')) return;

    $option_key = 'custom_booknetic_partner_api_keys';
    $existing = get_option($option_key, []);

    if (isset($_POST['booknetic_save_keys'])) {
        check_admin_referer('booknetic_save_keys');

        $partners     = $_POST['partner'] ?? [];
        $keys         = $_POST['api_key'] ?? [];
        $enabled_keys = $_POST['enabled'] ?? [];
        $method_keys  = $_POST['methods'] ?? [];
        $to_delete    = $_POST['partner_delete'] ?? [];

        $new = [];
        foreach ($partners as $i => $partner_name) {
            if (empty($partner_name) || in_array($partner_name, $to_delete)) continue;

            $key     = sanitize_text_field($keys[$i] ?? '');
            $enabled = isset($enabled_keys[$partner_name]);
            $methods = isset($method_keys[$partner_name]) ? array_map('sanitize_text_field', $method_keys[$partner_name]) : [];

            $new[$partner_name] = [
                'key'     => $key,
                'enabled' => $enabled,
                'methods' => $methods
            ];
        }

        update_option($option_key, $new);
        echo '<div class="updated"><p>API keys updated.</p></div>';
        $existing = $new;
    }
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
                    <?php foreach ($existing as $partner => $config): 
                        $rand = wp_generate_password(20, false); ?>
                        <tr>
                            <td><input type="text" name="partner[]" value="<?php echo esc_attr($partner); ?>" /></td>
                            <td>
                                <input type="text" name="api_key[]" value="<?php echo esc_attr($config['key']); ?>" size="30" />
                                <button type="button" class="button" onclick="this.previousElementSibling.value='<?php echo esc_js($rand); ?>'">Generate</button>
                            </td>
                            <td><input type="checkbox" name="enabled[<?php echo esc_attr($partner); ?>]" <?php checked($config['enabled']); ?> /></td>
                            <td>
                                <?php foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method): ?>
                                    <label><input type="checkbox" name="methods[<?php echo esc_attr($partner); ?>][]" value="<?php echo $method; ?>" <?php checked(in_array($method, $config['methods'])); ?> /> <?php echo $method; ?></label><br>
                                <?php endforeach; ?>
                            </td>
                            <td><input type="checkbox" name="partner_delete[]" value="<?php echo esc_attr($partner); ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><input type="text" name="partner[]" /></td>
                        <td><input type="text" name="api_key[]" /></td>
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
