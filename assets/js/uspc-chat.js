/* global USP, USPUploaders, usp_beats */

var uspc_last_activity = { }; //last request for new messages 
var uspc_beat = new Array; //array of open chats
var uspc_write = 0; //user writes
var uspc_inactive_counter = -1; //user idle counter
var uspc_important = 0;
var uspc_max_words = 300;

jQuery( function( $ ) {
    uspc_chat_inactivity_counter();

    $( '.uspc-message-for-you' ).animateCss( 'tada' );

    if ( USPUploaders.isset( 'uspc_chat_uploader' ) ) {
        USPUploaders.get( 'uspc_chat_uploader' ).animateLoading = function( status ) {
            status ? usp_preloader_show( $( '.uspc-im__form' ) ) : usp_preloader_hide();
        };
    }
} );

// play
function uspc_play_sound() {
    const audioPlay = ( () => {
        let context = null;
        return async url => {
            if ( context )
                context.close();
            context = new AudioContext();
            const source = context.createBufferSource();
            source.buffer = await fetch( url )
                .then( res => res.arrayBuffer() )
                .then( arrayBuffer => context.decodeAudioData( arrayBuffer ) );
            source.connect( context.destination );
            source.start();
        };
    } )();

    return audioPlay( USP.usp_chat.sounds );
}

function uspc_chat_inactivity_cancel() {
    uspc_inactive_counter = -1;
}

function uspc_chat_inactivity_counter() {
    uspc_inactive_counter++;
    setTimeout( 'uspc_chat_inactivity_counter()', 60000 );
}

function uspc_scroll_down( token ) {
    if ( !token )
        return;

    var talk = jQuery( '.uspc-im[data-token="' + token + '"] .uspc-im__talk' );

    if ( talk.length > 0 ) {
        jQuery( talk ).scrollTop( jQuery( talk ).get( 0 ).scrollHeight );
    }
}

function uspc_chat_counter_reset( form ) {
    form.find( '.uspc-im-form__sign-count' ).text( USP.usp_chat.words ).removeAttr( 'style' );
}

// if empty dialog
function uspc_clear_notice() {
    jQuery( '.uspc-im-talk__write' ).hide();
}

function uspc_get_wrap_im_by_token( token ) {
    return jQuery( '.uspc-im[data-token="' + token + '"]' );
}

function uspc_chat_add_message( e ) {
    var form = jQuery( e ).parents( '.uspc-im__form' );

    uspc_chat_add_new_message( form );
}

function uspc_chat_clear_beat( token ) {
    var all_beats = usp_beats;
    var all_chats = uspc_beat;

    all_beats.forEach( function( beat, index, usp_beats ) {
        if ( beat.beat_name != 'uspc_chat_beat_core' )
            return;
        if ( beat.data.token != token )
            return;
        delete usp_beats[index];
    } );

    all_chats.forEach( function( chat_token, index, chats ) {
        if ( chat_token != token )
            return;
        delete uspc_beat[index];
    } );

    console.log( 'chat beat ' + token + ' clear' );
}

function uspc_init_chat( chat ) {
    chat = usp_apply_filters( 'uspc_init', chat );

    uspc_scroll_down( chat.token );

    uspc_max_words = chat.max_words;
    uspc_last_activity[chat.token] = chat.open_chat;

    var i = uspc_beat.length;
    uspc_beat[i] = chat.token;

    usp_do_action( 'uspc_init', chat );
}

function uspc_disable_button( form ) {
    jQuery( form ).find( '.uspc-im-form__send' ).addClass( 'usp-bttn__disabled' );
}

function uspc_enable_button( form ) {
    jQuery( form ).find( '.uspc-im-form__send' ).removeClass( 'usp-bttn__disabled' );
}

// send button opening after load file
usp_add_action( 'usp_uploader_after_done', 'uspc_after_upload' );
function uspc_after_upload( e ) {
    var form = jQuery( e.target ).parents( '.uspc-im__form' );
    uspc_enable_button( form );
}

