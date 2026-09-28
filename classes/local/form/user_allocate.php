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

use tool_mulib\muform\element\autocomplete;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;
use tool_mutenancy\muform\autocomplete\user_allocate as allocate_source;

/**
 * User allocation form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_allocate extends form {
    #[\Override]
    protected function definition(): void {
        $user = $this->get_extra_data()['user'];

        $info = '<div class="alert alert-warning">' . markdown_to_html(get_string('user_allocate_info', 'tool_mutenancy')) . '</div>';
        $this->add(new inforawhtml('info', '', $info));

        $this->add(new autocomplete('tenantid', get_string('tenant', 'tool_mutenancy'), new allocate_source($user->id)));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('user_allocate', 'tool_mutenancy')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        global $DB;
        $user = $this->get_extra_data()['user'];

        if ($data['tenantid']) {
            $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $data['tenantid']]);
            if (!$tenant) {
                $allerrors['tenantid'][] = get_string('error');
            } else if ($tenant->id == $user->tenantid) {
                $allerrors['tenantid'][] = get_string('error:changerequired', 'tool_mutenancy');
            } else if ($tenant->memberlimit) {
                $count = $DB->count_records('user', ['tenantid' => $tenant->id, 'deleted' => 0]);
                if ($count >= $tenant->memberlimit) {
                    $allerrors['tenantid'][] = get_string('error:memberlimitreached', 'tool_mutenancy');
                }
            }
        } else {
            if (!$user->tenantid) {
                $allerrors['tenantid'][] = get_string('required');
            }
        }
    }
}
