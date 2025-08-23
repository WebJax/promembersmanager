<?php
/**
 * Template for displaying the member list
 *
 * @package Pro_Members_Manager
 */

defined('ABSPATH') || exit;

// Get current user permissions
$can_edit = current_user_can('edit_users');
$can_view = current_user_can('viewuserlist') || $can_edit;

if (!$can_view) {
    echo '<p>' . __('You do not have permission to view this page.', 'pro-members-manager') . '</p>';
    return;
}

// Get filter values
$from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : date('Y-m-d', strtotime('-1 year'));
$to_date = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : date('Y-m-d');
$member_type = isset($_GET['member_type']) ? sanitize_text_field($_GET['member_type']) : '';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// Get member manager instance
$member_manager = new ProMembersManager\Core\Member_Manager();

// Get members with filters
$args = [
    'from_date' => $from_date,
    'to_date' => $to_date,
    'member_type' => $member_type,
    'search' => $search,
    'page' => $page,
    'per_page' => 20
];

$members = $member_manager->get_members($args);
$stats = $member_manager->get_member_statistics($members);
?>

<div class="pmm-frontend-container">
    <h2><?php _e('Member List', 'pro-members-manager'); ?></h2>
    
    <!-- Statistics Overview -->
    <div class="pmm-stats-overview">
        <div class="stat-item">
            <span class="stat-label"><?php _e('Total Members:', 'pro-members-manager'); ?></span>
            <span class="stat-value"><?php echo count($members); ?></span>
        </div>
        
        <div class="stat-item">
            <span class="stat-label"><?php _e('Private Members:', 'pro-members-manager'); ?></span>
            <span class="stat-value"><?php echo $stats['private']['total'] ?? 0; ?></span>
        </div>
        
        <div class="stat-item">
            <span class="stat-label"><?php _e('Union Members:', 'pro-members-manager'); ?></span>
            <span class="stat-value"><?php echo $stats['union']['total'] ?? 0; ?></span>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="pmm-filters-frontend">
        <form method="get" class="pmm-filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="from_date"><?php _e('From Date:', 'pro-members-manager'); ?></label>
                    <input type="date" id="from_date" name="from_date" value="<?php echo esc_attr($from_date); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="to_date"><?php _e('To Date:', 'pro-members-manager'); ?></label>
                    <input type="date" id="to_date" name="to_date" value="<?php echo esc_attr($to_date); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="member_type"><?php _e('Type:', 'pro-members-manager'); ?></label>
                    <select id="member_type" name="member_type">
                        <option value=""><?php _e('All Types', 'pro-members-manager'); ?></option>
                        <option value="private" <?php selected($member_type, 'private'); ?>><?php _e('Private', 'pro-members-manager'); ?></option>
                        <option value="pension" <?php selected($member_type, 'pension'); ?>><?php _e('Pension', 'pro-members-manager'); ?></option>
                        <option value="union" <?php selected($member_type, 'union'); ?>><?php _e('Union', 'pro-members-manager'); ?></option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search"><?php _e('Search:', 'pro-members-manager'); ?></label>
                    <input type="text" id="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Name, email, company...', 'pro-members-manager'); ?>">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="button"><?php _e('Filter', 'pro-members-manager'); ?></button>
                    <a href="<?php echo remove_query_arg(['from_date', 'to_date', 'member_type', 'search', 'paged']); ?>" class="button"><?php _e('Reset', 'pro-members-manager'); ?></a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Export Button (Admin only) -->
    <?php if ($can_edit): ?>
        <div class="pmm-actions-bar">
            <button id="pmm-export-csv" class="button button-secondary">
                <?php _e('Export to CSV', 'pro-members-manager'); ?>
            </button>
            <button id="pmm-create-member" class="button button-primary">
                <?php _e('Create New Member', 'pro-members-manager'); ?>
            </button>
        </div>
    <?php endif; ?>
    
    <!-- Members Table -->
    <div class="pmm-members-table-container">
        <?php if (!empty($members)): ?>
            <table class="pmm-members-table">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'pro-members-manager'); ?></th>
                        <th><?php _e('Company', 'pro-members-manager'); ?></th>
                        <th><?php _e('Email', 'pro-members-manager'); ?></th>
                        <th><?php _e('Phone', 'pro-members-manager'); ?></th>
                        <th><?php _e('City', 'pro-members-manager'); ?></th>
                        <th><?php _e('Type', 'pro-members-manager'); ?></th>
                        <th><?php _e('Joined', 'pro-members-manager'); ?></th>
                        <?php if ($can_edit): ?>
                            <th><?php _e('Actions', 'pro-members-manager'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($member['name']); ?></strong>
                            </td>
                            <td><?php echo esc_html($member['company'] ?: '—'); ?></td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($member['email']); ?>">
                                    <?php echo esc_html($member['email']); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($member['phone'] ?: '—'); ?></td>
                            <td><?php echo esc_html($member['address']['city'] ?? '—'); ?></td>
                            <td>
                                <span class="member-type-badge member-type-<?php echo esc_attr($member['subscription_details']['type'] ?? 'unknown'); ?>">
                                    <?php 
                                    $type = $member['subscription_details']['type'] ?? 'unknown';
                                    $renewal = $member['subscription_details']['renewal_type'] ?? 'unknown';
                                    echo esc_html(ucfirst($type) . ' (' . ucfirst($renewal) . ')'); 
                                    ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date('d/m/Y', strtotime($member['joined_date']))); ?></td>
                            <?php if ($can_edit): ?>
                                <td>
                                    <button class="pmm-edit-member button-link" data-order-id="<?php echo esc_attr($member['id']); ?>">
                                        <?php _e('Edit', 'pro-members-manager'); ?>
                                    </button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination would go here -->
            <div class="pmm-pagination">
                <!-- TODO: Implement pagination -->
            </div>
            
        <?php else: ?>
            <div class="pmm-no-members">
                <p><?php _e('No members found matching your criteria.', 'pro-members-manager'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Include frontend scripts for member editing (admin only) -->
<?php if ($can_edit): ?>
    <script>
        var pmmFrontend = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('pmm_frontend_nonce'); ?>'
        };
    </script>
<?php endif; ?>