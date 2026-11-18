/* global _SEARCHWP */

( function($) {

    'use strict';

    const app = {

        /**
         * Init.
         *
         * @since 1.5.0
         */
        init: () => {

            $( app.ready );
        },

        /**
         * Document ready
         *
         * @since 1.5.0
         */
        ready: () => {

            app.events();
        },

        /**
         * Page events.
         *
         * @since 1.5.0
         */
        events: () => {

            app.UIEvents();

            $( '#swp-results-page-save' ).on( 'click', app.saveSettings );
        },

        /**
         * Save page settings.
         *
         * @since 1.5.0
         */
        saveSettings: () => {

            const settings = {
                'swp-layout-theme': $( 'input[name=swp-layout-theme]:checked' ).val(),
                'swp-layout-style': $( 'input[name=swp-layout-style]:checked' ).val(),
                'swp-max-results': $( 'input[name=swp-max-results]' ).val(),
                'swp-block-title': $( 'input[name=swp-block-title]' ).val(),
                'swp-image-size': $( 'select[name=swp-image-size]' ).val(),
                'swp-description-enabled': $( 'input[name=swp-description-enabled]' ).is( ':checked' ),
                'swp-engine': $( 'select[name=swp-engine]' ).val(),
                'swp-auto-append-post-types': $( 'select[name=swp-auto-append-post-types]' ).val(),
                'swp-auto-append-logic': $( 'input[name=swp-auto-append-logic]:checked' ).val(),
                'swp-append-after-paragraph-num': $( 'input[name=swp-append-after-paragraph-num]' ).val(),
                'swp-post-exclude-logic': $( 'select[name=swp-post-exclude-logic]' ).val(),
                'swp-post-exclude-ids-in': $( 'select[name=swp-post-exclude-ids-in]' ).val(),
                'swp-post-exclude-ids-not-in': $( 'select[name=swp-post-exclude-ids-not-in]' ).val(),
            };

            const data = {
                _ajax_nonce: _SEARCHWP.nonce,
                action: 'searchwp_related_save_settings',
                settings: JSON.stringify( settings ),
            };

            const $enabledInputs = $( '.swp-content-container button:not([disabled]), .swp-content-container input:not([disabled])' );
            const $saveButton    = $( '#swp-results-page-save' );

            $enabledInputs.attr( 'disabled','disabled' );
            $saveButton.addClass( 'swp-button--processing' );

            $.post( ajaxurl, data, ( response ) => {
                $enabledInputs.removeAttr( 'disabled' );
                $saveButton.removeClass( 'swp-button--processing' );

                if ( response.success ) {
                    $saveButton.addClass( 'swp-button--completed' );
                    setTimeout( () => { $saveButton.removeClass( 'swp-button--completed' ) }, 1500 );
                }
            } );
        },

        /**
         * Page UI events.
         *
         * @since 1.5.0
         */
        UIEvents: () => {

            $('[name="swp-layout-theme"]').on('change', (e) => {

                const theme = e.target.value;
                const $layout = $( 'input[name=swp-layout-style]' );
                const imageSizeChoicesJs = document.querySelector( 'select[name=swp-image-size]' ).data.choicesjs;
                const $descEnabledCheckbox = $( 'input[name=swp-description-enabled]' );
                const $preview = $('.swp-rp-theme-preview > div');

                console.log( );

                if ( theme === 'alpha' ) {
                    $layout.filter( '[value=grid]' ).prop( 'checked',true );
                    imageSizeChoicesJs.setChoiceByValue( '' );
                    $descEnabledCheckbox.prop( 'checked', false );
                    $preview
                        .removeClass('swp-flex')
                        .addClass('swp-grid');
                    $preview
                        .removeClass('swp-rp--img-sm swp-rp--img-m swp-rp--img-l')
                        .addClass('swp-result-item--img--off');
                    $preview
                        .addClass('swp-result-item--desc--off');
                }

                if ( theme === 'beta' ) {
                    $layout.filter( '[value=grid]' ).prop( 'checked',true );
                    imageSizeChoicesJs.setChoiceByValue( '' );
                    $descEnabledCheckbox.prop( 'checked', true );
                    $preview
                        .removeClass('swp-flex')
                        .addClass('swp-grid');
                    $preview
                        .removeClass('swp-rp--img-sm swp-rp--img-m swp-rp--img-l')
                        .addClass('swp-result-item--img--off');
                    $preview
                        .removeClass('swp-result-item--desc--off');
                }

                if ( theme === 'gamma' ) {
                    $layout.filter( '[value=list]' ).prop( 'checked',true );
                    imageSizeChoicesJs.setChoiceByValue( 'medium' );
                    $descEnabledCheckbox.prop( 'checked', true );
                    $preview
                        .removeClass('swp-grid')
                        .addClass('swp-flex');
                    $preview
                        .removeClass('swp-result-item--img--off swp-rp--img-sm swp-rp--img-l')
                        .addClass('swp-rp--img-m');
                    $preview
                        .removeClass('swp-result-item--desc--off');
                }

                if ( theme === 'epsilon' ) {
                    $layout.filter( '[value=grid]' ).prop( 'checked',true );
                    imageSizeChoicesJs.setChoiceByValue( 'large' );
                    $descEnabledCheckbox.prop( 'checked', false );
                    $preview
                        .removeClass('swp-flex')
                        .addClass('swp-grid');
                    $preview
                        .removeClass('swp-result-item--img--off swp-rp--img-sm swp-rp--img-m')
                        .addClass('swp-rp--img-l');
                    $preview
                        .addClass('swp-result-item--desc--off');
                }

                if ( theme === 'zeta' ) {
                    $layout.filter( '[value=grid]' ).prop( 'checked',true );
                    imageSizeChoicesJs.setChoiceByValue( 'large' );
                    $descEnabledCheckbox.prop( 'checked', true );
                    $preview
                        .removeClass('swp-flex')
                        .addClass('swp-grid');
                    $preview
                        .removeClass('swp-result-item--img--off swp-rp--img-sm swp-rp--img-m')
                        .addClass('swp-rp--img-l');
                    $preview
                        .removeClass('swp-result-item--desc--off');
                }

                if ( theme === 'custom' ) {
                    $layout.filter( '[value=list]' ).prop( 'checked',true );
                    imageSizeChoicesJs.setChoiceByValue( '' );
                    $descEnabledCheckbox.prop( 'checked', true );
                    $preview
                        .removeClass('swp-grid')
                        .addClass('swp-flex');
                    $preview
                        .removeClass('swp-rp--img-sm swp-rp--img-m swp-rp--img-l')
                        .addClass('swp-result-item--img--off');
                    $preview
                        .removeClass('swp-result-item--desc--off');
                }
            });

            $('[name=swp-layout-style]').on('change', (e) => {

                const layout = e.target.value;
                const $preview = $('.swp-rp-theme-preview > div');

                $( '[name="swp-layout-theme"][value="custom"]' ).prop( 'checked', true );

                if ( layout === 'list' ) {
                    $preview
                        .removeClass('swp-grid')
                        .addClass('swp-flex');
                }

                if ( layout === 'grid' ) {
                    $preview
                        .removeClass('swp-flex')
                        .addClass('swp-grid');
                }
            });

            $('[name=swp-image-size]').on('change', (e) => {

                const imageSize = e.target.value;
                const $preview = $('.swp-rp-theme-preview > div');

                $( '[name="swp-layout-theme"][value="custom"]' ).prop( 'checked', true );

                if ( ! imageSize ) {
                    $preview
                        .removeClass('swp-rp--img-sm swp-rp--img-m swp-rp--img-l')
                        .addClass('swp-result-item--img--off');
                }

                if ( imageSize === 'small' ) {
                    $preview
                        .removeClass('swp-result-item--img--off swp-rp--img-m swp-rp--img-l')
                        .addClass('swp-rp--img-sm');
                }

                if ( imageSize === 'medium' ) {
                    $preview
                        .removeClass('swp-result-item--img--off swp-rp--img-sm swp-rp--img-l')
                        .addClass('swp-rp--img-m');
                }

                if ( imageSize === 'large' ) {
                    $preview
                        .removeClass('swp-result-item--img--off swp-rp--img-sm swp-rp--img-m')
                        .addClass('swp-rp--img-l');
                }
            });

            $('[name="swp-description-enabled"]').on('change', (e) => {
                const descriptionEnabled = e.target.checked;
                const $preview = $('.swp-rp-theme-preview > div');

                $( '[name="swp-layout-theme"][value="custom"]' ).prop( 'checked', true );

                if ( descriptionEnabled ) {
                    $preview.removeClass('swp-result-item--desc--off');
                } else {
                    $preview.addClass('swp-result-item--desc--off');
                }
            });

            $('[name=swp-auto-append-logic]').on('change', (e) => {

                const logic = e.target.value;
                const $AfterParagraphNumBlock = $('#swp-append-after-paragraph-num-block');

                if ( logic === 'bottom' ) {
                    $AfterParagraphNumBlock.hide();
                }

                if ( logic === 'after-paragraph' ) {
                    $AfterParagraphNumBlock.show();
                }
            });

            $('[name=swp-post-exclude-logic]').on('change', (e) => {

                const logic = e.target.value;
                const $postExcludeLogicInBlock = $('#swp-post-exclude-ids-in-block');
                const $postExcludeLogicNotInBlock = $('#swp-post-exclude-ids-not-in-block');

                if ( logic === 'in' ) {
                    $postExcludeLogicNotInBlock.hide();
                    $postExcludeLogicInBlock.show();
                }

                if ( logic === 'not_in' ) {
                    $postExcludeLogicInBlock.hide();
                    $postExcludeLogicNotInBlock.show();
                }
            });

            $( '[name="swp-sfe-embed"]' ).on( 'change', (e) => {
                $('.swp-sfe-embed--desc').hide();
                $( `#${e.target.value}` ).show();
            } );
        },
    };

    app.init();

    window.searchwp = window.searchwp || {};

    window.searchwp.AdminRelatedPage = app;

}( jQuery ) );
