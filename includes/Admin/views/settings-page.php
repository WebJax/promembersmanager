<?php
defined('ABSPATH') || exit;

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['pmm_settings_nonce'], 'pmm_save_settings')) {
    // Save general settings
    if ($active_tab === 'general') {
        update_option('pmm_dianalund_postcode', sanitize_text_field($_POST['pmm_dianalund_postcode']));
        update_option('pmm_enable_frontend_list', isset($_POST['pmm_enable_frontend_list']) ? 1 : 0);
        update_option('pmm_members_per_page', absint($_POST['pmm_members_per_page']));
        update_option('pmm_default_date_range', sanitize_text_field($_POST['pmm_default_date_range']));
    }
    
    // Save product settings
    if ($active_tab === 'products') {
        update_option('pmm_private_auto_product', absint($_POST['pmm_private_auto_product']));
        update_option('pmm_private_manual_product', absint($_POST['pmm_private_manual_product']));
        update_option('pmm_pension_auto_product', absint($_POST['pmm_pension_auto_product']));
        update_option('pmm_pension_manual_product', absint($_POST['pmm_pension_manual_product']));
        update_option('pmm_union_auto_product', absint($_POST['pmm_union_auto_product']));
        update_option('pmm_union_manual_product', absint($_POST['pmm_union_manual_product']));
    }
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'pro-members-manager') . '</p></div>';
}

// Get current settings
$dianalund_postcode = get_option('pmm_dianalund_postcode', '4293');
$enable_frontend = get_option('pmm_enable_frontend_list', 1);
$members_per_page = get_option('pmm_members_per_page', 20);
$default_date_range = get_option('pmm_default_date_range', '1_year');

