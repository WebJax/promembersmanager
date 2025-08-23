<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="pmm-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="pmm-stats">
            
            <label for="date_from"><?php _e('From Date:', 'pro-members-manager'); ?></label>
            <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr($_GET['date_from'] ?? date('Y-m-01')); ?>">
            
            <label for="date_to"><?php _e('To Date:', 'pro-members-manager'); ?></label>
            <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr($_GET['date_to'] ?? date('Y-m-d')); ?>">
            
            <label for="member_type"><?php _e('Member Type:', 'pro-members-manager'); ?></label>
            <select id="member_type" name="member_type">
                <option value=""><?php _e('All Types', 'pro-members-manager'); ?></option>
                <option value="private" <?php selected($_GET['member_type'] ?? '', 'private'); ?>><?php _e('Private', 'pro-members-manager'); ?></option>
                <option value="pension" <?php selected($_GET['member_type'] ?? '', 'pension'); ?>><?php _e('Pension', 'pro-members-manager'); ?></option>
                <option value="union" <?php selected($_GET['member_type'] ?? '', 'union'); ?>><?php _e('Union', 'pro-members-manager'); ?></option>
            </select>
            
            <?php submit_button(__('Filter', 'pro-members-manager'), 'secondary', 'filter', false); ?>
        </form>
    </div>
    
    <div class="pmm-stats-detailed">
        <h2><?php _e('Detailed Statistics', 'pro-members-manager'); ?></h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Period', 'pro-members-manager'); ?></th>
                    <th><?php _e('Total Members', 'pro-members-manager'); ?></th>
                    <th><?php _e('Private Members', 'pro-members-manager'); ?></th>
                    <th><?php _e('Union Members', 'pro-members-manager'); ?></th>
                    <th><?php _e('Auto Renewals', 'pro-members-manager'); ?></th>
                    <th><?php _e('Manual Renewals', 'pro-members-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($daily_stats)): ?>
                    <?php foreach ($daily_stats as $stat): ?>
                        <tr>
                            <td><?php echo esc_html($stat['stat_date']); ?></td>
                            <td><?php echo esc_html($stat['total_members']); ?></td>
                            <td><?php echo esc_html($stat['private_members']); ?></td>
                            <td><?php echo esc_html($stat['union_members']); ?></td>
                            <td><?php echo esc_html($stat['auto_renewals']); ?></td>
                            <td><?php echo esc_html($stat['manual_renewals']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6"><?php _e('No statistics available for the selected period.', 'pro-members-manager'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>