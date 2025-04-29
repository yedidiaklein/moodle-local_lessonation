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

defined('MOODLE_INTERNAL') || die();
/**
 * Add the AI Lessonation menu to the course administration menu.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_lessonation_extend_settings_navigation($settingsnav, $context) {
    global $CFG, $PAGE, $USER;

    // Add the AI Lessonation to users that have permissions to add lessons to course.
    // Check if the user has the capability to add lessons.
    if (has_capability('moodle/course:manageactivities', $context)) {

        if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
            $strfather = get_string('lessonation', 'local_lessonation');
            $fathernode = navigation_node::create(
                $strfather,
                null,
                navigation_node::NODETYPE_BRANCH,
                'local_lessonation_father',
                'local_lessonation_father'
            );

            $settingnode->add_node($fathernode);
            $strlist = get_string('generate', 'local_lessonation');
            $url = new moodle_url('/local/lessonation/generate_form.php', array('courseid' => $PAGE->course->id));
            $listnode = navigation_node::create(
                $strlist,
                $url,
                navigation_node::NODETYPE_LEAF,
                'local_lessonation_generate',
                'local_lessonation_generate',
                new pix_icon('f/avi-24', $strlist)
            );

            if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
                $listnode->make_active();
            }
            $fathernode->add_node($listnode);
        }
    }
}