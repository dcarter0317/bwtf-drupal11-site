(function (Drupal, once) {
  Drupal.behaviors.bwtfSlider = {
    attach(context, settings) {
      once('bwtf-swiper', '.mySwiper', context).forEach((el) => {
        function start() {
          if (typeof Swiper === 'undefined') {
            setTimeout(start, 50);
            return;
          }

          // Read autoplay delay from drupalSettings (ms). 0 disables autoplay.
          const cfg = (settings && settings.bwtf && settings.bwtf.slider) || {};
          let delay = Number(cfg.autoplayDelay);
          if (!Number.isFinite(delay) || delay < 0) delay = 5000;
          const autoplay = delay === 0 ? false : {
            delay,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
          };

          const nextEl = el.querySelector('.swiper-button-next');
          const prevEl = el.querySelector('.swiper-button-prev');
          const pagEl  = el.querySelector('.swiper-pagination');

          // eslint-disable-next-line no-new
          new Swiper(el, {
            loop: true,
            speed: 1000,
            slidesPerView: 1,
            navigation: nextEl && prevEl ? { nextEl, prevEl } : undefined,
            pagination: pagEl ? { el: pagEl, clickable: true } : undefined,
            autoplay,
            a11y: { enabled: true },
            observer: true,
            observeParents: true,
          });
        }

        start();
      });
    },
  };
})(Drupal, once);
