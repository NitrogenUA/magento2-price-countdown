define([
    'jquery',
    'Perspective_Specprice/js/jquery.countdown.min'
], ($) => {
    return function(config, element) {
        'use strict';
        
        $(document).ready(() => {
            $(element).countdown(new Date(config.date), function(event) {
                $(this).html(event.strftime('%D days %H:%M:%S'));
            });
        });
    }
});
