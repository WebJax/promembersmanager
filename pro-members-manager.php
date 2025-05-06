<?php
/**
 * Plugin Name: Pro Members Manager
 * Plugin URI:  https://example.com/pro-members-manager
 * Description: Professional member management system with advanced statistics, CSV exports, and member tracking
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pro-members-manager
 * Domain Path: /languages
 * 
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('PMM_VERSION', '1.0.0');
define('PMM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PMM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PMM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'ProMembersManager\\';
    $base_dir = PMM_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Main plugin class
final class Pro_Members_Manager {
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Check dependencies
        add_action('plugins_loaded', [$this, 'check_dependencies']);
        
        // Initialize plugin components
        add_action('plugins_loaded', [$this, 'init_plugin']);
        
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function check_dependencies() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('Pro Members Manager requires WooCommerce to be installed and activated.', 'pro-members-manager'); ?></p>
                </div>
                <?php
            });
            return;
        }
    }

    public function init_plugin() {
        // Load text domain
        load_plugin_textdomain('pro-members-manager', false, dirname(PMM_PLUGIN_BASENAME) . '/languages');

        // Initialize components
        new \ProMembersManager\Admin\Admin_Menu();
        new \ProMembersManager\Frontend\Member_List();
        new \ProMembersManager\Core\Member_Manager();
        new \ProMembersManager\Core\CSV_Handler();
        new \ProMembersManager\Core\API_Handler();
        new \ProMembersManager\Core\Stats_Manager();
    }

    public function activate() {
        // Create necessary database tables and options
        \ProMembersManager\Core\Database::create_tables();
        
        // Add custom capabilities
        \ProMembersManager\Core\Capabilities::add_caps();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate() {
        // Clean up plugin data if needed
        \ProMembersManager\Core\Capabilities::remove_caps();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
function PMM() {
    return Pro_Members_Manager::instance();
}

// Start the plugin
PMM();
