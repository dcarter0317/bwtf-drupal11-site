/**
 * SimpleAds carousel without slick
 * Pure JavaScript/CSS carousel implementation that works with jQuery 4
 */
(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.simpleadsFixSlick = {
    attach: function (context, settings) {
      
      // Initialize carousel manually without slick
      $(once('simpleads-fix', '.block-simpleads .simpleads', context)).each(function() {
        var $el = $(this);
        var rotationType = $el.data('rotation-type');
        
        if (rotationType === 'loop') {
          var groupId = $el.data('group');
          var nodeRefField = $el.data('ref-node');
          var simpleadsRefField = $el.data('ref-simpleads');
          
          console.log('SimpleAds Fix: Initializing group ' + groupId + ' with custom carousel');
          
          // Use SimpleAds library to get ads
          if (typeof window.SimpleAds !== 'undefined') {
            var lib = new SimpleAds();
            
            lib.getAds(groupId, settings.simpleads?.current_node_id, nodeRefField, simpleadsRefField, function(data) {
              console.log('SimpleAds Fix: Raw data received:', data);
              console.log('SimpleAds Fix: Data type:', typeof data);
              console.log('SimpleAds Fix: Is array?', Array.isArray(data));

              // Normalize response formats.
              // SimpleAds REST returns: { code, message, data: { items: [...], count: n } }
              var ads = [];
              if (data && typeof data === 'object') {
                if (data.code) {
                  console.log('SimpleAds Fix: Response code:', data.code, 'message:', data.message);
                }
                if (data.data && data.data.items) {
                  ads = Array.isArray(data.data.items) ? data.data.items : Object.values(data.data.items);
                }
                else if (data.items) {
                  ads = Array.isArray(data.items) ? data.items : Object.values(data.items);
                }
                else if (data.ads) {
                  ads = Array.isArray(data.ads) ? data.ads : Object.values(data.ads);
                }
                else if (Array.isArray(data)) {
                  ads = data;
                }
              }

              console.log('SimpleAds Fix: Received ' + ads.length + ' ads');
              if (ads.length > 0) {
                console.log('SimpleAds Fix: First ad structure:', JSON.stringify(ads[0]));
              }

              if (ads.length <= 1) {
                console.log('SimpleAds Fix: Only ' + ads.length + ' ad in group ' + groupId + ' — no rotation/dots will be shown.');
              }
              
              if (ads.length === 0) {
                console.log('SimpleAds Fix: No ads found for group ' + groupId);
                return;
              }
              
              // Build custom carousel HTML
              var html = '<div class="simpleads-custom-carousel">';
              html += '<div class="carousel-slides">';
              
              ads.forEach(function(ad, index) {
                if (!ad || typeof ad !== 'object') {
                  console.warn('SimpleAds Fix: Skipping non-object ad at index', index, ad);
                  return;
                }
                var activeClass = index === 0 ? ' active' : '';
                var entityId = ad.entity_id || ad.id || '';
                var adUrl = ad.url || ad.ad_url || '#';
                var adTarget = ad.url_target || ad.target || '_blank';
                var adHtml = ad.ad_html || ad.html || ad.image || '';

                // Decode HTML entities if any were returned escaped, then try to extract picture/img
                var tempDiv = document.createElement('div');
                var adContent = adHtml || '';
                // Decode HTML entities if returned escaped
                if (adContent.indexOf('&lt;') !== -1) {
                  var txt = document.createElement('textarea');
                  txt.innerHTML = adContent;
                  adContent = txt.value;
                }
                // Strip HTML comments (theme debug) to avoid confusing parsers
                adContent = adContent.replace(/<!--([\s\S]*?)-->/g, '');
                tempDiv.innerHTML = adContent;
                var pictureElement = tempDiv.querySelector('picture') || tempDiv.querySelector('img');

                // Fallback: try regex to pull <picture> or <img> if DOM parsing didn't find them
                if (!pictureElement) {
                  var m = adContent.match(/(<picture[\s\S]*?<\/picture>)/i);
                  if (m) {
                    var holder = document.createElement('div');
                    holder.innerHTML = m[1];
                    pictureElement = holder.firstChild;
                  }
                  else {
                    m = adContent.match(/(<img[\s\S]*?>)/i);
                    if (m) {
                      var holder2 = document.createElement('div');
                      holder2.innerHTML = m[1];
                      pictureElement = holder2.firstChild;
                    }
                  }
                }

                // Ensure <img> has a src attribute (use first srcset candidate as fallback)
                if (pictureElement) {
                  var imgEl = pictureElement.tagName && pictureElement.tagName.toLowerCase() === 'img' ? pictureElement : pictureElement.querySelector('img');
                  if (imgEl) {
                    var src = imgEl.getAttribute('src');
                    var srcset = imgEl.getAttribute('srcset') || imgEl.dataset.srcset;
                    if ((!src || src === '') && srcset) {
                      // Choose first URL from srcset
                      var first = srcset.split(',')[0].trim().split(' ')[0];
                      if (first) {
                        imgEl.src = first;
                      }
                    }
                  }
                }

                var cleanHtml = pictureElement ? pictureElement.outerHTML : adContent;
                
                console.log('SimpleAds Fix: Ad ' + index + ' cleanHtml:', cleanHtml.substring(0, 200));
                
                html += '<div class="carousel-slide' + activeClass + '" data-id="' + entityId + '">';
                html += '<a href="' + adUrl + '" target="' + adTarget + '" data-id="' + entityId + '">';
                html += cleanHtml;
                html += '</a>';
                html += '</div>';
              });
              
              html += '</div>';
              
              // Add navigation dots
              if (ads.length > 1) {
                html += '<div class="carousel-dots">';
                ads.forEach(function(ad, index) {
                  var activeClass = index === 0 ? ' active' : '';
                  html += '<button class="carousel-dot' + activeClass + '" data-index="' + index + '"></button>';
                });
                html += '</div>';
              }
              
              html += '</div>';
              
              $el.html(html);
              
              var $carousel = $el.find('.simpleads-custom-carousel');
              var $slides = $carousel.find('.carousel-slide');
              var $dots = $carousel.find('.carousel-dot');
              var currentIndex = 0;
              var intervalId = null;
              
              // Track initial impression
              var initialEntityId = $slides.eq(0).data('id');
              if (initialEntityId) {
                lib.trackImpression(initialEntityId);
              }
              
              // Function to show specific slide
              function showSlide(index) {
                $slides.removeClass('active').eq(index).addClass('active');
                $dots.removeClass('active').eq(index).addClass('active');
                currentIndex = index;
                
                // Track impression
                var entityId = $slides.eq(index).data('id');
                if (entityId) {
                  lib.trackImpression(entityId);
                }
              }
              
              // Function to show next slide
              function nextSlide() {
                var nextIndex = (currentIndex + 1) % $slides.length;
                showSlide(nextIndex);
              }
              
              // Auto-advance every 3 seconds
              if ($slides.length > 1) {
                intervalId = setInterval(nextSlide, 3000);
                
                // Dot click handlers
                $dots.on('click', function() {
                  clearInterval(intervalId);
                  showSlide($(this).data('index'));
                  intervalId = setInterval(nextSlide, 3000);
                });
              }
              
              // Track clicks
              lib.clickAd($slides.find('a'), function() {});
              
              console.log('SimpleAds Fix: Custom carousel initialized successfully with ' + ads.length + ' ads');
            });
          }
        }
      });
    }
  };

})(jQuery, Drupal, once);

