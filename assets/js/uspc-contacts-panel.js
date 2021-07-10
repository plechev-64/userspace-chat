var uspc_contact_token = 0; //open contact

// open .uspc-mini__im
function uspc_get_minichat( e, user_id ) {
    if ( uspc_contact_token ) {
        uspc_chat_clear_beat( uspc_contact_token );
    }

    usp_preloader_show( '#uspc-mini > div', 36 );

    usp_ajax( {
        data: {
            action: 'uspc_get_chat_private_ajax',
            user_id: user_id
        },
        success: function( data ) {
            if ( data['content'] ) {
                var box = jQuery( '#uspc-mini' );
                var animate = box.hasClass( 'uspc-on-left' ) ? 'fadeInLeft' : 'fadeInRight';
                box.children( '.uspc-mini__im' ).html( data['content'] ).animateCss( animate );
                box.find( '.uspc-im__header' ).prepend( data['name'] ).append( data['bttn'] );
                box.addClass( 'uspc-mini-opened' );
                uspc_contact_token = data['chat_token'];
                uspc_set_active_minichat( e );
                uspc_scroll_down( uspc_contact_token );
            }
        }
    } );

    return false;
}

function uspc_set_active_minichat( e ) {
    uspc_reset_active_minichat();

    jQuery( e ).addClass( 'uspc-mini-person__opened' ).children( 'i' ).remove();
}

function uspc_reset_active_minichat() {
    jQuery( '.uspc-mini__person' ).removeClass( 'uspc-mini-person__opened' );
}

function uspc_close_minichat( e ) {
    uspc_reset_active_minichat();

    var token = jQuery( e ).parents( '.uspc-mini__im' ).find( '.uspc-im' ).data( 'token' );
    uspc_chat_clear_beat( token );

    var minichat = jQuery( '#uspc-mini' );
    minichat.removeClass( 'uspc-mini-opened' );
    
    var animate = minichat.hasClass( 'uspc-on-left' ) ? 'fadeOutLeft' : 'fadeOutRight';
    
    minichat.children( '.uspc-mini__im' ).animateCss( animate, function( e ) {
        jQuery( e ).empty();
    } );

    usp_do_action( 'uspc_minichat_closed', token );
}

function uspc_shift_contacts_panel() {
    var minichat = jQuery( '#uspc-mini' );

    if ( minichat.hasClass( 'uspc-mini-opened' ) )
        return;

    var view = ( jQuery.cookie( 'uspc_contacts_panel_full' ) == 1 ) ? 0 : 1;

    if ( view ) {
        minichat.removeClass( 'uspc-mini__hide' ).animateCss( 'slideInUp' );
    } else {
        minichat.addClass( 'uspc-mini__hide' );
    }

    jQuery.cookie( 'uspc_contacts_panel_full', view, {
        expires: 30,
        path: '/'
    } );

    return false;
}
