<?php

require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');

class block_enrolcode_enrol_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('html', '<p>' . get_string('code:enter', 'block_enrolcode') . '</p>');
        $mform->addElement('text', 'code', get_string('code:accesscode', 'block_enrolcode'));
        $mform->setType('code', PARAM_ALPHANUM);
        $mform->addRule('code', null, 'required', null, 'client');
        $this->add_action_buttons(false, get_string('code:enrol', 'block_enrolcode'));
    }
}

require_login();

$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/enrolcode/enrol.php');
$PAGE->set_title(get_string('code:accesscode', 'block_enrolcode'));
$PAGE->set_heading(get_string('code:accesscode', 'block_enrolcode'));

// With guest autologin enabled, require_login() lets guests through, but they can't be enrolled.
if (isguestuser()) {
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('block_enrolcode/alert', [
        'type' => 'danger',
        'content' => get_string('code:enrol:guesterror', 'block_enrolcode'),
        'url' => $CFG->wwwroot . '/login/index.php',
    ]);
    echo $OUTPUT->footer();
    exit;
}

$form = new block_enrolcode_enrol_form($PAGE->url);
// The code comes either from the form or directly from a link / QR code.
$code = ($data = $form->get_data()) ? $data->code : optional_param('code', '', PARAM_ALPHANUM);

$error = false;
if ($code) {
    $courseid = \block_enrolcode\locallib::enrol_by_code($code);
    if ($courseid) {
        redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
    }
    // Keep the wrong code in the field, so it can be corrected.
    $error = true;
    $form->set_data(['code' => $code]);
}

echo $OUTPUT->header();
if ($error) {
    echo $OUTPUT->notification(get_string('code:enrol:error', 'block_enrolcode'), 'error');
}
$form->display();
echo $OUTPUT->footer();
