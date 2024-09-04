/*
 * @package    block_enrolcode
 * @copyright  2020 Center for learning management (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module block_enrolcode/main
 */
define(
  ['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates', 'core/url', 'core/modal_events', 'core/modal_factory', 'core/templates'],
  function ($, AJAX, NOTIFICATION, STR, TEMPLATES, URL, ModalEvents, ModalFactory, Templates) {
    return {
      debug: true,
      /**
       *
       */
      deleteCode: function (code, uniqid) {
        STR.get_strings([
          {'key': 'confirmation', 'component': 'block_enrolcode'},
          {'key': 'really_delete', 'component': 'block_enrolcode', 'param': {'code': code}},
        ]).done(function (s) {
            ModalFactory.create({
              type: ModalFactory.types.SAVE_CANCEL,
              title: s[0],
              body: s[1],
              large: false,
            }).then(function (modal) {
              var root = modal.getRoot();
              root.on(ModalEvents.save, function () {
                modal.hide();
                AJAX.call([{
                  methodname: 'block_enrolcode_delete',
                  args: {code: code},
                  done: function (result) {
                    if (result == '1') {
                      // Remove code from list and close fullsize-pane.
                      if (typeof (uniqid) !== 'undefined') {
                        $('#block_enrolcode_old_codes-' + uniqid + ' [data-code=' + code + ']').remove();
                      }
                    } else {
                      alert(result);
                    }
                  },
                  fail: NOTIFICATION.exception
                }]);
              });
              modal.show();
            });
          }
        ).fail(NOTIFICATION.exception);
      },
      /**
       * Show a code in full-size
       */
      fullsizeCode: function (uniqid, subid) {
        if (this.debug) console.log('block_enrolcode/main::fullsizeCode(uniqid, subid)', uniqid, subid);

        var enrolcode = {};
        var parentid = '#enrolcode-item-' + uniqid + '-' + subid;
        var parent = $(parentid);
        var fields = ['accesscode', 'group', 'maturity', 'enrolmentend', 'role'];
        fields.forEach(function (field) {
          enrolcode[field] = $(parentid + ' .' + field).html();
        });
        enrolcode['qr'] = $(parentid + ' .qr').attr('src');
        enrolcode['url'] = $(parentid + ' .accesscode').attr('href');

        STR.get_strings([
          {'key': 'code:accesscode', 'component': 'block_enrolcode'},
        ]).done(function (s) {
            ModalFactory.create({
              type: ModalFactory.types.OK,
              title: s[0] + ' <strong>' + enrolcode.accesscode + '</strong>',
              body: TEMPLATES.render('block_enrolcode/code_fullsize', enrolcode),
              large: true,
            }).then(function (modal) {
              modal.show();
            });
          }
        ).fail(NOTIFICATION.exception);
      },
      /**
       * Generate the URL for enrolment.
       */
      generateEnrolURL: function (code) {
        return URL.relativeUrl('/blocks/enrolcode/enrol.php?code=' + code);
      },
      /**
       * Get a code for fast enrolment.
       * @param uniqid of form
       */
      getCode: function (src) {
        this.injectCSS();
        var MAIN = this;
        console.log('MAIN.getCode(src)', src);

        var form = $(src).closest('form');

        var courseid = +$(form).find('[name="courseid"]').val();
        var roleid = +$(form).find('[name="roleid"]').val();
        var groupid = +$(form).find('[name="groupid"]').val();
        var custommaturity = $(form).find('[name="custommaturity"]').is(":checked") ? 1 : 0;
        var chkenrolmentend = $(form).find('[name="chkenrolmentend"]').is(":checked") ? 1 : 0;
        var maturity = new Date(
          $(form).find('#id_maturity_year').val(),
          $(form).find('#id_maturity_month').val() - 1, // JavaScript starts with January = 0
          $(form).find('#id_maturity_day').val(),
          $(form).find('#id_maturity_hour').val(),
          $(form).find('#id_maturity_minute').val(),
          0,
          0);
        var enrolmentend = new Date(
          $(form).find('#id_enrolmentend_year').val(),
          $(form).find('#id_enrolmentend_month').val() - 1, // JavaScript starts with January = 0
          $(form).find('#id_enrolmentend_day').val(),
          $(form).find('#id_enrolmentend_hour').val(),
          $(form).find('#id_enrolmentend_minute').val(),
          0,
          0);
        var data = {
          courseid: courseid,
          roleid: roleid,
          groupid: groupid,
          custommaturity: custommaturity,
          maturity: Math.ceil(maturity.getTime() / 1000),
          chkenrolmentend: chkenrolmentend,
          enrolmentend: Math.ceil(enrolmentend.getTime() / 1000)
        };

        STR.get_strings([
          {'key': 'code:get', component: 'block_enrolcode'},
          {'key': 'finished', component: 'block_enrolcode'},
        ]).done(function (s) {
          AJAX.call([{
            methodname: 'block_enrolcode_get',
            args: data,
            done: function (result) {
              var code = result;
              if (result != '' && result != null) {
                // We got the code return it!
                console.log('Got code', result);
                ModalFactory.create({
                  type: ModalFactory.types.ALERT,
                  title: s[0],
                  body: Templates.render('block_enrolcode/modal_code', {
                    code: code,
                    qrcode_image: URL.relativeUrl('/blocks/enrolcode/pix/qr.php?format=base64&txt=' + btoa(MAIN.generateEnrolURL(result))),
                    enrolurl: MAIN.generateEnrolURL(result),
                  }),
                  buttons: {
                    cancel: s[1],
                  }
                }).then(function (modal) {
                  modal.show();
                });
              } else {
                // There was an error - show error box
                ModalFactory.create({
                  type: ModalFactory.types.OK,
                  title: 'Error',
                  body: TEMPLATES.render('block_enrolcode/code_get_error', {}),
                }).then(function (modal) {
                  modal.show();
                });
              }
            },
            fail: NOTIFICATION.exception
          }]);
        });
      },
      /**
       * Show the form to get a code in a modal.
       * @param courseid the courseid we need the modal for.
       */
      getCodeModal: function (courseid) {
        this.injectCSS();
        AJAX.call([{
          methodname: 'block_enrolcode_form',
          args: {'courseid': courseid},
          done: function (result) {
            STR.get_strings([
              {'key': 'code:get', component: 'block_enrolcode'},
            ]).done(function (s) {
                ModalFactory.create({
                  type: ModalFactory.types.OK,
                  title: s[0],
                  body: result,
                  large: true,
                }).then(function (modal) {
                  var root = modal.getRoot();
                  root.on(ModalEvents.OK, function () {
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
      injectButton: function (courseid) {
        if (typeof courseid === 'undefined' || courseid <= 1) return;
        STR.get_strings([
          {'key': 'code:get', component: 'block_enrolcode'},
        ]).done(function (s) {
            $('#page-content div.enrolusersbutton').parent().prepend(
              $('<div class="singlebutton enrolusersbutton block_enrolcode">').append(
                $('<a href="#" onclick="require([\'block_enrolcode/main\'], function(MAIN) { MAIN.getCodeModal(' + courseid + '); }); return false;" class="btn btn-secondary my-1">' + s[0] + '</a>')
              )
            );
          }
        ).fail(NOTIFICATION.exception);
      },
      injectCSS: function () {
        if ($('head>link[href$="/blocks/enrolcode/style/enrolcode.css"]').length == 0) {
          console.log('Adding CSS File ', URL.relativeUrl('/blocks/enrolcode/style/enrolcode.css'));
          $('head').append($('<link rel="stylesheet" type="text/css" href="' + URL.relativeUrl('/blocks/enrolcode/style/enrolcode.css') + '">'));
        }
      },
      /**
       * Let's inject a button to enter a code directly in users main menu.
       */
      injectMainmenuButton: function () {
        STR.get_strings([
          {'key': 'code:accesscode', component: 'block_enrolcode'},
        ]).done(function (s) {
            $('.usermenu .dropdown a[href$="/user/preferences.php"]').after(
              $('<a>').attr('href', '#').attr('onclick', 'require([\'block_enrolcode/main\'], function(MAIN) { MAIN.sendCodeModal(); }); return false;')
                .addClass('dropdown-item menu-action').attr('role', 'menuitem')
                .attr('data-title', 'moodle,accesscard').attr('aria-labelledby', 'actionmenuaction-accesscard')
                .attr('data-ajax', 'false').append([
                $('<i>').addClass('icon fa fa-key fa-fw').attr('aria-hidden', 'true'),
                $('<span>').addClass('menu-action-text').attr('id', 'actionmenuaction-accesscard').html(s[0]),
              ])
            );
          }
        ).fail(NOTIFICATION.exception);
      },
      revokeCode: function (code) {
        this.injectCSS();
        var MAIN = this;
        console.log('MAIN.revokeCode(code)', code);

        AJAX.call([{
          methodname: 'block_enrolcode_revoke',
          args: {'code': code},
          done: function (result) {
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
      sendCode: function (uniqid, code) {
        this.injectCSS();
        var MAIN = this;
        console.log('MAIN.sendCode(uniqid, code)', uniqid, code);
        if (typeof uniqid !== 'undefined' && uniqid != '') {
          code = $('#code-' + uniqid).val();
        }
        AJAX.call([{
          methodname: 'block_enrolcode_send',
          args: {'code': code},
          done: function (result) {
            console.log('Got courseid ', result);
            if (result > 1) {
              // We are enrolled - automatically redirect to course!
              top.location.href = URL.relativeUrl('/course/view.php?id=' + result, {});
            } else {
              // There was an error - show error box
              ModalFactory.create({
                type: ModalFactory.types.OK,
                title: 'Error',
                body: 'Invalid code',
              }).then(function (modal) {
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
      sendCodeModal: function () {
        var MAIN = this;
        this.injectCSS();

        STR.get_strings([
          {'key': 'code:get', component: 'block_enrolcode'},
          {'key': 'finished', component: 'block_enrolcode'},
        ]).done(function (s) {
          ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: s[0],
            body: Templates.render('block_enrolcode/modal_enter', {}),
            buttons: {
              save: s[1],
            }
          }).then(function (modal) {
            modal.show();

            var root = modal.getRoot();
            root.on(ModalEvents.save, function () {
              var code = $(root).find('#code').val();
              MAIN.sendCode('', code);
            });
          });
        });
      },
      /**
       * Share URL via a social network.
       * @param src The button that was pressed.
       */
      shareCode: function (src) {
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
