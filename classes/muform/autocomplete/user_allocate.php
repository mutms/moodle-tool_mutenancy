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
use tool_mulib\local\sql;

/**
 * Tenants a user may be allocated to.
 *
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_allocate extends \tool_mulib\muform\autocomplete\base {
    /** @var int current tenant of the user, 0 if none */
    private int $currenttenantid;

    /**
     * Constructor.
     *
     * @param int $userid
     */
    public function __construct(
        /** @var int user id */
        private readonly int $userid
    ) {
        global $DB;
        require_capability('tool/mutenancy:allocate', \context_system::instance());
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], 'id, tenantid', MUST_EXIST);
        $this->currenttenantid = (int)$user->tenantid;
    }

    #[\Override]
    public function get_args(): array {
        return [$this->userid];
    }

    #[\Override]
    public function search(string $query, int $maxitems): ?array {
        global $DB;

        $sql = (new sql(
            "SELECT t.id, t.name
               FROM {tool_mutenancy_tenant} t
              WHERE t.id <> :tenantid
                    /* searchsql */
           ORDER BY t.name ASC",
            ['tenantid' => $this->currenttenantid]
        ))->replace_comment('searchsql', search_util::get_search_query($query, ['name', 'idnumber'], 't')->wrap('AND ', ''));

        $tenants = $DB->get_records_sql_menu($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($tenants) > $maxitems) {
            return null;
        }
        return array_map('format_string', $tenants);
    }

    #[\Override]
    public function label(string $value): ?string {
        global $DB;
        // Any existing tenant, the current tenant of the user is a valid value too.
        $name = $DB->get_field('tool_mutenancy_tenant', 'name', ['id' => (int)$value]);
        return ($name === false) ? null : format_string($name);
    }
}
