/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';


// SHOW TOOLTIP IMMEDIATELY ON HOVER
    $('.tooltip-show').tooltip({
        show: null,
        trigger : 'hover'
    });

    $('.tooltip-show').on('click', function () {
        $(this).tooltip('hide')
    })

// FLASH MESSAGES
    $(document).ready(function(){
        $('.flash-message-error').each(function(){
          toastr.error($(this).text());
        });
        $('.flash-message-success').each(function(){
          toastr.success($(this).text());
        });
        $('.flash-message-warning').each(function(){
          toastr.warning($(this).text());
        });
    });

// SHOW UNREAD MESSAGES IN TOP NAVBAR
    $(document).ready(function(){
        let path = $('#navbarUnreadMessages').data('path');
        if (path) {
            $.ajax({
                type: 'GET',
                url: path,
                success: function(data) {
                    if (data) {
                        $('#navbarUnreadMessages').after('<span class="badge badge-danger navbar-badge">'+data+'</span>');
                    }
                }
            });
        }
    });

// SHOW UNREAD FORUM REPLIES IN TOP NAVBAR
    $(document).ready(function(){
        let path = $('#navbarUnreadForum').data('path');
        if (path) {
            $.ajax({
                type: 'GET',
                url: path,
                success: function(data) {
                    if (data) {
                        $('#navbarUnreadForum').after('<span class="badge badge-success navbar-badge">'+data+'</span>');
                    }
                }
            });
        }
    });

// SHOW UNREAD EVENT UPDATES IN TOP NAVBAR
    $(document).ready(function(){
        let path = $('#navbarEventUpdates').data('path');
        if (path) {
            $.ajax({
                type: 'GET',
                url: path,
                success: function(data) {
                    if (data) {
                        $('#navbarEventUpdates').after('<span class="badge badge-primary navbar-badge">'+data+'</span>');
                    }
                }
            });
        }
    });

// DISPLAY INFO IN EMPTY MODAL on AJAX CALL
    $('.trigger-empty-modal').on('click', function(event) {
        event.preventDefault();
        let path = $(this).data('path');
        $.ajax({
            type: 'GET',
            url: path,
            success: function(data) {
                $('#empty-modal .modal-body').html(data);
                $('#empty-modal').modal();
            }
        });
    });

    $('.trigger-empty-md-modal').on('click', function(event) {
        event.preventDefault();
        let path = $(this).data('path');
        $.ajax({
            type: 'GET',
            url: path,
            success: function(data) {
                $('#empty-md-modal .modal-body').html(data);
                $('#empty-md-modal').modal();
            }
        });
    });

// USER IMAGE UPLOAD
    $('.user-image-input').on('change', function(event) {
        var inputFile = event.currentTarget;
        $('.user-image-upload-name')
        .html(inputFile.files[0].name);
    });


