<?php
defined('ABSPATH') || exit;
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="pmm-dashboard">
        <div class="pmm-stats-grid">
            <div class="pmm-stat-card">
                <h3><?php _e('Total Members', 'pro-members-manager'); ?></h3>
                <div class="stat-number">
                    <?php echo esc_html('0'); ?>
                </div>
            </div>
        </div>
        
        <p><?php _e('Pro Members Manager dashboard loaded successfully.', 'pro-members-manager'); ?></p>
    </div>
</div>
