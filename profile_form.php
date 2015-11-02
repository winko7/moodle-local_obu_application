<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * OBU Application - User profile form
 *
 * @package    obu_application
 * @category   local
 * @author     Peter Welham
 * @copyright  2015, Oxford Brookes University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class profile_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
		$data->formref = $this->_customdata['formref'];
		$data->record = $this->_customdata['record'];
		
		if ($data->record != null) {
			$description['text'] = $data->record->description;
			$fields = [
				'name' => $data->record->name,
				'description' => $description,
				'student' => $data->record->student,
				'visible' => $data->record->visible,
				'auth_1_role' => $data->record->auth_1_role,
				'auth_1_notes' => $data->record->auth_1_notes,
				'auth_2_role' => $data->record->auth_2_role,
				'auth_2_notes' => $data->record->auth_2_notes,
				'auth_3_role' => $data->record->auth_3_role,
				'auth_3_notes' => $data->record->auth_3_notes
			];
			$this->set_data($fields);
		}
		
		$mform->addElement('html', '<h2>' . get_string('amend_settings', 'local_obu_forms') . '</h2>');

		if ($data->formref == '') {
			$mform->addElement('text', 'formref', get_string('form', 'local_obu_forms'), 'size="10" maxlength="10"');
			$this->add_action_buttons(false, get_string('continue', 'local_obu_forms'));
			return;
		}
		$mform->addElement('hidden', 'formref', strtoupper($data->formref));
		$mform->addElement('static', null, get_string('form', 'local_obu_forms'), strtoupper($data->formref));
		
		$mform->addElement('text', 'name', get_string('form_name', 'local_obu_forms'), 'size="60" maxlength="60"');
		$mform->addElement('editor', 'description', get_string('description', 'local_obu_forms'));
		$mform->setType('description', PARAM_RAW);
		$mform->addElement('advcheckbox', 'student', get_string('student_form', 'local_obu_forms'), null, null, array(0, 1));
		$mform->addElement('advcheckbox', 'visible', get_string('form_visible', 'local_obu_forms'), null, null, array(0, 1));
		
		$authorisers = get_authorisers();
		$select = $mform->addElement('select', 'auth_1_role', get_string('auth_1_role', 'local_obu_forms'), $authorisers, null);
		$select->setSelected(auth_1_role);
		$mform->addElement('text', 'auth_1_notes', get_string('auth_1_notes', 'local_obu_forms'), 'size="60" maxlength="200"');
		$select = $mform->addElement('select', 'auth_2_role', get_string('auth_2_role', 'local_obu_forms'), $authorisers, null);
		$select->setSelected(auth_2_role);
		$mform->addElement('text', 'auth_2_notes', get_string('auth_2_notes', 'local_obu_forms'), 'size="60" maxlength="200"');
		$select = $mform->addElement('select', 'auth_3_role', get_string('auth_3_role', 'local_obu_forms'), $authorisers, null);
		$select->setSelected(auth_3_role);
		$mform->addElement('text', 'auth_3_notes', get_string('auth_3_notes', 'local_obu_forms'), 'size="60" maxlength="200"');

        $this->add_action_buttons(true, get_string('save', 'local_obu_forms'));
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

        if (!validate_email($data['username'])) {
            $errors['username'] = get_string('invalidemail');
        } else if ($DB->record_exists('user', array('email' => $data['username']))) {
            $errors['username'] = get_string('emailexists') . ' <a href="forgot_password.php">' . get_string('newpassword') . '?</a>';
        }
		
        if (empty($data['email'])) {
            $errors['email'] = get_string('missingemail');
        } else if ($data['email'] != $data['username']) {
            $errors['email'] = get_string('invalidemail');
        }
		
        $errmsg = '';
        if (!check_password_policy($data['password'], $errmsg)) {
            $errors['password'] = $errmsg;
        }

        // If reCAPTCHA is setup we would have used it - so check it!
		if (!empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey)) {
            $recaptcha_element = $this->_form->getElement('recaptcha_element');
            if (!empty($this->_form->_submitValues['recaptcha_challenge_field'])) {
                $challenge_field = $this->_form->_submitValues['recaptcha_challenge_field'];
                $response_field = $this->_form->_submitValues['recaptcha_response_field'];
                if (true !== ($result = $recaptcha_element->verify($challenge_field, $response_field))) {
                    $errors['recaptcha'] = $result;
                }
            } else {
                $errors['recaptcha'] = get_string('missingrecaptchachallengefield');
            }
        }

        return $errors;
    }
}