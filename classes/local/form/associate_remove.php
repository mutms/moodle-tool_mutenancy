<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_mutenancy\local\form;

use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Disassociate user form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class associate_remove extends form {
    #[\Override]
    protected function definition(): void {
        global $DB;

        $cohort = $this->get_extra_data()['cohort'];
        $user = $this->get_extra_data()['user'];

        $info = '<div class="alert alert-warning">' . markdown_to_html(get_string('associate_remove_info', 'tool_mutenancy')) . '</div>';
        $this->add(new inforawhtml('info', '', $info));

        $tenants = $DB->get_records_menu('tool_mutenancy_tenant', ['assoccohortid' => $cohort->id], 'name ASC', 'id, name');
        $tenants = array_map('format_string', $tenants);
        $label = (count($tenants) > 1) ? get_string('tenants', 'tool_mutenancy') : get_string('tenant', 'tool_mutenancy');
        $this->add(new info('tenants', $label, implode(', ', $tenants), info::PLAIN));
        $this->add(new info('cohortname', get_string('associate_cohort', 'tool_mutenancy'), format_string($cohort->name), info::PLAIN));
        $this->add(new info('fullname', get_string('user'), fullname($user), info::PLAIN));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('associate_remove', 'tool_mutenancy')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
