/* global wp, USP */
// real time preview

var alpha = USP.uspc_css.alpha;
var r = USP.uspc_css.r, g = USP.uspc_css.g, b = USP.uspc_css.b, from = USP.uspc_css.from;

wp.customize('usp_customizer[uspc_theme]', function (value) {
    value.bind(function (to) {
        from = to;
        let hex = to.replace('#', '');
        r = parseInt(hex.substring(0, 2), 16);
        g = parseInt(hex.substring(2, 4), 16);
        b = parseInt(hex.substring(4, 6), 16);

        uspc_customizer_set_color();
    });
});

wp.customize('usp_customizer[uspc_alpha]', function (value) {
    value.bind(function (to) {
        alpha = to;

        uspc_customizer_set_color();
    });
});

function uspc_customizer_set_color() {
    let rgba = 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';

    jQuery('.uspc-you .uspc-post__message').css({
        'background': 'linear-gradient(180deg, ' + from + ' 0%, ' + rgba + ' 51%) no-repeat fixed center center',
    });
}
