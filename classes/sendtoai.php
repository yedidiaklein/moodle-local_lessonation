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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details.
 *
 * @package    local_lessonation
 * @author     Yedidia Klein <yedidia@openapp.co.il>
 * @copyright  Yedidia Klein
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_lessonation;

defined('MOODLE_INTERNAL') || die();

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

        // Check the user has permission to use the AI service.
        //self::validate_context($context);
        //if (!utils::is_html_editor_placement_action_available($context, 'generate_text', \core_ai\aiactions\generate_text::class)) {
        //    throw new \moodle_exception('noeditor', 'aiplacement_editor');
        //}

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
