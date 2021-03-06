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
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_scheduler_activity_task
 */

/**
 * Structure step to restore one scheduler activity
 */
class restore_scheduler_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $scheduler = new restore_path_element('scheduler', '/activity/scheduler');
        $paths[] = $scheduler;

        $slot = new restore_path_element('scheduler_slot', '/activity/scheduler/slots/slot');
        $paths[] = $slot;

        if ($userinfo) {
            $appointment = new restore_path_element('scheduler_appointment', '/activity/scheduler/slots/slot/appointments/appointment');
            $paths[] = $appointment;
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_scheduler($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);

        if ($data->scale < 0) { // scale found, get mapping
            $data->scale = -($this->get_mappingid('scale', abs($data->scale)));
        }
        $data->teacher = $this->get_mappingid('user', $data->teacher);

        // insert the scheduler record
        $newitemid = $DB->insert_record('scheduler', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_scheduler_slot($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->schedulerid = $this->get_new_parentid('scheduler');
        $data->starttime = $this->apply_date_offset($data->starttime);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->emaildate = $this->apply_date_offset($data->emaildate);
        $data->hideuntil = $this->apply_date_offset($data->hideuntil);

        $data->teacherid = $this->get_mappingid('user', $data->teacherid);

        $newitemid = $DB->insert_record('scheduler_slots', $data);
        $this->set_mapping('scheduler_slot', $oldid, $newitemid, true);
        // Apply only once we have files in the slot
    }

    protected function process_scheduler_appointment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->slotid = $this->get_new_parentid('scheduler_slot');

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $data->studentid = $this->get_mappingid('user', $data->studentid);

        $newitemid = $DB->insert_record('scheduler_appointment', $data);
        // $this->set_mapping('scheduler_appointments', $oldid, $newitemid, true);
        // Apply only once we have files in the appointment
    }

    protected function after_execute() {
        // Add scheduler related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_scheduler', 'intro', null);
    }
}
