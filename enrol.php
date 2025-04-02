<?php

require_once('../../config.php');
sesskey();

require_once($CFG->dirroot . '/blocks/enrolcode/locallib.php');
require_login();

$code = required_param('code', PARAM_ALPHANUM);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/blocks/enrolcode/enrol.php', array('code' => $code));
$PAGE->set_title(get_string('code:accesscode', 'block_enrolcode'));
$PAGE->set_heading(get_string('code:accesscode', 'block_enrolcode'));

if (!isloggedin() || isguestuser($USER)) {
    require_login();
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('block_enrolcode/alert', array(
        'type' => 'danger',
        'content' => get_string('code:enrol:guesterror', 'block_enrolcode'),
        'url' => $CFG->wwwroot . '/login/index.php',
    ));
    echo $OUTPUT->footer();
} else {
    $courseid = \block_enrolcode\locallib::enrol_by_code($code);
    if ($courseid) {
        redirect($CFG->wwwroot . '/course/view.php?id=' . $courseid);
        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('block_enrolcode/alert', array(
            'type' => 'success',
            'content' => get_string('enrol:success:redirect', 'block_enrolcode'),
            'url' => $CFG->wwwroot . '/course/view.php?id=' . $courseid,
        ));
        echo $OUTPUT->footer();
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->render_from_template('block_enrolcode/alert', array(
            'type' => 'danger',
            'content' => get_string('code:enrol:error', 'block_enrolcode'),
            'url' => $CFG->wwwroot . '/my',
        ));
        echo $OUTPUT->footer();
    }
}
