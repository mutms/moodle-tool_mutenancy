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

namespace tool_mutenancy\muform\autocomplete;

use tool_mulib\local\search_util;
use tool_mulib\local\context_map;
use tool_mulib\local\sql;

/**
 * Cohorts that may be associated with a tenant.
 *
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant_assoccohortid extends \tool_mulib\muform\autocomplete\base {
    /** @var \context tenant or system context */
    private \context $context;

    /**
     * Constructor.
     *
     * @param int $tenantid 0 when creating a new tenant
     */
    public function __construct(
        /** @var int tenant id */
        private readonly int $tenantid
    ) {
        $this->context = $tenantid ? \context_tenant::instance($tenantid) : \context_system::instance();
        require_capability('tool/mutenancy:admin', $this->context);
    }

    #[\Override]
    public function get_args(): array {
        return [$this->tenantid];
    }

    #[\Override]
    public function search(string $query, int $maxitems): ?array {
        global $DB, $USER;

        $sql = (
            new sql(
                "SELECT ch.id, ch.name
                   FROM {cohort} ch
                   JOIN {context} c ON c.id = ch.contextid AND (c.tenantid IS NULL OR c.tenantid = :tenantid)
                   /* capsubquery */
                  WHERE (ch.component = '' OR ch.component IS NULL)
                        /* capwhere */
                        /* searchsql */
               ORDER BY ch.name ASC",
                ['tenantid' => $this->tenantid]
            )
        )
            ->replace_comment('searchsql', search_util::get_cohort_search_query($query, 'ch')->wrap('AND ', ''))
            ->replace_comment(
                'capsubquery',
                context_map::get_contexts_by_capability_query(
                    'moodle/cohort:view',
                    $USER->id,
                    new sql("(ctx.contextlevel = ? OR ctx.contextlevel = ?)", [\context_system::LEVEL, \context_coursecat::LEVEL])
                )->wrap("LEFT JOIN (", ")capctx ON capctx.id = c.id")
            )
            ->replace_comment('capwhere', "AND (ch.visible = 1 OR capctx.id IS NOT NULL)");

        $cohorts = $DB->get_records_sql_menu($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($cohorts) > $maxitems) {
            return null;
        }
        return array_map(fn($name) => format_string($name, true, ['context' => $this->context]), $cohorts);
    }

    #[\Override]
    public function label(string $value): ?string {
        global $DB;
        if ($this->validate($value) !== null) {
            return null;
        }
        $name = $DB->get_field('cohort', 'name', ['id' => (int)$value]);
        return format_string($name, true, ['context' => $this->context]);
    }

    #[\Override]
    public function validate(string $value): ?string {
        global $DB;

        $cohort = $DB->get_record('cohort', ['id' => (int)$value]);
        if (!$cohort) {
            return get_string('error');
        }
        $context = \context::instance_by_id($cohort->contextid, IGNORE_MISSING);
        if (!$context) {
            return get_string('error');
        }

        if ($this->tenantid) {
            $tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $this->tenantid]);
            if (!$tenant) {
                return get_string('error');
            }
            if ($tenant->assoccohortid == $cohort->id) {
                // Allow whatever existing cohort is there.
                return null;
            }
            if ($context->tenantid && $context->tenantid != $this->tenantid) {
                // Do not allow cohorts from other tenants.
                return get_string('error');
            }
        } else if ($context->tenantid) {
            // Do not allow cohorts from other tenants.
            return get_string('error');
        }

        if ($DB->record_exists('tool_mutenancy_tenant', ['cohortid' => $cohort->id])) {
            // Do not allow tenant member cohorts.
            return get_string('error');
        }

        if (!search_util::is_cohort_visible($cohort)) {
            return get_string('error');
        }

        return null;
    }
}
