<?php
// Test the new get_active_members_on_date method
require_once '../../../wp-load.php';

if (!current_user_can('manage_options')) {
    die('Access denied');
}

use ProMembersManager\Core\Member_Manager;

$member_manager = new Member_Manager();

echo "<h2>Test af get_active_members_on_date metoden</h2>";

// Test different dates
$test_dates = [
    date('Y-m-d'), // Today
    date('Y-m-d', strtotime('-1 month')), // 1 month ago
    date('Y-m-d', strtotime('-6 months')), // 6 months ago
    date('Y-m-d', strtotime('-1 year')) // 1 year ago
];

foreach ($test_dates as $test_date) {
    echo "<h3>Aktive medlemmer på: " . date('d-m-Y', strtotime($test_date)) . "</h3>";
    
    $counts = $member_manager->get_active_members_on_date($test_date);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Type</th><th>Antal</th></tr>";
    
    foreach ($counts as $type => $count) {
        $type_name = [
            'total' => 'Total',
            'private' => 'Private',
            'pension' => 'Pension', 
            'union' => 'Union'
        ][$type] ?? $type;
        
        echo "<tr><td>{$type_name}</td><td>{$count}</td></tr>";
    }
    echo "</table>";
}

// Test filtering by member type
echo "<h3>Test af filtrering efter medlemstype (i dag):</h3>";
$today = date('Y-m-d');

foreach (['private', 'pension', 'union'] as $type) {
    $count = $member_manager->get_active_members_on_date($today, $type);
    echo "<p>{$type}: {$count} medlemmer</p>";
}
?>
