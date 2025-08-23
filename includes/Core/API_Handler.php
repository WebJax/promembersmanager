<?php
namespace ProMembersManager\Core;

defined('ABSPATH') || exit;

/**
 * Handles API requests for the Pro Members Manager plugin
 */
class API_Handler {
    private $api_endpoint = 'https://api.quickpay.net/subscriptions/';
    private $api_key;
    
    public function __construct() {
        $this->api_key = $this->get_api_key();
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('wp_ajax_pmm_check_payment_status', [$this, 'check_payment_status']);
        add_action('pmm_daily_payment_check', [$this, 'scheduled_payment_check']);
        
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Get the API key from plugin settings
     * 
     * @return string API key
     */
    private function get_api_key() {
        return get_option('pmm_quickpay_api_key', 'fa61899694ac3b9b36a0c14263e93db0e8d3e22dedfe62e81b78f20bc223c0b9');
    }
    
    /**
     * Get payment information from the payment gateway
     * 
     * @param string $transaction_id
     * @return array|false Payment data or false on failure
     */
    public function get_payment_info($transaction_id) {
        if (empty($transaction_id)) {
            return false;
        }
        
        $url = $this->api_endpoint . $transaction_id;
        
        $args = [
            'headers' => [
                'Accept-Version' => 'v10',
                'Authorization' => 'Basic ' . base64_encode(':' . $this->api_key)
            ]
        ];
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data) || isset($data['error'])) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * Handle AJAX request to check payment status
     */
    public function check_payment_status() {
        check_ajax_referer('pmm_check_payment', 'nonce');
        
        if (!current_user_can('edit_users')) {
            wp_send_json_error(['message' => __('Permission denied.', 'pro-members-manager')]);
        }
        
        $transaction_id = isset($_POST['transaction_id']) ? sanitize_text_field($_POST['transaction_id']) : '';
        
        $payment_info = $this->get_payment_info($transaction_id);
        
        if ($payment_info) {
            wp_send_json_success([
                'payment_info' => $payment_info,
                'status' => $payment_info['state'] ?? 'unknown',
                'formatted' => $this->format_payment_info($payment_info)
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to fetch payment information.', 'pro-members-manager')]);
        }
    }
    
    /**
     * Format payment information for display
     * 
     * @param array $payment_info
     * @return array Formatted payment info
     */
    private function format_payment_info($payment_info) {
        $formatted = [
            'id' => $payment_info['id'] ?? '',
            'status' => $payment_info['state'] ?? '',
            'card_type' => isset($payment_info['link']['card_type']) ? ucfirst($payment_info['link']['card_type']) : '',
            'last4' => $payment_info['link']['masked_card'] ?? '',
            'expiry' => isset($payment_info['link']['exp_month'], $payment_info['link']['exp_year']) ? 
                sprintf('%02d/%s', $payment_info['link']['exp_month'], substr($payment_info['link']['exp_year'], -2)) : '',
            'created' => isset($payment_info['created_at']) ? date('d-m-Y H:i', strtotime($payment_info['created_at'])) : '',
            'next_charge' => isset($payment_info['next_scheduled_at']) ? date('d-m-Y', strtotime($payment_info['next_scheduled_at'])) : ''
        ];
        
        return $formatted;
    }
    
    /**
     * Run scheduled payment check for all members
     */
    public function scheduled_payment_check() {
        $args = [
            'per_page' => -1,
            'from_date' => date('Y-m-d', strtotime('-1 month')),
            'to_date' => date('Y-m-d')
        ];
        
        $database = new Database();
        $members = $database->get_members($args);
        
        $failures = [];
        
        foreach ($members as $member) {
            $transaction_id = $member['betalingid'];
            
            if (empty($transaction_id)) {
                continue;
            }
            
            $payment_info = $this->get_payment_info($transaction_id);
            
            if ($payment_info && isset($payment_info['state']) && $payment_info['state'] !== 'active') {
                $failures[] = [
                    'member_id' => $member['id'],
                    'user_id' => $member['userid'],
                    'name' => $member['firstname'] . ' ' . $member['lastname'],
                    'email' => $member['email'],
                    'payment_id' => $transaction_id,
                    'status' => $payment_info['state']
                ];
            }
        }
        
        if (!empty($failures)) {
            $this->notify_admin_of_payment_failures($failures);
        }
    }
    
    /**
     * Send notification to admin about payment failures
     * 
     * @param array $failures List of failed payments
     */
    private function notify_admin_of_payment_failures($failures) {
        $admin_email = get_option('admin_email');
        
        $subject = sprintf(
            __('[%s] Payment Status Alert: %d Issues Found', 'pro-members-manager'),
            get_bloginfo('name'),
            count($failures)
        );
        
        $message = sprintf(
            __('The following members have payment issues with their subscriptions as of %s:', 'pro-members-manager'),
            date_i18n(get_option('date_format'))
        );
        
        $message .= "\n\n";
        
        foreach ($failures as $failure) {
            $message .= sprintf(
                "%s (ID: %d, Email: %s) - Payment Status: %s\n",
                $failure['name'],
                $failure['user_id'],
                $failure['email'],
                $failure['status']
            );
        }
        
        $message .= "\n\n";
        $message .= __('Please log in to check and address these issues.', 'pro-members-manager');
        $message .= "\n";
        $message .= admin_url('admin.php?page=pro-members-manager');
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_rest_routes() {
        register_rest_route('pro-members-manager/v1', '/members', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_members'],
            'permission_callback' => [$this, 'api_permissions_check']
        ]);
        
        register_rest_route('pro-members-manager/v1', '/members/statistics', [
            'methods' => 'GET',
            'callback' => [$this, 'api_get_statistics'],
            'permission_callback' => [$this, 'api_permissions_check']
        ]);
    }
    
    /**
     * Permission callback for REST API
     * 
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function api_permissions_check($request) {
        // Check if user has appropriate capability
        if (current_user_can('edit_users') || current_user_can('viewuserlist')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * API endpoint to get members
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function api_get_members($request) {
        $from_date = $request->get_param('from_date') ?? date('Y-m-d', strtotime('-1 year'));
        $to_date = $request->get_param('to_date') ?? date('Y-m-d');
        $type = $request->get_param('type') ?? '';
        
        $args = [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'per_page' => -1
        ];
        
        if (!empty($type)) {
            $args['member_type'] = $type;
        }
        
        $database = new Database();
        $members = $database->get_members($args);
        
        // Remove sensitive data for API response
        $sanitized_members = [];
        foreach ($members as $member) {
            $sanitized_members[] = [
                'id' => $member['id'],
                'name' => $member['firstname'] . ' ' . $member['lastname'],
                'company' => $member['company'],
                'city' => $member['by'],
                'postcode' => $member['postnr'],
                'joined_date' => $member['date'],
                'membership_type' => $member['produkt-id'],
                'quantity' => $member['antal']
            ];
        }
        
        return rest_ensure_response([
            'count' => count($sanitized_members),
            'members' => $sanitized_members
        ]);
    }
    
    /**
     * API endpoint to get statistics
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function api_get_statistics($request) {
        $from_date = $request->get_param('from_date') ?? date('Y-m-d', strtotime('-1 year'));
        $to_date = $request->get_param('to_date') ?? date('Y-m-d');
        
        $args = [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'per_page' => -1
        ];
        
        $stats_manager = new Stats_Manager();
        $stats = $stats_manager->get_stats($args);
        
        return rest_ensure_response([
            'private' => [
                'total' => $stats['private']['total'],
                'auto' => $stats['private']['auto']['total'],
                'manual' => $stats['private']['manual']['total'],
            ],
            'union' => [
                'total' => $stats['union']['total'],
                'in_dianalund' => $stats['union']['in_dianalund'],
                'outside_dianalund' => $stats['union']['outside_dianalund'],
            ],
            'total_members' => $stats['total']
        ]);
    }
}