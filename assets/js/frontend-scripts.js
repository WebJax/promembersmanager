/**
 * Pro Members Manager - Frontend Scripts
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Member search functionality
        $('#pmm-member-search').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('.pmm-table-row').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
        
        // Member edit modal
        $('.pmm-edit-member').on('click', function() {
            var memberId = $(this).data('id');
            openMemberEditModal(memberId);
        });
        
        // Close modal buttons
        $(document).on('click', '.pmm-close-modal, #pmm-close-button', function() {
            closeMemberModals();
        });
        
        // Close modal on background click
        $('.pmm-background-edit-user-modal').on('click', function(e) {
            if ($(e.target).hasClass('pmm-background-edit-user-modal')) {
                closeMemberModals();
            }
        });
        
        // Create member button
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
            $btn.prop('disabled', true).text(pmm_data.i18n.loading);
            $('.pmm-result').html('');
            
            // Basic validation
            var firstNameField = $('#pmm-createfornavn');
            var lastNameField = $('#pmm-createefternavn');
            var addressField = $('#pmm-createadresse');
            var postalCodeField = $('#pmm-createpostnr');
            var cityField = $('#pmm-createby');
            var phoneField = $('#pmm-createtlf');
            
            var isValid = true;
            var requiredFields = [firstNameField, lastNameField, addressField, postalCodeField, cityField, phoneField];
            
            // Check required fields
            requiredFields.forEach(function(field) {
                if (!field.val().trim()) {
                    field.addClass('pmm-invalid-field');
                    isValid = false;
                } else {
                    field.removeClass('pmm-invalid-field');
                }
            });
            
            if (!isValid) {
                $('.pmm-result').html('<p class="pmm-error">' + pmm_data.i18n.required_fields + '</p>');
                $btn.prop('disabled', false).text(pmm_data.i18n.create_membership);
                return;
            }
            
            // Collect form data
            var formData = {
                action: 'pmm_create_member',
                nonce: $('#pmm-membernonce').val(),
                product_id: productId,
                first_name: firstNameField.val(),
                last_name: lastNameField.val(),
                address_1: addressField.val(),
                postcode: postalCodeField.val(),
                city: cityField.val(),
                company: $('#pmm-createforening').val(),
                phone: phoneField.val()
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
                        $btn.prop('disabled', false).text(pmm_data.i18n.create_membership);
                    }
                },
                error: function() {
                    $('.pmm-result').html('<p class="pmm-error">' + pmm_data.i18n.error + '</p>');
                    $btn.prop('disabled', false).text(pmm_data.i18n.create_membership);
                }
            });
        });
        
        // Handle update member form submission
        $(document).on('click', '#pmm-update-member-button', function() {
            var $btn = $(this);
            var $form = $btn.closest('form');
            
            // Show loading state
            $btn.prop('disabled', true).text(pmm_data.i18n.loading);
            $('.pmm-update-result').html('');
            
            // Collect form data
            var formData = $form.serialize();
            
            // Submit form data via AJAX
            $.ajax({
                url: pmm_data.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('.pmm-update-result').html('<p class="pmm-success">' + response.data.message + '</p>');
                        
                        // Update the member row in the table
                        updateMemberRowInTable(response.data.member);
                        
                        setTimeout(function() {
                            closeMemberModals();
                        }, 1500);
                    } else {
                        $('.pmm-update-result').html('<p class="pmm-error">' + response.data.message + '</p>');
                        $btn.prop('disabled', false).text(pmm_data.i18n.update_membership);
                    }
                },
                error: function() {
                    $('.pmm-update-result').html('<p class="pmm-error">' + pmm_data.i18n.error + '</p>');
                    $btn.prop('disabled', false).text(pmm_data.i18n.update_membership);
                }
            });
        });
    });
    
    /**
     * Open member edit modal with AJAX loading
     */
    function openMemberEditModal(memberId) {
        // Show loading state
        $('.pmm-background-edit-user-modal').show();
        $('.pmm-show-edit-user-modal').html('<p>' + pmm_data.i18n.loading + '</p>');
        
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
                $('.pmm-show-edit-user-modal').html('<p class="pmm-error">' + pmm_data.i18n.error + '</p>');
            }
        });
    }
    
    /**
     * Close all member modals
     */
    function closeMemberModals() {
        $('.pmm-background-edit-user-modal').hide();
        $('.pmm-show-edit-user-modal').html('');
        $('#pmm-create-member-modal').hide();
    }
    
    /**
     * Update member row in the table after edit
     */
    function updateMemberRowInTable(member) {
        var $row = $('.pmm-table-row[data-row-order-id="' + member.id + '"]');
        
        if ($row.length) {
            // Update member info
            $row.find('.pmm-memberinfo').html(
                '<strong>ID: ' + member.user_id + '</strong><br>' + 
                member.name + '<br>' +
                member.address.line1 + '<br>' +
                member.address.postcode + ' ' + member.address.city + '<br>' +
                'Phone: ' + member.phone
            );
            
            // Update email
            $row.find('.pmm-mailadresse').html(
                '<a href="mailto:' + member.email + '">' + member.email + '</a>'
            );
            
            // Update company/organization
            $row.find('.pmm-erhverv-forening').text(member.company);
        }
    }
    
})(jQuery);