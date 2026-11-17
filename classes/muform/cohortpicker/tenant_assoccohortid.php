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

namespace tool_mutenancy\muform\cohortpicker;

use core\context;
use tool_mulib\local\sql;

/**
 * Cohort that may be associated with a tenant: a standard cohort that does not belong to other tenant.
 *
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant_assoccohortid extends \tool_mulib\muform\cohortpicker\base {
    /** @var context tenant or system context */
    private context $context;

    /**
     * Constructor.
     *
     * @param int $tenantid 0 when creating a new tenant
     */
    public function __construct(
        /** @var int tenant id */
        private readonly int $tenantid
    ) {
        $this->context = $tenantid ? context\tenant::instance($tenantid) : context\system::instance();
        require_capability('tool/mutenancy:admin', $this->context);
    }

    #[\Override]
    public function get_args(): array {
        return [$this->tenantid];
    }

    #[\Override]
    protected function get_context(): context {
        return $this->context;
    }

    /**
     * The cohort already associated stays valid.
     *
     * @return int[]
     */
    #[\Override]
    protected function get_current_cohortids(): array {
        global $DB;
        if (!$this->tenantid) {
            return [];
        }
        $cohortid = $DB->get_field('tool_mutenancy_tenant', 'assoccohortid', ['id' => $this->tenantid]);
        return $cohortid ? [(int)$cohortid] : [];
    }

    #[\Override]
    protected function get_restriction_sql(): ?sql {
        return new sql(
            "(ch.component = '' OR ch.component IS NULL)
             AND (ctx.tenantid IS NULL OR ctx.tenantid = :tenantid)
             AND NOT EXISTS (SELECT 'x' FROM {tool_mutenancy_tenant} mt WHERE mt.cohortid = ch.id)",
            ['tenantid' => $this->tenantid]
        );
    }
}
