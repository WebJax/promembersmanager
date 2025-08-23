<?php
// Extremely simple test - no dependencies
echo '<div class="wrap">';
echo '<h1>Basic Test</h1>';
echo '<p>If you see this, PHP is working</p>';
echo '<p>WordPress constant check: ' . (defined('ABSPATH') ? 'ABSPATH defined' : 'ABSPATH not defined') . '</p>';
echo '<p>Current time: ' . date('Y-m-d H:i:s') . '</p>';
echo '</div>';
?>