// send button opening after insert emoji
usp_add_action( 'usp_emoji_insert', 'uspc_insert_emoji_enabled_bttn' );
function uspc_insert_emoji_enabled_bttn( box ) {
    var form = jQuery( box ).parents( '.uspc-im__form' );
    uspc_enable_button( form );
}

// send button disabled after delete file if empty textarea
usp_add_action( 'usp_uploader_delete', 'uspc_delete_attachment_actions' );
function uspc_delete_attachment_actions( e ) {
    var form = jQuery( e ).parents( '.uspc-im__form' );

    if ( !form.find( '.uspc-im-form__textarea' ).val() ) {
        uspc_disable_button( form );
    }
}

usp_add_action( 'uspc_init', 'uspc_chat_init_beat' );
function uspc_chat_init_beat( chat ) {
    var delay = ( chat.delay != 0 ) ? chat.delay : USP.usp_chat.delay, chat;
    usp_add_beat( 'uspc_chat_beat_core', delay, chat );
}

function uspc_chat_write_status( token ) {
    var chat = uspc_get_wrap_im_by_token( token ),
        chat_status = chat.find( '.uspc-im__writes' );

    chat_status.css( {
        width: 18
    } );
    chat_status.animate( {
        width: 36
    }, 1000 );

    uspc_write = setTimeout( 'uspc_chat_write_status("' + token + '")', 3000 );

    usp_do_action( 'uspc_user_write', token );
}

function uspc_chat_write_status_cancel( token ) {
    clearTimeout( uspc_write );

    var chat = uspc_get_wrap_im_by_token( token ),
        chat_status = chat.find( '.uspc-im__writes' );

    chat_status.css( {
        width: 0
    } );

    usp_do_action( 'uspc_user_stopped_writing', token );
}

// if the attempt to send an empty message
function uspc_mark_textarea() {
    var field = jQuery( '.uspc-im-form__textarea' );
    field.css( 'border', '2px solid var(--uspRed-300)' );
    setTimeout( () => {
        field.css( 'border', '' );
    }, 5000 );
}

// send new message in form
function uspc_chat_add_new_message( form ) {
    uspc_chat_inactivity_cancel();

    var token = form.children( '[name="chat[token]"]' ).val(),
        chat = uspc_get_wrap_im_by_token( token ),
        message_text = form.children( 'textarea' ).val(),
        file = form.find( '#usp-media-uspc_chat_uploader .usp-media__item' );

    message_text = jQuery.trim( message_text );

    if ( !message_text.length && !file.length ) {
        usp_notice( USP.local.uspc_empty, 'error', 10000 );
        uspc_mark_textarea();
        return false;
    }

    if ( message_text.length > USP.usp_chat.words ) {
        usp_notice( USP.local.uspc_text_words, 'error', 10000 );
        return false;
    }

    usp_preloader_show( '.uspc-im__form' );

    usp_ajax( {
        data: 'action=uspc_chat_add_message&'
            + form.serialize()
            + '&office_ID=' + USP.office_ID
            + '&last_activity=' + uspc_last_activity[token],
        success: function( data ) {

            if ( data['content'] ) {
                form.find( 'textarea' ).val( '' );
                jQuery( "#usp-media-uspc_chat_uploader" ).html( '' );

                chat.find( '.uspc-im__talk' ).append( data['content'] ).find( '.uspc-post' ).last().animateCss( 'zoomIn' );
                chat.find( '.uspc-chat-uploader' ).show();

                uspc_scroll_down( token );
                uspc_chat_counter_reset( form );

                if ( data.new_messages ) {
                    uspc_play_sound();
                }

                uspc_last_activity[token] = data.last_activity;

                uspc_clear_notice();
                uspc_disable_button( form );

                usp_do_action( 'uspc_added_message', {
                    token: token,
                    result: data
                } );
            }
        }
    } );

    return false;
}

