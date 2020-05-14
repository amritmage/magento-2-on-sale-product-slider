/**
 * This script is a simple wrapper that allows you to use OwlCarousel with Magento 2
 */

define([
    "jquery",
    "owlcarousel"
], function($, owlcarousel){
    return function (config, element) {
        $('.osl-item').show();
        return $(element).owlCarousel(config);
    }
});