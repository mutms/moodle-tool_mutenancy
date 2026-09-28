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
use tool_mutenancy\local\tenancy;

/**
 * Switch tenant form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant_switch extends form {
    #[\Override]
    protected function definition(): void {
        if (has_capability('tool/mutenancy:admin', \context_system::instance())) {
            $info = '<div class="alert alert-info">' . markdown_to_html(get_string('tenant_switch_info', 'tool_mutenancy')) . '</div>';
            $this->add(new inforawhtml('info', '', $info));
        }

        $source = new \tool_mutenancy\muform\autocomplete\tenant_switch();
        $tenant = new autocomplete('tenantid', tenancy::get_tenant_string('tenant'), $source);
        $tenant->set_required(true);
        $this->add($tenant);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', tenancy::get_tenant_string('tenant_switch')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $tenantid = (int)tenancy::get_current_tenantid();

        if ((int)$data['tenantid'] === $tenantid) {
            $allerrors['tenantid'][] = get_string('error:changerequired', 'tool_mutenancy');
        }
    }

    /**
     * Returns list of tenant switching targets.
     * @return array
     */
    public static function get_options(): array {
        global $DB, $USER;

        $notenant = tenancy::get_tenant_string('tenant_switch_notenant');
        $mytenants = tenancy::get_tenants_string('tenant_switch_my');

        $options = [];
        $options[''][0] = $notenant;

        $sql = "SELECT t.id, t.name
                  FROM {tool_mutenancy_tenant} t
                  JOIN {cohort_members} cm ON cm.cohortid = t.assoccohortid AND cm.userid = :me
                 WHERE t.archived = 0";
        $tenants = $DB->get_records_sql_menu($sql, ['me' => $USER->id]);
        $tenants = array_map('format_string', $tenants);
        \core_collator::asort($tenants);
        foreach ($tenants as $k => $v) {
            $options[$mytenants][$k] = $v;
        }

        if (isset($options[$mytenants])) {
            $othertenants = tenancy::get_tenants_string('tenant_switch_other');
        } else {
            $othertenants = tenancy::get_tenants_string('tenants');
        }

        // Cheat here a bit to make this faster,
        // also keep this consistent with tenancy::can_switch().
        $syscontext = \context_system::instance();

        if (has_capability('tool/mutenancy:switch', $syscontext)) {
            $sql = "SELECT t.id, t.name
                      FROM {tool_mutenancy_tenant} t
                 LEFT JOIN {cohort_members} cm ON cm.cohortid = t.assoccohortid AND cm.userid = :me
                     WHERE t.archived = 0 AND cm.id IS NULL";
            $params = ['me' => $USER->id];
        } else {
            [$needed, $forbidden] = get_roles_with_cap_in_context($syscontext, 'tool/mutenancy:switch');
            if (!$needed) {
                return $options;
            }
            $needed = implode(',', $needed);
            $sql = "SELECT t.id, t.name
                      FROM {role_assignments} ra
                      JOIN {context} c ON c.id = ra.contextid AND c.contextlevel = :tenantlevel
                      JOIN {tool_mutenancy_tenant} t ON t.id = c.instanceid AND t.archived = 0
                 LEFT JOIN {cohort_members} cm ON cm.cohortid = t.assoccohortid AND cm.userid = ra.userid
                     WHERE ra.userid = :me AND ra.roleid IN ($needed) AND cm.id IS NULL";
            $params = ['tenantlevel' => \context_tenant::LEVEL, 'me' => $USER->id];
        }

        $tenants = $DB->get_records_sql_menu($sql, $params);
        $tenants = array_map('format_string', $tenants);
        \core_collator::asort($tenants);
        foreach ($tenants as $tid => $tname) {
            // Use real capability check here, no more guessing!
            $tenantcontext = \context_tenant::instance($tid);
            if (!has_capability('tool/mutenancy:switch', $tenantcontext)) {
                continue;
            }
            $options[$othertenants][$tid] = $tname;
        }

        return $options;
    }
}
