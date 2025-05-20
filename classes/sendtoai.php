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
namespace local_lessonation;

/**
 * Class responsible for sending data to the AI for generation.
 *
 * @package     local_lessonation
 */
class sendtoai {

    /**
     * Generate text from the AI placement.
     *
     * @param int $contextid The context ID.
     * @param string $prompttext The data encoded as a json array.
     * @return array The generated content.
     * @since  Moodle 4.5
     */
    public static function execute(
        int $contextid,
        string $prompttext): array {

        global $USER;

        // Context validation and permission check.
        // Get the context from the passed in ID.
        $context = \core\context::instance_by_id($contextid);

        // Prepare the action.
        $action = new \core_ai\aiactions\generate_text(
            contextid: $contextid,
            userid: $USER->id,
            prompttext: $prompttext,
        );

        // Send the action to the AI manager.
        $manager = \core\di::get(\core_ai\manager::class);
        $response = $manager->process_action($action);
        // Return the response.
        return [
            'success' => $response->get_success(),
            'generatedcontent' => $response->get_response_data()['generatedcontent'] ?? '',
            'finishreason' => $response->get_response_data()['finishreason'] ?? '',
            'errorcode' => $response->get_errorcode(),
            'error' => $response->get_errormessage(),
            'timecreated' => $response->get_timecreated(),
            'prompttext' => $prompttext,
        ];
    }
}
