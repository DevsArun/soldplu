/**
 * Competitor Spy Widget - Frontend JavaScript
 *
 * Handles lazy loading, analytics tracking, and widget interactions.
 *
 * @package CompetitorSpyWidget
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    var CSWWidget = {
        sessionId: '',
        tracked: {},

        /**
         * Initialize the widget.
         */
        init: function () {
            this.sessionId = this.generateSessionId();
            this.initLazyLoad();
            this.initAnalytics();
            this.initConversionTracking();
        },

        /**
         * Generate a unique session ID for analytics.
         */
        generateSessionId: function () {
            var stored = this.getCookie('csw_session');
            if (stored) {
                return stored;
            }
            var id = 'csw_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            this.setCookie('csw_session', id, 30);
            return id;
        },

        /**
         * Initialize lazy loading for widgets.
         */
        initLazyLoad: function () {
            var self = this;
            var $containers = $('.csw-widget-container[data-lazy="true"]');

            if ($containers.length === 0) {
                return;
            }

            if (cswWidget.lazyLoad === '1') {
                // Use Intersection Observer for lazy loading
                if ('IntersectionObserver' in window) {
                    var observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                self.loadWidget($(entry.target));
                                observer.unobserve(entry.target);
                            }
                        });
                    }, {
                        rootMargin: '200px'
                    });

                    $containers.each(function () {
                        observer.observe(this);
                    });
                } else {
                    // Fallback: load immediately
                    $containers.each(function () {
                        self.loadWidget($(this));
                    });
                }
            }
        },

        /**
         * Load widget content via AJAX.
         */
        loadWidget: function ($container) {
            var self = this;
            var productId = $container.data('product-id');

            if (!productId) {
                return;
            }

            // Show skeleton
            $container.show();

            $.ajax({
                url: cswWidget.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'csw_get_widget',
                    nonce: cswWidget.nonce,
                    product_id: productId
                },
                success: function (response) {
                    if (response.success && response.data.show) {
                        $container.html(response.data.html);
                        $container.show();

                        // Add animation class
                        if (cswWidget.animated === '1') {
                            $container.find('.csw-widget-container').addClass('csw-animated');
                        }

                        // Track impression
                        self.trackEvent(productId, 'widget_impression');

                        // Set impression cookie for conversion tracking
                        self.setCookie('csw_impression_' + productId, self.sessionId, 1);
                    } else {
                        // Hide container if no data or widget shouldn't show
                        $container.hide();
                        self.trackEvent(productId, 'widget_hidden');
                    }
                },
                error: function () {
                    $container.hide();
                }
            });

            // Track view regardless
            self.trackEvent(productId, 'widget_view');
        },

        /**
         * Initialize analytics tracking.
         */
        initAnalytics: function () {
            if (cswWidget.analytics !== '1') {
                return;
            }

            var self = this;
            var productId = cswWidget.productId;

            // Track page view with widget data
            if (productId) {
                // Check if widget is already visible (non-lazy)
                var $staticWidget = $('.csw-widget-container:not([data-lazy])');
                if ($staticWidget.length > 0 && $staticWidget.is(':visible')) {
                    self.trackEvent(productId, 'widget_view');
                    self.trackEvent(productId, 'widget_impression');
                    self.setCookie('csw_impression_' + productId, self.sessionId, 1);
                }
            }
        },

        /**
         * Initialize conversion tracking on add-to-cart.
         */
        initConversionTracking: function () {
            var self = this;

            // WooCommerce add to cart button
            $(document.body).on('click', '.single_add_to_cart_button', function () {
                var productId = cswWidget.productId;
                var impressionCookie = self.getCookie('csw_impression_' + productId);

                if (impressionCookie && productId) {
                    self.trackEvent(productId, 'conversion');
                }
            });

            // Also listen for AJAX add to cart
            $(document.body).on('added_to_cart', function (e, fragments, cartHash, $button) {
                var productId = $button ? $button.data('product_id') : cswWidget.productId;
                var impressionCookie = self.getCookie('csw_impression_' + productId);

                if (impressionCookie && productId) {
                    self.trackEvent(productId, 'conversion');
                }
            });
        },

        /**
         * Track an analytics event.
         */
        trackEvent: function (productId, eventType, competitorId) {
            if (cswWidget.analytics !== '1') {
                return;
            }

            // Prevent duplicate tracking in same session
            var trackKey = productId + '_' + eventType;
            if (this.tracked[trackKey]) {
                return;
            }
            this.tracked[trackKey] = true;

            $.ajax({
                url: cswWidget.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'csw_track_event',
                    nonce: cswWidget.nonce,
                    product_id: productId,
                    event_type: eventType,
                    competitor_id: competitorId || 0,
                    session_id: this.sessionId,
                    referrer: document.referrer
                }
            });
        },

        /**
         * Set a cookie.
         */
        setCookie: function (name, value, minutes) {
            var expires = '';
            if (minutes) {
                var date = new Date();
                date.setTime(date.getTime() + (minutes * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
        },

        /**
         * Get a cookie value.
         */
        getCookie: function (name) {
            var nameEQ = name + '=';
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var c = cookies[i].trim();
                if (c.indexOf(nameEQ) === 0) {
                    return decodeURIComponent(c.substring(nameEQ.length));
                }
            }
            return null;
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function () {
        CSWWidget.init();
    });

})(jQuery);
