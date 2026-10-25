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


namespace tool_mutenancy\callback;

use tool_mutenancy\local\tenancy;

/**
 * Hook callbacks for tool_mucohort.
 *
 * @package    tool_mutenancy
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tool_mucohort {
    /**
     * Describe tenant cohorts.
     *
     * @param \tool_mucohort\hook\cohort_owner $hook
     */
    public static function cohort_owner(\tool_mucohort\hook\cohort_owner $hook): void {
        global $DB;

        $cohort = $hook->get_cohort();
        if ($cohort->component !== 'tool_mutenancy') {
            return;
        }
        $tenant = $DB->get_record('tool_mutenancy_tenant', ['cohortid' => $cohort->id]);
        if (!$tenant) {
            return;
        }
        [$name, $url] = self::get_tenant_link($tenant);
        $hook->set_description(get_string('tenant_cohort_owner', 'tool_mutenancy', $name), $url);
    }

    /**
     * List tenants using the cohort.
     *
     * @param \tool_mucohort\hook\cohort_usage $hook
     */
    public static function cohort_usage(\tool_mucohort\hook\cohort_usage $hook): void {
        global $DB;

        $cohort = $hook->get_cohort();
        $fields = [
            'cohortid' => get_string('cohort_usage_tenant', 'tool_mutenancy'),
            'assoccohortid' => get_string('cohort_usage_associate', 'tool_mutenancy'),
        ];
        foreach ($fields as $field => $section) {
            $tenants = $DB->get_records('tool_mutenancy_tenant', [$field => $cohort->id], 'name ASC, id ASC');
            foreach ($tenants as $tenant) {
                [$name, $url] = self::get_tenant_link($tenant);
                $hook->add_item($section, $name, $url, null, $url !== null);
            }
        }
    }

    /**
     * Returns plain text tenant name and link if the current user may view the tenant.
     *
     * @param \stdClass $tenant
     * @return array
     */
    private static function get_tenant_link(\stdClass $tenant): array {
        $url = null;
        if (tenancy::is_active()) {
            $context = \context_tenant::instance($tenant->id);
            if (has_capability('tool/mutenancy:view', $context)) {
                $url = new \core\url('/admin/tool/mutenancy/tenant.php', ['id' => $tenant->id]);
            }
        }
        $name = format_string($tenant->name, true, ['context' => \context_system::instance(), 'escape' => false]);
        return [$name, $url];
    }
}
