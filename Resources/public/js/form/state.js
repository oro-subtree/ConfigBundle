/*jshint browser:true*/
/*global define, confirm*/
define(['underscore', 'backbone', 'oroui/js/mediator', 'orotranslation/js/translator'],
    function (_, Backbone, mediator, __) {
        'use strict';

        var $ = Backbone.$,
            formState = function () {
                this.initialize.apply(this, arguments);
            };

        _.extend(formState.prototype, {
            UNLOAD_EVENT: 'beforeunload.configFormState',
            LOAD_EVENT: 'ready.configFormState',
            CONFIRMATION_MESSAGE: __('You have unsaved changes, are you sure you want to leave this page?'),

            data: null,
            form: null,

            initialize: function () {
                mediator.once('page:request', this._onDestroyHandler, this);

                this.form = $('.system-configuration-container').parents('form');
                $(window).on(this.LOAD_EVENT, _.bind(this._collectHandler, this));
                this._collectHandler();

                $(window).on(this.UNLOAD_EVENT, _.bind(function () {
                    if (this.isChanged()) {
                        return this.CONFIRMATION_MESSAGE;
                    }
                }, this));
                mediator.on('hash_navigation_click', this._confirmHashChange, this);
            },

            /**
             * Check is form changed
             *
             * @returns {boolean}
             */
            isChanged: function () {
                if (!_.isNull(this.data) && !(this.form.data('nohash') && this.form.data('sent'))) {
                    return this.data !== this.getState();
                }

                return false;
            },

            /**
             * Collect form state
             *
             * @returns {*}
             */
            getState: function () {
                if (this.form.length) {
                    return JSON.stringify(
                        _.reject(
                            this.form.serializeArray(),
                            function (el) {
                                return el.name === 'input_action';
                            }
                        )
                    );
                }

                return false;
            },

            /**
             * Hash change event handler
             *
             * @param event
             * @private
             */
            _confirmHashChange: function (event) {
                if (this.isChanged()) {
                    event.stoppedProcess = !confirm(this.CONFIRMATION_MESSAGE);
                }
            },

            /**
             * Collecting event handler
             *
             * @private
             */
            _collectHandler: function () {
                this.data = this.getState();
            },

            /**
             * Destroys event handlers
             *
             * @private
             */
            _onDestroyHandler: function () {
                if (_.isNull(this.data)) {
                    // data was not collected disable listener
                    mediator.off('page:afterChange', this._collectHandler, this);
                } else {
                    this.data = null;
                }
                mediator.off('hash_navigation_click', this._confirmHashChange, this);
                $(window).off(this.UNLOAD_EVENT);
                $(document).off(this.LOAD_EVENT);
            }
        });

        return formState;
    });
