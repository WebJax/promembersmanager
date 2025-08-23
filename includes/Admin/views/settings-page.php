<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('pmm_settings'); ?>
        <?php do_settings_sections('pmm_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="pmm_member_types"><?php _e('Member Types Configuration', 'pro-members-manager'); ?></label>
                </th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php _e('Member Types Configuration', 'pro-members-manager'); ?></span>
                        </legend>
                        
                        <h4><?php _e('Private Membership', 'pro-members-manager'); ?></h4>
                        <p>
                            <label for="private_auto_product"><?php _e('Auto Renewal Product ID:', 'pro-members-manager'); ?></label>
                            <input type="number" id="private_auto_product" name="pmm_private_auto_product" 
                                   value="<?php echo esc_attr(get_option('pmm_private_auto_product', 9503)); ?>" class="small-text">
                        </p>
                        <p>
                            <label for="private_manual_product"><?php _e('Manual Renewal Product ID:', 'pro-members-manager'); ?></label>
                            <input type="number" id="private_manual_product" name="pmm_private_manual_product" 
                                   value="<?php echo esc_attr(get_option('pmm_private_manual_product', 10968)); ?>" class="small-text">
                        </p>
                        
                        <h4><?php _e('Pension Membership', 'pro-members-manager'); ?></h4>
                        <p>
                            <label for="pension_auto_product"><?php _e('Auto Renewal Product ID:', 'pro-members-manager'); ?></label>
                            <input type="number" id="pension_auto_product" name="pmm_pension_auto_product" 
                                   value="<?php echo esc_attr(get_option('pmm_pension_auto_product', 28736)); ?>" class="small-text">
                        </p>
                        <p>
                            <label for="pension_manual_product"><?php _e('Manual Renewal Product ID:', 'pro-members-manager'); ?></label>
                            <input type="number" id="pension_manual_product" name="pmm_pension_manual_product" 
                                   value="<?php echo esc_attr(get_option('pmm_pension_manual_product', 28735)); ?>" class="small-text">
                        </p>
                        
                        <h4><?php _e('Union Membership', 'pro-members-manager'); ?></h4>
                        <p>
                            <label for="union_auto_product"><?php _e('Auto Renewal Product ID:', 'pro-members-manager'); ?></label>
                            <input type="number" id="union_auto_product" name="pmm_union_auto_product" 
                                   value="<?php echo esc_attr(get_option('pmm_union_auto_product', 30734)); ?>" class="small-text">
                        </p>
                        <p>
                            <label for="union_manual_product"><?php _e('Manual Renewal Product ID:', 'pro-members-manager'); ?></label>
                            <input type="number" id="union_manual_product" name="pmm_union_manual_product" 
                                   value="<?php echo esc_attr(get_option('pmm_union_manual_product', 19221)); ?>" class="small-text">
                        </p>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="pmm_dianalund_postcode"><?php _e('Dianalund Postcode', 'pro-members-manager'); ?></label>
                </th>
                <td>
                    <input type="text" id="pmm_dianalund_postcode" name="pmm_dianalund_postcode" 
                           value="<?php echo esc_attr(get_option('pmm_dianalund_postcode', '4293')); ?>" class="regular-text">
                    <p class="description"><?php _e('Postcode used to identify union members in Dianalund', 'pro-members-manager'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="pmm_enable_frontend_list"><?php _e('Enable Frontend Member List', 'pro-members-manager'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="pmm_enable_frontend_list" name="pmm_enable_frontend_list" value="1" 
                           <?php checked(get_option('pmm_enable_frontend_list', 1), 1); ?>>
                    <p class="description"><?php _e('Allow authorized users to view member list on frontend', 'pro-members-manager'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>