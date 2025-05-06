<?php
namespace ProMembersManager\Frontend;

defined('ABSPATH') || exit;

/**
 * Handles frontend display of member lists
 */
class Member_List {
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Register shortcodes
        add_shortcode('pro_members_list', [$this, 'render_members_list_shortcode']);
        add_shortcode('pro_members_stats', [$this, 'render_members_stats_shortcode']);
        
        // Add frontend scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Add AJAX handlers for frontend
        add_action('wp_ajax_nopriv_pmm_load_members', [$this, 'ajax_load_members']);
        add_action('wp_ajax_pmm_load_members', [$this, 'ajax_load_members']);
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_assets() {
        // Only load on pages that use our shortcodes
        global $post;
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'pro_members_list') || 
            has_shortcode($post->post_content, 'pro_members_stats')
        )) {
            // Enqueue CSS
            wp_enqueue_style(
                'pmm-frontend-styles',
                PMM_PLUGIN_URL . 'assets/css/frontend-styles.css',
                [],
                PMM_PLUGIN_VERSION
            );
            
            // Enqueue Chart.js for statistics
            if (has_shortcode($post->post_content, 'pro_members_stats')) {
                wp_enqueue_script(
                    'chartjs',
                    'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js',
                    [],
                    '3.7.0',
                    true
                );
            }
            
            // Enqueue frontend scripts
            wp_enqueue_script(
                'pmm-frontend-scripts',
                PMM_PLUGIN_URL . 'assets/js/frontend-scripts.js',
                ['jquery'],
                PMM_PLUGIN_VERSION,
                true
            );
            
            // Localize script with data
            wp_localize_script('pmm-frontend-scripts', 'pmm_data', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pmm_frontend_nonce'),
                'i18n' => [
                    'loading' => __('Loading...', 'pro-members-manager'),
                    'no_results' => __('No members found.', 'pro-members-manager')
                ]
            ]);
        }
    }
    
    /**
     * Render members list shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered shortcode HTML
     */
    public function render_members_list_shortcode($atts) {
        // Check permissions
        if (!current_user_can('viewuserlist') && !current_user_can('edit_users')) {
            return sprintf(
                '<div class="pmm-restricted">%s</div>',
                __('You do not have permission to view the member list.', 'pro-members-manager')
            );
        }
        
        $atts = shortcode_atts([
            'limit' => 20,
            'type' => '',
            'search' => true,
            'pagination' => true,
            'filters' => true
        ], $atts, 'pro_members_list');
        
        // Start output buffering
        ob_start();
        
        // Get member data
        $member_manager = new \ProMembersManager\Core\Member_Manager();
        
        $args = [
            'per_page' => absint($atts['limit']),
            'page' => isset($_GET['pmm_page']) ? absint($_GET['pmm_page']) : 1
        ];
        
        if (!empty($atts['type'])) {
            $args['member_type'] = sanitize_text_field($atts['type']);
        }
        
        $members = $member_manager->get_members($args);
        
        // Include the template file
        include PMM_PLUGIN_PATH . 'templates/member-list-shortcode.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render members statistics shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered shortcode HTML
     */
    public function render_members_stats_shortcode($atts) {
        // Check permissions
        if (!current_user_can('viewuserlist') && !current_user_can('edit_users')) {
            return sprintf(
                '<div class="pmm-restricted">%s</div>',
                __('You do not have permission to view member statistics.', 'pro-members-manager')
            );
        }
        
        $atts = shortcode_atts([
            'chart_type' => 'pie', // pie, bar, line
            'show_private' => true,
            'show_union' => true,
            'show_growth' => true
        ], $atts, 'pro_members_stats');
        
        // Start output buffering
        ob_start();
        
        // Get statistics data
        $stats_manager = new \ProMembersManager\Core\Stats_Manager();
        $stats = $stats_manager->get_stats();
        
        // Include the template file
        include PMM_PLUGIN_PATH . 'templates/member-stats-shortcode.php';
        
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for loading member data
     */
    public function ajax_load_members() {
        // Verify nonce
        check_ajax_referer('pmm_frontend_nonce', 'nonce');
        
        // Check permissions
        if (!current_user_can('viewuserlist') && !current_user_can('edit_users')) {
            wp_send_json_error(['message' => __('Permission denied.', 'pro-members-manager')]);
            return;
        }
        
        // Get parameters
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 20;
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        // Get member data
        $member_manager = new \ProMembersManager\Core\Member_Manager();
        
        $args = [
            'per_page' => $limit,
            'page' => $page,
            'search' => $search
        ];
        
        if (!empty($type)) {
            $args['member_type'] = $type;
        }
        
        $members = $member_manager->get_members($args);
        
        // Prepare sanitized response data
        $response_data = [];
        foreach ($members as $member) {
            $response_data[] = [
                'id' => $member['id'],
                'name' => $member['name'],
                'company' => $member['company'],
                'city' => $member['address']['city'],
                'postcode' => $member['address']['postcode'],
                'type' => $this->get_member_type_label($member['subscription_details']['type'], $member['subscription_details']['renewal_type']),
                'joined_date' => date('d-m-Y', strtotime($member['joined_date']))
            ];
        }
        
        wp_send_json_success([
            'members' => $response_data,
            'count' => count($response_data)
        ]);
    }
    
    /**
     * Get a user-friendly label for a membership type and renewal type
     * 
     * @param string $type Membership type
     * @param string $renewal_type Renewal type (auto or manual)
     * @return string Formatted type label
     */
    private function get_member_type_label($type, $renewal_type) {
        $types = [
            'private' => $renewal_type === 'auto' ? __('Auto Private', 'pro-members-manager') : __('Manual Private', 'pro-members-manager'),
            'pension' => $renewal_type === 'auto' ? __('Auto Pension', 'pro-members-manager') : __('Manual Pension', 'pro-members-manager'),
            'union' => $renewal_type === 'auto' ? __('Auto Union', 'pro-members-manager') : __('Manual Union', 'pro-members-manager')
        ];
        
        return isset($types[$type]) ? $types[$type] : __('Unknown', 'pro-members-manager');
    }
}