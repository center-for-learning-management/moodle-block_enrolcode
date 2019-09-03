define(
    ['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates', 'core/url', 'core/modal_events', 'core/modal_factory', 'block_enrolcode/modal_code', 'block_enrolcode/modal_enter'],
    function($, AJAX, NOTIFICATION, STR, TEMPLATES, URL, ModalEvents, ModalFactory, ModalCode, ModalEnter) {
    return {
        /**
         * Get a code for fast enrolment.
         * @param uniqid of form
         */
        getCode: function(uniqid) {
            var MAIN = this;
            console.log('MAIN.getCode(uniqid)', uniqid);

            var courseid = $('#courseid-' + uniqid).val();
            var roleid = $('#roleid-' + uniqid).val();
            //console.log({ 'courseid': courseid, 'roleid': roleid });
            AJAX.call([{
                methodname: 'block_enrolcode_get',
                args: { 'courseid': courseid, 'roleid': roleid },
                done: function(result) {
                    if (result != '' && result != null) {
                        // We got the code return it!
                        console.log('Got code', result);
                        ModalFactory.create({
                            type: ModalCode.TYPE
                        }).then(function(modal) {
                            var root = modal.getRoot();
                            $(root).find('#code').html(result);
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
        revokeCode: function(code) {
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
            ModalFactory.create({
                type: ModalEnter.TYPE
            }).then(function(modal) {
                modal.show();
            });
        },
    };
});
