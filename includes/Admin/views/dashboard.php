<?php
defined('ABSPATH') || exit;

// Get stats if not provided
if (!isset($stats)) {
    $member_manager = new ProMembersManager\Core\Member_Manager();
    $member_stats = $member_manager->get_members_count(['group_by' => 'both']);
    $stats = [
        'total_members' => array_sum(array_map('array_sum', $member_stats)),
        'private_members' => array_sum($member_stats['private'] ?? []),
        'union_members' => array_sum($member_stats['union'] ?? []),
        'auto_renewals' => ($member_stats['private']['auto'] ?? 0) + ($member_stats['union']['auto'] ?? 0) + ($member_stats['pension']['auto'] ?? 0)
    ];
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="pmm-dashboard">
        <div class="pmm-stats-grid">
            <div class="pmm-stat-card">
                <h3><?php _e('Total Members', 'pro-members-manager'); ?></h3>
                <div class="stat-number" id="total-members">
                    <?php echo esc_html($stats['total_members'] ?? 0); ?>
                </div>
            </div>
            
            <div class="pmm-stat-card">
                <h3><?php _e('Private Members', 'pro-members-manager'); ?></h3>
                <div class="stat-number" id="private-members">
                    <?php echo esc_html($stats['private_members'] ?? 0); ?>
                </div>
            </div>
            
            <div class="pmm-stat-card">
                <h3><?php _e('Union Members', 'pro-members-manager'); ?></h3>
                <div class="stat-number" id="union-members">
                    <?php echo esc_html($stats['union_members'] ?? 0); ?>
                </div>
            </div>
            
            <div class="pmm-stat-card">
                <h3><?php _e('Auto Renewals', 'pro-members-manager'); ?></h3>
                <div class="stat-number" id="auto-renewals">
                    <?php echo esc_html($stats['auto_renewals'] ?? 0); ?>
                </div>
            </div>
        </div>
        
        <div class="pmm-charts-section">
            <h2><?php _e('Membership Growth', 'pro-members-manager'); ?></h2>
            <canvas id="membershipChart" width="400" height="200"></canvas>
        </div>
    </div>
</div>