<?php
defined('ABSPATH') || exit;

echo '<div class="wrap">';
echo '<h1>Statistics Debug</h1>';

// Basic HTML output to test if page loads
echo '<p>Page is loading...</p>';

// Check if classes exist
if (class_exists('ProMembersManager\Core\Member_Manager')) {
    echo '<p>✓ Member_Manager class exists</p>';
} else {
    echo '<p>✗ Member_Manager class missing</p>';
}

if (class_exists('ProMembersManager\Core\Database')) {
    echo '<p>✓ Database class exists</p>';
} else {
    echo '<p>✗ Database class missing</p>';
}

// Try to instantiate classes
try {
    $member_manager = new ProMembersManager\Core\Member_Manager();
    echo '<p>✓ Member_Manager instantiated</p>';
    
    // Try to get some basic data
    $count = $member_manager->get_members_count(['group_by' => 'member_type']);
    echo '<p>Member count result: ' . print_r($count, true) . '</p>';
    
} catch (Exception $e) {
    echo '<p>✗ Error: ' . esc_html($e->getMessage()) . '</p>';
}

echo '</div>';
?>
