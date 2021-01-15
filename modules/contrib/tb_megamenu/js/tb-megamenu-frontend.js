Drupal.TBMegaMenu = Drupal.TBMegaMenu || {};

(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.TBMegaMenu.oldWindowWidth = 0;
  Drupal.TBMegaMenu.displayedMenuMobile = false;
  Drupal.TBMegaMenu.supportedScreens = [980];
  Drupal.TBMegaMenu.menuResponsive = function () {
    var windowWidth = window.innerWidth ? window.innerWidth : $(window).width();
    var navCollapse = $('.tb-megamenu').children('.nav-collapse');
    if (windowWidth < Drupal.TBMegaMenu.supportedScreens[0]) {
      navCollapse.addClass('collapse');
      if (Drupal.TBMegaMenu.displayedMenuMobile) {
        navCollapse.css({height: 'auto', overflow: 'visible'});
      } else {
        navCollapse.css({height: 0, overflow: 'hidden'});
      }
    } else {
      // If width of window is greater than 980 (supported screen).
      navCollapse.removeClass('collapse');
      if (navCollapse.height() <= 0) {
        navCollapse.css({height: 'auto', overflow: 'visible'});
      }
    }
  };

  Drupal.behaviors.tbMegaMenuAction = {
    attach: function (context, settings) {

      var ariaCheck = function() {
        $("li.tb-megamenu-item").each(function () {
          if ($(this).is('.mega-group')) {
            // Mega menu item has mega class (it's a true mega menu)
            if(!$(this).parents().is('.open')) {
              // Mega menu item has mega class and its ancestor is closed, so apply appropriate ARIA attributes
              $(this).children().attr('aria-expanded', 'false');
            }
            else if ($(this).parents().is('.open')) {
              // Mega menu item has mega class and its ancestor is open, so apply appropriate ARIA attributes
              $(this).children().attr('aria-expanded', 'true');
            }
          }
          else if ($(this).is('.dropdown') || $(this).is('.dropdown-submenu')) {
            // Mega menu item has dropdown (it's a flyout menu)
            if (!$(this).is('.open')) {
              // Mega menu item has dropdown class and is closed, so apply appropriate ARIA attributes
              $(this).children().attr('aria-expanded', 'false');
            }
            else if ($(this).is('.open')) {
              // Mega menu item has dropdown class and is open, so apply appropriate ARIA attributes
              $(this).children().attr('aria-expanded', 'true');
            }
          }
          else {
            // Mega menu item is neither a mega or dropdown class, so remove ARIA attributes (it doesn't have children)
            $(this).children().removeAttr('aria-expanded');
          }
        });
      };

      var showMenu = function ($menuItem, mm_timeout) {
        $menuItem.children('.dropdown-toggle').attr('aria-expanded', 'true');
        if ($menuItem.hasClass ('mega')) {
          $menuItem.addClass ('animating');
          clearTimeout ($menuItem.data('animatingTimeout'));
          $menuItem.data('animatingTimeout', setTimeout(function() {
            $menuItem.removeClass ('animating')
          }, mm_timeout));
          clearTimeout ($menuItem.data('hoverTimeout'));
          $menuItem.data('hoverTimeout', setTimeout(function() {
            $menuItem.addClass ('open');
            ariaCheck();
          }, 100));
        } else {
          clearTimeout ($menuItem.data('hoverTimeout'));
          $menuItem.data('hoverTimeout', setTimeout(function() {
            $menuItem.addClass ('open');
            ariaCheck();
          }, 100));
        }
      }

      var hideMenu = function ($menuItem, mm_timeout) {
        $menuItem.children('.dropdown-toggle').attr('aria-expanded', 'false');
        if ($menuItem.hasClass ('mega')) {
          $menuItem.addClass ('animating');
          clearTimeout ($menuItem.data('animatingTimeout'));
          $menuItem.data('animatingTimeout', setTimeout(function() {
            $menuItem.removeClass ('animating')
          }, mm_timeout));
          clearTimeout ($menuItem.data('hoverTimeout'));
          $menuItem.data('hoverTimeout', setTimeout(function() {
            $menuItem.removeClass ('open');
            ariaCheck();
          }, 100));
        } else {
          clearTimeout ($menuItem.data('hoverTimeout'));
          $menuItem.data('hoverTimeout', setTimeout(function() {
            $menuItem.removeClass ('open');
            ariaCheck();
          }, 100));
        }
      };

      // Hide the menu quickly without animating. This is triggered when
      // pressing the ESC key.
      var hideMenuFast = function() {
        $('.tb-megamenu').find('.dropdown-toggle').attr('aria-expanded', 'false');
        $('.tb-megamenu').find('.tb-megamenu-item').removeClass('open');
        ariaCheck();
      }

      var button = $(context).find('.tb-megamenu-button').once('tb-megamenu-action');
      $(button).click(function () {
        if (parseInt($(this).parent().children('.nav-collapse').height())) {
          $(this).parent().children('.nav-collapse').css({height: 0, overflow: 'hidden'});
          Drupal.TBMegaMenu.displayedMenuMobile = false;
        }
        else {
          $(this).parent().children('.nav-collapse').css({height: 'auto', overflow: 'visible'});
          Drupal.TBMegaMenu.displayedMenuMobile = true;
        }
      });

      // Handle keyboard navigation.
      // ESC closes any open menus.
      // RIGHT moves to the next top level menu item.
      // LEFT moves to the previous top level menu item.
      var handleKeyboard = function(mm_timeout) {
        $(document).on('keydown.tbMegamenu', function(event) {
          // ESC
          if (event.keyCode == 27) {
            hideMenuFast($(this), mm_timeout)
            return;
          }

          // Right arrow
          if (event.keyCode == 39) {
            var $openItem = $('.tb-megamenu').find('.tb-megamenu-item.level-1.open');

            if ($openItem.length === 0) {
              $openItem = $('.tb-megamenu').find('a:focus, span:focus').closest('.level-1');
            }
            var $nextItem = $openItem.next('li');

            hideMenu($openItem, mm_timeout);

            if ($nextItem.length > 0) {
              $nextItem.children('a, span').focus();
            }

            return;
          }

          // Left arrow
          if (event.keyCode == 37) {
            var $openItem = $('.tb-megamenu').find('.tb-megamenu-item.level-1.open');

            if ($openItem.length === 0) {
              $openItem = $('.tb-megamenu').find('a:focus, span:focus').closest('.level-1');
            }

            var $nextItem = $openItem.prev('li');

            hideMenu($openItem, mm_timeout);
            
            if ($nextItem.length > 0) {
              $nextItem.children('a, span').focus();
            }

            return;
          }
        });
      }

      var isTouch = 'ontouchstart' in window || window.navigator.msMaxTouchPoints;

      $(document).ready(function ($) {
        var mm_duration = 0;
        $('.tb-megamenu', context).each(function () {
          if ($(this).data('duration')) {
            mm_duration = $(this).data('duration');
          }
        });
        var mm_timeout = mm_duration ? 100 + mm_duration : 500;

        if (!isTouch) {
          $('.nav > li, li.mega', context).bind('mouseenter', function(event) {
            showMenu($(this), mm_timeout)
          });
          $('.nav > li > .dropdown-toggle, li.mega > .dropdown-toggle', context).bind('focus', function(event) {
            var $this = $(this);
            var $subMenu = $this.closest('li');
  
            showMenu($subMenu, mm_timeout);
            // If the focus moves outside of the subMenu, close it.
            $(document).bind('focusin', function(event) {
              if ($subMenu.has(event.target).length) {
                return;
              }
              $(document).unbind(event);
              hideMenu($subMenu, mm_timeout);
            });
          });
          $('.nav > li, li.mega', context).bind('mouseleave', function(event) {
            hideMenu($(this), mm_timeout);
          });
  
          // Handle keyboard input whenever the mouse enters the menu or it
          // receives focus.
          $('.tb-megamenu', context).on('mouseenter focusin', function(event) {
            $(document).off('keydown.tbMegamenu');
            handleKeyboard(mm_timeout);
          })
  
          // Remove keyboard listener whenever the mouse leaves.
          $('.tb-megamenu', context).on('mouseleave', function(event) {
            // Does an element in the menu still have focus? If so, do nothing.
            var $hasFocus = $('.tb-megamenu').find('a:focus');
  
            if ($hasFocus.length === 0) {
              $(document).off('keydown.tbMegamenu');
            }
          })
  
          // Remove keyboard listener whenever the menu loses focus.
          $('.tb-megamenu', context).on('focusout', function(event) {
            $(document).off('keydown.tbMegamenu');
          }) 
        }

        // Define actions for touch devices.
        var createTouchMenu = function (items) {
          items.children("a, span").each(function () {
            var $item = $(this);
            var tbitem = $(this).parent();

            $item.click(function (event) {
              // If the menu link has already been clicked once...
              if ($item.hasClass("tb-megamenu-clicked")) {
                var $uri = $item.attr("href");

                // If the menu link has a URI, go to the link.
                // <nolink> menu items will not have a URI.
                if ($uri) {
                  window.location.href = $uri;
                } else {
                  $item.removeClass("tb-megamenu-clicked");
                  hideMenu(tbitem, mm_timeout);
                }
              } else {
                event.preventDefault();

                // Hide any already open menus.
                hideMenuFast();
                $(".tb-megamenu").find(".tb-megamenu-clicked").removeClass("tb-megamenu-clicked");

                // Open the submenu.
                $item.addClass("tb-megamenu-clicked");
                showMenu(tbitem, mm_timeout);
              }
            });
          });
        };

        if (isTouch) {
          createTouchMenu($(".tb-megamenu ul.nav li.mega", context).has(".dropdown-menu"));
        }
      });

      $(window).resize(function () {
        var windowWidth = window.innerWidth ? window.innerWidth : $(window).width();
        if (windowWidth != Drupal.TBMegaMenu.oldWindowWidth) {
          Drupal.TBMegaMenu.oldWindowWidth = windowWidth;
          Drupal.TBMegaMenu.menuResponsive();
        }
      });

    }
  };
})(jQuery, Drupal, drupalSettings);

