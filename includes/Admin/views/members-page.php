<?php
/**
 * Admin view for the members page
 *
 * @package Pro_Members_Manager
 */

defined('ABSPATH') || exit;

// Get member statistics and data
$stats_manager = new ProMembersManager\Core\Stats_Manager();
$member_manager = new ProMembersManager\Core\Member_Manager();

// Set default date range
$from_date = isset($_GET['from_date']) ? sanitize_text_field($_GET['from_date']) : date('Y-m-d', strtotime('-1 year'));
$to_date = isset($_GET['to_date']) ? sanitize_text_field($_GET['to_date']) : date('Y-m-d');

// Get arguments based on tab
$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'all';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 20;

$args = [
    'from_date' => $from_date,
    'to_date' => $to_date,
    'per_page' => $per_page,
    'page' => $page,
    'search' => $search
];

// Add specific filters based on tab
switch ($tab) {
    case 'private':
        $args['member_type'] = 'private';
        break;
    case 'pension':
        $args['member_type'] = 'pension';
        break;
    case 'union':
        $args['member_type'] = 'union';
        break;
    case 'auto':
        $args['renewal_type'] = 'auto';
        break;
    case 'manual':
        $args['renewal_type'] = 'manual';
        break;
    case 'dianalund':
        $args['postcode'] = '4293';
        break;
}

// Get members and count
$members = $member_manager->get_members($args);
$total_members = count($members);
$total_pages = ceil($total_members / $per_page);

// Get statistics for display
$stats = $stats_manager->get_stats([
    'from_date' => $from_date,
    'to_date' => $to_date
]);

// Format date for display
$from_date_display = date_i18n(get_option('date_format'), strtotime($from_date));
$to_date_display = date_i18n(get_option('date_format'), strtotime($to_date));
?>

