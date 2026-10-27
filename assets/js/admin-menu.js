/* eslint-env browser, jquery */
/* global attnAdmin */
/**
 * Attention Network — WordPress Admin JavaScript
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        var currentMenuId = $('#attn-menu-app').data('menu-id');

        /* -------------------------------------------------------------
         * 1. Settings Page: Test Connection
         * ------------------------------------------------------------- */
        $('#attn-btn-test-connection').on('click', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $status = $('#attn-connection-status');

            $btn.prop('disabled', true).text(attnAdmin.strings.testing);
            $status.removeClass('success error').text('');

            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_test_connection',
                nonce: attnAdmin.nonce
            }, function (response) {
                $btn.prop('disabled', false).text('Test Connection');
                if (response.success) {
                    $status.addClass('success').text('✓ ' + (response.data.message || attnAdmin.strings.connected));
                } else {
                    $status.addClass('error').text('✕ ' + (response.data.message || attnAdmin.strings.connectionFailed));
                }
            }).fail(function () {
                $btn.prop('disabled', false).text('Test Connection');
                $status.addClass('error').text('✕ ' + attnAdmin.strings.connectionFailed);
            });
        });

        /* -------------------------------------------------------------
         * 2. Tab Navigation
         * ------------------------------------------------------------- */
        $('.attn-tab-btn').on('click', function () {
            var tab = $(this).data('tab');
            $('.attn-tab-btn').removeClass('active');
            $(this).addClass('active');

            $('.attn-tab-content').removeClass('active');
            $('#attn-tab-' + tab).addClass('active');
        });

        /* -------------------------------------------------------------
         * 3. Menu Switcher Dropdown
         * ------------------------------------------------------------- */
        $('#attn-active-menu-select').on('change', function () {
            var menuId = $(this).val();
            if (menuId) {
                window.location.href = 'admin.php?page=attention-network-menus&menu_id=' + encodeURIComponent(menuId);
            }
        });

        /* -------------------------------------------------------------
         * 4. Modals Management (Open / Close / ESC)
         * ------------------------------------------------------------- */
        function openModal($modal) {
            $modal.fadeIn(150);
        }

        function closeModal() {
            $('.attn-modal-backdrop').fadeOut(150);
        }

        $('.attn-modal-close, .attn-modal-cancel').on('click', function (e) {
            e.preventDefault();
            closeModal();
        });

        $('.attn-modal-backdrop').on('click', function (e) {
            if ($(e.target).hasClass('attn-modal-backdrop')) {
                closeModal();
            }
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        /* -------------------------------------------------------------
         * 5. Menu CRUD
         * ------------------------------------------------------------- */
        $('#attn-btn-open-create-menu, #attn-btn-open-create-menu-empty').on('click', function () {
            $('#attn-menu-modal-title').text('Create New Menu');
            $('#attn-menu-field-id').val('');
            $('#attn-menu-field-name').val('');
            $('#attn-menu-field-desc').val('');
            $('#attn-menu-field-active').prop('checked', true);
            openModal($('#attn-modal-menu'));
        });

        $('#attn-btn-edit-active-menu').on('click', function () {
            $('#attn-menu-modal-title').text('Edit Menu Details');
            $('#attn-menu-field-id').val(currentMenuId);
            $('#attn-menu-field-name').val($('.attn-menu-name').text().trim());
            $('#attn-menu-field-desc').val($('.attn-menu-desc').text().trim());
            openModal($('#attn-modal-menu'));
        });

        $('#attn-form-menu').on('submit', function (e) {
            e.preventDefault();
            var id = $('#attn-menu-field-id').val();
            var name = $('#attn-menu-field-name').val();
            var desc = $('#attn-menu-field-desc').val();
            var isActive = $('#attn-menu-field-active').is(':checked') ? 1 : 0;
            var action = id ? 'attention_network_update_menu' : 'attention_network_create_menu';

            var data = {
                action: action,
                nonce: attnAdmin.nonce,
                menu_id: id,
                name: name,
                description: desc,
                is_active: isActive
            };

            var $btn = $('#attn-btn-save-menu').prop('disabled', true);
            $.post(attnAdmin.ajaxUrl, data, function (response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    var newId = (response.data && response.data.id) ? response.data.id : id;
                    window.location.href = 'admin.php?page=attention-network-menus' + (newId ? '&menu_id=' + encodeURIComponent(newId) : '');
                } else {
                    alert('Error: ' + (response.data.message || 'Operation failed.'));
                }
            }).fail(function () {
                $btn.prop('disabled', false);
                alert('Network error communicating with Attention Platform.');
            });
        });

        $('#attn-btn-delete-active-menu').on('click', function () {
            if (!confirm(attnAdmin.strings.confirmDelete)) {
                return;
            }
            var menuId = $(this).data('menu-id');
            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_delete_menu',
                nonce: attnAdmin.nonce,
                menu_id: menuId
            }, function (response) {
                if (response.success) {
                    window.location.href = 'admin.php?page=attention-network-menus';
                } else {
                    alert('Error: ' + (response.data.message || 'Delete failed.'));
                }
            });
        });

        /* -------------------------------------------------------------
         * 6. Section CRUD & Reordering
         * ------------------------------------------------------------- */
        $('#attn-btn-add-section, #attn-btn-add-section-empty').on('click', function () {
            $('#attn-section-modal-title').text('Add Menu Section');
            $('#attn-section-field-id').val('');
            $('#attn-section-field-name').val('');
            $('#attn-section-field-desc').val('');
            openModal($('#attn-modal-section'));
        });

        $(document).on('click', '.attn-btn-edit-section', function () {
            var $btn = $(this);
            $('#attn-section-modal-title').text('Edit Section');
            $('#attn-section-field-id').val($btn.data('section-id'));
            $('#attn-section-field-name').val($btn.data('name'));
            $('#attn-section-field-desc').val($btn.data('desc'));
            openModal($('#attn-modal-section'));
        });

        $('#attn-form-section').on('submit', function (e) {
            e.preventDefault();
            var sectionId = $('#attn-section-field-id').val();
            var name = $('#attn-section-field-name').val();
            var desc = $('#attn-section-field-desc').val();
            var action = sectionId ? 'attention_network_update_section' : 'attention_network_create_section';

            var data = {
                action: action,
                nonce: attnAdmin.nonce,
                menu_id: currentMenuId,
                section_id: sectionId,
                name: name,
                description: desc
            };

            var $btn = $('#attn-btn-save-section').prop('disabled', true);
            $.post(attnAdmin.ajaxUrl, data, function (response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Save failed.'));
                }
            });
        });

        $(document).on('click', '.attn-btn-delete-section', function () {
            if (!confirm(attnAdmin.strings.confirmDelete)) {
                return;
            }
            var sectionId = $(this).data('section-id');
            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_delete_section',
                nonce: attnAdmin.nonce,
                menu_id: currentMenuId,
                section_id: sectionId
            }, function (response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Delete failed.'));
                }
            });
        });

        $(document).on('click', '.attn-btn-move-section', function () {
            var $card = $(this).closest('.attn-section-card');
            var dir = $(this).data('direction');

            if (dir === 'up' && $card.prev('.attn-section-card').length) {
                $card.insertBefore($card.prev('.attn-section-card'));
            } else if (dir === 'down' && $card.next('.attn-section-card').length) {
                $card.insertAfter($card.next('.attn-section-card'));
            } else {
                return;
            }

            var sectionIds = [];
            $('.attn-section-card').each(function () {
                sectionIds.push($(this).data('section-id'));
            });

            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_reorder_sections',
                nonce: attnAdmin.nonce,
                menu_id: currentMenuId,
                section_ids: sectionIds
            });
        });

        /* -------------------------------------------------------------
         * 7. Item CRUD
         * ------------------------------------------------------------- */
        $(document).on('click', '.attn-btn-add-item', function () {
            var sectionId = $(this).data('section-id');
            $('#attn-item-modal-title').text('Add Menu Item');
            $('#attn-item-field-id').val('');
            $('#attn-item-field-section-id').val(sectionId);
            $('#attn-item-field-name').val('');
            $('#attn-item-field-price').val('');
            $('#attn-item-field-desc').val('');
            $('#attn-item-field-calories').val('');
            $('#attn-item-field-photo').val('');
            $('input[name="dietary_tags[]"]').prop('checked', false);
            openModal($('#attn-modal-item'));
        });

        $(document).on('click', '.attn-btn-edit-item', function () {
            var item = $(this).data('item');
            var sectionId = $(this).data('section-id');

            $('#attn-item-modal-title').text('Edit Menu Item');
            $('#attn-item-field-id').val(item.id);
            $('#attn-item-field-section-id').val(sectionId);
            $('#attn-item-field-name').val(item.name);
            $('#attn-item-field-price').val(item.price_amount);
            $('#attn-item-field-desc').val(item.description || '');
            $('#attn-item-field-calories').val(item.calories || '');
            $('#attn-item-field-photo').val(item.photo_url || '');

            $('input[name="dietary_tags[]"]').prop('checked', false);
            if (item.dietary_tags && Array.isArray(item.dietary_tags)) {
                item.dietary_tags.forEach(function (tag) {
                    $('input[name="dietary_tags[]"][value="' + tag + '"]').prop('checked', true);
                });
            }

            openModal($('#attn-modal-item'));
        });

        $('#attn-form-item').on('submit', function (e) {
            e.preventDefault();
            var itemId = $('#attn-item-field-id').val();
            var sectionId = $('#attn-item-field-section-id').val();
            var action = itemId ? 'attention_network_update_item' : 'attention_network_create_item';

            var tags = [];
            $('input[name="dietary_tags[]"]:checked').each(function () {
                tags.push($(this).val());
            });

            var data = {
                action: action,
                nonce: attnAdmin.nonce,
                menu_id: currentMenuId,
                section_id: sectionId,
                item_id: itemId,
                name: $('#attn-item-field-name').val(),
                price_amount: $('#attn-item-field-price').val(),
                description: $('#attn-item-field-desc').val(),
                calories: $('#attn-item-field-calories').val(),
                photo_url: $('#attn-item-field-photo').val(),
                dietary_tags: tags
            };

            var $btn = $('#attn-btn-save-item').prop('disabled', true);
            $.post(attnAdmin.ajaxUrl, data, function (response) {
                $btn.prop('disabled', false);
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Save failed.'));
                }
            });
        });

        $(document).on('click', '.attn-btn-delete-item', function () {
            if (!confirm(attnAdmin.strings.confirmDelete)) {
                return;
            }
            var itemId = $(this).data('item-id');
            var sectionId = $(this).data('section-id');

            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_delete_item',
                nonce: attnAdmin.nonce,
                menu_id: currentMenuId,
                section_id: sectionId,
                item_id: itemId
            }, function (response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Delete failed.'));
                }
            });
        });

        /* -------------------------------------------------------------
         * 8. Item Modifiers / Options
         * ------------------------------------------------------------- */
        $(document).on('click', '.attn-btn-manage-options', function () {
            var itemId = $(this).data('item-id');
            var itemName = $(this).data('item-name');
            var sectionId = $(this).data('section-id');

            $('#attn-options-item-name').text(itemName);
            $('#attn-opt-item-id').val(itemId);
            $('#attn-opt-section-id').val(sectionId);

            loadOptionsForModal(itemId);
            openModal($('#attn-modal-options'));
        });

        function loadOptionsForModal(itemId) {
            var $container = $('#attn-options-container');
            $container.html('<p style="color:#64748b; font-size:12px;">Loading modifiers...</p>');

            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_get_menu_data',
                nonce: attnAdmin.nonce,
                menu_id: currentMenuId
            }, function (response) {
                if (response.success && response.data) {
                    var foundItem = null;
                    (response.data.sections || []).forEach(function (sec) {
                        (sec.items || []).forEach(function (it) {
                            if (it.id === itemId) {
                                foundItem = it;
                            }
                        });
                    });

                    if (foundItem) {
                        renderOptionsList(foundItem.options || []);
                    }
                }
            });
        }

        function renderOptionsList(options) {
            var $container = $('#attn-options-container');
            $container.empty();

            if (!options.length) {
                $container.html('<p style="color:#94a3b8; font-size:13px; font-style:italic;">No modifiers configured yet for this item.</p>');
                return;
            }

            options.forEach(function (opt) {
                var delta = opt.price_modifier ? (opt.price_modifier > 0 ? '+$' : '-$') + Math.abs(opt.price_modifier).toFixed(2) : '$0.00';
                var html = '<div class="attn-option-row">' +
                    '<div><strong>' + $('<div>').text(opt.name).html() + '</strong> <span style="color:#059669; font-weight:600; margin-left:8px;">' + delta + '</span></div>' +
                    '<button type="button" class="button button-small button-link-delete attn-btn-delete-opt" data-option-id="' + opt.id + '">Remove</button>' +
                    '</div>';
                $container.append(html);
            });
        }

        $('#attn-form-add-option').on('submit', function (e) {
            e.preventDefault();
            var itemId = $('#attn-opt-item-id').val();
            var sectionId = $('#attn-opt-section-id').val();
            var name = $('#attn-opt-name').val();
            var price = $('#attn-opt-price').val();

            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_create_option',
                nonce: attnAdmin.nonce,
                menu_id: currentMenuId,
                section_id: sectionId,
                item_id: itemId,
                name: name,
                price_modifier: price
            }, function (response) {
                if (response.success) {
                    $('#attn-opt-name').val('');
                    $('#attn-opt-price').val('');
                    loadOptionsForModal(itemId);
                } else {
                    alert('Error: ' + (response.data.message || 'Failed to add modifier.'));
                }
            });
        });

        $(document).on('click', '.attn-btn-delete-opt', function () {
            var optId = $(this).data('option-id');
            var itemId = $('#attn-opt-item-id').val();
            var sectionId = $('#attn-opt-section-id').val();

            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_delete_option',
                nonce: attnAdmin.nonce,
                menu_id: currentMenuId,
                section_id: sectionId,
                item_id: itemId,
                option_id: optId
            }, function (response) {
                if (response.success) {
                    loadOptionsForModal(itemId);
                }
            });
        });

        /* -------------------------------------------------------------
         * 9. Display & Layout Settings Form
         * ------------------------------------------------------------- */
        $('.attn-radio-card input').on('change', function () {
            $('.attn-radio-card').removeClass('selected');
            $(this).closest('.attn-radio-card').addClass('selected');
        });

        $('#attn-form-display-settings').on('submit', function (e) {
            e.preventDefault();
            var $status = $('#attn-save-settings-status').text('Saving...');
            var formData = $(this).serializeArray();
            var dataObj = {
                action: 'attention_network_save_display_settings',
                nonce: attnAdmin.nonce,
                menu_id: currentMenuId
            };

            formData.forEach(function (field) {
                dataObj[field.name] = field.value;
            });

            $.post(attnAdmin.ajaxUrl, dataObj, function (response) {
                if (response.success) {
                    $status.css('color', '#059669').text('✓ Display settings saved.');
                    setTimeout(function () { $status.text(''); }, 3000);
                } else {
                    $status.css('color', '#dc2626').text('✕ ' + (response.data.message || 'Save failed.'));
                }
            });
        });

        /* -------------------------------------------------------------
         * 10. Operating Hours & Emergency Dispatch Handlers
         * ------------------------------------------------------------- */
        $('.attn-day-closed-toggle').on('change', function () {
            var $row = $(this).closest('tr');
            var isClosed = $(this).is(':checked');
            $row.find('.attn-open-time, .attn-close-time').prop('disabled', isClosed);
        });

        $('#attn_sp_closed').on('change', function () {
            if ($(this).is(':checked')) {
                $('#attn_sp_times_wrap').hide();
            } else {
                $('#attn_sp_times_wrap').css('display', 'flex');
            }
        });

        $('#attn-hours-form').on('submit', function (e) {
            e.preventDefault();
            var $status = $('#attn-hours-status-msg').text('Saving regular hours...');
            var locationId = $('#attn_hours_location_id').val();
            var emergencyDispatch = $('#attn_emergency_dispatch').is(':checked') ? '1' : '0';

            var hoursArray = [];
            $('#attn-hours-form tbody tr').each(function () {
                var day = $(this).data('day');
                var isClosed = $(this).find('.attn-day-closed-toggle').is(':checked');
                var open = $(this).find('.attn-open-time').val();
                var close = $(this).find('.attn-close-time').val();

                hoursArray.push({
                    dayOfWeek: day,
                    isClosed: isClosed ? 1 : 0,
                    openTime: open,
                    closeTime: close
                });
            });

            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_save_weekly_hours',
                nonce: attnAdmin.nonce,
                location_id: locationId,
                emergency_dispatch: emergencyDispatch,
                hours: hoursArray
            }, function (res) {
                if (res.success) {
                    $status.css('color', '#059669').text('✓ Hours saved successfully.');
                    setTimeout(function () { $status.text(''); }, 3000);
                } else {
                    $status.css('color', '#dc2626').text('✕ ' + (res.data.message || 'Error saving hours.'));
                }
            });
        });

        $('#attn-special-hours-form').on('submit', function (e) {
            e.preventDefault();
            var locationId = $('#attn_hours_location_id').val();
            var date = $('#attn_sp_date').val();
            var note = $('#attn_sp_note').val();
            var isClosed = $('#attn_sp_closed').is(':checked') ? 1 : 0;
            var open = $('#attn_sp_open').val();
            var close = $('#attn_sp_close').val();

            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_create_special_hours',
                nonce: attnAdmin.nonce,
                location_id: locationId,
                date: date,
                note: note,
                is_closed: isClosed,
                open_time: open,
                close_time: close
            }, function (res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (res.data.message || 'Could not add special hours.'));
                }
            });
        });

        $(document).on('click', '.attn-delete-sp', function (e) {
            e.preventDefault();
            if (!confirm('Remove this special hours entry?')) return;
            var spId = $(this).data('sp-id');
            var locationId = $('#attn_hours_location_id').val();

            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_delete_special_hours',
                nonce: attnAdmin.nonce,
                location_id: locationId,
                special_hours_id: spId
            }, function (res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (res.data.message || 'Could not delete entry.'));
                }
            });
        });

        /* -------------------------------------------------------------
         * 11. Staff Directory Handlers
         * ------------------------------------------------------------- */
        $('#attn-btn-add-staff, .attn-trigger-add-staff').on('click', function () {
            $('#attn-staff-modal-title').text('Add Team Member / Field Pro');
            $('#attn_staff_card_id').val('');
            $('#attn-staff-form')[0].reset();
            $('#attn-staff-modal').show();
        });

        $('.attn-modal-close').on('click', function () {
            $('#attn-staff-modal').hide();
        });

        $('#attn-staff-form').on('submit', function (e) {
            e.preventDefault();
            var dataObj = {
                action: 'attention_network_save_staff_member',
                nonce: attnAdmin.nonce,
                card_id: $('#attn_staff_card_id').val(),
                first_name: $('#attn_staff_first_name').val(),
                last_name: $('#attn_staff_last_name').val(),
                title: $('#attn_staff_title').val(),
                license: $('#attn_staff_license').val(),
                phone: $('#attn_staff_phone').val(),
                email: $('#attn_staff_email').val(),
                photo: $('#attn_staff_photo').val(),
                bio: $('#attn_staff_bio').val(),
                tip_url: $('#attn_staff_tip_url').val(),
                booking_url: $('#attn_staff_booking_url').val()
            };

            $.post(attnAdmin.ajaxUrl, dataObj, function (res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (res.data.message || 'Failed to save team member.'));
                }
            });
        });

        $(document).on('click', '.attn-delete-staff', function (e) {
            e.preventDefault();
            if (!confirm('Remove this team member?')) return;
            var cardId = $(this).data('card-id');

            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_delete_staff_member',
                nonce: attnAdmin.nonce,
                card_id: cardId
            }, function (res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (res.data.message || 'Could not delete staff member.'));
                }
            });
        });

        /* -------------------------------------------------------------
         * 12. Attention Push Handlers
         * ------------------------------------------------------------- */
        $('#attn-send-push-form').on('submit', function (e) {
            e.preventDefault();
            var $status = $('#attn-push-status-msg').text('Broadcasting notification...');
            var $btn = $('#attn-btn-send-push').prop('disabled', true);

            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_send_push',
                nonce: attnAdmin.nonce,
                title: $('#attn_push_title').val(),
                body: $('#attn_push_body').val(),
                url: $('#attn_push_url').val(),
                segment: $('#attn_push_segment').val()
            }, function (res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    $status.css('color', '#059669').text('✓ Notification broadcast sent!');
                    $('#attn-send-push-form')[0].reset();
                    setTimeout(function () { $status.text(''); }, 5000);
                } else {
                    $status.css('color', '#dc2626').text('✕ ' + (res.data.message || 'Failed to send notification.'));
                }
            }).fail(function () {
                $btn.prop('disabled', false);
                $status.css('color', '#dc2626').text('✕ Network request error.');
            });
        });

        $('#attn-push-settings-form').on('submit', function (e) {
            e.preventDefault();
            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_save_push_settings',
                nonce: attnAdmin.nonce,
                app_id: $('#attn_push_app_id').val(),
                auto_prompt: $('#attn_push_auto_prompt').is(':checked') ? 1 : 0,
                prompt_text: $('#attn_push_prompt_text').val()
            }, function (res) {
                if (res.success) {
                    alert('Attention Push settings saved successfully.');
                } else {
                    alert('Error saving push settings.');
                }
            });
        });

        /* -------------------------------------------------------------
         * 13. Offers & Coupons Handlers
         * ------------------------------------------------------------- */
        $('#attn-btn-add-offer, #attn-btn-empty-add-offer').on('click', function () {
            $('#attn-offer-modal-title').text('Create Promotional Offer');
            $('#attn-offer-form')[0].reset();
            $('#attn-offer-id').val('');
            $('#attn-offer-status').prop('checked', true);
            $('#attn-offer-modal').fadeIn(150);
        });

        $(document).on('click', '.attn-btn-edit-offer', function () {
            var data = $(this).data('offer');
            if (typeof data === 'string') data = JSON.parse(data);
            $('#attn-offer-modal-title').text('Edit Promotional Offer');
            $('#attn-offer-id').val(data.id || '');
            $('#attn-offer-title').val(data.title || '');
            $('#attn-offer-code').val(data.code || '');
            $('#attn-offer-category').val(data.category || '');
            $('#attn-offer-discount-type').val(data.discountType || 'fixed');
            $('#attn-offer-discount-display').val(data.discountDisplay || '');
            $('#attn-offer-badge').val(data.badgeText || '');
            $('#attn-offer-expires').val(data.expiresAt || '');
            $('#attn-offer-description').val(data.description || '');
            $('#attn-offer-terms').val(data.terms || '');
            $('#attn-offer-cta-url').val(data.ctaUrl || '');
            $('#attn-offer-status').prop('checked', data.status !== 'paused');
            $('#attn-offer-modal').fadeIn(150);
        });

        $('#attn-btn-save-offer').on('click', function () {
            var form = $('#attn-offer-form');
            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }
            var formData = form.serializeArray();
            var dataObj = {
                action: 'attention_network_save_offer',
                nonce: attnAdmin.nonce
            };
            $.each(formData, function (_, field) {
                dataObj[field.name] = field.value;
            });
            if (!dataObj.status) {
                dataObj.status = 'paused';
            }

            var $btn = $(this).prop('disabled', true).text('Saving...');
            $.post(attnAdmin.ajaxUrl, dataObj, function (res) {
                $btn.prop('disabled', false).text('Save Offer');
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (res.data.message || 'Failed to save offer.'));
                }
            });
        });

        $(document).on('click', '.attn-btn-delete-offer', function () {
            if (!confirm('Are you sure you want to delete this offer?')) return;
            var id = $(this).data('id');
            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_delete_offer',
                nonce: attnAdmin.nonce,
                id: id
            }, function (res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Error deleting offer.');
                }
            });
        });

        /* -------------------------------------------------------------
         * 14. Customer Reviews & Reputation Handlers
         * ------------------------------------------------------------- */
        $(document).on('click', '.attn-btn-add-reply, .attn-btn-edit-reply', function () {
            var revId = $(this).data('review-id');
            var existingReply = $(this).data('reply') || '';
            $('#attn-reply-review-id').val(revId);
            $('#attn-reply-text').val(existingReply);
            $('#attn-reply-modal').fadeIn(150);
        });

        $('#attn-btn-submit-reply').on('click', function () {
            var revId = $('#attn-reply-review-id').val();
            var reply = $('#attn-reply-text').val();
            if (!reply.trim()) {
                alert('Please enter a response message.');
                return;
            }

            var $btn = $(this).prop('disabled', true).text('Publishing...');
            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_reply_review',
                nonce: attnAdmin.nonce,
                review_id: revId,
                reply: reply
            }, function (res) {
                $btn.prop('disabled', false).text('Publish Response');
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (res.data.message || 'Failed to publish response.'));
                }
            });
        });

        /* -------------------------------------------------------------
         * 15. Inbound Leads Queue Handlers
         * ------------------------------------------------------------- */
        $(document).on('change', '.attn-select-lead-status', function () {
            var leadId = $(this).data('lead-id');
            var status = $(this).val();
            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_update_lead_status',
                nonce: attnAdmin.nonce,
                lead_id: leadId,
                status: status
            }, function (res) {
                if (!res.success) {
                    alert('Error updating status.');
                }
            });
        });

        var activeLeadModalId = null;
        $(document).on('click', '.attn-btn-view-lead', function () {
            var lead = $(this).data('lead');
            if (typeof lead === 'string') lead = JSON.parse(lead);
            activeLeadModalId = lead.id;

            var html = '<div style="line-height:1.6;font-size:13px;">' +
                '<p><strong>Client Name:</strong> ' + $('<div>').text(lead.name || 'Unknown').html() + '</p>' +
                '<p><strong>Phone:</strong> ' + (lead.phone ? '<a href="tel:' + lead.phone + '">' + $('<div>').text(lead.phone).html() + '</a>' : '—') + '</p>' +
                '<p><strong>Email:</strong> ' + (lead.email ? '<a href="mailto:' + lead.email + '">' + $('<div>').text(lead.email).html() + '</a>' : '—') + '</p>' +
                '<p><strong>Service Requested:</strong> ' + $('<div>').text(lead.service || 'General').html() + '</p>' +
                '<p><strong>Service Location:</strong> ' + $('<div>').text(lead.address || '—').html() + '</p>' +
                '<p><strong>Urgency:</strong> ' + (lead.urgency === 'emergency' ? '<span style="color:#dc2626;font-weight:700;">🚨 24/7 Emergency Dispatch</span>' : $('<div>').text(lead.urgency || 'Normal').html()) + '</p>' +
                '<p><strong>Message / Project Scope:</strong><br><div style="background:#f8fafc;padding:10px 12px;border-radius:6px;border:1px solid #e2e8f0;margin-top:4px;">' + $('<div>').text(lead.message || 'No description provided.').html() + '</div></p>' +
                '</div>';

            $('#attn-lead-modal-content').html(html);
            $('#attn-lead-internal-notes').val(lead.internalNotes || '');
            $('#attn-lead-modal').fadeIn(150);
        });

        $('#attn-btn-save-lead-notes').on('click', function () {
            if (!activeLeadModalId) return;
            var notes = $('#attn-lead-internal-notes').val();
            var $btn = $(this).prop('disabled', true).text('Saving...');

            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_update_lead_status',
                nonce: attnAdmin.nonce,
                lead_id: activeLeadModalId,
                notes: notes
            }, function (res) {
                $btn.prop('disabled', false).text('Save Notes');
                if (res.success) {
                    $('#attn-lead-modal').fadeOut(150);
                    window.location.reload();
                } else {
                    alert('Error saving notes.');
                }
            });
        });

        /* -------------------------------------------------------------
         * 16. Project Portfolio Handlers
         * ------------------------------------------------------------- */
        $('#attn-btn-add-project, #attn-btn-empty-add-project').on('click', function () {
            $('#attn-project-modal-title').text('Add Showcase Project');
            $('#attn-project-form')[0].reset();
            $('#attn-project-id').val('');
            $('#attn-project-modal').fadeIn(150);
        });

        $(document).on('click', '.attn-btn-edit-project', function () {
            var data = $(this).data('project');
            if (typeof data === 'string') data = JSON.parse(data);
            $('#attn-project-modal-title').text('Edit Showcase Project');
            $('#attn-project-id').val(data.id || '');
            $('#attn-project-title').val(data.title || '');
            $('#attn-project-category').val(data.category || '');
            $('#attn-project-duration').val(data.duration || '');
            $('#attn-project-before-url').val(data.beforeUrl || '');
            $('#attn-project-after-url').val(data.afterUrl || '');
            $('#attn-project-location').val(data.location || '');
            $('#attn-project-description').val(data.description || '');
            $('#attn-project-modal').fadeIn(150);
        });

        $('#attn-btn-save-project').on('click', function () {
            var form = $('#attn-project-form');
            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }
            var formData = form.serializeArray();
            var dataObj = {
                action: 'attention_network_save_portfolio_item',
                nonce: attnAdmin.nonce
            };
            $.each(formData, function (_, field) {
                dataObj[field.name] = field.value;
            });

            var $btn = $(this).prop('disabled', true).text('Saving...');
            $.post(attnAdmin.ajaxUrl, dataObj, function (res) {
                $btn.prop('disabled', false).text('Save Project');
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (res.data.message || 'Failed to save project.'));
                }
            });
        });

        $(document).on('click', '.attn-btn-delete-project', function () {
            if (!confirm('Delete this showcase project?')) return;
            var id = $(this).data('id');
            $.post(attnAdmin.ajaxUrl, {
                action: 'attention_network_delete_portfolio_item',
                nonce: attnAdmin.nonce,
                id: id
            }, function (res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Error deleting project.');
                }
            });
        });

    });
})(jQuery);

