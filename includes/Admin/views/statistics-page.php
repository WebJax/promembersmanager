<?php
defined('ABSPATH') || exit;

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
                    <?php echo esc_html(array_sum(array_map('array_sum', $member_counts))); ?>
                </div>
            </div>
            
            <div class="pmm-stat-card">
                <h3><?php _e('Private Members', 'pro-members-manager'); ?></h3>
                <div class="stat-number">
                    <?php echo esc_html(array_sum($member_counts['private'] ?? [0])); ?>
                </div>
            </div>
            
            <div class="pmm-stat-card">
                <h3><?php _e('Pension Members', 'pro-members-manager'); ?></h3>
                <div class="stat-number">
                    <?php echo esc_html(array_sum($member_counts['pension'] ?? [0])); ?>
                </div>
            </div>
            
            <div class="pmm-stat-card">
                <h3><?php _e('Union Members', 'pro-members-manager'); ?></h3>
                <div class="stat-number">
                    <?php echo esc_html(array_sum($member_counts['union'] ?? [0])); ?>
                </div>
            </div>
        </div>
        
        <div class="pmm-stats-grid">
            <div class="pmm-stat-card">
                <h3><?php _e('Auto Renewals', 'pro-members-manager'); ?></h3>
                <div class="stat-number">
                    <?php 
                    $auto_total = 0;
                    foreach ($member_counts as $type => $renewals) {
                        $auto_total += $renewals['auto'] ?? 0;
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
                    foreach ($member_counts as $type => $renewals) {
                        $manual_total += $renewals['manual'] ?? 0;
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
                <?php foreach ($member_counts as $type => $renewals): ?>
                    <?php if ($type !== 'unknown' || array_sum($renewals) > 0): ?>
                        <tr>
                            <td><strong><?php echo esc_html(ucfirst($type)); ?></strong></td>
                            <td><?php echo esc_html($renewals['auto'] ?? 0); ?></td>
                            <td><?php echo esc_html($renewals['manual'] ?? 0); ?></td>
                            <td><strong><?php echo esc_html(array_sum($renewals)); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
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
                        <?php echo array_sum($member_counts['private'] ?? [0]); ?>,
                        <?php echo array_sum($member_counts['pension'] ?? [0]); ?>,
                        <?php echo array_sum($member_counts['union'] ?? [0]); ?>
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
