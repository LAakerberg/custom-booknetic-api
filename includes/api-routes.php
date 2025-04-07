<?php

add_action('rest_api_init', function () {
    // Most specific: /booknetic/{resource}/{id}
    register_rest_route('custom-booking/v2', '/booknetic/(?P<resource>[a-zA-Z0-9_-]+)/(?P<id>\d+)', [
        'methods'  => ['GET', 'PUT', 'DELETE'],
        'callback' => 'handle_booknetic_resource',
        'permission_callback' => 'allow_jwt_or_partner_key',
        'args' => [
            'resource' => ['required' => true],
            'id'       => ['required' => true],
        ],
    ]);

    // Then: /booknetic/{resource}
    register_rest_route('custom-booking/v2', '/booknetic/(?P<resource>[a-zA-Z0-9_-]+)', [
        'methods'  => 'GET',
        'callback' => 'handle_booknetic_resource_list',
        'permission_callback' => 'allow_jwt_or_partner_key',
        'args' => [
            'resource' => ['required' => true],
        ],
    ]);

    // Least specific: /booknetic
    register_rest_route('custom-booking/v2', '/booknetic', [
        'methods'  => ['GET'],
        'callback' => 'get_all_booknetic_data',
        'permission_callback' => 'allow_jwt_or_partner_key',
    ]);
});

function get_all_booknetic_data() {
    return rest_ensure_response([
        'appointments'   => get_booknetic_table_data('bkntc_appointments'),
        'customers'      => get_booknetic_table_data('bkntc_customers'),
        'coupons'        => get_booknetic_table_data('bkntc_coupons'),
        'giftcards'      => get_booknetic_table_data('bkntc_giftcards'),
        'locations'      => get_booknetic_table_data('bkntc_locations'),
        'staff'          => get_booknetic_table_data('bkntc_staff'),
        'services'       => get_booknetic_table_data('bkntc_services'),
        'workflow_logs'  => get_booknetic_table_data('bkntc_workflow_logs'),
    ]);
}

function get_booknetic_table_data($table_suffix) {
    global $wpdb;
    $table_name = $wpdb->prefix . $table_suffix;
    return $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
}

function handle_booknetic_resource(WP_REST_Request $request) {
    global $wpdb;

    $resource = sanitize_key($request->get_param('resource'));
    $id       = intval($request->get_param('id'));
    $method   = $request->get_method();

    $allowed_resources = [
        'appointments' => 'bkntc_appointments',
        'customers'    => 'bkntc_customers',
        'giftcards'    => 'bkntc_giftcards',
        'coupons'      => 'bkntc_coupons',
        'locations'    => 'bkntc_locations',
        'staff'        => 'bkntc_staff',
        'services'     => 'bkntc_services',
        'workflow_logs'=> 'bkntc_workflow_logs',
    ];

    if (!isset($allowed_resources[$resource])) {
        return new WP_Error('invalid_resource', 'Invalid resource: ' . $resource, ['status' => 404]);
    }

    $table = $wpdb->prefix . $allowed_resources[$resource];

    if ($method === 'GET') {
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
        return $row ? rest_ensure_response($row) : new WP_Error('not_found', 'Item not found.', ['status' => 404]);
    }

    if ($method === 'DELETE') {
        $deleted = $wpdb->delete($table, ['id' => $id]);
        return rest_ensure_response(['deleted' => (bool)$deleted]);
    }

    if ($method === 'PUT') {
        $body = $request->get_json_params();
        if (empty($body)) {
            return new WP_Error('empty_body', 'PUT request body is empty.', ['status' => 400]);
        }

        $updated = $wpdb->update($table, $body, ['id' => $id]);
        return rest_ensure_response(['updated' => (bool)$updated]);
    }

    return new WP_Error('unsupported_method', 'Unsupported request method.', ['status' => 405]);
}

function handle_booknetic_resource_list(WP_REST_Request $request) {
    global $wpdb;

    $resource = sanitize_key($request->get_param('resource'));

    $allowed_resources = [
        'appointments' => 'bkntc_appointments',
        'customers'    => 'bkntc_customers',
        'giftcards'    => 'bkntc_giftcards',
        'coupons'      => 'bkntc_coupons',
        'locations'    => 'bkntc_locations',
        'staff'        => 'bkntc_staff',
        'services'     => 'bkntc_services',
        'workflow_logs'=> 'bkntc_workflow_logs',
    ];

    if (!isset($allowed_resources[$resource])) {
        return new WP_Error('invalid_resource', 'Invalid resource: ' . $resource, ['status' => 404]);
    }

    $table = $wpdb->prefix . $allowed_resources[$resource];
    $results = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
    return rest_ensure_response($results);
}

function allow_jwt_or_partner_key(WP_REST_Request $request) {
    $method = $request->get_method();

    if (is_user_logged_in()) {
        if ($method === 'GET') return current_user_can('read');
        if (in_array($method, ['POST', 'PUT'])) return current_user_can('edit_posts');
        if ($method === 'DELETE') return current_user_can('delete_posts');
        return false;
    }

    $provided_key = trim($request->get_header('x-api-key'));
    $partner_keys = get_option('custom_booknetic_partner_api_keys', []);

    foreach ($partner_keys as $partner => $config) {
        if (
            $config['enabled'] &&
            $config['key'] === $provided_key &&
            in_array($method, $config['methods'])
        ) {
            return true;
        }
    }

    return new WP_Error('rest_forbidden', 'Unauthorized: missing or invalid token/key.', ['status' => 403]);
}