function uspc_chat_navi( page, e ) {
    uspc_chat_inactivity_cancel();

    var im = jQuery( e ).parents( '.uspc-im' ),
        token = jQuery( im ).data( 'token' ),
        important = jQuery( im ).data( 'important' );

    if ( !important )
        important = uspc_important;

    usp_preloader_show( '.uspc-im__footer' );

    usp_ajax( {
        data: {
            action: 'uspc_get_chat_page',
            token: token,
            page: page,
            in_page: jQuery( im ).data( 'in_page' ),
            important: important
        },
        success: function( data ) {
            if ( data['content'] ) {
                var imBox = jQuery( e ).parents( '.uspc-im__box' );
                imBox.find( '.uspc-im__talk, .uspc-im__footer' ).remove();
                imBox.append( data['content'] ).animateCss( 'fadeIn' );

                uspc_scroll_down( token );
            }
        }
    } );

    return false;
}

function uspc_contacts_navi( page, e ) {
    usp_preloader_show( '.usp-subtab-content' );

    usp_ajax( {
        data: {
            action: 'uspc_get_contacts_navi',
            page: page
        },
        success: function( data ) {
            if ( data['content'] ) {
                var list = jQuery( e ).parents( '.usp-subtab-content' );
                list.find( '.uspc-contact-box, .uspc-mini__nav' ).remove();
                list.append( data['nav'] ).find( '.uspc-userlist' ).append( data['content'] ).animateCss( 'fadeIn' );
            }
        }
    } );

    return false;
}

function uspc_chat_words_count( e, elem ) {
    evt = e || window.event;

    var key = evt.keyCode,
        form = jQuery( elem ).parents( '.uspc-im__form' );

    if ( key == 13 && evt.ctrlKey ) {
        uspc_chat_add_new_message( form );
        return false;
    }

    var words = jQuery( elem ).val();
    words = jQuery.trim( words );
    var counter = uspc_max_words - words.length,
        color;

    if ( counter > ( uspc_max_words - 1 ) ) {
        uspc_disable_button( form );
        return false;
    }

    if ( counter < 0 ) {
        jQuery( elem ).val( words.substr( 0, ( uspc_max_words - 1 ) ) );
        return false;
    }

    uspc_enable_button( form );

    if ( counter > 150 )
        color = 'green';
    else if ( 50 < counter && counter < 150 )
        color = 'orange';
    else if ( counter < 50 )
        color = 'red';

    jQuery( elem ).parent( '.uspc-im__form' ).find( '.uspc-im-form__sign-count' ).css( 'color', color ).text( counter );
}

function uspc_chat_remove_contact( e, chat_id ) {
    usp_preloader_show( '.uspc-userlist' );

    var contact = jQuery( e ).parents( '.uspc-contact-box' ).data( 'contact' );

    usp_ajax( {
        data: {
            action: 'uspc_chat_remove_contact',
            chat_id: chat_id
        },
        success: function( data ) {

            if ( data['remove'] ) {
                jQuery( '[data-contact="' + contact + '"]' ).animateCss( 'flipOutX', function( e ) {
                    jQuery( e ).remove();
                } );

                usp_do_action( 'uspc_removed_contact', chat_id );
            }
        }
    } );

    return false;
}

// scroll down in all important tab
usp_add_action( 'usp_upload_tab', 'uspc_important_load' );
usp_add_action( 'usp_init', 'uspc_important_load' );
function uspc_important_load( e ) {
    if ( e && e.result.subtab_id === 'important-messages' ) {
        var content = e.result.content,
            token = jQuery( content ).find( '.uspc-im' ).data( 'token' );
    } else {
        var tab = usp_get_value_url_params();
        if ( tab && tab['subtab'] === 'important-messages' ) {
            token = jQuery( '.uspc-im' ).data( 'token' );
        }
    }

    if ( token ) {
        uspc_scroll_down( token );
    }
}

function uspc_chat_message_important( message_id ) {
    usp_preloader_show( '.uspc-post[data-message="' + message_id + '"] > div' );

    usp_ajax( {
        data: {
            action: 'uspc_chat_message_important',
            message_id: message_id
        },
        success: function( data ) {
            jQuery( '.uspc-post[data-message="' + message_id + '"]' ).toggleClass( 'uspc-post__saved' );
        }
    } );

    return false;
}

