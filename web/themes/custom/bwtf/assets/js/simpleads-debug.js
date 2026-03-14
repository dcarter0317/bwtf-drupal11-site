/**
 * SimpleAds debugging helper
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.simpleadsDebug = {
    attach: function (context, settings) {
      // Only run once
      if (context !== document) {
        return;
      }

      console.log('SimpleAds Debug: Checking dependencies...');
      console.log('jQuery available:', typeof $ !== 'undefined');
      console.log('jQuery.fn.slick available:', typeof $.fn.slick !== 'undefined');
      console.log('SimpleAds library available:', typeof window.SimpleAds !== 'undefined');
      console.log('Cookies available:', typeof Cookies !== 'undefined');
      
      // Check for simpleads elements
      var $simpleadsBlocks = $('.block-simpleads .simpleads', context);
      console.log('SimpleAds blocks found:', $simpleadsBlocks.length);
      
      $simpleadsBlocks.each(function(index) {
        var $el = $(this);
        var rotationOpts = $el.data('rotation-options');
        console.log('Block ' + index + ' data:', {
          group: $el.data('group'),
          rotationType: $el.data('rotation-type'),
          rotationOptions: rotationOpts,
          rotationOptionsType: typeof rotationOpts,
          rotationOptionsParsed: (typeof rotationOpts === 'string') ? JSON.parse(rotationOpts) : rotationOpts
        });
      });
    }
  };

})(jQuery, Drupal);
