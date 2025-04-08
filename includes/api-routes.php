<?php

add_action('rest_api_init', function () {
    // Most specific: /booknetic/{resource}/{id}
    register_rest_route('custom-booking/v2', '/booknetic/(?P<resource>[a-zA-Z0-9_-]+)/(?P<id>\d+)', [
        'methods'  => ['GET', 'PUT', 'DELETE'],
        'callback' => 'handle_booknetic_resource',
        'permission_callback' => 'allow_jwt_or_api_key_name',
        'args' => [
            'resource' => ['required' => true],
            'id'       => ['required' => true],
        ],
    ]);

    // Then: /booknetic/{resource}
    register_rest_route('custom-booking/v2', '/booknetic/(?P<resource>[a-zA-Z0-9_-]+)', [
        'methods'  => 'GET',
        'callback' => 'handle_booknetic_resource_list',
        'permission_callback' => 'allow_jwt_or_api_key_name',
        'args' => [
            'resource' => ['required' => true],
        ],
    ]);

    register_rest_route('custom-booking/v2', '/booknetic/(?P<resource>[a-zA-Z0-9_-]+)', [
        'methods'  => 'POST',
        'callback' => 'create_booknetic_resource',
        'permission_callback' => 'allow_jwt_or_api_key_name',
        'args' => [
            'resource' => ['required' => true],
        ],
    ]);

    // Least specific: /booknetic
    register_rest_route('custom-booking/v2', '/booknetic', [
        'methods'  => ['GET'],
        'callback' => 'get_all_booknetic_data',
        'permission_callback' => 'allow_jwt_or_api_key_name',
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
    $method   = strtoupper($request->get_method());

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

function create_booknetic_resource(WP_REST_Request $request) {
    global $wpdb;

    $resource = sanitize_key($request->get_param('resource'));
    $body = $request->get_json_params();

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

    if (empty($body)) {
        return new WP_Error('empty_body', 'POST request body is empty.', ['status' => 400]);
    }

    $table = $wpdb->prefix . $allowed_resources[$resource];

    // Remove 'id' if provided — let MySQL autoincrement it
    unset($body['id']);

    $result = $wpdb->insert($table, $body);

    if (!$result) {
        return new WP_Error('insert_failed', 'Failed to insert record.', ['status' => 500]);
    }

    return rest_ensure_response([
        'inserted' => true,
        'insert_id' => $wpdb->insert_id,
    ]);
}

function allow_jwt_or_api_key_name(WP_REST_Request $request) {
    $method = strtoupper($request->get_method());

    // ✅ WordPress users via JWT
    if (is_user_logged_in()) {
        if ($method === 'GET') return current_user_can('read');
        if (in_array($method, ['POST', 'PUT'])) return current_user_can('edit_posts');
        if ($method === 'DELETE') return current_user_can('delete_posts');
        return false;
    }

    // ✅ External partners via API key
    $provided_key = trim($request->get_header('x-api-key'));
    if (empty($provided_key)) {
        return new WP_Error('rest_forbidden', 'Missing API key.', ['status' => 403]);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'booknetic_api_keys';

    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE api_key = %s AND enabled = 1",
        $provided_key
    ));

    if (!$row) {
        return new WP_Error('rest_forbidden', 'Invalid or disabled API key.', ['status' => 403]);
    }

    $allowed_methods = array_map('strtoupper', explode(',', $row->methods));
    if (!in_array($method, $allowed_methods)) {
        return new WP_Error('rest_forbidden', 'API key not allowed for this method.', ['status' => 403]);
    }

    return true;
}
