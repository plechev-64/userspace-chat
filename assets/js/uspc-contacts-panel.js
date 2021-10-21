// open PM in modal: 1 - this; 2 - id chat user

function uspc_get_chat_window(e, user_id) {
    // noinspection JSUnresolvedFunction
    usp_preloader_show(jQuery(e), 36);

    // noinspection JSUnresolvedFunction
    usp_ajax({
        data: {
            action: 'uspc_get_ajax_chat_window',
            user_id: user_id
        }
    });
}

// noinspection JSUnusedGlobalSymbols
function uspc_shift_contacts_panel() {
    let miniChat = jQuery('#uspc-mini');

    if (miniChat.hasClass('uspc-mini-opened'))
        return;

    let view = (+jQuery.cookie('uspc_contacts_panel_full') === 1) ? 0 : 1;

    if (view) {
        // noinspection JSUnresolvedFunction
        miniChat.removeClass('uspc-mini__hide').animateCss('slideInUp');
    } else {
        miniChat.addClass('uspc-mini__hide');
    }

    jQuery.cookie('uspc_contacts_panel_full', view, {
        expires: 30,
        path: '/'
    });

    return false;
}
