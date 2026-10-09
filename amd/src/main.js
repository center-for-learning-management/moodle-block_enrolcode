/* eslint-disable max-len, no-console, jsdoc/require-param */
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
  [ 'jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates',
    'core/url', 'core/modal_events', 'core/modal_factory' ],
  function ($, AJAX, NOTIFICATION, STR,
            TEMPLATES, URL, ModalEvents, ModalFactory) {
    return {
      debug: true,
      /**
       * delete a code
       * @param {string} code
       * @param {string} uniqid
       */
      deleteCode: function(code, uniqid) {
        STR.get_strings([
          {'key': 'confirmation', 'component': 'block_enrolcode'},
          {'key': 'really_delete', 'component': 'block_enrolcode', 'param': {'code': code}},
        ]).done(function(s) {
            ModalFactory.create({
              type: ModalFactory.types.SAVE_CANCEL,
              title: s[0],
              body: s[1],
              large: false,
            }).then(function(modal) {
              let root = modal.getRoot();
              root.on(ModalEvents.save, function () {
                modal.hide();
                AJAX.call([{
                  methodname: 'block_enrolcode_delete',
                  args: {code: code},
                  done: function (result) {
                    if (result === '1') {
                      // Remove code from list and close fullsize-pane.
                      if (typeof (uniqid) !== 'undefined') {
                        $('#block_enrolcode_old_codes-' + uniqid + ' [data-code=' + code + ']').remove();
                      }
                    } else {
                      // eslint-disable-next-line no-alert
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
       * @param {string} uniqid
       * @param {string} subid
       */
      fullsizeCode: function (uniqid, subid) {
        let enrolcode = {};
        let parentid = '#enrolcode-item-' + uniqid + '-' + subid;
        let fields = ['accesscode', 'group', 'maturity', 'enrolmentend', 'role'];
        fields.forEach(function (field) {
          enrolcode[field] = $(parentid + ' .' + field).html();
        });
        enrolcode.qr = $(parentid + ' .qr').attr('src');
        enrolcode.url = $(parentid + ' .accesscode').attr('href');

        STR.get_strings([
          {'key': 'code:accesscode', 'component': 'block_enrolcode'},
        ]).done(function(s) {
            ModalFactory.create({
              type: ModalFactory.types.OK,
              title: s[0] + ' <strong>' + enrolcode.accesscode + '</strong>',
              body: TEMPLATES.render('block_enrolcode/code_fullsize', enrolcode),
              large: true,
            }).then(function(modal) {
              modal.show();
            });
          }
        ).fail(NOTIFICATION.exception);
      },
      /**
       * Generate the URL for enrolment.
       * @param {string} code
       */
      generateEnrolURL: function (code) {
        return URL.relativeUrl('/blocks/enrolcode/enrol.php?code=' + code);
      },
      /**
       * Get a code for fast enrolment.
       * @param {string} src the clicked UI element
       */
      getCode: function (src) {
        this.injectCSS();
        let MAIN = this;

        let form = $(src).closest('form');

        let courseid = +$(form).find('[name="courseid"]').val();
        let roleid = +$(form).find('[name="roleid"]').val();
        let groupid = +$(form).find('[name="groupid"]').val();
        let custommaturity = $(form).find('[name="custommaturity"]').is(":checked") ? 1 : 0;
        let chkenrolmentend = $(form).find('[name="chkenrolmentend"]').is(":checked") ? 1 : 0;
        let maturity = new Date(
          $(form).find('#id_maturity_year').val(),
          $(form).find('#id_maturity_month').val() - 1, // JavaScript starts with January = 0
          $(form).find('#id_maturity_day').val(),
          $(form).find('#id_maturity_hour').val(),
          $(form).find('#id_maturity_minute').val(),
          0,
          0);
        let enrolmentend = new Date(
          $(form).find('#id_enrolmentend_year').val(),
          $(form).find('#id_enrolmentend_month').val() - 1, // JavaScript starts with January = 0
          $(form).find('#id_enrolmentend_day').val(),
          $(form).find('#id_enrolmentend_hour').val(),
          $(form).find('#id_enrolmentend_minute').val(),
          0,
          0);
        let data = {
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
              let code = result;
              if (result !== '' && result !== null) {
                let imageUrl = '/blocks/enrolcode/pix/qr.php?format=base64&txt=' + btoa(MAIN.generateEnrolURL(result));
                // We got the code return it!
                ModalFactory.create({
                  type: ModalFactory.types.ALERT,
                  title: s[0],
                  body: TEMPLATES.render('block_enrolcode/modal_code', {
                    code: code,
                    qrcode_image: URL.relativeUrl(imageUrl),
                    enrolurl: MAIN.generateEnrolURL(result),
                  }),
                  buttons: {
                    cancel: s[1],
                  }
                }).then(function(modal) {
                  modal.show();
                });
              } else {
                // There was an error - show error box
                Modal.create({
                  title: 'Error',
                  body: TEMPLATES.render('block_enrolcode/code_get_error', {}),
                }).then(function(modal) {
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
       * @param {int} courseid the courseid we need the modal for.
       */
      getCodeModal: function (courseid) {
        this.injectCSS();
        AJAX.call([{
          methodname: 'block_enrolcode_form',
          args: {'courseid': courseid},
          done: function(result) {
            STR.get_strings([
              {'key': 'code:get', component: 'block_enrolcode'},
            ]).done(function (s) {
                Modal.create({
                  title: s[0],
                  body: result,
                  large: true,
                }).then(function(modal) {
                  let root = modal.getRoot();
                  root.on(ModalEvents.OK, function () {
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
       * @param {int} courseid
       */
      injectButton: function(courseid) {
        if (typeof courseid === 'undefined' || courseid <= 1) {
          return;
        }
        STR.get_strings([
          {'key': 'code:get', component: 'block_enrolcode'},
        ]).done(function (s) {
            let req = 'require([\'block_enrolcode/main\'], function(MAIN) { MAIN.getCodeModal(' + courseid + '); }); return false;';
            $('#page-content #action_bar.tertiary-navigation>div.row>div.navitem:last-child>div:first-child')
              .prepend(
              $('<div class="singlebutton enrolusersbutton block_enrolcode">').append(
                $('<a href="#" onclick="' + req + '" class="btn btn-secondary my-1">' + s[0] + '</a>')
              )
            );
          }
        ).fail(NOTIFICATION.exception);
      },
      injectCSS: function() {
        if ($('head>link[href$="/blocks/enrolcode/style/enrolcode.css"]').length === 0) {
          let url = URL.relativeUrl('/blocks/enrolcode/style/enrolcode.css');
          $('head').append($('<link rel="stylesheet" type="text/css" href="' + url + '">'));
        }
      },
      /**
       * Let's inject a button to enter a code directly in users main menu.
       */
      injectMainmenuButton: function() {
        STR.get_strings([
          {'key': 'code:accesscode', component: 'block_enrolcode'},
        ]).done(function (s) {
          let onclick = 'require([\'block_enrolcode/main\'], function(MAIN) { MAIN.sendCodeModal(); }); return false;';
            $('.usermenu .dropdown a[href$="/user/preferences.php"]').after(
              $('<a>').attr('href', '#').attr('onclick', onclick)
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
      /**
       * Revoke a code
       * @param {string} code
       */
      revokeCode: function(code) {
        this.injectCSS();
        AJAX.call([{
          methodname: 'block_enrolcode_revoke',
          args: {'code': code},
          done: function() {
            // We don't really care about the answer.
          },
          fail: NOTIFICATION.exception
        }]);
      },
      /**
       * Send a code for fast enrolment, either provider uniqid OR code
       * @param {string} uniqid of form containing the input-element for the code.
       * @param {string} code the code directly
       */
      sendCode: function (uniqid, code) {
        this.injectCSS();
        if (typeof uniqid !== 'undefined' && uniqid != '') {
          code = $('#code-' + uniqid).val();
        }
        AJAX.call([{
          methodname: 'block_enrolcode_send',
          args: {'code': code},
          done: function(result) {
            if (result > 1) {
              // We are enrolled - automatically redirect to course!
              top.location.href = URL.relativeUrl('/course/view.php?id=' + result, {}, false);
            } else {
              // There was an error - show error box
              Modal.create({
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
        let MAIN = this;
        this.injectCSS();

        STR.get_strings([
          {'key': 'code:get', component: 'block_enrolcode'},
          {'key': 'finished', component: 'block_enrolcode'},
        ]).done(function (s) {
          ModalSaveCancel.create({
            title: s[0],
            body: TEMPLATES.render('block_enrolcode/modal_enter', {}),
            buttons: {
              save: s[1],
            }
          }).then(function(modal) {
            modal.show();

            let root = modal.getRoot();
            root.on(ModalEvents.save, function () {
              let code = $(root).find('#code').val();
              MAIN.sendCode('', code);
            });
          });
        });
      },
      /**
       * Share URL via a social network.
       * @param {DOMelement} src The button that was pressed.
       */
      shareCode: function (src) {
        let target = $(src).attr('data-target');
        let code = $(src).closest('.container').find('#code').html();
        let enrolurl = this.generateEnrolURL(code);
        switch (target) {
          case 'facebook':
            window.open('https://www.facebook.com/sharer.php?u=' + encodeURI(enrolurl));
            break;
        }
      }
    };
  });
