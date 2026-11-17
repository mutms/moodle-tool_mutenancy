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

/**
 * Multi-tenancy plugin upgrade.
 *
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade multi-tenancy.
 *
 * @param mixed $oldversion
 * @return true
 */
function xmldb_tool_mutenancy_upgrade($oldversion): bool {
    if ($oldversion < 2026111653) {
        // Standard cohort management is not usable with tenants,
        // the same takeover is enabled in \tool_mutenancy\local\tenancy::activate().
        if (get_config('tool_mutenancy', 'active')) {
            $oldtakeover = get_config('tool_mucohort', 'takeover');
            if (!$oldtakeover) {
                set_config('takeover', 1, 'tool_mucohort');
                add_to_config_log('takeover', (int)$oldtakeover, '1', 'tool_mucohort');
            }
        }

        upgrade_plugin_savepoint(true, 2026111653, 'tool', 'mutenancy');
    }

    return true;
}
