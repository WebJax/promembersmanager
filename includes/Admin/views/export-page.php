<?php
defined('ABSPATH') || exit;

// Handle CSV export if form submitted
if (isset($_POST['export_csv']) && wp_verify_nonce($_POST['pmm_export_nonce'], 'pmm_export_csv')) {
    $from_date = sanitize_text_field($_POST['from_date']);
    $to_date = sanitize_text_field($_POST['to_date']);
    $member_type = sanitize_text_field($_POST['member_type']);
    
    $member_manager = new ProMembersManager\Core\Member_Manager();
    $args = [
        'from_date' => $from_date,
        'to_date' => $to_date,
        'member_type' => $member_type,
        'per_page' => -1
    ];
    
    $members = $member_manager->get_members($args);
    $member_manager->export_members_csv($members);
    exit; // This won't be reached due to export_members_csv() exit
}

// Set defaults
$from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : date('Y-m-d', strtotime('-1 year'));
$to_date = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : date('Y-m-d');
$member_type = isset($_GET['member_type']) ? sanitize_text_field($_GET['member_type']) : '';

// Get member count for preview
$member_manager = new ProMembersManager\Core\Member_Manager();
$preview_args = [
    'from_date' => $from_date,
    'to_date' => $to_date,
    'member_type' => $member_type,
    'per_page' => 5
];
$preview_members = $member_manager->get_members($preview_args);
$total_count = $member_manager->get_members_count([
    'from_date' => $from_date,
    'to_date' => $to_date,
    'member_type' => $member_type
]);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="pmm-export-form">
        <h2><?php _e('Export Member Data', 'pro-members-manager'); ?></h2>
        <p><?php _e('Select your criteria and export member data to CSV format.', 'pro-members-manager'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('pmm_export_csv', 'pmm_export_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="from_date"><?php _e('From Date', 'pro-members-manager'); ?></label>
                    </th>
                    <td>
                        <input type="date" id="from_date" name="from_date" value="<?php echo esc_attr($from_date); ?>" required>
                        <p class="description"><?php _e('Start date for member data export', 'pro-members-manager'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="to_date"><?php _e('To Date', 'pro-members-manager'); ?></label>
                    </th>
                    <td>
                        <input type="date" id="to_date" name="to_date" value="<?php echo esc_attr($to_date); ?>" required>
                        <p class="description"><?php _e('End date for member data export', 'pro-members-manager'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="member_type"><?php _e('Member Type', 'pro-members-manager'); ?></label>
                    </th>
                    <td>
                        <select id="member_type" name="member_type">
                            <option value=""><?php _e('All Types', 'pro-members-manager'); ?></option>
                            <option value="private" <?php selected($member_type, 'private'); ?>><?php _e('Private Members', 'pro-members-manager'); ?></option>
                            <option value="pension" <?php selected($member_type, 'pension'); ?>><?php _e('Pension Members', 'pro-members-manager'); ?></option>
                            <option value="union" <?php selected($member_type, 'union'); ?>><?php _e('Union Members', 'pro-members-manager'); ?></option>
                        </select>
                        <p class="description"><?php _e('Filter by membership type', 'pro-members-manager'); ?></p>
                    </td>
                </tr>
            </table>
            
            <div class="pmm-export-actions">
                <?php submit_button(__('Update Preview', 'pro-members-manager'), 'secondary', 'update_preview', false); ?>
                <?php submit_button(__('Export to CSV', 'pro-members-manager'), 'primary', 'export_csv', false); ?>
            </div>
        </form>
    </div>
    
    <div class="pmm-export-preview">
        <h3><?php printf(__('Preview (%d members found)', 'pro-members-manager'), $total_count); ?></h3>
        
        <?php if (!empty($preview_members)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'pro-members-manager'); ?></th>
                        <th><?php _e('Company', 'pro-members-manager'); ?></th>
                        <th><?php _e('Email', 'pro-members-manager'); ?></th>
                        <th><?php _e('City', 'pro-members-manager'); ?></th>
                        <th><?php _e('Type', 'pro-members-manager'); ?></th>
                        <th><?php _e('Joined', 'pro-members-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview_members as $member): ?>
                        <tr>
                            <td><?php echo esc_html($member['name']); ?></td>
                            <td><?php echo esc_html($member['company'] ?: '—'); ?></td>
                            <td><?php echo esc_html($member['email']); ?></td>
                            <td><?php echo esc_html($member['address']['city'] ?? '—'); ?></td>
                            <td>
                                <?php 
                                $type = $member['subscription_details']['type'] ?? 'unknown';
                                $renewal = $member['subscription_details']['renewal_type'] ?? 'unknown';
                                echo esc_html(ucfirst($type) . ' (' . ucfirst($renewal) . ')'); 
                                ?>
                            </td>
                            <td><?php echo esc_html(date('d/m/Y', strtotime($member['joined_date']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_count > 5): ?>
                <p class="description"><?php printf(__('Showing first 5 of %d members. All %d members will be included in the CSV export.', 'pro-members-manager'), $total_count, $total_count); ?></p>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="notice notice-info">
                <p><?php _e('No members found matching your criteria.', 'pro-members-manager'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="pmm-export-info">
        <h3><?php _e('Export Information', 'pro-members-manager'); ?></h3>
        <ul>
            <li><?php _e('The CSV file will include: Name, Company, Email, Address, Phone, Membership Type, Join Date, and Payment Information', 'pro-members-manager'); ?></li>
            <li><?php _e('Files are encoded in UTF-8 with BOM for Excel compatibility', 'pro-members-manager'); ?></li>
            <li><?php _e('Semicolon (;) is used as field separator', 'pro-members-manager'); ?></li>
            <li><?php _e('Large exports may take some time to process', 'pro-members-manager'); ?></li>
        </ul>
    </div>
</div>

<style>
.pmm-export-form {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.pmm-export-actions {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.pmm-export-preview {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.pmm-export-info {
    background: #f0f6fc;
    border: 1px solid #c3e4f7;
    border-radius: 4px;
    padding: 20px;
}

.pmm-export-info ul {
    margin-left: 20px;
}
</style>
