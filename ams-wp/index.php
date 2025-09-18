<?php
/**
 * Asset Management System (AMS) REST API Plugin - Secured Version
 * 
 * Plugin Name: AMS REST API
 * Description: Custom REST API for Asset Management System with JWT Authentication
 * Version: 2.1.0
 * Author: Tee
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include Firebase JWT library (you need to install via Composer or manually)
// composer require firebase/php-jwt
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AMS_REST_API
{

    private $namespace = 'ams/v1';

    public function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('rest_api_init', array($this, 'register_api_routes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function init()
    {
        $this->create_asset_post_type();
        $this->add_meta_boxes();
        $this->setup_jwt_authentication();
        // Hook into WP login for wp-admin
        add_action('wp_login', array($this, 'set_admin_token_cookie'), 10, 2);
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }

    /**
     * Handle custom admin actions with proper security
     */
    public function handle_admin_actions()
    {
        // Check if our custom action is being triggered
        if (isset($_GET['ams_action']) && sanitize_text_field($_GET['ams_action']) === 'save_all_assets') {
            
            // Verify nonce for security
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'ams_save_all_assets')) {
                wp_die('Security check failed');
            }
            
            // Check user permissions
            if (!current_user_can('manage_options')) {
                wp_die('You do not have sufficient permissions to perform this action.');
            }
            
            $result = $this->save_all_assets();
            
            // Redirect back with result message
            $redirect_url = add_query_arg(array(
                'ams_message' => $result['success'] ? 'success' : 'error',
                'ams_count' => $result['count'],
                'ams_errors' => $result['errors']
            ), admin_url('edit.php?post_type=asset_item'));
            
            wp_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Save all asset posts
     */
    private function save_all_assets()
    {
        global $wpdb;
        
        // Use prepared statement for security
        $asset_ids = $wpdb->get_col($wpdb->prepare("
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = %s 
            AND post_status = %s
        ", 'asset_item', 'publish'));
        
        $saved_count = 0;
        $errors = 0;
        
        foreach ($asset_ids as $asset_id) {
            // Validate asset_id is numeric
            $asset_id = intval($asset_id);
            if ($asset_id <= 0) {
                $errors++;
                continue;
            }
            
            $post = get_post($asset_id);
            if ($post) {
                // Update post to trigger save_post hooks
                $updated = wp_update_post(array(
                    'ID' => $asset_id,
                    'post_modified' => current_time('mysql'),
                    'post_modified_gmt' => current_time('mysql', 1)
                ));
                
                if ($updated && !is_wp_error($updated)) {
                    $saved_count++;
                } else {
                    $errors++;
                }
            }
        }
        
        return array(
            'success' => $errors === 0,
            'count' => $saved_count,
            'errors' => $errors,
            'total' => count($asset_ids)
        );
    }

    /**
     * Create Asset Item Custom Post Type
     */
    public function create_asset_post_type()
    {
        $labels = array(
            'name' => 'Asset Items',
            'singular_name' => 'Asset Item',
            'menu_name' => 'Asset Items',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Asset Item',
            'edit_item' => 'Edit Asset Item',
            'new_item' => 'New Asset Item',
            'view_item' => 'View Asset Item',
            'search_items' => 'Search Asset Items',
            'not_found' => 'No asset items found',
            'not_found_in_trash' => 'No asset items found in trash',
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'asset-items'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-portfolio',
            'show_in_rest' => true,
            'rest_base' => 'asset-items',
            'supports' => array('title', 'editor', 'custom-fields'),
        );

        register_post_type('asset_item', $args);

        register_taxonomy('asset_location', 'asset_item', array(
            'label' => 'Locations',
            'rewrite' => array('slug' => 'asset-location'),
            'hierarchical' => true,
            'show_in_rest' => true,
        ));
    }

    /**
     * Add Meta Boxes for Asset Items
     */
    public function add_meta_boxes()
    {
        add_action('add_meta_boxes', function () {
            add_meta_box(
                'asset_details',
                'Asset Details',
                array($this, 'asset_details_callback'),
                'asset_item',
                'normal',
                'high'
            );
        });

        add_action('save_post', array($this, 'save_asset_meta'));
    }

    public function asset_details_callback($post)
    {
        wp_nonce_field('save_asset_meta', 'asset_meta_nonce');

        // Get existing values with proper sanitization
        $asset_tag = sanitize_text_field(get_post_meta($post->ID, '_asset_tag', true));
        $model = sanitize_text_field(get_post_meta($post->ID, '_model', true));
        $serial_number = sanitize_text_field(get_post_meta($post->ID, '_serial_number', true));
        $status = sanitize_text_field(get_post_meta($post->ID, '_status', true));
        $assigned_to = sanitize_text_field(get_post_meta($post->ID, '_assigned_to', true));
        $location = sanitize_text_field(get_post_meta($post->ID, '_location', true));
        $rfid_tag = sanitize_text_field(get_post_meta($post->ID, '_rfid_tag', true));
        $warranty_expiry = sanitize_text_field(get_post_meta($post->ID, '_warranty_expiry', true));
        $notes = sanitize_textarea_field(get_post_meta($post->ID, '_notes', true));
        $purchase_date = sanitize_text_field(get_post_meta($post->ID, '_purchase_date', true));
        $purchase_cost = floatval(get_post_meta($post->ID, '_purchase_cost', true));

        // Convert date fields from d-m-Y to Y-m-d for HTML date input
        $warranty_expiry_input = $this->convert_date_for_input($warranty_expiry);
        $purchase_date_input = $this->convert_date_for_input($purchase_date);

        echo '<table class="form-table">';
        echo '<tr><th><label for="asset_tag">Asset Tag</label></th><td><input type="text" id="asset_tag" name="asset_tag" value="' . esc_attr($asset_tag) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="model">Model</label></th><td><input type="text" id="model" name="model" value="' . esc_attr($model) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="serial_number">Serial Number</label></th><td><input type="text" id="serial_number" name="serial_number" value="' . esc_attr($serial_number) . '" class="regular-text" /></td></tr>';

        echo '<tr><th><label for="status">Status</label></th><td>';
        echo '<select id="status" name="status">';
        $statuses = array('new', 'staging', 'ready', 'defected', 'maintenance', 'archived', 'disposed', 'return');
        foreach ($statuses as $status_option) {
            echo '<option value="' . esc_attr($status_option) . '"' . selected($status, $status_option, false) . '>' . esc_html(ucfirst(str_replace('_', ' ', $status_option))) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th><label for="assigned_to">Assigned To</label></th><td><input type="text" id="assigned_to" name="assigned_to" value="' . esc_attr($assigned_to) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="location">Location</label></th><td><input type="text" id="location" name="location" value="' . esc_attr($location) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="rfid_tag">RFID Tag</label></th><td><input type="text" id="rfid_tag" name="rfid_tag" value="' . esc_attr($rfid_tag) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="warranty_expiry">Warranty Expiry</label></th><td><input type="date" id="warranty_expiry" name="warranty_expiry" value="' . esc_attr($warranty_expiry_input) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="purchase_date">Purchase Date</label></th><td><input type="date" id="purchase_date" name="purchase_date" value="' . esc_attr($purchase_date_input) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="purchase_cost">Purchase Cost</label></th><td><input type="number" step="0.01" id="purchase_cost" name="purchase_cost" value="' . esc_attr($purchase_cost) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="notes">Notes</label></th><td><textarea id="notes" name="notes" rows="4" class="large-text">' . esc_textarea($notes) . '</textarea></td></tr>';
        echo '</table>';
    }

    public function save_asset_meta($post_id)
    {
        // Security checks
        if (!isset($_POST['asset_meta_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['asset_meta_nonce']), 'save_asset_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['post_type']) && sanitize_text_field($_POST['post_type']) == 'asset_item') {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }

        $meta_fields = array(
            'asset_tag',
            'model',
            'serial_number',
            'status',
            'assigned_to',
            'location',
            'rfid_tag',
            'warranty_expiry',
            'notes',
            'purchase_date',
            'purchase_cost'
        );

        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                $value = '';
                
                // Handle different field types with proper sanitization
                switch ($field) {
                    case 'notes':
                        $value = sanitize_textarea_field($_POST[$field]);
                        break;
                    case 'purchase_cost':
                        $value = floatval($_POST[$field]);
                        break;
                    default:
                        $value = sanitize_text_field($_POST[$field]);
                        break;
                }
        
                // Convert date fields from Y-m-d (HTML input format) to d-m-Y for storage
                if (in_array($field, array('warranty_expiry', 'purchase_date')) && !empty($value)) {
                    $value = $this->convert_date_for_storage($value);
                }
                
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }

    /**
     * Setup JWT Authentication with improved CORS
     */
    public function setup_jwt_authentication()
    {
        // Add JWT secret to wp-config.php
        if (!defined('JWT_AUTH_SECRET_KEY')) {
            define('JWT_AUTH_SECRET_KEY', wp_hash('ams-jwt-secret-' . AUTH_KEY));
        }

        // Improved CORS handling
        add_action('rest_api_init', function () {
            remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
            add_filter('rest_pre_serve_request', array($this, 'handle_cors_headers'), 15);
        });
    }

    /**
     * Handle CORS headers with better security
     */
    public function handle_cors_headers($value)
    {
        $allowed_origins = array(
            home_url(),
            admin_url(),
            // 'https://frontend-domain.com'
        );

        $origin = isset($_SERVER['HTTP_ORIGIN']) ? esc_url_raw($_SERVER['HTTP_ORIGIN']) : '';
        
        if (in_array($origin, $allowed_origins) || $this->is_development_environment()) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        header('Access-Control-Allow-Credentials: true');
        
        return $value;
    }

    /**
     * Check if we're in development environment
     */
    private function is_development_environment()
    {
        return defined('WP_DEBUG') && WP_DEBUG === true;
    }

    /**
     * Register API Routes
     */
    public function register_api_routes()
    {
        // Authentication routes
        register_rest_route($this->namespace, '/auth/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'authenticate_user'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route($this->namespace, '/auth/validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'validate_token'),
            'permission_callback' => '__return_true',
        ));

        // Asset Items routes
        register_rest_route($this->namespace, '/assets', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_assets'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        register_rest_route($this->namespace, '/assets/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_asset'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        register_rest_route($this->namespace, '/asset/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_asset'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        register_rest_route($this->namespace, '/assets', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_asset'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        register_rest_route($this->namespace, '/assets/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_asset'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        register_rest_route($this->namespace, '/assets/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_asset'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        // Bulk operations
        register_rest_route($this->namespace, '/assets/bulk-deploy', array(
            'methods' => 'POST',
            'callback' => array($this, 'bulk_deploy_assets'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        // Metadata routes
        register_rest_route($this->namespace, '/metadata/statuses', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_statuses'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        register_rest_route($this->namespace, '/metadata/models', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_models'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        register_rest_route($this->namespace, '/metadata/locations', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_locations'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));

        register_rest_route($this->namespace, '/metadata/assigned', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_assigned'),
            'permission_callback' => array($this, 'check_admin_permission'),
        ));
    }

    /**
     * Authentication Methods using Firebase JWT
     */
    public function authenticate_user($request)
    {
        $username = sanitize_text_field($request->get_param('username'));
        $password = $request->get_param('password'); // Don't sanitize passwords

        if (empty($username) || empty($password)) {
            return new WP_Error('missing_credentials', 'Username and password are required', array('status' => 400));
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return new WP_Error('authentication_failed', 'Invalid credentials', array('status' => 401));
        }

        if (!in_array('administrator', $user->roles)) {
            return new WP_Error('insufficient_permissions', 'Only administrators can access this API', array('status' => 403));
        }

        $token = $this->generate_jwt_token($user);

        return array(
            'success' => true,
            'token' => $token,
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'roles' => $user->roles,
            ),
        );
    }

    public function validate_token($request)
    {
        $token = $this->get_auth_token($request);

        if (is_wp_error($token)) {
            return $token;
        }

        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is missing', array('status' => 401));
        }

        $user = $this->validate_jwt_token($token);

        if (is_wp_error($user)) {
            return $user;
        }

        return array(
            'success' => true,
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
            ),
        );
    }

    private function generate_jwt_token($user)
    {
        $issued_at = time();
        $expiration = $issued_at + (24 * 60 * 60); // 24 hours

        $payload = array(
            'iss' => get_site_url(),
            'iat' => $issued_at,
            'exp' => $expiration,
            'data' => array(
                'user' => array(
                    'id' => $user->ID,
                ),
            ),
        );

        return JWT::encode($payload, JWT_AUTH_SECRET_KEY, 'HS256');
    }

    private function validate_jwt_token($token)
    {
        try {
            $decoded = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
            $user = get_user_by('id', $decoded->data->user->id);

            if (!$user || !in_array('administrator', $user->roles)) {
                return new WP_Error('invalid_token', 'Invalid token or insufficient permissions', array('status' => 401));
            }

            return $user;
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Token validation failed: ' . $e->getMessage(), array('status' => 401));
        }
    }

    private function get_auth_token($request)
    {
        $auth_header = $request->get_header('Authorization');

        if (!$auth_header) {
            return new WP_Error('missing_authorization_header', 'Authorization header is missing', array('status' => 401));
        }

        $parts = explode(' ', $auth_header, 2);
        if (count($parts) !== 2) {
            return new WP_Error('invalid_authorization_format', 'Authorization header format is invalid', array('status' => 401));
        }

        list($bearer, $token) = $parts;

        if ($bearer !== 'Bearer' || empty($token)) {
            return new WP_Error('invalid_bearer_token', 'Authorization must use Bearer scheme with a valid token', array('status' => 401));
        }

        return $token;
    }

    /**
     * Permission Callback
     */
    public function check_admin_permission($request)
    {
        $token = $this->get_auth_token($request);

        if (is_wp_error($token)) {
            return $token;
        }

        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is missing', array('status' => 401));
        }

        $user = $this->validate_jwt_token($token);

        if (is_wp_error($user)) {
            return $user;
        }

        return true;
    }

    /**
     * Asset CRUD Operations with improved security
     */
    public function get_assets($request)
    {
        global $wpdb;
        
        $page = max(1, intval($request->get_param('page') ?: 1));
        $per_page = max(1, min(100, intval($request->get_param('per_page') ?: 10))); // Limit max per_page
        $search = sanitize_text_field($request->get_param('search'));
        $status = sanitize_text_field($request->get_param('status'));
        $model = sanitize_text_field($request->get_param('model'));
        $rfid_tag = sanitize_text_field($request->get_param('rfid_tag'));
        $location = sanitize_text_field($request->get_param('location'));
        $assigned_to = sanitize_text_field($request->get_param('assigned_to'));
        $sort_by = sanitize_text_field($request->get_param('sort_by') ?: 'date');
        $sort_order = strtoupper(sanitize_text_field($request->get_param('sort_order') ?: 'DESC'));
        
        // Validate sort_order
        $sort_order = in_array($sort_order, array('ASC', 'DESC')) ? $sort_order : 'DESC';

        $args = array(
            'post_type' => 'asset_item',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'order' => $sort_order,
        );

        // Handle different sort types securely
        $allowed_sort_fields = array('date', 'title', 'model', 'serial_number', 'warranty_expiry', 'purchase_date', 'purchase_cost');
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'date';
        }

        $meta_sort_fields = array('model', 'serial_number', 'warranty_expiry', 'purchase_date', 'purchase_cost');

        if (in_array($sort_by, $meta_sort_fields)) {
            $args['meta_key'] = '_' . $sort_by;
            $args['orderby'] = 'meta_value';

            if (in_array($sort_by, array('warranty_expiry', 'purchase_date'))) {
                $args['orderby'] = 'meta_value_datetime';
            }

            if (in_array($sort_by, array('purchase_cost'))) {
                $args['orderby'] = 'meta_value_num';
            }
        } else {
            switch ($sort_by) {
                case 'title':
                    $args['orderby'] = 'title';
                    break;
                case 'date':
                default:
                    $args['orderby'] = 'date';
                    break;
            }
        }

        // Handle search and filters with prepared statements where needed
        $meta_query = array();

        if ($search) {
            // For search, we'll use a more secure approach
            $search_args = $args;
            $search_args['s'] = $search;
            
            // Also search in metadata using prepared statements
            $meta_search_ids = $wpdb->get_col($wpdb->prepare("
                SELECT DISTINCT post_id 
                FROM {$wpdb->postmeta} 
                WHERE (meta_key = '_asset_tag' OR meta_key = '_serial_number' OR meta_key = '_assigned_to' OR meta_key = '_location') 
                AND meta_value LIKE %s
            ", '%' . $wpdb->esc_like($search) . '%'));
            
            if (!empty($meta_search_ids)) {
                $title_query = new WP_Query($search_args);
                $combined_ids = array_unique(array_merge(wp_list_pluck($title_query->posts, 'ID'), $meta_search_ids));
                
                if (!empty($combined_ids)) {
                    $args['post__in'] = array_map('intval', $combined_ids);
                    $args['posts_per_page'] = -1;
                    $total_found = count($combined_ids);
                } else {
                    $args['post__in'] = array(0);
                    $total_found = 0;
                }
            } else {
                $args['s'] = $search;
            }
        }

        // Add other filters to meta_query
        if ($status) {
            $meta_query[] = array(
                'key' => '_status',
                'value' => $status,
                'compare' => '='
            );
        }

        if ($model) {
            $meta_query[] = array(
                'key' => '_model',
                'value' => $model,
                'compare' => 'LIKE'
            );
        }

        if ($rfid_tag) {
            $meta_query[] = array(
                'key' => '_rfid_tag',
                'value' => $rfid_tag,
                'compare' => 'LIKE'
            );
        }

        if ($location) {
            $meta_query[] = array(
                'key' => '_location',
                'value' => $location,
                'compare' => 'LIKE'
            );
        }

        if ($assigned_to) {
            $meta_query[] = array(
                'key' => '_assigned_to',
                'value' => $assigned_to,
                'compare' => 'LIKE'
            );
        }

        if (count($meta_query) > 0) {
            $args['meta_query'] = array(
                'relation' => 'AND',
                ...$meta_query
            );
        }

        $query = new WP_Query($args);
        $assets = array();

        // Handle pagination for search results
        if ($search && isset($total_found)) {
            $all_posts = $query->posts;
            $offset = ($page - 1) * $per_page;
            $paged_posts = array_slice($all_posts, $offset, $per_page);
            
            foreach ($paged_posts as $post) {
                $assets[] = $this->format_asset_data($post);
            }
            
            $pagination = array(
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_found,
                'total_pages' => ceil($total_found / $per_page),
            );
        } else {
            foreach ($query->posts as $post) {
                $assets[] = $this->format_asset_data($post);
            }
            
            $pagination = array(
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $query->found_posts,
                'total_pages' => $query->max_num_pages,
            );
        }

        return array(
            'success' => true,
            'data' => $assets,
            'pagination' => $pagination,
            'sort' => array(
                'sort_by' => $sort_by,
                'sort_order' => $sort_order,
            ),
            'filters_applied' => array(
                'search' => $search,
                'status' => $status,
                'model' => $model,
                'rfid_tag' => $rfid_tag,
                'location' => $location,
                'assigned_to' => $assigned_to,
            ),
        );
    }

    public function get_asset($request)
    {
        $id = intval($request->get_param('id'));
        
        if ($id <= 0) {
            return new WP_Error('invalid_id', 'Invalid asset ID', array('status' => 400));
        }
        
        $post = get_post($id);

        if (!$post || $post->post_type !== 'asset_item') {
            return new WP_Error('asset_not_found', 'Asset not found', array('status' => 404));
        }

        return array(
            'success' => true,
            'data' => $this->format_asset_data($post),
        );
    }

    public function create_asset($request)
    {
        $raw_data = $request->get_body();
        
        if (empty($raw_data)) {
            return new WP_Error('empty_data', 'Request body is empty', array('status' => 400));
        }

        $data = json_decode($raw_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Invalid JSON data: ' . json_last_error_msg(), array('status' => 400));
        }

        // Validate required fields
        if (empty($data['title'])) {
            return new WP_Error('missing_title', 'Asset title is required', array('status' => 400));
        }

        $post_data = array(
            'post_type' => 'asset_item',
            'post_title' => sanitize_text_field($data['title']),
            'post_content' => sanitize_textarea_field($data['description'] ?? ''),
            'post_status' => 'publish',
        );

        $post_id = wp_insert_post($post_data);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // Save meta fields
        $this->save_asset_meta_from_api($post_id, $data);

        return array(
            'success' => true,
            'message' => 'Asset created successfully',
            'data' => $this->format_asset_data(get_post($post_id)),
        );
    }

    public function update_asset($request)
    {
        $id = intval($request->get_param('id'));
        $raw_data = $request->get_body();
        
        if ($id <= 0) {
            return new WP_Error('invalid_id', 'Invalid asset ID', array('status' => 400));
        }
        
        if (empty($raw_data)) {
            return new WP_Error('empty_data', 'Request body is empty', array('status' => 400));
        }

        $data = json_decode($raw_data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Invalid JSON data: ' . json_last_error_msg(), array('status' => 400));
        }

        $post = get_post($id);

        if (!$post || $post->post_type !== 'asset_item') {
            return new WP_Error('asset_not_found', 'Asset not found', array('status' => 404));
        }

        $post_data = array(
            'ID' => $id,
        );
        
        if (isset($data['title'])) {
            $post_data['post_title'] = sanitize_text_field($data['title']);
        }
        
        if (isset($data['description'])) {
            $post_data['post_content'] = sanitize_textarea_field($data['description']);
        }

        $result = wp_update_post($post_data);

        if (is_wp_error($result)) {
            return $result;
        }

        // Save meta fields
        $this->save_asset_meta_from_api($id, $data);

        return array(
            'success' => true,
            'message' => 'Asset updated successfully',
            'data' => $this->format_asset_data(get_post($id)),
        );
    }

    public function delete_asset($request)
    {
        $id = intval($request->get_param('id'));
        
        if ($id <= 0) {
            return new WP_Error('invalid_id', 'Invalid asset ID', array('status' => 400));
        }
        
        $post = get_post($id);

        if (!$post || $post->post_type !== 'asset_item') {
            return new WP_Error('asset_not_found', 'Asset not found', array('status' => 404));
        }

        $result = wp_delete_post($id, true);

        if (!$result) {
            return new WP_Error('delete_failed', 'Failed to delete asset', array('status' => 500));
        }

        return array(
            'success' => true,
            'message' => 'Asset deleted successfully',
        );
    }

    public function bulk_deploy_assets($request)
    {
        $raw_data = $request->get_body();
        
        if (empty($raw_data)) {
            return new WP_Error('empty_data', 'Request body is empty', array('status' => 400));
        }

        $data = json_decode($raw_data, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Invalid JSON data: ' . json_last_error_msg(), array('status' => 400));
        }
        
        $asset_ids = $data['asset_ids'] ?? array();

        if (empty($asset_ids) || !is_array($asset_ids)) {
            return new WP_Error('missing_assets', 'No valid assets specified for bulk deploy', array('status' => 400));
        }

        $updated_count = 0;
        $errors = array();

        foreach ($asset_ids as $asset_id) {
            $asset_id = intval($asset_id);
            
            if ($asset_id <= 0) {
                $errors[] = "Invalid asset ID: $asset_id";
                continue;
            }
            
            $post = get_post($asset_id);
            if ($post && $post->post_type === 'asset_item') {
                $result = update_post_meta($asset_id, '_status', 'ready');
                if ($result !== false) {
                    $updated_count++;
                } else {
                    $errors[] = "Failed to update asset ID: $asset_id";
                }
            } else {
                $errors[] = "Asset not found: $asset_id";
            }
        }

        return array(
            'success' => empty($errors),
            'message' => sprintf('%d assets updated successfully', $updated_count),
            'updated_count' => $updated_count,
            'errors' => $errors,
        );
    }

    /**
     * Metadata Methods with improved security
     */
    public function get_statuses($request)
    {
        return array(
            'success' => true,
            'data' => array(
                'new' => 'New',
                'staging' => 'Staging',
                'ready' => 'Ready to Deploy',
                'defected' => 'Defected',
                'maintenance' => 'Under Maintenance',
                'archived' => 'Archived',
                'disposed' => 'Disposed',
                'return' => 'Returned to Client',
            ),
        );
    }

    public function get_models($request)
    {
        global $wpdb;

        $models = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.meta_value 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = %s 
            AND pm.meta_value != '' 
            AND p.post_type = %s
            AND p.post_status = %s
            ORDER BY pm.meta_value ASC
            LIMIT 100
        ", '_model', 'asset_item', 'publish'));

        return array(
            'success' => true,
            'data' => array_map('sanitize_text_field', $models),
        );
    }

    public function get_locations($request)
    {
        global $wpdb;

        $locations = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.meta_value 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = %s 
            AND pm.meta_value != '' 
            AND p.post_type = %s
            AND p.post_status = %s
            ORDER BY pm.meta_value ASC
            LIMIT 100
        ", '_location', 'asset_item', 'publish'));

        return array(
            'success' => true,
            'data' => array_map('sanitize_text_field', $locations),
        );
    }

    public function get_assigned($request)
    {
        global $wpdb;

        $assigned = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.meta_value 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = %s 
            AND pm.meta_value != '' 
            AND p.post_type = %s
            AND p.post_status = %s
            ORDER BY pm.meta_value ASC
            LIMIT 100
        ", '_assigned_to', 'asset_item', 'publish'));

        return array(
            'success' => true,
            'data' => array_map('sanitize_text_field', $assigned),
        );
    }

    /**
     * Helper Methods
     */
    private function format_asset_data($post)
    {
        $meta_fields = array(
            'asset_tag',
            'model',
            'serial_number',
            'status',
            'assigned_to',
            'location',
            'rfid_tag',
            'warranty_expiry',
            'notes',
            'purchase_date',
            'purchase_cost'
        );

        $asset_data = array(
            'id' => intval($post->ID),
            'title' => sanitize_text_field($post->post_title),
            'description' => sanitize_textarea_field($post->post_content),
            'date_created' => $this->format_date($post->post_date),
            'date_modified' => $this->format_date($post->post_modified),
        );

        foreach ($meta_fields as $field) {
            $value = get_post_meta($post->ID, '_' . $field, true);
            
            switch ($field) {
                case 'notes':
                    $asset_data[$field] = sanitize_textarea_field($value);
                    break;
                case 'purchase_cost':
                    $asset_data[$field] = floatval($value);
                    break;
                default:
                    $asset_data[$field] = sanitize_text_field($value);
                    break;
            }
        }

        return $asset_data;
    }

    /**
     * Helper method to format dates consistently
     */
    private function format_date($date_string)
    {
        if (empty($date_string)) {
            return '';
        }

        $timestamp = strtotime($date_string);

        if ($timestamp === false) {
            return sanitize_text_field($date_string);
        }

        return date('d-m-Y', $timestamp);
    }

    private function save_asset_meta_from_api($post_id, $data)
    {
        $meta_fields = array(
            'asset_tag' => 'text',
            'model' => 'text',
            'serial_number' => 'text',
            'status' => 'text',
            'assigned_to' => 'text',
            'location' => 'text',
            'rfid_tag' => 'text',
            'warranty_expiry' => 'date',
            'notes' => 'textarea',
            'purchase_date' => 'date',
            'purchase_cost' => 'number'
        );

        // Validate status if provided
        $allowed_statuses = array('new', 'staging', 'ready', 'defected', 'maintenance', 'archived', 'disposed', 'return');

        foreach ($meta_fields as $field => $type) {
            if (isset($data[$field])) {
                $value = $data[$field];
                
                // Type-specific validation and sanitization
                switch ($type) {
                    case 'textarea':
                        $value = sanitize_textarea_field($value);
                        break;
                    case 'number':
                        $value = floatval($value);
                        break;
                    case 'date':
                        $value = sanitize_text_field($value);
                        // Validate date format if not empty
                        if (!empty($value) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && !preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
                            continue 2; // Skip this field if invalid date format
                        }
                        break;
                    default:
                        $value = sanitize_text_field($value);
                        break;
                }
                
                // Additional validation for specific fields
                if ($field === 'status' && !empty($value) && !in_array($value, $allowed_statuses)) {
                    continue; // Skip invalid status
                }
                
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }

    public function enqueue_scripts()
    {
        // Enqueue any frontend scripts if needed
    }

    /**
     * On admin login, set JWT token cookie (ams_token) - Improved version
     */
    public function set_admin_token_cookie($user_login, $user)
    {
        if (in_array('administrator', (array) $user->roles)) {
            $token = $this->generate_jwt_token($user);

            $cookie_options = array(
                'expires' => time() + (24 * 60 * 60), // 24h expiry
                'path' => '/',
                'domain' => parse_url(home_url(), PHP_URL_HOST),
                'secure' => is_ssl(),
                'httponly' => false, // Allow JavaScript access for API calls
                'samesite' => 'Lax'
            );

            if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
                setcookie('ams_token', $token, $cookie_options);
            } else {
                setcookie(
                    'ams_token',
                    $token,
                    $cookie_options['expires'],
                    $cookie_options['path'],
                    $cookie_options['domain'],
                    $cookie_options['secure'],
                    $cookie_options['httponly']
                );
            }

            // Log for debugging (remove in production)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AMS Token cookie set for user: ' . sanitize_text_field($user->user_login));
            }
        }
    }

    /**
     * Convert date from d-m-Y (storage format) to Y-m-d (HTML input format)
     */
    private function convert_date_for_input($date_string)
    {
        if (empty($date_string)) {
            return '';
        }

        // Check if already in Y-m-d format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_string)) {
            return $date_string;
        }

        // Convert from d-m-Y to Y-m-d
        $date = DateTime::createFromFormat('d-m-Y', $date_string);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }

        return sanitize_text_field($date_string);
    }

    /**
     * Convert date from Y-m-d (HTML input format) to d-m-Y (storage format)
     */
    private function convert_date_for_storage($date_string)
    {
        if (empty($date_string)) {
            return '';
        }

        // Check if already in d-m-Y format
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date_string)) {
            return $date_string;
        }

        // Convert from Y-m-d to d-m-Y
        $date = DateTime::createFromFormat('Y-m-d', $date_string);
        if ($date !== false) {
            return $date->format('d-m-Y');
        }

        return sanitize_text_field($date_string);
    }

    /**
     * Rate limiting for API endpoints
     */
    private function check_rate_limit($user_id, $endpoint)
    {
        $transient_key = 'ams_rate_limit_' . $user_id . '_' . md5($endpoint);
        $requests = get_transient($transient_key) ?: 0;
        
        $rate_limit = 100; // requests per hour
        
        if ($requests >= $rate_limit) {
            return new WP_Error('rate_limit_exceeded', 'Rate limit exceeded', array('status' => 429));
        }
        
        set_transient($transient_key, $requests + 1, HOUR_IN_SECONDS);
        return true;
    }
}

// Initialize the plugin
new AMS_REST_API();

/**
 * Activation Hook - Create necessary database tables and default data
 */
register_activation_hook(__FILE__, 'ams_api_activate');

function ams_api_activate()
{
    // Flush rewrite rules
    flush_rewrite_rules();

    // Create default locations with proper sanitization
    $default_locations = array(
        'IT Room', 
        'Office 3', 
        'Office 4', 
        'Kitchen', 
        'Store A', 
        'Store B', 
        'Store C'
    );

    foreach ($default_locations as $location) {
        $sanitized_location = sanitize_text_field($location);
        if (!term_exists($sanitized_location, 'asset_location')) {
            wp_insert_term($sanitized_location, 'asset_location');
        }
    }

    // Set default options
    update_option('ams_api_version', '2.1.0');
    update_option('ams_api_activated', current_time('mysql'));
}

/**
 * Deactivation Hook
 */
register_deactivation_hook(__FILE__, 'ams_api_deactivate');

function ams_api_deactivate()
{
    flush_rewrite_rules();
    
    // Clean up transients
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ams_rate_limit_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ams_rate_limit_%'");
}

/**
 * Additional Security Functions
 */

/**
 * Log security events
 */
function ams_log_security_event($event, $details = array()) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event' => sanitize_text_field($event),
            'details' => $details,
            'user_ip' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')
        );
        
        error_log('AMS Security Event: ' . json_encode($log_entry));
    }
}