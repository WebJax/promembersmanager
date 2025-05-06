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
}
