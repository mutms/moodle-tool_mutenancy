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

namespace tool_mutenancy\muform\userpicker;

use tool_mulib\local\sql;

/**
 * Tenant manager candidates: global users and members of the tenant.
 *
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant_managers extends \tool_mulib\muform\userpicker\base {
    /** @var \context tenant context */
    private \context $context;

    /**
     * Constructor.
     *
     * @param int $tenantid
     */
    public function __construct(
        /** @var int tenant id */
        private readonly int $tenantid
    ) {
        $this->context = \context_tenant::instance($tenantid);
        require_capability('tool/mutenancy:admin', $this->context);
    }

    #[\Override]
    public function get_args(): array {
        return [$this->tenantid];
    }

    #[\Override]
    protected function get_context(): \core\context {
        // Global users are candidates too, tenant member restrictions do not apply here.
        return \core\context\system::instance();
    }

    /**
     * Only global users and tenant members may manage the tenant.
     *
     * @return sql|null
     */
    #[\Override]
    protected function get_candidates_sql(): ?sql {
        return new sql('(u.tenantid IS NULL OR u.tenantid = :managertenantid)', ['managertenantid' => $this->tenantid]);
    }
}
