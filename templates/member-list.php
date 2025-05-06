<?php
/**
 * Template for displaying the member list
 *
 * @package Pro_Members_Manager
 */

defined('ABSPATH') || exit;

// Calculate the from and to dates in readable format
$from_date_display = date('d-m-Y', strtotime($from_date));
$to_date_display = date('d-m-Y', strtotime($to_date));
?>

<div class="pmm-member-administration">
    <div class="pmm-topgrid">
        <div class="pmm-lefttopgrid">
            <h1><?php _e('Member Administration', 'pro-members-manager'); ?></h1>
            <p class="pmm-period"><?php printf(__('Period: %s to %s', 'pro-members-manager'), $from_date_display, $to_date_display); ?></p>
        </div>
        <div class="pmm-righttopgrid">
            <button id="pmm-create-member" class="button button-primary"><?php _e('Create Manual Member', 'pro-members-manager'); ?></button>
        </div>
    </div>

    <?php 
    // Display statistics section
    do_action('pmm_before_member_list', $stats); 
    
    // Display statistics grid
    ?>
    <div class="pmm-graph-grid">
        <div class="pmm-graph-grid-row pmm-numbers">
            <div class="pmm-graph-data-box pmm-private">
                <div class="pmm-graph-headline">
                    <h3><?php _e('Private:', 'pro-members-manager'); ?></h3>
                    <h3><?php echo $stats['private']['total']; ?></h3>
                </div>
                <div class="pmm-graph-subheadline">
                    <h4><?php _e('Auto Renewals', 'pro-members-manager'); ?></h4>
                    <h4><?php echo $stats['private']['auto']['total']; ?></h4>
                </div>
                <div class="pmm-graph-subdata">
                    <div><?php _e('Pensioners', 'pro-members-manager'); ?></div>
                    <div><?php echo $stats['private']['auto']['pension']; ?></div>
                </div>
                <div class="pmm-graph-subdata">
                    <div><?php _e('Regular', 'pro-members-manager'); ?></div>
                    <div><?php echo $stats['private']['auto']['regular']; ?></div>
                </div>
                <div class="pmm-graph-subheadline">
                    <h4><?php _e('Manual Renewals', 'pro-members-manager'); ?></h4>
                    <h4><?php echo $stats['private']['manual']['total']; ?></h4>
                </div>
                <div class="pmm-graph-subdata">
                    <div><?php _e('Pensioners', 'pro-members-manager'); ?></div>
                    <div><?php echo $stats['private']['manual']['pension']; ?></div>
                </div>
                <div class="pmm-graph-subdata">
                    <div><?php _e('Regular', 'pro-members-manager'); ?></div>
                    <div><?php echo $stats['private']['manual']['regular']; ?></div>
                </div>
                <div class="pmm-graph-headline">
                    <h3><?php _e('Organizations:', 'pro-members-manager'); ?></h3>
                    <h3><?php echo $stats['union']['total']; ?></h3>
                </div>
                <div class="pmm-graph-subdata">
                    <div><?php _e('From 4293', 'pro-members-manager'); ?></div>
                    <div><?php echo $stats['union']['in_dianalund']; ?></div>
                </div>
                <div class="pmm-graph-subdata">
                    <div><?php _e('Outside 4293', 'pro-members-manager'); ?></div>
                    <div><?php echo $stats['union']['outside_dianalund']; ?></div>
                </div>
            </div>
            <div class="pmm-graph-visual-box pmm-alle">
                <div id="pmm-membership-chart" style="width:100%; height:300px;"></div>
            </div>
        </div>
    </div>
    
    <?php 
    // Display export buttons
    do_action('pmm_after_member_list'); 
    ?>
    
    <div class="pmm-admin-search-allmembers">
        <input type="text" id="pmm-member-search" placeholder="<?php _e('Search for member', 'pro-members-manager'); ?>" class="pmm-search-input">
    </div>
    
    <div class="pmm-allmembers-edit-list-grid">
        <table class="pmm-members-table">
            <thead class="pmm-allmembers-edit-list-headline">
                <tr>
                    <th></th>
                    <th><?php _e('Member', 'pro-members-manager'); ?></th>
                    <th><?php _e('Email', 'pro-members-manager'); ?></th>
                    <th><?php _e('Organization', 'pro-members-manager'); ?></th>
                    <th><?php _e('Membership', 'pro-members-manager'); ?></th>
                    <th><?php _e('Member Since', 'pro-members-manager'); ?></th>
                    <th width="130px"><?php _e('Payment', 'pro-members-manager'); ?></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="pmm-allmembers-edit-list-body">
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
                        <td>
                            <button class="button pmm-edit-member" data-id="<?php echo esc_attr($member['id']); ?>">
                                <?php _e('Edit', 'pro-members-manager'); ?>
                            </button>
                        </td>
                        <td class="pmm-memberinfo">
                            <strong><?php printf(__('ID: %s', 'pro-members-manager'), esc_html($member['user_id'])); ?></strong><br>
                            <?php echo esc_html($member['name']); ?><br>
                            <?php echo esc_html($member['address']['line1']); ?><br>
                            <?php echo esc_html($member['address']['postcode']) . ' ' . esc_html($member['address']['city']); ?><br>
                            <?php printf(__('Phone: %s', 'pro-members-manager'), esc_html($member['phone'])); ?>
                        </td>
                        <td class="pmm-mailadresse">
                            <a href="mailto:<?php echo esc_attr($member['email']); ?>"><?php echo esc_html($member['email']); ?></a>
                        </td>
                        <td class="pmm-erhverv-forening"><?php echo esc_html($member['company']); ?></td>
                        <td><?php printf(__('%d x %s', 'pro-members-manager'), $quantity, $product_name); ?></td>
                        <td><?php echo date('d-m-Y', strtotime($member['joined_date'])); ?></td>
                        <td>
                            <span class="pmm-payment-method <?php echo esc_attr(str_replace(' ', '-', strtolower($member['payment_method']))); ?>">
                                <?php echo esc_html($member['payment_method']); ?>
                            </span>
                            <br>
                            <span class="pmm-qp-id"><?php _e('Transaction ID:', 'pro-members-manager'); ?></span><br>
                            <span class="pmm-qp-id number"><?php echo esc_html($member['payment_id']); ?></span>
                        </td>
                        <td>
                            <?php if (isset($member['subscription_actions']['cancel'])): ?>
                                <a href="<?php echo esc_url($member['subscription_actions']['cancel']); ?>" class="button pmm-cancel-btn">
                                    <?php _e('Cancel', 'pro-members-manager'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($member['subscription_actions']['change_payment'])): ?>
                                <a href="<?php echo esc_url($member['subscription_actions']['change_payment']); ?>" class="button pmm-change-btn">
                                    <?php _e('Change Payment', 'pro-members-manager'); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="pmm-admin-return-button">
        <a href="<?php echo esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>" class="button">
            <?php _e('Back to Your Profile', 'pro-members-manager'); ?>
        </a>
    </div>
    
    <div class="pmm-background-edit-user-modal" style="display:none;">
        <div class="pmm-show-edit-user-modal"></div>
    </div>
