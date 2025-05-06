<?php
namespace ProMembersManager\Admin;

defined('ABSPATH') || exit;

/**
 * Handles admin menu and pages for the Pro Members Manager plugin
 */
class Admin_Menu {
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Register admin menu pages
     */
    public function register_admin_menu() {
        add_menu_page(
            __('Members Manager', 'pro-members-manager'),
            __('Members', 'pro-members-manager'),
            'edit_users',
            'pro-members-manager',
            [$this, 'render_members_page'],
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'pro-members-manager',
            __('All Members', 'pro-members-manager'),
            __('All Members', 'pro-members-manager'),
            'edit_users',
            'pro-members-manager',
            [$this, 'render_members_page']
        );
        
        add_submenu_page(
            'pro-members-manager',
            __('Statistics', 'pro-members-manager'),
            __('Statistics', 'pro-members-manager'),
            'edit_users',
            'pmm-statistics',
            [$this, 'render_statistics_page']
        );
        
        add_submenu_page(
            'pro-members-manager',
            __('Export CSV', 'pro-members-manager'),
            __('Export CSV', 'pro-members-manager'),
            'edit_users',
            'pmm-export',
            [$this, 'render_export_page']
        );
        
        add_submenu_page(
            'pro-members-manager',
            __('Settings', 'pro-members-manager'),
            __('Settings', 'pro-members-manager'),
            'manage_options',
            'pmm-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_scripts($hook) {
        // Only enqueue on our plugin pages
        if (strpos($hook, 'pro-members-manager') === false && strpos($hook, 'pmm-') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'pmm-admin-styles',
            PMM_PLUGIN_URL . 'assets/css/admin-styles.css',
            [],
            PMM_PLUGIN_VERSION
        );
        
        // Enqueue Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js',
            [],
            '3.7.0',
            true
        );
        
        // Enqueue admin scripts
        wp_enqueue_script(
            'pmm-admin-scripts',
            PMM_PLUGIN_URL . 'assets/js/admin-scripts.js',
            ['jquery', 'chartjs'],
            PMM_PLUGIN_VERSION,
            true
        );
        
        // Localize script with data
        wp_localize_script('pmm-admin-scripts', 'pmm_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pmm_admin_nonce'),
            'i18n' => [
                'confirm_delete' => __('Are you sure you want to delete this member? This action cannot be undone.', 'pro-members-manager'),
                'loading' => __('Loading...', 'pro-members-manager'),
                'error' => __('An error occurred. Please try again.', 'pro-members-manager'),
                'success' => __('Operation completed successfully.', 'pro-members-manager'),
            ]
        ]);
    }
    
    /**
     * Render the main members page
     */
    public function render_members_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'all';
        
        // Include view
        include_once PMM_PLUGIN_PATH . 'includes/Admin/views/members-page.php';
    }
    
    /**
     * Render the statistics page
     */
    public function render_statistics_page() {
        // Set default date range
        $from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : date('Y-m-d', strtotime('-1 year'));
        $to_date = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : date('Y-m-d');
        
        // Include view
        include_once PMM_PLUGIN_PATH . 'includes/Admin/views/statistics-page.php';
    }
    
    /**
     * Render the export page
     */
    public function render_export_page() {
        // Set default date range
        $from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : date('Y-m-d', strtotime('-1 year'));
        $to_date = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : date('Y-m-d');
        
        // Include view
        include_once PMM_PLUGIN_PATH . 'includes/Admin/views/export-page.php';
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        // Include view
        include_once PMM_PLUGIN_PATH . 'includes/Admin/views/settings-page.php';
    }
}