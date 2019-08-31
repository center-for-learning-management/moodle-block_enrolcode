define(
    ['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates', 'core/url', 'core/modal_factory', 'block_enrolcode/modal_code'],
    function($, AJAX, NOTIFICATION, STR, TEMPLATES, URL, ModalFactory, ModalCode) {
    return {
        /**
         * Get a code for fast enrolment
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
         * Send a code for fast enrolment
         * @param uniqid of form containing the input-element for the code.
         */
        sendCode: function(uniqid) {
            var MAIN = this;
            console.log('MAIN.sendCode(uniqid)', uniqid);

            var code = $('#code-' + uniqid).val();
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
    };
});