// COOKIES CONSENT
(function (root, factory, undefined) {
  'use strict';
  if (typeof define === 'function' && define.amd) {
    define([], factory);
  } else if (typeof exports === 'object') {
    module.exports = factory();
  } else {
    // root is window
    root.CookiesEuBanner = factory();
  }
}(window, function () {
  'use strict';

  let CookiesEuBanner,
    document = window.document;

  CookiesEuBanner = function (launchFunction, waitAccept, useLocalStorage, undefined) {
    if (!(this instanceof CookiesEuBanner)) {
      return new CookiesEuBanner(launchFunction);
    }

    this.cookieTimeout = 31104000000; // 12 months in milliseconds
    this.bots = /bot|crawler|spider|crawling/i;
    this.cookieName = 'hasConsent';
    this.trackingCookiesNames = ['__utma', '__utmb', '__utmc', '__utmt', '__utmv', '__utmz', '_ga', '_gat', '_gid'];
    this.launchFunction = launchFunction;
    this.waitAccept = waitAccept || false;
    this.useLocalStorage = useLocalStorage || false;
    this.init();
  };

  CookiesEuBanner.prototype = {
    init: function () {
      // Detect if the visitor is a bot or not
      // Prevent for search engine take the cookie alert message as main content of the page
      let isBot = this.bots.test(navigator.userAgent);

      // Check if DoNotTrack is activated
      let dnt = navigator.doNotTrack || navigator.msDoNotTrack || window.doNotTrack;
      let isToTrack = (dnt !== null && dnt !== undefined) ? (dnt && dnt !== 'yes' && dnt !== 1 && dnt !== '1') : true;

      // Do nothing if it is a bot
      // If DoNotTrack is activated, do nothing too
      if (isBot || !isToTrack || this.hasConsent() === false) {
        this.removeBanner(0);
        return false;
      }

      // User has already consent to use cookies to tracking
      if (this.hasConsent() === true) {
        // Launch user custom function
        this.launchFunction();
        return true;
      }

      // If it's not a bot, no DoNotTrack and not already accept, so show banner
      this.showBanner();

      if (!this.waitAccept) {
        // Accept cookies by default for the next page
        this.setConsent(true);
      }
    },

    /*
     * Show banner at the top of the page
     */
    showBanner: function () {
      let _this = this,
        getElementById = document.getElementById.bind(document),
        banner = getElementById('cookies-eu-banner'),
        rejectButton = getElementById('cookies-eu-reject'),
        acceptButton = getElementById('cookies-eu-accept'),
        moreLink = getElementById('cookies-eu-more'),
        waitRemove = (banner.dataset.waitRemove === undefined) ? 0 : parseInt(banner.dataset.waitRemove),
        // Variables for minification optimization
        addClickListener = this.addClickListener,
        removeBanner = _this.removeBanner.bind(_this, waitRemove);

      banner.style.display = 'block';

      if (moreLink) {
        addClickListener(moreLink, function () {
          _this.deleteCookie(_this.cookieName);
        });
      }

      if (acceptButton) {
        addClickListener(acceptButton, function () {
          removeBanner();
          _this.setConsent(true);
          _this.launchFunction();
        });
      }

      if (rejectButton) {
        addClickListener(rejectButton, function () {
          removeBanner();
          _this.setConsent(false);

          // Delete existing tracking cookies
          _this.trackingCookiesNames.map(_this.deleteCookie);
        });
      }
    },

    /*
     * Set consent cookie or localStorage
     */
    setConsent: function (consent) {
      if (this.useLocalStorage) {
        return localStorage.setItem(this.cookieName, consent);
      }

      this.setCookie(this.cookieName, consent);
    },

    /*
     * Check if user already consent
     */
    hasConsent: function () {
      let cookieName = this.cookieName;
      let isCookieSetTo = function (value) {
        return document.cookie.indexOf(cookieName + '=' + value) > -1 || localStorage.getItem(cookieName) === value;
      };

      if (isCookieSetTo('true')) {
        return true;
      } else if (isCookieSetTo('false')) {
        return false;
      }

      return null;
    },

    /*
     * Create/update cookie
     */
    setCookie: function (name, value) {
      let date = new Date();
      date.setTime(date.getTime() + this.cookieTimeout);

      document.cookie = name + '=' + value + ';expires=' + date.toGMTString() + ';path=/' + ';SameSite=Lax';
    },

    /*
     * Delete cookie by changing expire
     */
    deleteCookie: function (name) {
      let hostname = document.location.hostname.replace(/^www\./, ''),
          commonSuffix = '; expires=Thu, 01-Jan-1970 00:00:01 GMT; path=/';

      document.cookie = name + '=; domain=.' + hostname + commonSuffix;
      document.cookie = name + '=' + commonSuffix;
    },

    addClickListener: function (DOMElement, callback) {
      if (DOMElement.attachEvent) { // For IE 8 and earlier versions
        return DOMElement.attachEvent('onclick', callback);
      }

      // For all major browsers, except IE 8 and earlier
      DOMElement.addEventListener('click', callback);
    },

    /*
     * Delays removal of banner allowing developers
     * to specify their own transition effects
     */
    removeBanner: function (wait) {
      let banner = document.getElementById('cookies-eu-banner');
      banner.classList.add('cookies-eu-banner--before-remove');
      setTimeout (function() {
        if (banner && banner.parentNode) {
          banner.parentNode.removeChild(banner);
        }
      }, wait);
    }
  };

new CookiesEuBanner(function () {
    // ADD GOOGLE ANALYTICS
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-1GE2PNDX6T');


}, true);

// SET COOKIE FOR LOGGED IN USERS
    $(document).ready(function(){
       let isUser = $('#cookies-eu-banner-user').data('user');
       if (isUser && !(document.cookie.indexOf('hasConsent=true') > -1)) {
            let date = new Date();
            date.setTime(date.getTime() + 31104000000); // 12 months in milliseconds
            document.cookie = 'hasConsent=true;expires=' + date.toGMTString() + ';path=/;SameSite=Lax;';
            $('#cookies-eu-banner').hide();
       }
    });

  return CookiesEuBanner;
}));

// END COOKIE CONSENT *****


// SET COOKIE PREFERENCES FROM POLICY PAGE
    $('#cookies-eu-reject-from-policy').on('click', function(event) {
        let date = new Date();
        date.setTime(date.getTime() + 31104000000);
        document.cookie = 'hasConsent=false;expires=' + date.toGMTString() + ';path=/;SameSite=Lax;';
        $('#cookies-eu-banner').hide();
    });

    $('#cookies-eu-accept-from-policy').on('click', function(event) {
        let date = new Date();
        date.setTime(date.getTime() + 31104000000);
        document.cookie = 'hasConsent=true;expires=' + date.toGMTString() + ';path=/;SameSite=Lax;';
        $('#cookies-eu-banner').hide();
    });