function uspc_chat_important_manager_shift( e, status ) {
    usp_preloader_show( '.uspc-im' );

    uspc_important = status;

    var token = jQuery( e ).parents( '.uspc-im' ).data( 'token' );

    usp_ajax( {
        data: {
            action: 'uspc_chat_important_manager_shift',
            token: token,
            status_important: status
        },
        success: function( data ) {
            if ( data['content'] ) {
                var form = jQuery( e ).parents( '.uspc-im' ).find( '.uspc-im__form' );
                status ? form.hide() : form.show();

                jQuery( e ).parents( '.uspc-im__box' ).html( data['content'] ).animateCss( 'fadeIn' );

                uspc_scroll_down( token );
            }
        }
    } );

    return false;
}

function uspc_chat_delete_message( message_id ) {
    usp_preloader_show( '.uspc-post[data-message="' + message_id + '"] > div' );

    usp_ajax( {
        data: {
            action: 'uspc_chat_ajax_delete_message',
            message_id: message_id
        },
        success: function( data ) {
            if ( data['remove'] ) {
                jQuery( '.uspc-post[data-message="' + message_id + '"]' ).animateCss( 'flipOutX', function( e ) {
                    jQuery( e ).remove();
                } );

                usp_do_action( 'uspc_deleted_message', message_id );
            }
        }
    } );

    return false;
}

function uspc_chat_delete_attachment( e, attachment_id ) {
    usp_preloader_show( '.uspc-im__form' );

    usp_ajax( {
        data: {
            action: 'uspc_chat_delete_attachment',
            attachment_id: attachment_id
        },
        success: function( data ) {
            if ( data['remove'] ) {
                var form = jQuery( e ).parents( '.uspc-im__form' );
                form.find( '.uspc-chat-uploader' ).show();
            }
        }
    } );

    return false;
}

function uspc_chat_beat_core( chat ) {
    if ( chat.timeout == 1 ) {
        if ( uspc_inactive_counter >= USP.usp_chat.inactivity ) {
            console.log( 'inactive:' + uspc_inactive_counter );
            return false;
        }
    }

    var chatBox = jQuery( '.uspc-im[data-token="' + chat.token + '"]' ),
        chat_form = chatBox.find( '.uspc-im__form' ),
        beat = {
            action: 'uspc_chat_get_new_messages',
            success: 'uspc_chat_beat_success',
            data: {
                last_activity: uspc_last_activity[chat.token],
                token: chat.token,
                update_activity: 1,
                user_write: ( chat_form.find( 'textarea' ).val() ) ? 1 : 0
            }
        };

    return beat;
}

function uspc_chat_beat_success( data ) {
    var chat = jQuery( '.uspc-im[data-token="' + data.token + '"]' );

    if ( !chat.length ) {
        uspc_chat_clear_beat( data.token );
        return;
    }

    var user_write = 0;
    chat.find( '.uspc-im-online__items' ).html( '' );
    uspc_chat_write_status_cancel( data.token );

    if ( data['errors'] ) {
        jQuery.each( data['errors'], function( index, error ) {
            usp_notice( error, 'error', 10000 );
        } );
    }

    if ( data['success'] ) {
        uspc_last_activity[data.token] = data['current_time'];

        if ( data['users'] ) {
            jQuery.each( data['users'], function( index, data ) {
                chat.find( '.uspc-im-online__items' ).append( data['link'] );
                if ( data['write'] == 1 )
                    user_write = 1;
            } );
        }

        if ( data['content'] ) {
            uspc_play_sound();
            chat.find( '.uspc-im__talk' ).append( data['content'] );
            uspc_scroll_down( data.token );
        } else {
            if ( user_write )
                uspc_chat_write_status( data.token );
        }
    }

    usp_do_action( 'uspc_get_new_messages', {
        token: data.token,
        result: data
    } );
}

// open PM in modal: 1 - this; 2 - id chat user
function uspc_get_chat_window( e, user_id ) {
    if ( e && jQuery( e ).parents( '.preloader-parent' ) ) {
        usp_preloader_show( jQuery( e ).parents( '.preloader-parent' ) );
    }

    usp_ajax( {
        data: {
            action: 'uspc_get_ajax_chat_window',
            user_id: user_id
        }
    } );
}

