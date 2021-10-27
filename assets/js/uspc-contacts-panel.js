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
