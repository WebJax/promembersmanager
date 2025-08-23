<?php
/**
 * Autoloader class for Pro Members Manager plugin
 *
 * @package Pro_Members_Manager
 * @since 1.0.0
 */

namespace ProMembersManager;

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
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Autoload function for plugin classes
     *
     * @param string $class The fully-qualified class name.
     * @since 1.0.0
     */
    public function autoload($class) {
        // Check if the class is in our namespace
        if (strpos($class, 'ProMembersManager\\') !== 0) {
            return;
        }

        // Remove namespace prefix to get the relative class name
        $relative_class = substr($class, strlen('ProMembersManager\\'));
        
        // Convert namespace separators to directory separators
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class);
        
        // Build possible file paths
        $possible_files = [
            // First try includes directory with namespace structure
            PMM_PLUGIN_PATH . 'includes' . DIRECTORY_SEPARATOR . $file . '.php',
            // Then try core directory for Member_Manager
            PMM_PLUGIN_PATH . 'core' . DIRECTORY_SEPARATOR . strtolower(str_replace('Core\\', '', $file)) . '.php'
        ];
        
        // Try each possible file location
        foreach ($possible_files as $file_path) {
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }
    }
}