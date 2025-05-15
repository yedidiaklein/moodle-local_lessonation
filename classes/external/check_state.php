<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Service for checking state of question generation.
 *
 * @package     local_lessonation
 * @category    admin
 * @copyright   Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_lessonation\external;
defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->libdir}/externallib.php");

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

/**
 * Service for checking state of lesson generation.
 *
 * @package     local_lessonation
 * @category    admin
 */
class check_state extends \external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'adhocid' => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    /**
     * Returns description of method result value
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'state' => new external_value(PARAM_INT, 'State of lesson generation, 0 in work, 1 done'),
                'lessonid' => new external_value(PARAM_INT, 'Lesson id'),
                'error' => new external_value(PARAM_TEXT, 'Error message'),])
        );
    }
    /**
     * Check state
     * @param int $adhocid string
     * @return array of state error and lessonid
     * @throws \moodle_exception
     */
    public static function execute($adhocid) {
        global $DB, $USER;

        // Validate Params.
        $params = self::validate_parameters(self::execute_parameters(), [
            'adhocid' => $adhocid,
        ]);

        $adhocid = $params['adhocid'];

        $state = $DB->get_record('local_lessonation', [ 'adhocid' => $adhocid ]);

        // If there is not yet data in table (adhoc didn't start yet).
        if (!$state) {
            $info['error'] = 'No data in local_lessonation table';
            $info['state'] = 0;
            $info['lessonid'] = '';
            $data[] = $info;
            return $data;
        }

        // Check that user is the user that created the lesson.
        if ($state->userid != $USER->id) {
            throw new \moodle_exception('nopermission');
        }

        $info = [];
        $info['state'] = $state->state;
        $info['lessonid'] = $state->lessonid;
        $info['error'] = '';
        $data[] = $info;
        return $data;
    }
}