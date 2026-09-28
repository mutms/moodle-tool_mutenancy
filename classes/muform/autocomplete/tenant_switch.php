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

use core\exception\moodle_exception;
use tool_mutenancy\local\tenancy;

/**
 * Tenants the current user may switch to, "No tenant" is value 0.
 *
 * Targets are ordered: no tenant, tenants of the user's associated cohorts, other tenants.
 *
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenant_switch extends \tool_mulib\muform\autocomplete\base {
    /** @var array|null switching targets indexed by tenant id */
    private ?array $targets = null;

    /**
     * Constructor.
     */
    public function __construct() {
        if (!tenancy::is_active() || !tenancy::can_switch()) {
            throw new moodle_exception('nopermissions', 'error', '', 'switch tenant');
        }
    }

    #[\Override]
    public function get_args(): array {
        return [];
    }

    #[\Override]
    public function search(string $query, int $maxitems): ?array {
        $query = \core_text::strtolower(trim($query));
        $result = [];
        foreach ($this->get_targets() as $tenantid => $name) {
            if ($query !== '' && !str_contains(\core_text::strtolower($name), $query)) {
                continue;
            }
            if (count($result) >= $maxitems) {
                return null;
            }
            $result[$tenantid] = $name;
        }
        return $result;
    }

    #[\Override]
    public function label(string $value): ?string {
        return $this->get_targets()[$value] ?? null;
    }

    /**
     * Returns switching targets.
     *
     * @return array formatted tenant names indexed by tenant id, 0 is no tenant
     */
    public function get_targets(): array {
        if ($this->targets === null) {
            $this->targets = [];
            foreach (\tool_mutenancy\local\form\tenant_switch::get_options() as $group) {
                $this->targets += $group;
            }
        }
        return $this->targets;
    }
}
