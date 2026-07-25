/**
 * Copyright (C) 2023 Pixel Développement
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*global define*/
define(
    [
        'ko',
        'jquery',
        'uiComponent',
        'cfTurnstile',
        'mage/translate'
    ],
    function (
        ko,
        $,
        Component
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'PixelOpen_CloudflareTurnstile/turnstile',
            },
            configSource: 'turnstileConfig',
            config: {
                'enabled': false,
                'sitekey': '',
                'forms': [],
                'size': 'normal',
                'theme': 'auto',
                'language': 'auto'
            },
            action: 'default',
            size: '', // Override config value if not empty
            theme: '', // Override config value if not empty,
            widgetId: null,
            autoRendering: true,
            element: null,

            /**
             * Initialize
             */
            initialize: function () {
                this._super();

                if (typeof window[this.configSource] !== 'undefined' && window[this.configSource].config) {
                    this.config = window[this.configSource].config;
                }
            },

            /**
             * Can show widget
             *
             * @returns {boolean}
             */
            canShow: function () {
                return this.config.enabled && this.config.forms.indexOf(this.action) >= 0;
            },

            /**
             * Load widget
             *
             * @param {object} element
             */
            load: function (element) {
                this.element = element;

                if (!this.config.sitekey) {
                    this.element.innerText = $.mage.__('Unable to secure the form. The site key is missing.');
                } else {
                    this.beforeRender();
                    if (this.autoRendering) {
                        this.render();
                    }
                }
            },

            /**
             * Render widget
             */
            render: function () {
                if (this.element) {
                    const widgetId = turnstile.render(this.element, {
                        sitekey: this.config.sitekey,
                        theme: this.theme || this.config.theme,
                        size: this.size || this.config.size,
                        action: this.action,
                        language: this.config.language || 'auto',
                        callback: this.onToken.bind(this),
                        'expired-callback': this.onTokenGone.bind(this),
                        'timeout-callback': this.onTokenGone.bind(this),
                        'error-callback': this.onWidgetError.bind(this)
                    });
                    if (typeof widgetId === 'undefined') {
                        this.element.innerText = $.mage.__('Unable to secure the form');
                    } else {
                        this.widgetId = widgetId;
                        this.toggleSubmit(false);
                    }
                    this.afterRender();
                }
            },

            /**
             * Submit buttons of the form hosting the widget
             *
             * @returns {jQuery}
             */
            getSubmitButtons: function () {
                return $(this.element).closest('form').find(':submit');
            },

            /**
             * Gate the host form's submit on token availability. Enabling is
             * unconditional on purpose: a fresh token means the form is
             * submittable again, whichever code path left the button disabled
             * (e.g. an AJAX error handler that never re-enabled it).
             *
             * @param {boolean} enabled
             */
            toggleSubmit: function (enabled) {
                this.getSubmitButtons()
                    .prop('disabled', !enabled)
                    .toggleClass('cf-turnstile-waiting', !enabled);
            },

            /**
             * A token was issued: the form may be submitted
             */
            onToken: function () {
                this.toggleSubmit(true);
            },

            /**
             * The token expired or timed out: Turnstile re-runs the challenge
             * (refresh-expired defaults to "auto"), so lock the submit until
             * the next token arrives
             */
            onTokenGone: function () {
                this.toggleSubmit(false);
            },

            /**
             * Widget failure (network, unsupported browser…): fail open so the
             * visitor is not hard-locked out of the form — the server-side
             * observer still rejects a missing/invalid token
             */
            onWidgetError: function () {
                this.toggleSubmit(true);
            },

            /**
             * Before render widget
             */
            beforeRender: function () {
                // Do something before rendering the widget
            },

            /**
             * After render widget
             */
            afterRender: function () {
                // Do something after rendering the widget
            },

            /**
             * Reset widget
             */
            reset: function () {
                if (this.widgetId) {
                    turnstile.reset(this.widgetId);
                    this.toggleSubmit(false);
                }
            }
        });
    }
);
