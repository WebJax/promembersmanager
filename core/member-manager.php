<?php
namespace ProMembersManager\Core;

defined('ABSPATH') || exit;

class Member_Manager {
    private $db;
    private $member_types = [
        'private' => [
            'auto' => 9503,
            'manual' => 10968
        ],
        'pension' => [
            'auto' => 28736,
            'manual' => 28735
        ],
        'union' => [
            'auto' => 30734,
            'manual' => 19221
        ]
    ];

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        $this->init_hooks();
    }

    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_pmm_edit_member', [$this, 'handle_edit_member']);
        add_action('wp_ajax_pmm_save_member', [$this, 'handle_save_member']);
        add_action('wp_ajax_pmm_create_member', [$this, 'handle_create_member']);
        
        // WooCommerce account integration
        add_filter('woocommerce_account_menu_items', [$this, 'add_member_list_endpoint']);
        add_action('init', [$this, 'add_endpoints']);
        add_action('woocommerce_account_member-list_endpoint', [$this, 'render_member_list']);
    }

    public function get_members($args = []) {
        $defaults = [
            'from_date' => date('Y-m-d', strtotime('-1 year')),
            'to_date' => date('Y-m-d'),
            'member_type' => '',
            'per_page' => 20,
            'page' => 1,
            'search' => ''
        ];

        $args = wp_parse_args($args, $defaults);
        
        // Get completed orders within date range
        $orders = wc_get_orders([
            'date_paid' => $args['from_date'] . '...' . $args['to_date'],
            'status' => 'completed',
            'limit' => $args['per_page'],
            'page' => $args['page']
        ]);

        $members = [];
        foreach ($orders as $order) {
            // Get member data
            $member = $this->prepare_member_data($order);
            
            // Filter by member type if specified
            if (!empty($args['member_type']) && $member['type'] !== $args['member_type']) {
                continue;
            }
            
            // Filter by search term if specified
            if (!empty($args['search'])) {
                $search_in = $member['name'] . ' ' . $member['email'] . ' ' . $member['company'];
                if (stripos($search_in, $args['search']) === false) {
                    continue;
                }
            }
            
            $members[] = $member;
        }

        return $members;
    }

    private function prepare_member_data($order) {
        $user_id = $order->get_user_id();
        $user = get_userdata($user_id);

        $member = [
            'id' => $order->get_id(),
            'user_id' => $user_id,
            'name' => $order->get_formatted_billing_full_name(),
            'email' => $order->get_billing_email(),
            'company' => $order->get_billing_company(),
            'address' => [
                'line1' => $order->get_billing_address_1(),
                'line2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country()
            ],
            'phone' => $order->get_billing_phone(),
            'joined_date' => $user->user_registered,
            'subscription_details' => $this->get_subscription_details($order),
            'payment_method' => $order->get_payment_method_title(),
            'payment_id' => $order->get_transaction_id()
        ];

        return $member;
    }

    private function get_subscription_details($order) {
        $details = [];
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $details = [
                'product_id' => $product_id,
                'quantity' => $item->get_quantity(),
                'type' => $this->get_member_type($product_id),
                'renewal_type' => $this->get_renewal_type($product_id)
            ];
        }
        return $details;
    }

    private function get_member_type($product_id) {
        foreach ($this->member_types as $type => $products) {
            if (in_array($product_id, $products)) {
                return $type;
            }
        }
        return 'unknown';
    }

    private function get_renewal_type($product_id) {
        foreach ($this->member_types as $products) {
            if ($product_id === $products['auto']) {
                return 'auto';
            } elseif ($product_id === $products['manual']) {
                return 'manual';
            }
        }
        return 'unknown';
    }

    public function handle_edit_member() {
        check_ajax_referer('pmm-edit-member', 'nonce');
        
        if (!current_user_can('edit_users')) {
            wp_send_json_error(['message' => __('Permission denied.', 'pro-members-manager')]);
        }

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        if (!$order_id) {
            wp_send_json_error(['message' => __('Invalid order ID.', 'pro-members-manager')]);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => __('Order not found.', 'pro-members-manager')]);
        }

        $member = $this->prepare_member_data($order);
        wp_send_json_success(['member' => $member]);
    }

    public function handle_save_member() {
        check_ajax_referer('pmm-save-member', 'nonce');
        
        if (!current_user_can('edit_users')) {
            wp_send_json_error(['message' => __('Permission denied.', 'pro-members-manager')]);
        }

        // Validate and sanitize input
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $data = $this->sanitize_member_data($_POST);

        if (!$order_id || empty($data)) {
            wp_send_json_error(['message' => __('Invalid data provided.', 'pro-members-manager')]);
        }

        // Update order
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => __('Order not found.', 'pro-members-manager')]);
        }

        // Update order data
        $order->set_billing_first_name($data['first_name']);
        $order->set_billing_last_name($data['last_name']);
        $order->set_billing_company($data['company']);
        $order->set_billing_address_1($data['address_1']);
        $order->set_billing_address_2($data['address_2']);
        $order->set_billing_city($data['city']);
        $order->set_billing_postcode($data['postcode']);
        $order->set_billing_phone($data['phone']);
        $order->set_billing_email($data['email']);
        
        $order->save();

        // Update user meta
        $user_id = $order->get_user_id();
        if ($user_id) {
            update_user_meta($user_id, 'first_name', $data['first_name']);
            update_user_meta($user_id, 'last_name', $data['last_name']);
            update_user_meta($user_id, 'billing_first_name', $data['first_name']);
            update_user_meta($user_id, 'billing_last_name', $data['last_name']);
            update_user_meta($user_id, 'billing_company', $data['company']);
            update_user_meta($user_id, 'billing_address_1', $data['address_1']);
            update_user_meta($user_id, 'billing_address_2', $data['address_2']);
            update_user_meta($user_id, 'billing_city', $data['city']);
            update_user_meta($user_id, 'billing_postcode', $data['postcode']);
            update_user_meta($user_id, 'billing_phone', $data['phone']);
            update_user_meta($user_id, 'billing_email', $data['email']);
        }

        wp_send_json_success([
            'message' => __('Member updated successfully.', 'pro-members-manager'),
            'member' => $this->prepare_member_data($order)
        ]);
    }

    public function handle_create_member() {
        check_ajax_referer('pmm-create-member', 'nonce');
        
        if (!current_user_can('edit_users')) {
            wp_send_json_error(['message' => __('Permission denied.', 'pro-members-manager')]);
        }

        // Validate and sanitize input
        $data = [
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
            'company' => sanitize_text_field($_POST['company'] ?? ''),
            'address_1' => sanitize_text_field($_POST['address_1'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'postcode' => sanitize_text_field($_POST['postcode'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'product_id' => absint($_POST['product_id'] ?? 0)
        ];

        // Verify required fields
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['product_id'])) {
            wp_send_json_error(['message' => __('Required fields missing.', 'pro-members-manager')]);
        }

        // Create a new order
        $order = wc_create_order();

        // Add product to order
        $product = wc_get_product($data['product_id']);
        if (!$product) {
            wp_send_json_error(['message' => __('Invalid product.', 'pro-members-manager')]);
        }
        
        $order->add_product($product, 1);

        // Set order billing details
        $address = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'company' => $data['company'],
            'address_1' => $data['address_1'],
            'city' => $data['city'],
            'postcode' => $data['postcode'],
            'phone' => $data['phone'],
            'country' => 'DK'
        ];
        
        $order->set_address($address, 'billing');
        
        // Get current user as creator
        $current_user = wp_get_current_user();
        $email = $current_user->user_email;
        
        // Calculate totals and complete order
        $order->calculate_totals();
        $order->update_status('completed', __('Manually created membership by', 'pro-members-manager') . ' ' . $email, true);
        
        $order_id = $order->get_id();
        
        // Create a user for this membership with a generated email
        $username = strtolower($data['first_name']) . '_' . $order_id;
        $email = $order_id . '@' . parse_url(home_url(), PHP_URL_HOST);
        $password = wp_generate_password(12, false);
        
        $user_id = wc_create_new_customer($email, $username, $password);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }
        
        // Link user to order
        update_post_meta($order_id, '_customer_user', $user_id);
        
        // Update user meta
        update_user_meta($user_id, 'first_name', $data['first_name']);
        update_user_meta($user_id, 'last_name', $data['last_name']);
        update_user_meta($user_id, 'billing_first_name', $data['first_name']);
        update_user_meta($user_id, 'billing_last_name', $data['last_name']);
        update_user_meta($user_id, 'billing_company', $data['company']);
        update_user_meta($user_id, 'billing_address_1', $data['address_1']);
        update_user_meta($user_id, 'billing_city', $data['city']);
        update_user_meta($user_id, 'billing_postcode', $data['postcode']);
        update_user_meta($user_id, 'billing_phone', $data['phone']);
        update_user_meta($user_id, 'billing_country', 'DK');
        
        wp_send_json_success([
            'message' => __('Member created successfully.', 'pro-members-manager'),
            'order_id' => $order_id,
            'user_id' => $user_id
        ]);
    }

    private function sanitize_member_data($data) {
        return [
            'first_name' => sanitize_text_field($data['first_name'] ?? ''),
            'last_name' => sanitize_text_field($data['last_name'] ?? ''),
            'company' => sanitize_text_field($data['company'] ?? ''),
            'address_1' => sanitize_text_field($data['address_1'] ?? ''),
            'address_2' => sanitize_text_field($data['address_2'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'postcode' => sanitize_text_field($data['postcode'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'email' => sanitize_email($data['email'] ?? '')
        ];
    }

    // WooCommerce Endpoint for member list
    public function add_member_list_endpoint($menu_items) {
        if (current_user_can('edit_users') || current_user_can('viewuserlist')) {
            $menu_items = array_slice($menu_items, 0, 5, true) 
                + ['member-list' => __('Member List', 'pro-members-manager')]
                + array_slice($menu_items, 5, null, true);
        }
        return $menu_items;
    }

    public function add_endpoints() {
        add_rewrite_endpoint('member-list', EP_ROOT | EP_PAGES);
    }

    public function render_member_list() {
        if (!current_user_can('edit_users') && !current_user_can('viewuserlist')) {
            echo '<p>' . __('You do not have permission to view this page.', 'pro-members-manager') . '</p>';
            return;
        }

        // Get statistics
        $from_date = date('Y-m-d', strtotime('-1 year'));
        $to_date = date('Y-m-d');
        
        $args = [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'per_page' => -1
        ];
        
        $members = $this->get_members($args);
        
        // Count statistics
        $stats = $this->get_member_statistics($members);
        
        // Include template
        include PMM_PLUGIN_DIR . 'templates/member-list.php';
    }

    public function get_member_statistics($members) {
        $stats = [
            'private' => [
                'total' => 0,
                'auto' => [
                    'total' => 0,
                    'pension' => 0,
                    'regular' => 0
                ],
                'manual' => [
                    'total' => 0,
                    'pension' => 0,
                    'regular' => 0
                ]
            ],
            'union' => [
                'total' => 0,
                'in_dianalund' => 0,
                'outside_dianalund' => 0
            ]
        ];
        
        foreach ($members as $member) {
            $type = $member['subscription_details']['type'];
            $renewal = $member['subscription_details']['renewal_type'];
            $quantity = $member['subscription_details']['quantity'];
            
            if ($type === 'private' || $type === 'pension') {
                $stats['private']['total'] += $quantity;
                
                if ($renewal === 'auto') {
                    $stats['private']['auto']['total'] += $quantity;
                    
                    if ($type === 'pension') {
                        $stats['private']['auto']['pension'] += $quantity;
                    } else {
                        $stats['private']['auto']['regular'] += $quantity;
                    }
                } else {
                    $stats['private']['manual']['total'] += $quantity;
                    
                    if ($type === 'pension') {
                        $stats['private']['manual']['pension'] += $quantity;
                    } else {
                        $stats['private']['manual']['regular'] += $quantity;
                    }
                }
            } elseif ($type === 'union') {
                $stats['union']['total'] += $quantity;
                
                if ($member['address']['postcode'] === '4293') {
                    $stats['union']['in_dianalund'] += $quantity;
                } else {
                    $stats['union']['outside_dianalund'] += $quantity;
                }
            }
        }
        
        return $stats;
    }

    public function export_members_csv($members) {
        $filename = 'members-export-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fputs($output, "\xEF\xBB\xBF");
        
        // Headers
        fputcsv($output, [
            __('First Name', 'pro-members-manager'),
            __('Last Name', 'pro-members-manager'),
            __('Company', 'pro-members-manager'),
            __('Email', 'pro-members-manager'),
            __('Address', 'pro-members-manager'),
            __('Postal Code', 'pro-members-manager'),
            __('City', 'pro-members-manager'),
            __('Phone', 'pro-members-manager'),
            __('Joined Date', 'pro-members-manager'),
            __('Membership Type', 'pro-members-manager'),
            __('Quantity', 'pro-members-manager'),
            __('Payment Method', 'pro-members-manager'),
            __('Transaction ID', 'pro-members-manager')
        ], ';');
        
        foreach ($members as $member) {
            $row = [
                $member['first_name'],
                $member['last_name'],
                $member['company'],
                $member['email'],
                $member['address']['line1'],
                $member['address']['postcode'],
                $member['address']['city'],
                $member['phone'],
                date('d-m-Y', strtotime($member['joined_date'])),
                $this->get_member_type_label($member['subscription_details']['type'], $member['subscription_details']['renewal_type']),
                $member['subscription_details']['quantity'],
                $member['payment_method'],
                $member['payment_id']
            ];
            
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
    }
    
    private function get_member_type_label($type, $renewal_type) {
        $types = [
            'private' => $renewal_type === 'auto' ? __('Auto Private', 'pro-members-manager') : __('Manual Private', 'pro-members-manager'),
            'pension' => $renewal_type === 'auto' ? __('Auto Pension', 'pro-members-manager') : __('Manual Pension', 'pro-members-manager'),
            'union' => $renewal_type === 'auto' ? __('Auto Union', 'pro-members-manager') : __('Manual Union', 'pro-members-manager')
        ];
        
        return $types[$type] ?? __('Unknown', 'pro-members-manager');
    }
}
