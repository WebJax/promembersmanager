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
        
        // Build the full path
        $file = PMM_PLUGIN_PATH . 'includes' . DIRECTORY_SEPARATOR . $file . '.php';
        
        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        }
    }
}