$private_auto_product = get_option('pmm_private_auto_product', 9503);
$private_manual_product = get_option('pmm_private_manual_product', 10968);
$pension_auto_product = get_option('pmm_pension_auto_product', 28736);
$pension_manual_product = get_option('pmm_pension_manual_product', 28735);
$union_auto_product = get_option('pmm_union_auto_product', 30734);
$union_manual_product = get_option('pmm_union_manual_product', 19221);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="?page=pmm-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('General', 'pro-members-manager'); ?>
        </a>
        <a href="?page=pmm-settings&tab=products" class="nav-tab <?php echo $active_tab === 'products' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Product Settings', 'pro-members-manager'); ?>
        </a>
        <a href="?page=pmm-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Advanced', 'pro-members-manager'); ?>
        </a>
    </nav>
    
    <div class="tab-content">
        <form method="post" action="">
            <?php wp_nonce_field('pmm_save_settings', 'pmm_settings_nonce'); ?>
            
            <?php if ($active_tab === 'general'): ?>
                <!-- General Settings -->
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pmm_dianalund_postcode"><?php _e('Dianalund Postcode', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="pmm_dianalund_postcode" name="pmm_dianalund_postcode" 
                                   value="<?php echo esc_attr($dianalund_postcode); ?>" class="regular-text">
                            <p class="description"><?php _e('Postcode used to identify union members in Dianalund', 'pro-members-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="pmm_enable_frontend_list"><?php _e('Enable Frontend Member List', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="pmm_enable_frontend_list" name="pmm_enable_frontend_list" value="1" 
                                   <?php checked($enable_frontend, 1); ?>>
                            <p class="description"><?php _e('Allow authorized users to view member list on frontend using shortcode [pro_members_list]', 'pro-members-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="pmm_members_per_page"><?php _e('Members Per Page', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="pmm_members_per_page" name="pmm_members_per_page" 
                                   value="<?php echo esc_attr($members_per_page); ?>" min="10" max="100" class="small-text">
                            <p class="description"><?php _e('Number of members to display per page in lists', 'pro-members-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="pmm_default_date_range"><?php _e('Default Date Range', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <select id="pmm_default_date_range" name="pmm_default_date_range">
                                <option value="1_month" <?php selected($default_date_range, '1_month'); ?>><?php _e('Last Month', 'pro-members-manager'); ?></option>
                                <option value="3_months" <?php selected($default_date_range, '3_months'); ?>><?php _e('Last 3 Months', 'pro-members-manager'); ?></option>
                                <option value="6_months" <?php selected($default_date_range, '6_months'); ?>><?php _e('Last 6 Months', 'pro-members-manager'); ?></option>
                                <option value="1_year" <?php selected($default_date_range, '1_year'); ?>><?php _e('Last Year', 'pro-members-manager'); ?></option>
                                <option value="all" <?php selected($default_date_range, 'all'); ?>><?php _e('All Time', 'pro-members-manager'); ?></option>
                            </select>
                            <p class="description"><?php _e('Default date range for member lists and statistics', 'pro-members-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
            <?php elseif ($active_tab === 'products'): ?>
                <!-- Product Settings -->
                <h2><?php _e('WooCommerce Product Mapping', 'pro-members-manager'); ?></h2>
                <p><?php _e('Map your WooCommerce products to membership types. These settings determine how members are categorized.', 'pro-members-manager'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th colspan="2"><h3><?php _e('Private Membership Products', 'pro-members-manager'); ?></h3></th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pmm_private_auto_product"><?php _e('Auto Renewal Product ID', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="pmm_private_auto_product" name="pmm_private_auto_product" 
                                   value="<?php echo esc_attr($private_auto_product); ?>" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pmm_private_manual_product"><?php _e('Manual Renewal Product ID', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="pmm_private_manual_product" name="pmm_private_manual_product" 
                                   value="<?php echo esc_attr($private_manual_product); ?>" class="small-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th colspan="2"><h3><?php _e('Pension Membership Products', 'pro-members-manager'); ?></h3></th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pmm_pension_auto_product"><?php _e('Auto Renewal Product ID', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="pmm_pension_auto_product" name="pmm_pension_auto_product" 
                                   value="<?php echo esc_attr($pension_auto_product); ?>" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pmm_pension_manual_product"><?php _e('Manual Renewal Product ID', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="pmm_pension_manual_product" name="pmm_pension_manual_product" 
                                   value="<?php echo esc_attr($pension_manual_product); ?>" class="small-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th colspan="2"><h3><?php _e('Union Membership Products', 'pro-members-manager'); ?></h3></th>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pmm_union_auto_product"><?php _e('Auto Renewal Product ID', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="pmm_union_auto_product" name="pmm_union_auto_product" 
                                   value="<?php echo esc_attr($union_auto_product); ?>" class="small-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pmm_union_manual_product"><?php _e('Manual Renewal Product ID', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="pmm_union_manual_product" name="pmm_union_manual_product" 
                                   value="<?php echo esc_attr($union_manual_product); ?>" class="small-text">
                        </td>
                    </tr>
                </table>
                
            <?php elseif ($active_tab === 'advanced'): ?>
                <!-- Advanced Settings -->
                <h2><?php _e('Advanced Settings', 'pro-members-manager'); ?></h2>
                <p><?php _e('Advanced configuration options for the Pro Members Manager plugin.', 'pro-members-manager'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Database Tables', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <p><?php _e('Click the button below to recreate database tables if needed.', 'pro-members-manager'); ?></p>
                            <button type="button" class="button button-secondary" onclick="recreateTables()">
                                <?php _e('Recreate Database Tables', 'pro-members-manager'); ?>
                            </button>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Plugin Information', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <p><strong><?php _e('Version:', 'pro-members-manager'); ?></strong> <?php echo PMM_PLUGIN_VERSION; ?></p>
                            <p><strong><?php _e('Database Version:', 'pro-members-manager'); ?></strong> <?php echo PMM_DB_VERSION; ?></p>
                            <p><strong><?php _e('Plugin Path:', 'pro-members-manager'); ?></strong> <?php echo PMM_PLUGIN_PATH; ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label><?php _e('Debug Information', 'pro-members-manager'); ?></label>
                        </th>
                        <td>
                            <?php
                            global $wpdb;
                            $members_table = $wpdb->prefix . 'pmm_membership_metadata';
                            $stats_table = $wpdb->prefix . 'pmm_dayly_statistics';
                            
                            $members_exists = $wpdb->get_var("SHOW TABLES LIKE '$members_table'") === $members_table;
                            $stats_exists = $wpdb->get_var("SHOW TABLES LIKE '$stats_table'") === $stats_table;
                            ?>
                            <p><strong><?php _e('Members Table:', 'pro-members-manager'); ?></strong> 
                               <?php echo $members_exists ? '<span style="color:green;">✓ Exists</span>' : '<span style="color:red;">✗ Missing</span>'; ?>
                            </p>
                            <p><strong><?php _e('Statistics Table:', 'pro-members-manager'); ?></strong> 
                               <?php echo $stats_exists ? '<span style="color:green;">✓ Exists</span>' : '<span style="color:red;">✗ Missing</span>'; ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
            <?php endif; ?>
            
            <?php submit_button(); ?>
        </form>
    </div>
</div>

<script>
function recreateTables() {
    if (confirm('<?php _e('Are you sure you want to recreate database tables? This will not delete existing data.', 'pro-members-manager'); ?>')) {
        jQuery.post(ajaxurl, {
            action: 'pmm_recreate_tables',
            nonce: '<?php echo wp_create_nonce('pmm_recreate_tables'); ?>'
        }, function(response) {
            if (response.success) {
                alert('<?php _e('Tables recreated successfully!', 'pro-members-manager'); ?>');
                location.reload();
            } else {
                alert('<?php _e('Error recreating tables:', 'pro-members-manager'); ?> ' + response.data.message);
            }
        });
    }
}
</script>

<style>
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.tab-content {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.form-table h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #0073aa;
}
</style>
