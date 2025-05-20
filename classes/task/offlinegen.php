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
 * Service for checking state of lesson generation.
 *
 * @package     local_lessonation
 * @category    admin
 * @copyright   Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_lessonation\task;

/*
 * This file contains the offlinegen class for the lessonation adhoc task.
 * It handles the request of the user to create a lesson that will be sent to AI.
 */
/**
 * Class for the offlinegen task.
 *
 * @package     local_lessonation
 */
class offlinegen extends \core\task\adhoc_task {
    /**
     * Get plugin name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_lessonation');
    }

    /**
     * Set the creation time.
     *
     * @param int $timecreated The time the task was created.
     */
    public function set_timecreated($timecreated) {
        $this->timecreated = $timecreated;
    }

    /**
     * Execute the task.
     *
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        $prompt = $data->prompt;
        $courseid = $data->courseid;
        $sectionid = $data->sectionid;
        // Get context ID from the course ID.
        $context = \context_course::instance($courseid);
        // Check if the user has permission to use the AI service.

        $aianswer = new \local_lessonation\sendtoai();
        $result = $aianswer->execute($context->id, $prompt);
        if ($result['success']) {
            $json = $result['generatedcontent'];
            $lessonation = new \local_lessonation\create();
            $moduleid = $lessonation->json_to_lesson($json, $courseid, $sectionid);
            // Get local_lessonation record.
            $lessonationrecord = $DB->get_record('local_lessonation',
                ['adhocid' => $this->get_id()], '*', MUST_EXIST);
            // Update local_lessonation record.
            $lessonationrecord->lessonid = $moduleid;
            $lessonationrecord->state = 1;
            $DB->update_record('local_lessonation', $lessonationrecord);

            mtrace(get_string('lessoncreated', 'local_lessonation') . ' mod id ' . $moduleid);
        } else {
            mtrace(get_string('error', 'local_lessonation') . $result['data']);
        }
    }
}
