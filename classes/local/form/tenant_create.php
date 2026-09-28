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
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\number;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\text;
use tool_mulib\muform\form;
use tool_mutenancy\local\tenancy;
use tool_mutenancy\local\tenant;
use tool_mutenancy\muform\autocomplete\tenant_assoccohortid;

/**
 * Create a new tenant form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant_create extends form {
    #[\Override]
    protected function definition(): void {
        $context = $this->get_extra_data()['context'];

        $name = new text('name', get_string('tenant_name', 'tool_mutenancy'), ['maxlength' => 255]);
        $name->set_required(true);
        $this->add($name);

        $idnumber = new text('idnumber', get_string('tenant_idnumber', 'tool_mutenancy'), ['type' => 'rawtext', 'maxlength' => 50]);
        $idnumber->set_required(true);
        $this->add($idnumber);

        $this->add(new checkbox('loginshow', get_string('tenant_loginshow', 'tool_mutenancy')));

        $memberlimit = new number('memberlimit', get_string('tenant_memberlimit', 'tool_mutenancy'), ['min' => 0, 'width' => 'small']);
        $memberlimit->add_help_button('tenant_memberlimit', 'tool_mutenancy');
        $this->add($memberlimit);

        $source = new tenant_assoccohortid(0);
        $assoccohortid = new autocomplete('assoccohortid', get_string('associate_cohort', 'tool_mutenancy'), $source);
        $assoccohortid->add_help_button('associate_cohort', 'tool_mutenancy');
        $this->add($assoccohortid);

        if (has_capability('moodle/cohort:manage', $context)) {
            $create = new checkbox('assoccohortcreate', get_string('associate_cohort_create', 'tool_mutenancy'));
            $create->add_help_button('associate_cohort_create', 'tool_mutenancy');
            $this->add($create);
            $this->get_display_manager()->hide_if('assoccohortid', 'assoccohortcreate', 'checked');
        }

        $this->add(new text('sitefullname', get_string('tenant_sitefullname', 'tool_mutenancy'), ['maxlength' => 255]));
        $this->add(new text('siteshortname', get_string('tenant_siteshortname', 'tool_mutenancy'), ['maxlength' => 255]));

        $categoryname = new text('categoryname', get_string('tenant_categoryname', 'tool_mutenancy'), ['maxlength' => 255]);
        $this->add($categoryname);
        $this->add(new text('categoryidnumber', get_string('tenant_categoryidnumber', 'tool_mutenancy'), ['maxlength' => 255]));

        $cohortname = new text('cohortname', get_string('tenant_cohortname', 'tool_mutenancy'), ['maxlength' => 255]);
        $this->add($cohortname);
        $this->add(new text('cohortidnumber', get_string('tenant_cohortidnumber', 'tool_mutenancy'), ['maxlength' => 255]));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', tenancy::get_tenant_string('tenant_create')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        global $DB;

        if (!preg_match(tenant::IDNUMBER_REGEX, $data['idnumber'])) {
            $allerrors['idnumber'][] = get_string('error');
        } else if ($DB->record_exists_select('tool_mutenancy_tenant', 'LOWER(idnumber) = LOWER(?)', [$data['idnumber']])) {
            $allerrors['idnumber'][] = get_string('duplicate');
        }

        if (trim($data['categoryidnumber']) !== '') {
            if ($DB->record_exists_select('course_categories', 'LOWER(idnumber) = LOWER(?)', [$data['categoryidnumber']])) {
                $allerrors['categoryidnumber'][] = get_string('duplicate');
            }
        }

        if (trim($data['cohortidnumber']) !== '') {
            if ($DB->record_exists_select('cohort', 'LOWER(idnumber) = LOWER(?)', [$data['cohortidnumber']])) {
                $allerrors['cohortidnumber'][] = get_string('duplicate');
            }
        }
    }
}
