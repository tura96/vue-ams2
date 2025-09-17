<?php
/**
 * Asset Management System (AMS) REST API Plugin
 * 
 * Plugin Name: AMS REST API
 * Description: Custom REST API for Asset Management System with JWT Authentication
 * Version: 2.0.0
 * Author: Tee
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

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
     * Handle custom admin actions
     */
    public function handle_admin_actions()
    {
        // Check if our custom action is being triggered
        if (isset($_GET['ams_action']) && $_GET['ams_action'] === 'save_all_assets') {
            
            // Verify nonce for security
            // if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'ams_save_all_assets')) {
            //     wp_die('Security check failed');
            // }
            
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
        $args = array(
            'post_type' => 'asset_item',
            'post_status' => 'publish',
            'posts_per_page' => -1, // Get all posts
            'fields' => 'ids', // Only get IDs for efficiency
        );
        
        $asset_ids = get_posts($args);
        $saved_count = 0;
        $errors = 0;
        
        foreach ($asset_ids as $asset_id) {
            // Trigger save_post hook for each asset
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
                    
                    // Optional: Perform additional processing here
                    // For example, validate and fix date formats
                    // $this->validate_and_fix_asset_dates($asset_id);
                    
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

        // Register taxonomies
        // register_taxonomy('asset_category', 'asset_item', array(
        //     'label' => 'Categories',
        //     'rewrite' => array('slug' => 'asset-category'),
        //     'hierarchical' => true,
        //     'show_in_rest' => true,
        // ));

        // register_taxonomy('asset_manufacturer', 'asset_item', array(
        //     'label' => 'Manufacturers',
        //     'rewrite' => array('slug' => 'asset-manufacturer'),
        //     'hierarchical' => true,
        //     'show_in_rest' => true,
        // ));

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

        // Get existing values
        $asset_tag = get_post_meta($post->ID, '_asset_tag', true);
        $model = get_post_meta($post->ID, '_model', true);
        $serial_number = get_post_meta($post->ID, '_serial_number', true);
        $status = get_post_meta($post->ID, '_status', true);
        $assigned_to = get_post_meta($post->ID, '_assigned_to', true);
        $location = get_post_meta($post->ID, '_location', true);
        $rfid_tag = get_post_meta($post->ID, '_rfid_tag', true);
        $warranty_expiry = get_post_meta($post->ID, '_warranty_expiry', true);
        $notes = get_post_meta($post->ID, '_notes', true);
        $purchase_date = get_post_meta($post->ID, '_purchase_date', true);
        $purchase_cost = get_post_meta($post->ID, '_purchase_cost', true);

        // Convert date fields from d-m-Y to Y-m-d for HTML date input
        $warranty_expiry_input = $this->convert_date_for_input($warranty_expiry);
        $purchase_date_input = $this->convert_date_for_input($purchase_date);

        // Get taxonomy terms
        // $categories = get_terms(array('taxonomy' => 'asset_category', 'hide_empty' => false));
        $locations = get_terms(array('taxonomy' => 'asset_location', 'hide_empty' => false));
        $stores = get_terms(array('taxonomy' => 'asset_store', 'hide_empty' => false));
        $models = get_terms(array('taxonomy' => 'asset_model', 'hide_empty' => false));

        // Get current terms for the post
        $current_categories = wp_get_post_terms($post->ID, 'asset_category', array('fields' => 'ids'));
        $current_locations = wp_get_post_terms($post->ID, 'asset_location', array('fields' => 'ids'));
        $current_stores = wp_get_post_terms($post->ID, 'asset_store', array('fields' => 'ids'));
        $current_models = wp_get_post_terms($post->ID, 'asset_model', array('fields' => 'ids'));


        echo '<table class="form-table">';
        echo '<tr><th><label for="asset_tag">Asset Tag</label></th><td><input type="text" id="asset_tag" name="asset_tag" value="' . esc_attr($asset_tag) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="model">Model</label></th><td><input type="text" id="model" name="model" value="' . esc_attr($model) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="serial_number">Serial Number</label></th><td><input type="text" id="serial_number" name="serial_number" value="' . esc_attr($serial_number) . '" class="regular-text" /></td></tr>';

        echo '<tr><th><label for="status">Status</label></th><td>';
        echo '<select id="status" name="status">';
        $statuses = array('new', 'staging', 'ready', 'defected', 'maintenance', 'archived', 'disposed', 'return');
        foreach ($statuses as $status_option) {
            echo '<option value="' . $status_option . '"' . selected($status, $status_option, false) . '>' . ucfirst(str_replace('_', ' ', $status_option)) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th><label for="assigned_to">Assigned To</label></th><td><input type="text" id="assigned_to" name="assigned_to" value="' . esc_attr($assigned_to) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="location">Location</label></th><td><input type="text" id="location" name="location" value="' . esc_attr($location) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="rfid_tag">Rfid tag</label></th><td><input type="text" id="rfid_tag" name="rfid_tag" value="' . esc_attr($rfid_tag) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="warranty_expiry">Warranty Expiry</label></th><td><input type="date" id="warranty_expiry" name="warranty_expiry" value="' . esc_attr($warranty_expiry_input) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="purchase_date">Purchase Date</label></th><td><input type="date" id="purchase_date" name="purchase_date" value="' . esc_attr($purchase_date_input) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="purchase_cost">Purchase Cost</label></th><td><input type="number" step="0.01" id="purchase_cost" name="purchase_cost" value="' . esc_attr($purchase_cost) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="notes">Notes</label></th><td><textarea id="notes" name="notes" rows="4" class="large-text">' . esc_textarea($notes) . '</textarea></td></tr>';
        echo '</table>';
    }

    public function save_asset_meta($post_id)
    {
        if (!isset($_POST['asset_meta_nonce']) || !wp_verify_nonce($_POST['asset_meta_nonce'], 'save_asset_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['post_type']) && 'asset_item' == $_POST['post_type']) {
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
                // update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
                $value = sanitize_text_field($_POST[$field]);
        
                // Convert date fields from Y-m-d (HTML input format) to d-m-Y for storage
                if (in_array($field, array('warranty_expiry', 'purchase_date')) && !empty($value)) {
                    $value = $this->convert_date_for_storage($value);
                }
                
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }

    /**
     * Setup JWT Authentication
     */
    public function setup_jwt_authentication()
    {
        // Add JWT secret to wp-config.php
        if (!defined('JWT_AUTH_SECRET_KEY')) {
            define('JWT_AUTH_SECRET_KEY', '0704648eaf0077561235cf4906d101e0');
        }

        // Enable CORS
        add_action('rest_api_init', function () {
            remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
            add_filter('rest_pre_serve_request', function ($value) {
                header('Access-Control-Allow-Origin: *');
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
                header('Access-Control-Allow-Credentials: true');
                return $value;
            });
        }, 15);
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
     * Authentication Methods
     */
    public function authenticate_user($request)
    {
        $username = sanitize_text_field($request->get_param('username'));
        $password = sanitize_text_field($request->get_param('password'));

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

        if (!$token) {
            // return new WP_Error('missing_token', 'Authorization token is missing' , array('status' => 401));
            return new WP_Error(
                'missing_token',
                'Authorization token is missing. Request params: ' . json_encode($request),
                array('status' => 401)
            );
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

        return $this->jwt_encode($payload, JWT_AUTH_SECRET_KEY);
    }

    private function validate_jwt_token($token)
    {
        try {
            $decoded = $this->jwt_decode($token, JWT_AUTH_SECRET_KEY);
            $user = get_user_by('id', $decoded->data->user->id);

            if (!$user || !in_array('administrator', $user->roles)) {
                return new WP_Error('invalid_token', 'Invalid token or insufficient permissions', array('status' => 401));
            }

            return $user;
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid token in validate_jwt_token', array('status' => 401));
        }
    }

    // private function get_auth_token($request) {
    //     $auth_header = $request->get_header('Authorization');

    //     if (!$auth_header) {
    //         return false;
    //     }

    //     list($bearer, $token) = explode(' ', $auth_header);

    //     if ($bearer !== 'Bearer' || !$token) {
    //         return false;
    //     }

    //     return $token;
    // }
    private function get_auth_token($request)
    {
        $auth_header = $request->get_header('Authorization');

        $debug = [
            'headers' => $request->get_headers(),
            'auth_header' => $auth_header,
        ];

        if (!$auth_header) {
            return new WP_Error(
                'missing_authorization_header',
                'Authorization header is missing',
                ['status' => 401, 'debug' => $debug]
            );
        }

        $parts = explode(' ', $auth_header, 2);
        if (count($parts) !== 2) {
            return new WP_Error(
                'invalid_authorization_format',
                'Authorization header format is invalid',
                ['status' => 401, 'debug' => $debug]
            );
        }

        list($bearer, $token) = $parts;

        if ($bearer !== 'Bearer' || empty($token)) {
            return new WP_Error(
                'invalid_bearer_token',
                'Authorization must use Bearer scheme with a valid token',
                ['status' => 401, 'debug' => $debug]
            );
        }

        // Optional: include token (truncated for security)
        $debug['token_preview'] = substr($token, 0, 20) . '...';

        // You could also attach debug info on success
        // return ['token' => $token, 'debug' => $debug];

        return $token;
    }


    /**
     * Permission Callback
     */
    public function check_admin_permission($request)
    {
        $token = $this->get_auth_token($request);

        if (!$token) {
            // return new WP_Error('missing_token', 'Authorization token is missing', array('status' => 401));
            return new WP_Error(
                'missing_token',
                'Authorization token is missing. Request params: ' . json_encode($request),
                array('status' => 401)
            );
        }

        $user = $this->validate_jwt_token($token);

        if (is_wp_error($user)) {
            return $user;
        }

        return true;
    }

    /**
     * Asset CRUD Operations
     */
    // public function get_assets($request) {
    //     $page = $request->get_param('page') ?: 1;
    //     $per_page = $request->get_param('per_page') ?: 10;
    //     $search = $request->get_param('search');
    //     $status = $request->get_param('status');
    //     $model = $request->get_param('model');
    //     $rfid_tag = $request->get_param('rfid_tag');
    //     $sort_by = $request->get_param('sort_by') ?: 'date';
    //     $sort_order = $request->get_param('sort_order') ?: 'DESC';

    //     $args = array(
    //         'post_type' => 'asset_item',
    //         'post_status' => 'publish',
    //         'posts_per_page' => $per_page,
    //         'paged' => $page,
    //         'orderby' => $sort_by,
    //         'order' => $sort_order,
    //     );

    //     // Add search
    //     if ($search) {
    //         $args['s'] = $search;
    //     }

    //     // Add meta query for filters
    //     $meta_query = array();

    //     if ($status) {
    //         $meta_query[] = array(
    //             'key' => '_status',
    //             'value' => $status,
    //             'compare' => '='
    //         );
    //     }

    //     if ($model) {
    //         $meta_query[] = array(
    //             'key' => '_model',
    //             'value' => $model,
    //             'compare' => 'LIKE'
    //         );
    //     }

    //     if ($rfid_tag) {
    //         $meta_query[] = array(
    //             'key' => '_rfid_tag',
    //             'value' => $rfid_tag,
    //             'compare' => 'LIKE'
    //         );
    //     }

    //     if (count($meta_query) > 0) {
    //         $args['meta_query'] = $meta_query;
    //     }

    //     $query = new WP_Query($args);
    //     $assets = array();

    //     foreach ($query->posts as $post) {
    //         $assets[] = $this->format_asset_data($post);
    //     }

    //     return array(
    //         'success' => true,
    //         'data' => $assets,
    //         'pagination' => array(
    //             'current_page' => $page,
    //             'per_page' => $per_page,
    //             'total_items' => $query->found_posts,
    //             'total_pages' => $query->max_num_pages,
    //         ),
    //     );
    // }


    public function get_assets($request)
    {
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 10;
        $search = $request->get_param('search');
        $status = $request->get_param('status');
        $model = $request->get_param('model');
        $rfid_tag = $request->get_param('rfid_tag');
        $location = $request->get_param('location');
        $assigned_to = $request->get_param('assigned_to');
        $sort_by = $request->get_param('sort_by') ?: 'date';
        $sort_order = $request->get_param('sort_order') ?: 'DESC';

        $args = array(
            'post_type' => 'asset_item',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'order' => $sort_order,
        );

        // Handle different sort types
        $meta_sort_fields = array('model', 'serial_number', 'warranty_expiry');

        if (in_array($sort_by, $meta_sort_fields)) {
            // Sorting by meta field
            $args['meta_key'] = '_' . $sort_by;
            $args['orderby'] = 'meta_value';

            // For date fields, use meta_value_datetime
            if (in_array($sort_by, array('warranty_expiry', 'purchase_date'))) {
                $args['orderby'] = 'meta_value_datetime';
            }

            // For numeric fields, use meta_value_num
            if (in_array($sort_by, array('purchase_cost'))) {
                $args['orderby'] = 'meta_value_num';
            }
        } else {
            // Default WordPress fields
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

        // Add search
        // if ($search) {
        //     $args['s'] = $search;
        // }

        // Add meta query for filters
        $meta_query = array();

        // Add search across metadata fields
        if ($search) {
            // Add default WordPress search for post_title and post_content
            $args['s'] = $search;

            // Define metadata fields to search
            $searchable_meta_fields = array(
                '_asset_tag',
                '_serial_number',
                '_assigned_to',
                '_title',
            );

            // Create meta_query for search
            $search_meta_query = array(
                'relation' => 'OR',
            );

            foreach ($searchable_meta_fields as $meta_key) {
                $search_meta_query[] = array(
                    'key' => $meta_key,
                    'value' => $search,
                    'compare' => 'LIKE',
                );
            }

            // Add search meta_query to main meta_query
            $meta_query[] = $search_meta_query;
        }

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
            $args['meta_query'] = $meta_query;

            // If we have multiple meta queries, set relation
            if (count($meta_query) > 1) {
                $args['meta_query']['relation'] = 'AND';
            }
        }

        $query = new WP_Query($args);
        $assets = array();

        foreach ($query->posts as $post) {
            $assets[] = $this->format_asset_data($post);
        }

        // Optional: Add custom sorting for complex cases (if needed)
        // if (!in_array($sort_by, array_merge($meta_sort_fields, array('title', 'date')))) {
        //     // Custom sorting logic for special cases
        //     switch ($sort_by) {
        //         case 'status_priority':
        //             // Example: Custom status priority sorting
        //             $status_priority = array('new' => 1, 'staging' => 2, 'ready' => 3, 'assigned' => 4, 'maintenance' => 5, 'defected' => 6, 'archived' => 7, 'disposed' => 8);
        //             usort($assets, function ($a, $b) use ($status_priority, $sort_order) {
        //                 $a_priority = $status_priority[$a['status']] ?? 999;
        //                 $b_priority = $status_priority[$b['status']] ?? 999;

        //                 if ($sort_order === 'ASC') {
        //                     return $a_priority <=> $b_priority;
        //                 } else {
        //                     return $b_priority <=> $a_priority;
        //                 }
        //             });
        //             break;
        //     }
        // }

        return array(
            'success' => true,
            'data' => $assets,
            'pagination' => array(
                'current_page' => (int) $page,
                'per_page' => (int) $per_page,
                'total_items' => $query->found_posts,
                'total_pages' => $query->max_num_pages,
            ),
            'sort' => array(
                'sort_by' => $sort_by,
                'sort_order' => $sort_order,
            ),
        );
    }

    public function get_asset($request)
    {
        $id = $request->get_param('id');
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
        $data = json_decode($request->get_body(), true);

        if (!$data) {
            return new WP_Error('invalid_data', 'Invalid JSON data', array('status' => 400));
        }

        $post_data = array(
            'post_type' => 'asset_item',
            'post_title' => sanitize_text_field($data['title'] ?? 'New Asset'),
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
        $id = $request->get_param('id');
        $data = json_decode($request->get_body(), true);

        if (!$data) {
            return new WP_Error('invalid_data', 'Invalid JSON data', array('status' => 400));
        }

        $post = get_post($id);

        if (!$post || $post->post_type !== 'asset_item') {
            return new WP_Error('asset_not_found', 'Asset not found', array('status' => 404));
        }

        $post_data = array(
            'ID' => $id,
            'post_title' => sanitize_text_field($data['title'] ?? $post->post_title),
            'post_content' => sanitize_textarea_field($data['description'] ?? $post->post_content),
        );

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
        $id = $request->get_param('id');
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
        $data = json_decode($request->get_body(), true);
        $asset_ids = $data['asset_ids'] ?? array();

        if (empty($asset_ids)) {
            return new WP_Error('missing_assets', 'No assets specified for bulk deploy', array('status' => 400));
        }

        $updated_count = 0;

        foreach ($asset_ids as $asset_id) {
            $post = get_post($asset_id);
            if ($post && $post->post_type === 'asset_item') {
                update_post_meta($asset_id, '_status', 'assigned');
                $updated_count++;
            }
        }

        return array(
            'success' => true,
            'message' => sprintf('%d assets assigned successfully', $updated_count),
            'updated_count' => $updated_count,
        );
    }

    /**
     * Metadata Methods
     */
    public function get_statuses($request)
    {
        return array(
            'success' => true,
            'data' => array(
                'new' => 'New',
                'staging' => 'Staging',
                'ready' => 'Ready to Deploy',
                // 'assigned' => 'assigned',
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

        $models = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_model' 
            AND meta_value != '' 
            ORDER BY meta_value ASC
        ");

        return array(
            'success' => true,
            'data' => $models,
        );
    }

    public function get_locations($request)
    {
        global $wpdb;

        $locations = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_location' 
            AND meta_value != '' 
            ORDER BY meta_value ASC
        ");

        return array(
            'success' => true,
            'data' => $locations,
        );
    }

    public function get_assigned($request)
    {
        global $wpdb;

        $locations = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_assigned_to' 
            AND meta_value != '' 
            ORDER BY meta_value ASC
        ");

        return array(
            'success' => true,
            'data' => $locations,
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
            'id' => $post->ID,
            'title' => $post->post_title,
            'description' => $post->post_content,
            'date_created' => $this->format_date($post->post_date),
            'date_modified' => $this->format_date($post->post_modified),
        );

        foreach ($meta_fields as $field) {
            $asset_data[$field] = get_post_meta($post->ID, '_' . $field, true);
            // Format specific date fields
            // if (in_array($field, ['purchase_date', 'warranty_expiry']) && !empty($value)) {
            //     $asset_data[$field] = $this->format_date($value);
            // } else {
            //     $asset_data[$field] = $value;
            // }
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

        // Handle different date formats
        $timestamp = strtotime($date_string);

        if ($timestamp === false) {
            return $date_string; // Return original if can't parse
        }

        return date('d-m-Y', $timestamp);
    }

    private function save_asset_meta_from_api($post_id, $data)
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

        foreach ($meta_fields as $field) {
            if (isset($data[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($data[$field]));
            }
        }
    }

    /**
     * Simple JWT Implementation (for production, use Firebase JWT library)
     */
    private function jwt_encode($payload, $key)
    {
        $header = json_encode(array('typ' => 'JWT', 'alg' => 'HS256'));
        $payload = json_encode($payload);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $key, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    private function jwt_decode($jwt, $key)
    {
        if (!is_string($jwt) || empty($jwt)) {
            throw new Exception('Invalid JWT input: expected string, got ' . gettype($jwt));
        }

        $parts = explode('.', $jwt);

        if (count($parts) != 3) {
            throw new Exception('Invalid token');
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Signature));
        $expectedSignature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $key, true);

        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Invalid signature');
        }

        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)));

        if ($payload->exp < time()) {
            throw new Exception('Token expired');
        }

        return $payload;
    }

    public function enqueue_scripts()
    {
        // Enqueue any frontend scripts if needed
    }

    /**
     * On admin login, set JWT token cookie (ams_token)
     */
    public function set_admin_token_cookie($user_login, $user)
    {
        if (in_array('administrator', (array) $user->roles)) {
            $token = $this->generate_jwt_token($user);

            // More explicit cookie settings
            $cookie_options = array(
                'expires' => time() + (24 * 60 * 60), // 24h expiry
                'path' => '/',
                'domain' => '', // Let it use the current domain
                'secure' => is_ssl(), // Only on HTTPS if available
                'httponly' => false, // Allow JavaScript access
                'samesite' => 'Lax' // Allow cross-site requests
            );

            // Set cookie using setcookie with options array (PHP 7.3+)
            if (version_compare(PHP_VERSION, '7.3.0', '>=')) {
                setcookie('ams_token', $token, $cookie_options);
            } else {
                // Fallback for older PHP versions
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

            // Debug log
            error_log('AMS Token cookie set for user: ' . $user->user_login);
            error_log('Token preview: ' . substr($token, 0, 20) . '...');
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

        return $date_string; // Return original if can't parse
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

        return $date_string; // Return original if can't parse
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

    // Create default categories, manufacturers, and locations
    $default_categories = array('Computers', 'Printers', 'Furniture', 'Appliances');
    $default_manufacturers = array('Dell', 'HP', 'Lenovo', 'Samsung', 'Epson');
    $default_locations = array('IT Room', 'Office 3', 'Office 4', 'Kitchen', 'Store A', 'Store B', 'Store C');

    foreach ($default_categories as $category) {
        wp_insert_term($category, 'asset_category');
    }

    // foreach ($default_manufacturers as $manufacturer) {
    //     wp_insert_term($manufacturer, 'asset_manufacturer');
    // }

    foreach ($default_locations as $location) {
        wp_insert_term($location, 'asset_location');
    }
}

/**
 * Deactivation Hook
 */
register_deactivation_hook(__FILE__, 'ams_api_deactivate');

function ams_api_deactivate()
{
    flush_rewrite_rules();
}