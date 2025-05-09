<?php
/**
 * Pro Members Manager
 *
 * @package Pro_Members_Manager
 * @author  Jacob Thygesen
 * @license GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Pro Members Manager
 * Plugin URI:  https://dianalund.dk
 * Description: Advanced membership management system for organizations
 * Version:     1.0.0
 * Author:      Jacob Thygesen
 * Author URI:  https://dianalund.dk
 * Text Domain: pro-members-manager
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 *
 * This plugin is a complete rewrite of the JxwMembers plugin with improved architecture and functionality.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('PMM_PLUGIN_FILE', __FILE__);
define('PMM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PMM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PMM_PLUGIN_VERSION', '1.0.0');
define('PMM_PLUGIN_NAME', 'pro-members-manager');

// Include the autoloader
require_once PMM_PLUGIN_PATH . 'includes/class-autoloader.php';

/**
 * Initialize the plugin
 *
 * @since 1.0.0
 */
function promembersmanager_init() {
    // Register activation and deactivation hooks
    register_activation_hook(PMM_PLUGIN_FILE, 'promembersmanager_activate');
    register_deactivation_hook(PMM_PLUGIN_FILE, 'promembersmanager_deactivate');
    
    // Initialize autoloader
    $autoloader = new ProMembersManager\Autoloader();
    $autoloader->register();
    
    // Initialize core components
    $database = new ProMembersManager\Core\Database();
    $database->init();
    
    // Initialize admin
    if (is_admin()) {
        $admin_menu = new ProMembersManager\Admin\Admin_Menu();
    }
    
    // Initialize frontend
    if (!is_admin()) {
        $member_list = new ProMembersManager\Frontend\Member_List();
    }
    
    // Initialize API Handler
    $api_handler = new ProMembersManager\Core\API_Handler();
    
    // Register assets
    add_action('wp_enqueue_scripts', 'promembersmanager_enqueue_frontend_assets');
    add_action('admin_enqueue_scripts', 'promembersmanager_enqueue_admin_assets');
    
    // Register AJAX handlers
    add_action('wp_ajax_pmm_export_csv', array(new ProMembersManager\Core\CSV_Handler(), 'export_csv'));
    add_action('wp_ajax_pmm_create_member', array(new ProMembersManager\Core\Member_Manager(), 'create_member'));
    add_action('wp_ajax_pmm_edit_member', array(new ProMembersManager\Core\Member_Manager(), 'edit_member'));
    add_action('wp_ajax_pmm_update_member', array(new ProMembersManager\Core\Member_Manager(), 'update_member'));
    add_action('wp_ajax_pmm_delete_member', array(new ProMembersManager\Core\Member_Manager(), 'delete_member'));
    
    // Add shortcodes
    add_shortcode('pro_members_list', array(new ProMembersManager\Frontend\Member_List(), 'render_shortcode'));
}

/**
 * Plugin activation hook
 *
 * @since 1.0.0
 */
function promembersmanager_activate() {
    // Create database tables
    $database = new ProMembersManager\Core\Database();
    $database->create_tables();
    
    // Add capabilities
    promembersmanager_add_capabilities();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook
 *
 * @since 1.0.0
 */
function promembersmanager_deactivate() {
    // Remove capabilities
    promembersmanager_remove_capabilities();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Add custom capabilities
 *
 * @since 1.0.0
 */
function promembersmanager_add_capabilities() {
    global $wp_roles;
    
    if (!isset($wp_roles)) {
        $wp_roles = new WP_Roles();
    }
    
    // Subscriber capabilities for reference
    $subscriber = $wp_roles->get_role('subscriber');
    
    // Create board member role
    if (!get_role('boardmember')) {
        $wp_roles->add_role('boardmember', __('Board Member', 'pro-members-manager'), $subscriber->capabilities);
        $wp_roles->add_cap('boardmember', 'view_members');
        $wp_roles->add_cap('administrator', 'view_members');
        $wp_roles->add_cap('administrator', 'manage_members');
    }
}

/**
 * Remove custom capabilities
 *
 * @since 1.0.0
 */
function promembersmanager_remove_capabilities() {
    global $wp_roles;
    
    if (!isset($wp_roles)) {
        $wp_roles = new WP_Roles();
    }
    
    // Remove board member role
    $wp_roles->remove_role('boardmember');
    
    // Remove capabilities from admin
    $wp_roles->remove_cap('administrator', 'view_members');
    $wp_roles->remove_cap('administrator', 'manage_members');
}

/**
 * Enqueue frontend assets
 *
 * @since 1.0.0
 */
function promembersmanager_enqueue_frontend_assets() {
    wp_enqueue_style(
        'pmm-frontend-styles',
        PMM_PLUGIN_URL . 'assets/css/frontend-styles.css',
        array(),
        PMM_PLUGIN_VERSION
    );
    
    wp_enqueue_script(
        'pmm-frontend-scripts',
        PMM_PLUGIN_URL . 'assets/js/frontend-scripts.js',
        array('jquery'),
        PMM_PLUGIN_VERSION,
        true
    );
    
    wp_localize_script(
        'pmm-frontend-scripts',
        'pmmVars',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pmm_frontend_nonce')
        )
    );
}

/**
 * Enqueue admin assets
 *
 * @since 1.0.0
 */
function promembersmanager_enqueue_admin_assets() {
    $screen = get_current_screen();
    
    // Only load on plugin admin pages
    if ($screen && strpos($screen->id, 'pro-members-manager') !== false) {
        wp_enqueue_style(
            'pmm-admin-styles',
            PMM_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            PMM_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'pmm-admin-scripts',
            PMM_PLUGIN_URL . 'assets/js/admin-scripts.js',
            array('jquery'),
            PMM_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script(
            'pmm-admin-scripts',
            'pmmAdminVars',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pmm_admin_nonce'),
                'i18n' => array(
                    'confirmDelete' => __('Are you sure you want to delete this member?', 'pro-members-manager'),
                    'confirmCancel' => __('Are you sure you want to cancel this membership?', 'pro-members-manager'),
                    'processingRequest' => __('Processing request...', 'pro-members-manager'),
                    'success' => __('Success!', 'pro-members-manager'),
                    'error' => __('Error:', 'pro-members-manager')
                )
            )
        );
    }
}

// Initialize the plugin
add_action('plugins_loaded', 'promembersmanager_init');
