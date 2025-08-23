<?php
namespace ProMembersManager\Core;

defined('ABSPATH') || exit;

/**
 * Handles CSV exports and imports for the Pro Members Manager plugin
 */
class CSV_Handler {
    private $database;
    
    public function __construct() {
        $this->init_hooks();
        $this->database = new Database();
    }
    
    private function init_hooks() {
        add_action('admin_post_pmm_export_members', [$this, 'process_export']);
        add_action('wp_ajax_pmm_generate_csv', [$this, 'ajax_generate_csv']);
        
        // Handle template hooks
        add_action('pmm_after_member_list', [$this, 'add_export_buttons']);
    }
    
    /**
     * Process the export request
     */
    public function process_export() {
        if (!current_user_can('edit_users') && !current_user_can('viewuserlist')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'pro-members-manager'));
        }
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'pmm_export_members')) {
            wp_die(__('Security check failed. Please try again.', 'pro-members-manager'));
        }
        
        // Get parameters
        $from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : date('Y-m-d', strtotime('-1 year'));
        $to_date = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : date('Y-m-d');
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        
        // Get members
        $args = [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'member_type' => $type,
            'per_page' => -1
        ];
        
        $members = $this->database->get_members($args);
        
        // Generate filename
        $filename = 'members-export';
        if (!empty($type)) {
            $filename .= '-' . $type;
        }
        $filename .= '-' . date('Y-m-d') . '.csv';
        
        // Set headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fputs($output, "\xEF\xBB\xBF");
        
        // Headers
        fputcsv($output, [
            __('First Name', 'pro-members-manager'),
            __('Last Name', 'pro-members-manager'),
            __('Company/Organization', 'pro-members-manager'),
            __('Email', 'pro-members-manager'),
            __('Address', 'pro-members-manager'),
            __('Postal Code', 'pro-members-manager'),
            __('City', 'pro-members-manager'),
            __('Phone', 'pro-members-manager'),
            __('Joined Date', 'pro-members-manager'),
            __('Membership Type', 'pro-members-manager'),
            __('Quantity', 'pro-members-manager'),
            __('Payment Method', 'pro-members-manager')
        ], ';');
        
        foreach ($members as $member) {
            $row = [
                $member['name'],
                $member['last_name'] ?? '',
                $member['company'],
                $member['email'],
                $member['address']['line1'],
                $member['address']['postcode'],
                $member['address']['city'],
                $member['phone'],
                date('d-m-Y', strtotime($member['joined_date'])),
                $this->get_membership_type_label($member['subscription_details']['product_id']),
                $member['subscription_details']['quantity'],
                $member['payment_method']
            ];
            
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Add export buttons to the member list page
     */
    public function add_export_buttons() {
        $nonce = wp_create_nonce('pmm_export_members');
        
        // Current date parameters
        $from_date = date('Y-m-d', strtotime('-1 year'));
        $to_date = date('Y-m-d');
        
        $export_url = add_query_arg([
            'action' => 'pmm_export_members',
            'from_date' => $from_date,
            'to_date' => $to_date,
            '_wpnonce' => $nonce
        ], admin_url('admin-post.php'));
        
        ?>
        <div class="pmm-export-buttons">
            <h3><?php _e('Export Member Lists', 'pro-members-manager'); ?></h3>
            <p><?php _e('Download member data in CSV format', 'pro-members-manager'); ?></p>
            
            <a href="<?php echo esc_url($export_url); ?>" class="button button-primary">
                <?php _e('Export All Members', 'pro-members-manager'); ?>
            </a>
            
            <a href="<?php echo esc_url(add_query_arg('type', 'private', $export_url)); ?>" class="button">
                <?php _e('Export Private Members', 'pro-members-manager'); ?>
            </a>
            
            <a href="<?php echo esc_url(add_query_arg('type', 'union', $export_url)); ?>" class="button">
                <?php _e('Export Organization Members', 'pro-members-manager'); ?>
            </a>
        </div>
        <?php
    }
    
    /**
     * Handle AJAX request to generate CSV
     */
    public function ajax_generate_csv() {
        check_ajax_referer('pmm_generate_csv', 'nonce');
        
        if (!current_user_can('edit_users') && !current_user_can('viewuserlist')) {
            wp_send_json_error(['message' => __('Permission denied.', 'pro-members-manager')]);
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all';
        
        // Generate CSV file
        $file_path = $this->generate_csv_file($type);
        
        if ($file_path) {
            wp_send_json_success([
                'file_url' => content_url(str_replace(WP_CONTENT_DIR, '', $file_path)),
                'message' => __('CSV file generated successfully.', 'pro-members-manager')
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to generate CSV file.', 'pro-members-manager')]);
        }
    }

    /**
     * Generate CSV for stats
     * 
     * @param string $type The type of report to generate
     * @return string|false Path to the generated file or false on failure
     */
    public function generate_csv($headers, $data, $filename) {
        // Set headers
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fputs($output, "\xEF\xBB\xBF");
        // Headers
        fputcsv($output, $headers, ';');
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
        fclose($output);
        exit;
    }
    
    /**
     * Generate CSV file based on type
     *
     * @param string $type The type of report to generate
     * @return string|false Path to the generated file or false on failure
     */
    private function generate_csv_file($type = 'all') {
        $from_date = date('Y-m-d', strtotime('-1 year'));
        $to_date = date('Y-m-d');
        
        $args = [
            'from_date' => $from_date,
            'to_date' => $to_date,
            'per_page' => -1
        ];
        
        // Filter by type if not 'all'
        if ($type !== 'all') {
            $args['member_type'] = $type;
        }
        
        $members = $this->database->get_members($args);
        
        // Generate file name
        $filename = 'members-export';
        if ($type !== 'all') {
            $filename .= '-' . $type;
        }
        $filename .= '-' . date('Y-m-d') . '.csv';
        
        $file_path = PMM_PLUGIN_PATH . 'exports/' . $filename;
        
        // Ensure exports directory exists
        wp_mkdir_p(PMM_PLUGIN_PATH . 'exports');
        
        $output = fopen($file_path, 'w');
        
        if ($output === false) {
            return false;
        }
        
        // Add BOM for Excel UTF-8 compatibility
        fputs($output, "\xEF\xBB\xBF");
        
        // Headers
        fputcsv($output, [
            __('First Name', 'pro-members-manager'),
            __('Last Name', 'pro-members-manager'),
            __('Company/Organization', 'pro-members-manager'),
            __('Email', 'pro-members-manager'),
            __('Address', 'pro-members-manager'),
            __('Postal Code', 'pro-members-manager'),
            __('City', 'pro-members-manager'),
            __('Phone', 'pro-members-manager'),
            __('Joined Date', 'pro-members-manager'),
            __('Membership Type', 'pro-members-manager'),
            __('Quantity', 'pro-members-manager'),
            __('Payment Method', 'pro-members-manager')
        ], ';');
        
        foreach ($members as $member) {
            $row = [
                $member['name'],
                $member['last_name'] ?? '',
                $member['company'],
                $member['email'],
                $member['address']['line1'],
                $member['address']['postcode'],
                $member['address']['city'],
                $member['phone'],
                date('d-m-Y', strtotime($member['joined_date'])),
                $this->get_membership_type_label($member['subscription_details']['product_id']),
                $member['subscription_details']['quantity'],
                $member['payment_method']
            ];
            
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        
        return $file_path;
    }
    
    /**
     * Get a user-friendly label for a membership type
     *
     * @param int $product_id The product ID
     * @return string Membership type label
     */
    private function get_membership_type_label($product_id) {
        $types = [
            9503 => __('Auto Private', 'pro-members-manager'),
            10968 => __('Manual Private', 'pro-members-manager'),
            28736 => __('Auto Pension', 'pro-members-manager'),
            28735 => __('Manual Pension', 'pro-members-manager'),
            30734 => __('Auto Union', 'pro-members-manager'),
            19221 => __('Manual Union', 'pro-members-manager'),
        ];
        
        return isset($types[$product_id]) ? $types[$product_id] : __('Unknown', 'pro-members-manager');
    }
}