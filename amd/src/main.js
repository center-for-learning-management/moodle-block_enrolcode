/*
 * @package    block_enrolcode
 * @copyright  2020 Center for learning management (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module block_enrolcode/main
 */
import $ from 'jquery';
import {call as fetchMany} from 'core/ajax';
import Notification from 'core/notification';
import {getString, getStrings} from 'core/str';
import Templates from 'core/templates';
import Url from 'core/url';
import ModalEvents from 'core/modal_events';
import Modal from 'core/modal';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalAlert from 'core/local/modal/alert';

/**
 * delete a code
 * @param {string} code
 * @param {string} uniqid
 */
export async function deleteCode(code, uniqid) {
  const [title, body] = await getStrings([
    {key: 'confirmation', component: 'block_enrolcode'},
    {key: 'really_delete', component: 'block_enrolcode', param: {code: code}},
  ]);
  const modal = await ModalSaveCancel.create({
    title: title,
    body: body,
    large: false,
    show: true,
  });
  modal.getRoot().on(ModalEvents.save, async function () {
    modal.hide();
    try {
      const result = await fetchMany([{
        methodname: 'block_enrolcode_delete',
        args: {code: code},
      }])[0];
      if (result === '1') {
        // Remove code from list and close fullsize-pane.
        if (uniqid) {
          $('#block_enrolcode_old_codes-' + uniqid + ' [data-code=' + code + ']').remove();
        }
      } else {
        Notification.alert(await getString('error', 'moodle'), result);
      }
    } catch (error) {
      Notification.exception(error);
    }
  });
}

/**
 * Show a code in full-size
 * @param {string} uniqid
 * @param {string} subid
 */
export async function fullsizeCode(uniqid, subid) {
  let enrolcode = {};
  let parentid = '#enrolcode-item-' + uniqid + '-' + subid;
  let fields = ['accesscode', 'group', 'maturity', 'enrolmentend', 'role'];
  fields.forEach(function (field) {
    enrolcode[field] = $(parentid + ' .' + field).html();
  });
  enrolcode.qr = $(parentid + ' .qr').attr('src');
  enrolcode.url = $(parentid + ' .accesscode').attr('href');

  await Modal.create({
    title: await getString('code:accesscode', 'block_enrolcode') + ' <strong>' + enrolcode.accesscode + '</strong>',
    body: Templates.render('block_enrolcode/code_fullsize', enrolcode),
    large: true,
    show: true,
  });
}

/**
 * Generate the URL for enrolment.
 * @param {string} code
 */
function generateEnrolURL(code) {
  return Url.relativeUrl('/blocks/enrolcode/enrol.php?code=' + code);
}

/**
 * Get a code for fast enrolment.
 * @param {string} src the clicked UI element
 */
export async function getCode(src) {
  let form = $(src).closest('form');

  let courseid = +$(form).find('[name="courseid"]').val();
  let roleid = +$(form).find('[name="roleid"]').val();
  let groupid = +$(form).find('[name="groupid"]').val();
  let custommaturity = $(form).find('[name="custommaturity"]').is(":checked") ? 1 : 0;
  let chkenrolmentend = $(form).find('[name="chkenrolmentend"]').is(":checked") ? 1 : 0;
  // Send the raw selector values, the server converts them in the user's Moodle timezone,
  // which may differ from the browser's timezone.
  const readDate = (name) => ({
    year: +$(form).find('#id_' + name + '_year').val(),
    month: +$(form).find('#id_' + name + '_month').val(),
    day: +$(form).find('#id_' + name + '_day').val(),
    hour: +$(form).find('#id_' + name + '_hour').val(),
    minute: +$(form).find('#id_' + name + '_minute').val(),
  });
  let data = {
    courseid: courseid,
    roleid: roleid,
    groupid: groupid,
    custommaturity: custommaturity,
    maturity: readDate('maturity'),
    chkenrolmentend: chkenrolmentend,
    enrolmentend: readDate('enrolmentend'),
  };

  try {
    const [title, finished] = await getStrings([
      {key: 'code:get', component: 'block_enrolcode'},
      {key: 'finished', component: 'block_enrolcode'},
    ]);
    const code = await fetchMany([{
      methodname: 'block_enrolcode_get',
      args: data,
    }])[0];
    if (!code) {
      await Modal.create({
        title: await getString('error', 'moodle'),
        body: Templates.render('block_enrolcode/code_get_error', {}),
        show: true,
      });
      return;
    }
    await ModalAlert.create({
      title: title,
      body: Templates.render('block_enrolcode/modal_code', {
        code: code,
        qrcode_image: Url.relativeUrl('/blocks/enrolcode/qr.php', {code: code}),
        enrolurl: generateEnrolURL(code),
      }),
      buttons: {
        cancel: finished,
      },
      show: true,
    });
  } catch (error) {
    Notification.exception(error);
  }
}

/**
 * Show the form to get a code in a modal.
 * @param {int} courseid the courseid we need the modal for.
 */
export async function getCodeModal(courseid) {
  try {
    const form = await fetchMany([{
      methodname: 'block_enrolcode_form',
      args: {courseid: courseid},
    }])[0];
    await Modal.create({
      title: await getString('code:get', 'block_enrolcode'),
      body: form,
      large: true,
      show: true,
    });
  } catch (error) {
    Notification.exception(error);
  }
}

/**
 * Let's inject a button on the enrol users page.
 * @param {int} courseid
 */
export async function injectButton(courseid) {
  const label = await getString('code:get', 'block_enrolcode');
  const button = $('<a href="#" class="btn btn-secondary">').text(label).on('click', function (e) {
    e.preventDefault();
    getCodeModal(courseid);
  });
  // Own navitem after the navigation selector, because the navitem of the enrol button only exists
  // if the course has a manual enrolment instance. The container is div.row or div.d-flex (Bootstrap 5).
  $('#action_bar .navitem:first-child').after(
    $('<div class="navitem">').append($('<div class="singlebutton enrolusersbutton block_enrolcode">').append(button))
  );
}

/**
 * Send a code for fast enrolment.
 * @param {string} uniqid of form containing the input-element for the code.
 */
export async function sendCode(uniqid) {
  const code = $('#code-' + uniqid).val();
  try {
    const courseid = await fetchMany([{
      methodname: 'block_enrolcode_send',
      args: {code: code},
    }])[0];
    if (courseid > 1) {
      // We are enrolled - automatically redirect to course!
      top.location.href = Url.relativeUrl('/course/view.php?id=' + courseid, {}, false);
      return;
    }
    const [title, body] = await getStrings([
      {key: 'error', component: 'moodle'},
      {key: 'code:enrol:error', component: 'block_enrolcode'},
    ]);
    await Modal.create({
      title: title,
      body: body,
      show: true,
    });
  } catch (error) {
    Notification.exception(error);
  }
}
