define(
    ['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates', 'core/url', 'core/modal_events', 'core/modal_factory', 'block_enrolcode/modal_code', 'block_enrolcode/modal_enter'],
    function($, AJAX, NOTIFICATION, STR, TEMPLATES, URL, ModalEvents, ModalFactory, ModalCode, ModalEnter) {
    return {
        /**
         * Generate the URL for enrolment.
         */
        generateEnrolURL: function(code) {
            return URL.relativeUrl('/blocks/enrolcode/enrol.php?code=' + code);
        },
        /**
         * Get a code for fast enrolment.
         * @param uniqid of form
         */
        getCode: function(src) {
            this.injectCSS();
            var MAIN = this;
            console.log('MAIN.getCode(src)', src);

            var form = $(src).closest('form');

            var courseid = +$(form).find('[name="courseid"]').val();
            var roleid = +$(form).find('[name="roleid"]').val();
            var custommaturity = $(form).find('[name="custommaturity"]').is(":checked") ? 1 : 0;
            var maturity = new Date();
            maturity.setDate($(form).find('#id_maturity_day').val());
            maturity.setMonth($(form).find('#id_maturity_month').val());
            maturity.setYear($(form).find('#id_maturity_year').val());
            maturity.setHours($(form).find('#id_maturity_hour').val());
            maturity.setMinutes($(form).find('#id_maturity_minute').val());
            console.log({ 'courseid': courseid, 'roleid': roleid, custommaturity: custommaturity, maturity: Math.ceil(maturity.getTime()/1000) });
            AJAX.call([{
                methodname: 'block_enrolcode_get',
                args: { 'courseid': courseid, 'roleid': roleid, custommaturity: custommaturity, maturity: Math.ceil(maturity.getTime()/1000) },
                done: function(result) {
                    if (result != '' && result != null) {
                        // We got the code return it!
                        console.log('Got code', result);
                        ModalFactory.create({
                            type: ModalCode.TYPE
                        }).then(function(modal) {
                            var root = modal.getRoot();
                            $(root).find('#code').html(result);
                            $(root).find('#enrolurl').val(MAIN.generateEnrolURL(result));
                            $(root).find('#qrcode').attr('src', URL.relativeUrl('/blocks/enrolcode/pix/qr.php?format=base64&txt=' + btoa(MAIN.generateEnrolURL(result))));
                            modal.show();
                        });
                    } else {
                        // There was an error - show error box
                        ModalFactory.create({
                            type: ModalFactory.types.OK,
                            title: 'Error',
                            body: TEMPLATES.render('block_enrolcode/code_get_error', {}),
                        }).then(function(modal) {
                            modal.show();
                        });
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },
        /**
         * Show the form to get a code in a modal.
         * @param courseid the courseid we need the modal for.
         */
        getCodeModal: function(courseid) {
            this.injectCSS();
            AJAX.call([{
                methodname: 'block_enrolcode_form',
                args: { 'courseid': courseid },
                done: function(result) {
                    STR.get_strings([
                            {'key' : 'code:get', component: 'block_enrolcode' },
                        ]).done(function(s) {
                            ModalFactory.create({
                                type: ModalFactory.types.OK,
                                title: s[0],
                                body: result,
                            }).then(function(modal) {
                                var root = modal.getRoot();
                                root.on(ModalEvents.OK, function() {
                                    console.log('Hiding modal');
                                    modal.hide();
                                });
                                modal.show();
                            });
                        }
                    ).fail(NOTIFICATION.exception);
                },
                fail: NOTIFICATION.exception
            }]);
        },
        /**
         * Let's inject a button on the enrol users page.
         */
        injectButton: function(courseid) {
            if (typeof courseid === 'undefined' || courseid <= 1) return;
            STR.get_strings([
                    {'key' : 'code:get', component: 'block_enrolcode' },
                ]).done(function(s) {
                    $('#page-content div.enrolusersbutton').parent().prepend(
                        $('<a href="#" onclick="require([\'block_enrolcode/main\'], function(MAIN) { MAIN.getCodeModal(' + courseid + '); }); return false;" class="btn btn-secondary">' + s[0] + '</a>')
                    );
                }
            ).fail(NOTIFICATION.exception);
        },
        injectCSS: function() {
            if ($('head>link[href$="/blocks/enrolcode/style/enrolcode.css"]').length == 0) {
                console.log('Adding CSS File ', URL.relativeUrl('/blocks/enrolcode/style/enrolcode.css'));
                $('head').append($('<link rel="stylesheet" type="text/css" href="' + URL.relativeUrl('/blocks/enrolcode/style/enrolcode.css') + '">'));
            }
        },
        revokeCode: function(code) {
            this.injectCSS();
            var MAIN = this;
            console.log('MAIN.revokeCode(code)', code);

            AJAX.call([{
                methodname: 'block_enrolcode_revoke',
                args: { 'code': code },
                done: function(result) {
                    // We don't really care about the answer.
                },
                fail: NOTIFICATION.exception
            }]);
        },
        /**
         * Send a code for fast enrolment, either provider uniqid OR code
         * @param uniqid of form containing the input-element for the code.
         * @param code the code directly
         */
        sendCode: function(uniqid, code) {
            this.injectCSS();
            var MAIN = this;
            console.log('MAIN.sendCode(uniqid, code)', uniqid, code);
            if (typeof uniqid !== 'undefined' && uniqid != '') {
                code = $('#code-' + uniqid).val();
            }
            AJAX.call([{
                methodname: 'block_enrolcode_send',
                args: { 'code': code },
                done: function(result) {
                    console.log('Got courseid ', result);
                    if (result > 1) {
                        // We are enrolled - automatically redirect to course!
                        top.location.href = URL.relativeUrl('/course/view.php?id=' + result, {  });
                    } else {
                        // There was an error - show error box
                        ModalFactory.create({
                            type: ModalFactory.types.OK,
                            title: 'Error',
                            body: 'Invalid code',
                        }).then(function(modal) {
                            modal.show();
                        });
                    }
                },
                fail: NOTIFICATION.exception
            }]);
        },
        /**
         * Show the form to enter a code in a modal.
         */
        sendCodeModal: function() {
            this.injectCSS();
            ModalFactory.create({
                type: ModalEnter.TYPE
            }).then(function(modal) {
                modal.show();
            });
        },
        /**
         * Share URL via a social network.
         * @param src The button that was pressed.
         */
        shareCode: function(src) {
            console.log('MAIN.shareCode(src)', src);
            var target = $(src).attr('data-target');
            var code = $(src).closest('.container').find('#code').html();
            var enrolurl = MAIN.generateEnrolURL(code);
            switch (target) {
                case 'facebook':
                    window.open('https://www.facebook.com/sharer.php?u=' + encodeURI(enrolurl));
                break;
            }
        }
    };
});
