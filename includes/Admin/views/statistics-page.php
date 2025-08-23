<?php
defined('ABSPATH') || exit;

// Initialize variables with defaults
$daily_stats = [];
$member_counts = [
    'private' => ['auto' => 0, 'manual' => 0],
    'pension' => ['auto' => 0, 'manual' => 0],
    'union' => ['auto' => 0, 'manual' => 0],
    'unknown' => ['auto' => 0, 'manual' => 0]
];

try {
    // Check if classes exist before using them
    if (!class_exists('ProMembersManager\Core\Member_Manager') || !class_exists('ProMembersManager\Core\Database')) {
        throw new Exception(__('Required classes not found. Please check plugin installation.', 'pro-members-manager'));
    }

    // Get statistics data
    $member_manager = new ProMembersManager\Core\Member_Manager();
    $database = new ProMembersManager\Core\Database();

    // Get date range from URL or set defaults
    $from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : date('Y-m-d', strtotime('-1 month'));
    $to_date = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : date('Y-m-d');
    $member_type = isset($_GET['member_type']) ? sanitize_text_field($_GET['member_type']) : '';

    // Get member statistics
    $stats_args = [
        'from_date' => $from_date,
        'to_date' => $to_date
    ];

    $daily_stats = $database->get_member_statistics($stats_args);
    $member_counts = $member_manager->get_members_count(['group_by' => 'both']);

} catch (Exception $e) {
    echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div>';
    
    // Set safe defaults if there's an error
    $from_date = date('Y-m-d', strtotime('-1 month'));
    $to_date = date('Y-m-d');
    $member_type = '';
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="pmm-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="pmm-statistics">
            
            <label for="from_date"><?php _e('From Date:', 'pro-members-manager'); ?></label>
            <input type="date" id="from_date" name="from_date" value="<?php echo esc_attr($from_date); ?>">
            
            <label for="to_date"><?php _e('To Date:', 'pro-members-manager'); ?></label>
            <input type="date" id="to_date" name="to_date" value="<?php echo esc_attr($to_date); ?>">
            
            <label for="member_type"><?php _e('Member Type:', 'pro-members-manager'); ?></label>
            <select id="member_type" name="member_type">
                <option value=""><?php _e('All Types', 'pro-members-manager'); ?></option>
                <option value="private" <?php selected($member_type, 'private'); ?>><?php _e('Private', 'pro-members-manager'); ?></option>
                <option value="pension" <?php selected($member_type, 'pension'); ?>><?php _e('Pension', 'pro-members-manager'); ?></option>
                <option value="union" <?php selected($member_type, 'union'); ?>><?php _e('Union', 'pro-members-manager'); ?></option>
            </select>
            
            <?php submit_button(__('Filter', 'pro-members-manager'), 'secondary', 'filter', false); ?>
        </form>
    </div>
    
    <div class="pmm-stats-overview">
        <div class="pmm-stats-grid">
            <div class="pmm-stat-card">
                <h3><?php _e('Total Members', 'pro-members-manager'); ?></h3>
                <div class="stat-number">
                    <?php 
                    $total = 0;
                    if (is_array($member_counts)) {
                        foreach ($member_counts as $type_data) {
                            if (is_array($type_data)) {
                                $total += array_sum($type_data);
                            }
                        }
                    }
                    echo esc_html($total);
                    ?>
                </div>
            </div>
            
            <div class="pmm-stat-card">
                <h3><?php _e('Private Members', 'pro-members-manager'); ?></h3>
                <div class="stat-number">
                    <?php 
                    $private_total = 0;
                    if (isset($member_counts['private']) && is_array($member_counts['private'])) {
                        $private_total = array_sum($member_counts['private']);
                    }
                    echo esc_html($private_total);
                    ?>
                </div>
            </div>
            
            <div class="pmm-stat-card">
                <h3><?php _e('Pension Members', 'pro-members-manager'); ?></h3>
                <div class="stat-number">
                    <?php 
                    $pension_total = 0;
                    if (isset($member_counts['pension']) && is_array($member_counts['pension'])) {
                        $pension_total = array_sum($member_counts['pension']);
                    }
                    echo esc_html($pension_total);
                    ?>
                </div>
            </div>
            
            <div class="pmm-stat-card">
                <h3><?php _e('Union Members', 'pro-members-manager'); ?></h3>
                <div class="stat-number">
                    <?php 
                    $union_total = 0;
                    if (isset($member_counts['union']) && is_array($member_counts['union'])) {
                        $union_total = array_sum($member_counts['union']);
                    }
                    echo esc_html($union_total);
                    ?>
                </div>
            </div>
        </div>
        
        <div class="pmm-stats-grid">
            <div class="pmm-stat-card">
                <h3><?php _e('Auto Renewals', 'pro-members-manager'); ?></h3>
                <div class="stat-number">
                    <?php 
                    $auto_total = 0;
                    if (is_array($member_counts)) {
                        foreach ($member_counts as $type => $renewals) {
                            if (is_array($renewals) && isset($renewals['auto'])) {
                                $auto_total += intval($renewals['auto']);
                            }
                        }
                    }
                    echo esc_html($auto_total);
                    ?>
                </div>
            </div>
            
            <div class="pmm-stat-card">
                <h3><?php _e('Manual Renewals', 'pro-members-manager'); ?></h3>
                <div class="stat-number">
                    <?php 
                    $manual_total = 0;
                    if (is_array($member_counts)) {
                        foreach ($member_counts as $type => $renewals) {
                            if (is_array($renewals) && isset($renewals['manual'])) {
                                $manual_total += intval($renewals['manual']);
                            }
                        }
                    }
                    echo esc_html($manual_total);
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="pmm-charts-section">
        <h2><?php _e('Member Type Distribution', 'pro-members-manager'); ?></h2>
        <div class="chart-container">
            <canvas id="memberTypeChart" width="400" height="200"></canvas>
        </div>
    </div>
    
    <div class="pmm-detailed-stats">
        <h2><?php _e('Detailed Statistics', 'pro-members-manager'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Category', 'pro-members-manager'); ?></th>
                    <th><?php _e('Auto Renewal', 'pro-members-manager'); ?></th>
                    <th><?php _e('Manual Renewal', 'pro-members-manager'); ?></th>
                    <th><?php _e('Total', 'pro-members-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (is_array($member_counts)) {
                    foreach ($member_counts as $type => $renewals):
                        if (!is_array($renewals)) continue;
                        $type_total = array_sum($renewals);
                        if ($type !== 'unknown' || $type_total > 0): 
                ?>
                        <tr>
                            <td><strong><?php echo esc_html(ucfirst($type)); ?></strong></td>
                            <td><?php echo esc_html(intval($renewals['auto'] ?? 0)); ?></td>
                            <td><?php echo esc_html(intval($renewals['manual'] ?? 0)); ?></td>
                            <td><strong><?php echo esc_html($type_total); ?></strong></td>
                        </tr>
                <?php 
                        endif;
                    endforeach;
                } else {
                    echo '<tr><td colspan="4">' . __('No data available', 'pro-members-manager') . '</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Member Type Chart
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('memberTypeChart').getContext('2d');
        var memberTypeChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [
                    '<?php _e('Private', 'pro-members-manager'); ?>',
                    '<?php _e('Pension', 'pro-members-manager'); ?>',
                    '<?php _e('Union', 'pro-members-manager'); ?>'
                ],
                datasets: [{
                    data: [
                        <?php 
                        $private_total = isset($member_counts['private']) && is_array($member_counts['private']) ? array_sum($member_counts['private']) : 0;
                        $pension_total = isset($member_counts['pension']) && is_array($member_counts['pension']) ? array_sum($member_counts['pension']) : 0;
                        $union_total = isset($member_counts['union']) && is_array($member_counts['union']) ? array_sum($member_counts['union']) : 0;
                        
                        echo intval($private_total) . ',';
                        echo intval($pension_total) . ',';
                        echo intval($union_total);
                        ?>
                    ],
                    backgroundColor: [
                        '#0073aa',
                        '#00a32a',
                        '#ff6900'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>