<div class="wrap pmm-admin-wrap">
    <h1 class="wp-heading-inline"><?php _e('Members Manager', 'pro-members-manager'); ?></h1>
    
    <a href="#" class="page-title-action" id="pmm-create-member"><?php _e('Add Member', 'pro-members-manager'); ?></a>
    
    <!-- Date range filter -->
    <div class="pmm-date-range-filter">
        <form method="get">
            <input type="hidden" name="page" value="pro-members-manager">
            <?php if (!empty($tab)): ?>
                <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>">
            <?php endif; ?>
            
            <label for="from_date"><?php _e('From:', 'pro-members-manager'); ?></label>
            <input type="date" id="from_date" name="from_date" value="<?php echo esc_attr($from_date); ?>">
            
            <label for="to_date"><?php _e('To:', 'pro-members-manager'); ?></label>
            <input type="date" id="to_date" name="to_date" value="<?php echo esc_attr($to_date); ?>">
            
            <button type="submit" class="button"><?php _e('Filter', 'pro-members-manager'); ?></button>
        </form>
    </div>
    
    <!-- Statistics Overview -->
    <div class="pmm-stats-overview">
        <div class="pmm-stats-card">
            <h3><?php _e('Total Members', 'pro-members-manager'); ?></h3>
            <div class="pmm-stats-value"><?php echo esc_html($stats['total']); ?></div>
        </div>
        
        <div class="pmm-stats-card">
            <h3><?php _e('Private Members', 'pro-members-manager'); ?></h3>
            <div class="pmm-stats-value"><?php echo esc_html($stats['private']['total']); ?></div>
        </div>
        
        <div class="pmm-stats-card">
            <h3><?php _e('Organizations', 'pro-members-manager'); ?></h3>
            <div class="pmm-stats-value"><?php echo esc_html($stats['union']['total']); ?></div>
        </div>
        
        <div class="pmm-stats-card">
            <h3><?php _e('Auto Renewals', 'pro-members-manager'); ?></h3>
            <div class="pmm-stats-value">
                <?php echo esc_html($stats['private']['auto']['total'] + $stats['union']['total'] - $stats['private']['manual']['total']); ?>
            </div>
        </div>
    </div>
    
    <!-- Tab navigation -->
    <nav class="nav-tab-wrapper">
        <a href="?page=pro-members-manager" class="nav-tab <?php echo $tab === 'all' ? 'nav-tab-active' : ''; ?>">
            <?php _e('All Members', 'pro-members-manager'); ?>
        </a>
        <a href="?page=pro-members-manager&tab=private" class="nav-tab <?php echo $tab === 'private' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Private', 'pro-members-manager'); ?>
        </a>
        <a href="?page=pro-members-manager&tab=pension" class="nav-tab <?php echo $tab === 'pension' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Pensioners', 'pro-members-manager'); ?>
        </a>
        <a href="?page=pro-members-manager&tab=union" class="nav-tab <?php echo $tab === 'union' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Organizations', 'pro-members-manager'); ?>
        </a>
        <a href="?page=pro-members-manager&tab=auto" class="nav-tab <?php echo $tab === 'auto' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Auto Renewals', 'pro-members-manager'); ?>
        </a>
        <a href="?page=pro-members-manager&tab=manual" class="nav-tab <?php echo $tab === 'manual' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Manual Renewals', 'pro-members-manager'); ?>
        </a>
        <a href="?page=pro-members-manager&tab=dianalund" class="nav-tab <?php echo $tab === 'dianalund' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Dianalund', 'pro-members-manager'); ?>
        </a>
    </nav>
    
    <!-- Search box -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get">
                <input type="hidden" name="page" value="pro-members-manager">
                <?php if (!empty($tab)): ?>
                    <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>">
                <?php endif; ?>
                <input type="hidden" name="from_date" value="<?php echo esc_attr($from_date); ?>">
                <input type="hidden" name="to_date" value="<?php echo esc_attr($to_date); ?>">
                
                <input type="search" id="pmm-search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search members...', 'pro-members-manager'); ?>">
                <input type="submit" class="button" value="<?php esc_attr_e('Search', 'pro-members-manager'); ?>">
                
                <?php if (!empty($search)): ?>
                    <a href="?page=pro-members-manager<?php echo !empty($tab) ? '&tab=' . esc_attr($tab) : ''; ?>&from_date=<?php echo esc_attr($from_date); ?>&to_date=<?php echo esc_attr($to_date); ?>" class="button"><?php _e('Clear', 'pro-members-manager'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Export actions -->
        <div class="alignright">
            <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" class="pmm-export-form">
                <input type="hidden" name="action" value="pmm_export_csv">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('pmm_admin_nonce'); ?>">
                <input type="hidden" name="tab" value="<?php echo esc_attr($tab); ?>">
                <input type="hidden" name="from_date" value="<?php echo esc_attr($from_date); ?>">
                <input type="hidden" name="to_date" value="<?php echo esc_attr($to_date); ?>">
                <input type="hidden" name="search" value="<?php echo esc_attr($search); ?>">
                
                <button type="submit" class="button"><span class="dashicons dashicons-media-spreadsheet"></span> <?php _e('Export CSV', 'pro-members-manager'); ?></button>
            </form>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s member', '%s members', $total_members, 'pro-members-manager'), number_format_i18n($total_members)); ?>
                </span>
                
                <span class="pagination-links">
                    <?php
                    // First page
                    if ($page > 1) {
                        printf(
                            '<a class="first-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">«</span></a>',
                            esc_url(add_query_arg(['paged' => 1])),
                            __('First page', 'pro-members-manager')
                        );
                    } else {
                        echo '<span class="first-page button disabled"><span class="screen-reader-text">' . __('First page', 'pro-members-manager') . '</span><span aria-hidden="true">«</span></span>';
                    }
                    
                    // Previous page
                    if ($page > 1) {
                        printf(
                            '<a class="prev-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">‹</span></a>',
                            esc_url(add_query_arg(['paged' => max(1, $page - 1)])),
                            __('Previous page', 'pro-members-manager')
                        );
                    } else {
                        echo '<span class="prev-page button disabled"><span class="screen-reader-text">' . __('Previous page', 'pro-members-manager') . '</span><span aria-hidden="true">‹</span></span>';
                    }
                    
                    // Current page indicator
                    printf(
                        '<span class="paging-input"><span class="current-page">%s</span> / <span class="total-pages">%s</span></span>',
                        number_format_i18n($page),
                        number_format_i18n($total_pages)
                    );
                    
                    // Next page
                    if ($page < $total_pages) {
                        printf(
                            '<a class="next-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">›</span></a>',
                            esc_url(add_query_arg(['paged' => min($total_pages, $page + 1)])),
                            __('Next page', 'pro-members-manager')
                        );
                    } else {
                        echo '<span class="next-page button disabled"><span class="screen-reader-text">' . __('Next page', 'pro-members-manager') . '</span><span aria-hidden="true">›</span></span>';
                    }
                    
                    // Last page
                    if ($page < $total_pages) {
                        printf(
                            '<a class="last-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">»</span></a>',
                            esc_url(add_query_arg(['paged' => $total_pages])),
                            __('Last page', 'pro-members-manager')
                        );
                    } else {
                        echo '<span class="last-page button disabled"><span class="screen-reader-text">' . __('Last page', 'pro-members-manager') . '</span><span aria-hidden="true">»</span></span>';
                    }
                    ?>
                </span>
            </div>
        <?php endif; ?>
        
        <br class="clear">
    </div>
    
    <!-- Members table -->
    <table class="wp-list-table widefat fixed striped pmm-members-table">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-id"><?php _e('ID', 'pro-members-manager'); ?></th>
                <th scope="col" class="manage-column column-name"><?php _e('Name', 'pro-members-manager'); ?></th>
                <th scope="col" class="manage-column column-email"><?php _e('Email', 'pro-members-manager'); ?></th>
                <th scope="col" class="manage-column column-address"><?php _e('Address', 'pro-members-manager'); ?></th>
                <th scope="col" class="manage-column column-company"><?php _e('Organization', 'pro-members-manager'); ?></th>
                <th scope="col" class="manage-column column-membership"><?php _e('Membership', 'pro-members-manager'); ?></th>
                <th scope="col" class="manage-column column-joined"><?php _e('Member Since', 'pro-members-manager'); ?></th>
                <th scope="col" class="manage-column column-payment"><?php _e('Payment', 'pro-members-manager'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'pro-members-manager'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (empty($members)): ?>
                <tr>
                    <td colspan="9"><?php _e('No members found.', 'pro-members-manager'); ?></td>
                </tr>
            <?php else: ?>
                <?php 
                $member_types = [
                    '9503'  => __('Auto Private', 'pro-members-manager'),
                    '10968' => __('Manual Private', 'pro-members-manager'),
                    '28736' => __('Auto Pensioner', 'pro-members-manager'),
                    '28735' => __('Manual Pensioner', 'pro-members-manager'),
                    '30734' => __('Auto Organization', 'pro-members-manager'),
                    '19221' => __('Manual Organization', 'pro-members-manager'),
                ];
                
                foreach ($members as $member): 
                    $product_id = $member['subscription_details']['product_id'];
                    $product_name = isset($member_types[$product_id]) ? $member_types[$product_id] : __('Unknown', 'pro-members-manager');
                    $quantity = $member['subscription_details']['quantity'];
                ?>
                    <tr data-row-order-id="<?php echo esc_attr($member['id']); ?>" class="pmm-table-row">
                        <td class="column-id">
                            <?php echo esc_html($member['id']); ?>
                            <br>
                            <small><?php _e('User ID:', 'pro-members-manager'); ?> <?php echo esc_html($member['user_id']); ?></small>
                        </td>
                        <td class="column-name">
                            <?php echo esc_html($member['name']); ?>
                            <br>
                            <small><?php _e('Phone:', 'pro-members-manager'); ?> <?php echo esc_html($member['phone']); ?></small>
                        </td>
                        <td class="column-email">
                            <a href="mailto:<?php echo esc_attr($member['email']); ?>"><?php echo esc_html($member['email']); ?></a>
                        </td>
                        <td class="column-address">
                            <?php echo esc_html($member['address']['line1']); ?><br>
                            <?php echo esc_html($member['address']['postcode']) . ' ' . esc_html($member['address']['city']); ?>
                        </td>
                        <td class="column-company">
                            <?php echo esc_html($member['company']); ?>
                        </td>
                        <td class="column-membership">
                            <?php printf(__('%d x %s', 'pro-members-manager'), $quantity, $product_name); ?>
                        </td>
                        <td class="column-joined">
                            <?php echo date_i18n(get_option('date_format'), strtotime($member['joined_date'])); ?>
                        </td>
                        <td class="column-payment">
                            <span class="pmm-payment-method <?php echo esc_attr(str_replace(' ', '-', strtolower($member['payment_method']))); ?>">
                                <?php echo esc_html($member['payment_method']); ?>
                            </span>
                            <br>
                            <small><?php _e('Transaction ID:', 'pro-members-manager'); ?></small><br>
                            <code><?php echo esc_html($member['payment_id']); ?></code>
                        </td>
                        <td class="column-actions">
                            <button class="button pmm-edit-member" data-id="<?php echo esc_attr($member['id']); ?>">
                                <span class="dashicons dashicons-edit"></span> <?php _e('Edit', 'pro-members-manager'); ?>
                            </button>
                            
                            <?php if (isset($member['subscription_actions']['cancel'])): ?>
                                <a href="<?php echo esc_url($member['subscription_actions']['cancel']); ?>" class="button pmm-cancel-btn" onclick="return confirm('<?php esc_attr_e('Are you sure you want to cancel this membership?', 'pro-members-manager'); ?>');">
                                    <span class="dashicons dashicons-no"></span> <?php _e('Cancel', 'pro-members-manager'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Bottom pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s member', '%s members', $total_members, 'pro-members-manager'), number_format_i18n($total_members)); ?>
                </span>
                
                <span class="pagination-links">
                    <?php
                    // First page
                    if ($page > 1) {
                        printf(
                            '<a class="first-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">«</span></a>',
                            esc_url(add_query_arg(['paged' => 1])),
                            __('First page', 'pro-members-manager')
                        );
                    } else {
                        echo '<span class="first-page button disabled"><span class="screen-reader-text">' . __('First page', 'pro-members-manager') . '</span><span aria-hidden="true">«</span></span>';
                    }
                    
                    // Previous page
                    if ($page > 1) {
                        printf(
                            '<a class="prev-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">‹</span></a>',
                            esc_url(add_query_arg(['paged' => max(1, $page - 1)])),
                            __('Previous page', 'pro-members-manager')
                        );
                    } else {
                        echo '<span class="prev-page button disabled"><span class="screen-reader-text">' . __('Previous page', 'pro-members-manager') . '</span><span aria-hidden="true">‹</span></span>';
                    }
                    
                    // Current page indicator
                    printf(
                        '<span class="paging-input"><span class="current-page">%s</span> / <span class="total-pages">%s</span></span>',
                        number_format_i18n($page),
                        number_format_i18n($total_pages)
                    );
                    
                    // Next page
                    if ($page < $total_pages) {
                        printf(
                            '<a class="next-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">›</span></a>',
                            esc_url(add_query_arg(['paged' => min($total_pages, $page + 1)])),
                            __('Next page', 'pro-members-manager')
                        );
                    } else {
                        echo '<span class="next-page button disabled"><span class="screen-reader-text">' . __('Next page', 'pro-members-manager') . '</span><span aria-hidden="true">›</span></span>';
                    }
                    
                    // Last page
                    if ($page < $total_pages) {
                        printf(
                            '<a class="last-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">»</span></a>',
                            esc_url(add_query_arg(['paged' => $total_pages])),
                            __('Last page', 'pro-members-manager')
                        );
                    } else {
                        echo '<span class="last-page button disabled"><span class="screen-reader-text">' . __('Last page', 'pro-members-manager') . '</span><span aria-hidden="true">»</span></span>';
                    }
                    ?>
                </span>
            </div>
            
            <br class="clear">
        </div>
    <?php endif; ?>
</div>

<!-- Edit Member Modal -->
<div class="pmm-modal-background" id="pmm-edit-member-modal" style="display:none;">
    <div class="pmm-modal-content"></div>
</div>

<!-- Create Member Modal -->
<div class="pmm-modal-background" id="pmm-create-member-modal" style="display:none;">
    <div class="pmm-modal-content">
        <div class="pmm-modal-header">
            <h2><?php _e('Add New Member', 'pro-members-manager'); ?></h2>
            <button type="button" class="pmm-modal-close">×</button>
        </div>
        
        <div class="pmm-modal-body">
            <p class="pmm-result"></p>
            
            <form id="pmm-create-member-form">
                <input type="hidden" name="action" value="pmm_create_member">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('pmm_admin_nonce'); ?>">
                
                <div class="pmm-field-row">
                    <div class="pmm-field-column">
                        <label for="first_name"><?php _e('First Name:', 'pro-members-manager'); ?></label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="pmm-field-column">
                        <label for="last_name"><?php _e('Last Name:', 'pro-members-manager'); ?></label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="pmm-field-row">
                    <div class="pmm-field-column">
                        <label for="email"><?php _e('Email:', 'pro-members-manager'); ?></label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="pmm-field-column">
                        <label for="phone"><?php _e('Phone:', 'pro-members-manager'); ?></label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                </div>
                
                <div class="pmm-field-row">
                    <div class="pmm-field-full">
                        <label for="address_1"><?php _e('Address:', 'pro-members-manager'); ?></label>
                        <input type="text" id="address_1" name="address_1" required>
                    </div>
                </div>
                
                <div class="pmm-field-row">
                    <div class="pmm-field-column">
                        <label for="postcode"><?php _e('Postal Code:', 'pro-members-manager'); ?></label>
                        <input type="text" id="postcode" name="postcode" required>
                    </div>
                    <div class="pmm-field-column">
                        <label for="city"><?php _e('City:', 'pro-members-manager'); ?></label>
                        <input type="text" id="city" name="city" required>
                    </div>
                </div>
                
                <div class="pmm-field-row">
                    <div class="pmm-field-full">
                        <label for="company"><?php _e('Organization:', 'pro-members-manager'); ?></label>
                        <input type="text" id="company" name="company">
                    </div>
                </div>
                
                <div class="pmm-field-row">
                    <div class="pmm-field-full">
                        <label><?php _e('Membership Type:', 'pro-members-manager'); ?></label>
                        <div class="pmm-radio-group">
                            <label>
                                <input type="radio" name="product_id" value="10968" checked>
                                <?php _e('Private (Manual)', 'pro-members-manager'); ?>
                                <span class="pmm-price">75,-</span>
                            </label>
                            <label>
                                <input type="radio" name="product_id" value="28735">
                                <?php _e('Pensioner (Manual)', 'pro-members-manager'); ?>
                                <span class="pmm-price">50,-</span>
                            </label>
                            <label>
                                <input type="radio" name="product_id" value="19221">
                                <?php _e('Organization (Manual)', 'pro-members-manager'); ?>
                                <span class="pmm-price">150,-</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="pmm-field-row">
                    <div class="pmm-field-full">
                        <label for="quantity"><?php _e('Quantity:', 'pro-members-manager'); ?></label>
                        <input type="number" id="quantity" name="quantity" min="1" value="1">
                        <p class="pmm-field-help"><?php _e('For organizations with multiple members.', 'pro-members-manager'); ?></p>
                    </div>
                </div>
                
                <div class="pmm-field-row">
                    <div class="pmm-field-full">
                        <label for="notes"><?php _e('Notes:', 'pro-members-manager'); ?></label>
                        <textarea id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="pmm-form-actions">
                    <button type="button" class="button pmm-modal-cancel"><?php _e('Cancel', 'pro-members-manager'); ?></button>
                    <button type="submit" class="button button-primary"><?php _e('Create Member', 'pro-members-manager'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>