</div>

<!-- Member creation modal -->
<div id="pmm-create-member-modal" class="pmm-background-edit-user-modal" style="display:none;">
    <div class="pmm-create-manual-user-form">
        <h2><?php _e('Create Member', 'pro-members-manager'); ?></h2>
        <p class="pmm-result"></p>
        
        <div class="pmm-create-manual-member-selector-container">
            <label for="pmm-privatmedlem">
                <input type="radio" id="pmm-privatmedlem" name="pmm-medlemsprodukt" value="10968" checked>
                <?php _e('Private', 'pro-members-manager'); ?>
                <span class="pmm-create-manual-member-price">75,-</span>
            </label>
            <label for="pmm-pensionistmedlem">
                <input type="radio" id="pmm-pensionistmedlem" name="pmm-medlemsprodukt" value="28735">
                <?php _e('Pensioner', 'pro-members-manager'); ?>
                <span class="pmm-create-manual-member-price">50,-</span>
            </label>
            <label for="pmm-foreningmedlem">
                <input type="radio" id="pmm-foreningmedlem" name="pmm-medlemsprodukt" value="19221">
                <?php _e('Organization', 'pro-members-manager'); ?>
                <span class="pmm-create-manual-member-price">150,-</span>
            </label>
            <label for="pmm-erhvervmedlem">
                <input type="radio" id="pmm-erhvervmedlem" name="pmm-medlemsprodukt" value="29916">
                <?php _e('Business', 'pro-members-manager'); ?>
                <span class="pmm-create-manual-member-price">1.000,-</span>
            </label>
        </div>
        
        <input type="hidden" name="action" value="pmm_create_member">
        <input type="hidden" name="nonce" id="pmm-membernonce" value="<?php echo wp_create_nonce('pmm-create-member-nonce'); ?>">
        
        <div class="pmm-create-manual-member-text-felter-container">
            <label for="pmm-createfornavn"><?php _e('First Name:', 'pro-members-manager'); ?></label>
            <input type="text" id="pmm-createfornavn" name="first_name" placeholder="<?php esc_attr_e('Enter first name', 'pro-members-manager'); ?>" required/>
            
            <label for="pmm-createefternavn"><?php _e('Last Name:', 'pro-members-manager'); ?></label>
            <input type="text" id="pmm-createefternavn" name="last_name" placeholder="<?php esc_attr_e('Enter last name', 'pro-members-manager'); ?>" required/>
            
            <label for="pmm-createadresse"><?php _e('Address:', 'pro-members-manager'); ?></label>
            <input type="text" id="pmm-createadresse" name="address_1" placeholder="<?php esc_attr_e('Enter address', 'pro-members-manager'); ?>" required/>
            
            <label for="pmm-createpostnr"><?php _e('Postal Code:', 'pro-members-manager'); ?></label>
            <input type="text" id="pmm-createpostnr" name="postcode" placeholder="<?php esc_attr_e('Enter postal code', 'pro-members-manager'); ?>" required/>
            
            <label for="pmm-createby"><?php _e('City:', 'pro-members-manager'); ?></label>
            <input type="text" id="pmm-createby" name="city" placeholder="<?php esc_attr_e('Enter city', 'pro-members-manager'); ?>" required/>
            
            <label for="pmm-createforening"><?php _e('Organization:', 'pro-members-manager'); ?></label>
            <input type="text" id="pmm-createforening" name="company" placeholder="<?php esc_attr_e('Enter organization name', 'pro-members-manager'); ?>"/>
            
            <label for="pmm-createtlf"><?php _e('Phone:', 'pro-members-manager'); ?></label>
            <input type="text" id="pmm-createtlf" name="phone" placeholder="<?php esc_attr_e('Enter phone number', 'pro-members-manager'); ?>" required/>
            
            <div class="pmm-create-buttons-collection">
                <button id="pmm-cancel-manual-member-button" class="button"><?php _e('Cancel', 'pro-members-manager'); ?></button>
                <button id="pmm-create-manual-member-button" class="button button-primary"><?php _e('Create Membership', 'pro-members-manager'); ?></button>
            </div>
        </div>
        
        <button id="pmm-close-button" class="button"><?php _e('Close', 'pro-members-manager'); ?></button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Membership chart
    var ctx = document.getElementById('pmm-membership-chart').getContext('2d');
    var membershipChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['<?php _e("Private", "pro-members-manager"); ?>', '<?php _e("Organization", "pro-members-manager"); ?>'],
            datasets: [{
                data: [<?php echo $stats['private']['total']; ?>, <?php echo $stats['union']['total']; ?>],
                backgroundColor: ['#4e73df', '#1cc88a']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });

    // Search functionality
    $('#pmm-member-search').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.pmm-table-row').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Edit member functionality
    $('.pmm-edit-member').on('click', function() {
        var memberId = $(this).data('id');
        
        // Show loading state
        $('.pmm-background-edit-user-modal').show();
        $('.pmm-show-edit-user-modal').html('<p><?php _e("Loading member details...", "pro-members-manager"); ?></p>');
        
        // Load member details via AJAX
        $.ajax({
            url: pmm_data.ajax_url,
            type: 'POST',
            data: {
                action: 'pmm_edit_member',
                order_id: memberId,
                nonce: pmm_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.pmm-show-edit-user-modal').html(response.data.html);
                } else {
                    $('.pmm-show-edit-user-modal').html('<p class="pmm-error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('.pmm-show-edit-user-modal').html('<p class="pmm-error"><?php _e("An error occurred. Please try again.", "pro-members-manager"); ?></p>');
            }
        });
    });
    
    // Close modal functionality
    $(document).on('click', '#pmm-close-button', function() {
        $('.pmm-background-edit-user-modal').hide();
        $('.pmm-show-edit-user-modal').html('');
    });
    
    // Create member modal
    $('#pmm-create-member').on('click', function() {
        $('#pmm-create-member-modal').show();
    });
    
    // Cancel member creation
    $('#pmm-cancel-manual-member-button').on('click', function() {
        $('#pmm-create-member-modal').hide();
    });
    
    // Create member form submission
    $('#pmm-create-manual-member-button').on('click', function() {
        var $btn = $(this);
        var productId = $('input[name="pmm-medlemsprodukt"]:checked').val();
        
        // Show loading state
        $btn.prop('disabled', true).text('<?php _e("Creating...", "pro-members-manager"); ?>');
        $('.pmm-result').html('');
        
        // Collect form data
        var formData = {
            action: 'pmm_create_member',
            nonce: $('#pmm-membernonce').val(),
            product_id: productId,
            first_name: $('#pmm-createfornavn').val(),
            last_name: $('#pmm-createefternavn').val(),
            address_1: $('#pmm-createadresse').val(),
            postcode: $('#pmm-createpostnr').val(),
            city: $('#pmm-createby').val(),
            company: $('#pmm-createforening').val(),
            phone: $('#pmm-createtlf').val()
        };
        
        // Submit form data via AJAX
        $.ajax({
            url: pmm_data.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('.pmm-result').html('<p class="pmm-success">' + response.data.message + '</p>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('.pmm-result').html('<p class="pmm-error">' + response.data.message + '</p>');
                    $btn.prop('disabled', false).text('<?php _e("Create Membership", "pro-members-manager"); ?>');
                }
            },
            error: function() {
                $('.pmm-result').html('<p class="pmm-error"><?php _e("An error occurred. Please try again.", "pro-members-manager"); ?></p>');
                $btn.prop('disabled', false).text('<?php _e("Create Membership", "pro-members-manager"); ?>');
            }
        });
    });
});
</script>