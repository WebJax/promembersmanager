<?php
/**
 * Autoloader class for Pro Members Manager plugin
 *
 * @package Pro_Members_Manager
 * @since 1.0.0
 */

namespace ProMembersManager;

defined('ABSPATH') || exit;

/**
 * Class Autoloader
 *
 * PSR-4 compliant autoloader for the Pro Members Manager plugin
 */
class Autoloader {
    
    /**
     * Register the autoloader
     *
     * @since 1.0.0
     */
    public function register() {
        spl_autoload_register([$this, 'autoload']);
    }
    
    /**
     * Autoload function for plugin classes
     *
     * @param string $class The fully-qualified class name.
     * @since 1.0.0
     */
    public function autoload($class) {
        // Only autoload classes in our namespace
        if (strpos($class, 'ProMembersManager\\') !== 0) {
            return;
        }
        
        // Remove namespace prefix
        $class = str_replace('ProMembersManager\\', '', $class);
        
        // Convert namespace separators to directory separators
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        
        // Determine file paths to check
        $possible_files = [
            PMM_PLUGIN_PATH . 'includes/' . $class . '.php',
            PMM_PLUGIN_PATH . 'core/' . strtolower(str_replace('Core\\', '', $class)) . '.php'
        ];
        
        foreach ($possible_files as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
}