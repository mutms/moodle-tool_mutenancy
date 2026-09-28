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

namespace tool_mutenancy\muform\autocompletemany;

use core\exception\invalid_parameter_exception;
use tool_mulib\local\sql;
use tool_mulib\muform\util\autocomplete\user_trait;

/**
 * Global users that may be added to the associated cohort of a tenant.
 *
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class associate_add extends \tool_mulib\muform\autocompletemany\base {
    use user_trait;

    /** @var \context tenant context */
    private \context $context;
    /** @var int associated cohort id */
    private int $cohortid;

    /**
     * Constructor.
     *
     * @param int $tenantid
     */
    public function __construct(
        /** @var int tenant id */
        private readonly int $tenantid
    ) {
        global $DB;

        $this->context = \context_tenant::instance($tenantid);
        require_capability('tool/mutenancy:view', $this->context);

        $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);
        if (!$tenant->assoccohortid) {
            throw new invalid_parameter_exception('tenant does not have associated cohort');
        }
        $cohort = $DB->get_record('cohort', ['id' => $tenant->assoccohortid], '*', MUST_EXIST);
        if ($cohort->component) {
            throw new invalid_parameter_exception('Associate cohort cannot belong to any component');
        }
        require_capability('moodle/cohort:assign', \context::instance_by_id($cohort->contextid));
        $this->cohortid = (int)$cohort->id;
    }

    #[\Override]
    public function get_args(): array {
        return [$this->tenantid];
    }

    #[\Override]
    public function search(string $query, int $maxitems, array $exclude): ?array {
        // Global users are candidates too, tenant member restrictions do not apply here.
        return $this->search_users(\context_system::instance(), $query, $maxitems, $exclude, $this->get_where());
    }

    #[\Override]
    public function labels(array $values): array {
        return $this->user_labels(\context_system::instance(), $values, $this->get_where());
    }

    /**
     * Only global users that are not members yet.
     *
     * @return sql
     */
    private function get_where(): sql {
        return new sql(
            "u.tenantid IS NULL
             AND NOT EXISTS (SELECT 'x' FROM {cohort_members} cm WHERE cm.userid = u.id AND cm.cohortid = :assoccohortid)",
            ['assoccohortid' => $this->cohortid]
        );
    }
}
