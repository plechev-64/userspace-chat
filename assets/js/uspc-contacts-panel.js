// open PM in modal: 1 - this; 2 - id chat user
function uspc_get_chat_window(e, user_id) {
    if (e && jQuery(e).parents('.preloader-parent')) {
        usp_preloader_show(jQuery(e).parents('.preloader-parent'));
    }

    usp_ajax({
        data: {
            action: 'uspc_get_ajax_chat_window',
            user_id: user_id
        }
    });
}

function uspc_shift_contacts_panel() {
    let minichat = jQuery('#uspc-mini');

    if (minichat.hasClass('uspc-mini-opened'))
        return;

    let view = (+jQuery.cookie('uspc_contacts_panel_full') === 1) ? 0 : 1;

    if (view) {
        minichat.removeClass('uspc-mini__hide').animateCss('slideInUp');
    } else {
        minichat.addClass('uspc-mini__hide');
    }

    jQuery.cookie('uspc_contacts_panel_full', view, {
        expires: 30,
        path: '/'
    });

    return false;
}
