<?php
defined('ABSPATH') || exit;

// Simple test to identify the problem
echo '<div class="wrap">';
echo '<h1>Statistics Page Debug</h1>';

try {
    echo '<p>Starting debug...</p>';
    
    // Test 1: Basic class loading
    echo '<p>Test 1: Checking class existence...</p>';
    if (class_exists('ProMembersManager\Core\Member_Manager')) {
        echo '<p>✓ Member_Manager class exists</p>';
    } else {
        echo '<p>✗ Member_Manager class NOT found</p>';
    }
    
    if (class_exists('ProMembersManager\Core\Database')) {
        echo '<p>✓ Database class exists</p>';
    } else {
        echo '<p>✗ Database class NOT found</p>';
    }
    
    // Test 2: WooCommerce
    echo '<p>Test 2: Checking WooCommerce...</p>';
    if (function_exists('wc_get_orders')) {
        echo '<p>✓ WooCommerce wc_get_orders function exists</p>';
    } else {
        echo '<p>✗ WooCommerce wc_get_orders function NOT found</p>';
    }
    
    // Test 3: Try to instantiate classes
    echo '<p>Test 3: Instantiating classes...</p>';
    
    if (class_exists('ProMembersManager\Core\Member_Manager')) {
        $member_manager = new ProMembersManager\Core\Member_Manager();
        echo '<p>✓ Member_Manager instantiated successfully</p>';
        
        // Test 4: Try calling get_members_count
        echo '<p>Test 4: Calling get_members_count...</p>';
        $counts = $member_manager->get_members_count(['group_by' => 'both']);
        echo '<p>✓ get_members_count returned: ' . print_r($counts, true) . '</p>';
    }
    
    if (class_exists('ProMembersManager\Core\Database')) {
        $database = new ProMembersManager\Core\Database();
        echo '<p>✓ Database instantiated successfully</p>';
        
        // Test 5: Try calling get_member_statistics
        echo '<p>Test 5: Calling get_member_statistics...</p>';
        $stats = $database->get_member_statistics([]);
        echo '<p>✓ get_member_statistics returned data</p>';
    }
    
    echo '<p>All tests completed successfully!</p>';
    
} catch (Error $e) {
    echo '<div style="color: red; border: 1px solid red; padding: 10px;">';
    echo '<h3>Fatal Error:</h3>';
    echo '<p><strong>Message:</strong> ' . esc_html($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
    echo '<p><strong>Line:</strong> ' . esc_html($e->getLine()) . '</p>';
    echo '<p><strong>Trace:</strong><br><pre>' . esc_html($e->getTraceAsString()) . '</pre></p>';
    echo '</div>';
} catch (Exception $e) {
    echo '<div style="color: orange; border: 1px solid orange; padding: 10px;">';
    echo '<h3>Exception:</h3>';
    echo '<p><strong>Message:</strong> ' . esc_html($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . '</p>';
    echo '<p><strong>Line:</strong> ' . esc_html($e->getLine()) . '</p>';
    echo '<p><strong>Trace:</strong><br><pre>' . esc_html($e->getTraceAsString()) . '</pre></p>';
    echo '</div>';
}

echo '</div>';
?>