// Fallback: force-load lazy images created by SimpleAds or other scripts.
(function () {
  'use strict';

  function forceLoadLazyImages(root) {
    root = root || document;
    // imgs with data-src or data-srcset
    var imgs = Array.from(root.querySelectorAll('img[data-src], img[data-srcset]'));
    imgs.forEach(function (img) {
      try {
        if (img.dataset.src) {
          img.src = img.dataset.src;
        }
        if (img.dataset.srcset) {
          img.srcset = img.dataset.srcset;
        }
        img.removeAttribute('data-src');
        img.removeAttribute('data-srcset');
        // For <picture><source> elements that use data-srcset
        var picture = img.closest('picture');
        if (picture) {
          var sources = picture.querySelectorAll('source[data-srcset]');
          sources.forEach(function (s) {
            s.srcset = s.dataset.srcset || s.getAttribute('data-srcset');
            s.removeAttribute('data-srcset');
          });
        }
        // Trigger load event in case scripts wait for it
        img.dispatchEvent(new Event('load'));
      }
      catch (e) {
        // ignore
        console.warn('forceLoadLazyImages error', e);
      }
    });
  }

  // Try to run after DOMContentLoaded and again after a short delay
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { setTimeout(forceLoadLazyImages, 100); });
  } else {
    setTimeout(forceLoadLazyImages, 100);
  }

  // Also observe DOM mutations to catch dynamically injected ads/content
  try {
    var mo = new MutationObserver(function (mutations) {
      mutations.forEach(function (m) {
        if (m.addedNodes && m.addedNodes.length) {
          m.addedNodes.forEach(function (n) {
            if (n.nodeType === 1) {
              forceLoadLazyImages(n);
            }
          });
        }
      });
    });
    mo.observe(document.documentElement || document.body, { childList: true, subtree: true });
  } catch (e) {
    // MutationObserver may not be available in some environments
  }

})();
