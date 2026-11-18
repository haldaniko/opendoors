var _Site;

jQuery(function ($) {
  _Site.init();
});

(($) => {
  _Site = {
    init() {
      _Site.handleGalleryArrows();
      _Site.handleThumbnailsArrows();
      _Site.scrollDown();
      _Site.handleTeamMembersReadMore();
    },

    handleGalleryArrows() {
      const carouselId = $('[data-carousel-id]').data('carousel-id');
      const $carousel = $('#' + carouselId);
      if (!$carousel.length) return;

      const $prevBtn = $carousel.find('.carousel-control-prev');
      const $nextBtn = $carousel.find('.carousel-control-next');

      const toggleArrows = function () {
        const isEmbed = $carousel.find('.carousel-item.active .embed-wrapper').length > 0;
        $prevBtn.toggle(!isEmbed);
        $nextBtn.toggle(!isEmbed);
      };

      toggleArrows();
      $carousel.on('slid.bs.carousel', toggleArrows);
    },

    handleThumbnailsArrows() {
      const scrollStep = 200;

      $('.thumbnails-wrapper').each(function () {
        const $wrapper = $(this);
        const $track = $wrapper.find('.thumbnails-images');
        const $prev = $wrapper.siblings('.thumb-arrow.prev');
        const $next = $wrapper.siblings('.thumb-arrow.next');

        if (!$prev.length && !$next.length) return;
        if (!$track.length) return;

        $prev.on('click', function (e) {
          e.preventDefault();
          $wrapper.stop(true).animate({ scrollLeft: $wrapper.scrollLeft() - scrollStep }, 200);
        });

        $next.on('click', function (e) {
          e.preventDefault();
          $wrapper.stop(true).animate({ scrollLeft: $wrapper.scrollLeft() + scrollStep }, 200);
        });
      });
    },

    scrollDown() {
      var $btn = $('.scroll-down');

      if (!$btn.length || $btn.data('scrollInit')) return;

      var id = $btn.data('target') || 'next-section';
      var $target = $('#' + id);

      if (!$target.length) {
        $btn.addClass('is-hidden');
        return;
      }

      function updateBtn() {
        var rect = $target[0].getBoundingClientRect();
        var vpH = window.innerHeight;
        var visibleHeight = Math.max(0, Math.min(rect.bottom, vpH) - Math.max(rect.top, 0));

        var hide = ($(window).scrollTop() > 20) || (visibleHeight > 120);
        $btn.toggleClass('is-hidden', hide);
      }

      updateBtn();
      $(window).on('scroll resize', updateBtn);

      $btn.on('click', function (e) {
        e.preventDefault();
        if (!$target.length) return;

        $btn.addClass('is-hidden');
        $(window).off('scroll resize', updateBtn);

        $('html, body').stop(true).animate({ scrollTop: $target.offset().top }, 220, 'swing', function () {
          requestAnimationFrame(function () {
            setTimeout(function () {
              $(window).on('scroll resize', updateBtn);
              $btn.removeClass('is-hidden');
            }, 50);
          });
        });
      });
    },

    handleTeamMembersReadMore() {
      var $section = $('.wrapper-team-members');

      if (!$section.length || $section.data('teamMembersInit')) {
        return;
      }

      $section.data('teamMembersInit', true);

      $section.find('.wrapper-description').each(function () {
        var $description = $(this).find('.description').first();
        var $showMoreBtn = $(this).find('.show-more').first();

        if (!$description.length || !$showMoreBtn.length) {
          return;
        }

        $description.addClass('is-clamped');

        // Show button only if description is actually truncated.
        requestAnimationFrame(function () {
          $showMoreBtn.toggle(
            $description[0] &&
            $description[0].scrollHeight > $description[0].clientHeight + 1
          );
        });

        $showMoreBtn.on('click', function (e) {
          e.preventDefault();

          var isClamped = $description.toggleClass('is-clamped').hasClass('is-clamped');
          $showMoreBtn.text(isClamped ? 'Show more' : 'Show less');
        });
      });
    },

  };
})(jQuery);