/* Direct messages */

// open direct message 
function uspc_get_chat_dm( e, user_id ) {
    usp_preloader_show( jQuery( e ) );

    usp_ajax( {
        data: {
            action: 'uspc_get_direct_message',
            user_id: user_id
        },
        success: function( data ) {
            if ( data.chat_pm ) {
                var bTtn = '<div class="uspc-head__bttn" onclick="usp_load_tab(\'chat\', 0, this);return false;" data-token-dm="' + data.dm_token + '"><i class="uspi fa-angle-left"></i></div>';
                jQuery( '.usp-subtabs-menu, .uspc-userlist + .uspc-mini__nav, .usp-tab-chat .usp-subtab-title' ).remove();
                jQuery( '#usp-tab__chat.usp-bttn__active' ).addClass( 'usp-bttn__active-dm' );
                jQuery( '.uspc-head' ).html( bTtn + data.chat_name );
                jQuery( '.uspc-userlist' ).html( data.chat_pm );
            }
        }
    } );

}

// tab loading hook
usp_add_action( 'usp_upload_tab', 'uspc_set_chat_button_in_menu_active' );
function uspc_set_chat_button_in_menu_active( e ) {
    // is chat tab
    if ( e.result.tab_id === 'chat' ) {
        jQuery( '#usp-tab__chat' ).addClass( 'usp-bttn__active' );
    }
}

//
jQuery( function( $ ) {
    // after leaving the communication, clear usp_beat
    $( 'body' ).on( 'click', '.uspc-head__bttn', function() {
        var tok = $( '.uspc-head__bttn' ).data( 'token-dm' );
        uspc_chat_clear_beat( tok );
    } );

    // click on the block with unread - we will reduce the counters
    $( 'body' ).on( 'click', '.uspc-unread__incoming', function() {
        var cnt = $( '#usp-tab__chat .usp-bttn__count' ).text(),
            bttn = $( '.uspc_js_counter_unread .usp-bttn__count' );

        if ( cnt > 1 ) {
            setTimeout( function() {
                bttn.html( cnt - 1 );
            }, 1000 );
        } else if ( cnt === '1' ) {
            setTimeout( function() {
                bttn.hide();
            }, 1000 );
        }

        // remove notify on contact panel
        var contact = $( this ).data( 'contact' );
        var contact_panel_user = $( '.uspc-mini__contacts' ).find( "[data-contact='" + contact + "']" );

        contact_panel_user.children( ' .uspc-mini-person__in' ).hide();
    } );
} );

// from the chat tab went to direct communication
usp_add_action( 'uspc_get_direct_message', 'uspc_logged_in_to_dm' );
function uspc_logged_in_to_dm() {
    // scroll posts
    var mess = jQuery( '.uspc-post' );
    if ( mess.length >= 1 ) {
        setTimeout( function() {
            jQuery( '.uspc-post' ).scrollTop( jQuery( '.uspc-post' ).get( 0 ).scrollHeight );
        }, 200 );
    }

    uspc_slide_textarea();

// auto-height of the input field
    jQuery( '#usp-office .uspc-im__form textarea' ).each( function() {
        var h = this.scrollHeight + 9;
        this.setAttribute( 'style', 'height:' + h + 'px;overflow-y:hidden;' );
    } ).on( 'input', function() {
        this.style.height = 'auto';
        var h = this.scrollHeight + 9;
        this.style.height = h + 'px';
    } );
}

// scroll to form
function uspc_slide_textarea( top = 0 ) {
    var h = window.innerHeight;
    var chatForm = jQuery( '.uspc-im__form' );

    if ( chatForm.length < 1 )
        return false;

    //  offset to form
    var offsetToChat = chatForm.offset().top;
    // if the browser window is huge - not scroll
    if ( ( h + 150 ) < offsetToChat ) {
        var vw = jQuery( window ).width();
        // height of the form
        var offset = 166;
        if ( vw <= 480 ) {
            offset = 200;
        }
        jQuery( 'body,html' ).animate( {
            scrollTop: offsetToChat - ( h - offset - top )
        }, 1000 );
}
}

/* end */
