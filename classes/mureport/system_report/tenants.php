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

namespace tool_mutenancy\mureport\system_report;

use tool_mureport\mureport\system_report;
use tool_mutenancy\local\tenancy;
use tool_mutenancy\mureport\entity\tenant;
use core\lang_string;
use core\url;

/**
 * All tenants, for tenant viewers.
 *
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tenants extends system_report {
    /**
     * Make report instance.
     *
     * @return self
     */
    public static function make_instance(): self {
        return self::from_constraints([]);
    }

    #[\Override]
    public static function get_title(): string {
        return tenancy::get_tenants_string('tenants');
    }

    #[\Override]
    public static function get_url(): ?url {
        return new url('/admin/tool/mutenancy/index.php');
    }

    #[\Override]
    public static function is_available(): bool {
        return tenancy::is_active();
    }

    #[\Override]
    protected function check_access(): void {
        $this->require_capability('tool/mutenancy:view');
    }

    #[\Override]
    protected function define(): void {
        $this->set_main_entity(new tenant('tenant'));
        $this->set_row_muted('tenant.archived = 1', ['tenant']);

        $this->set_default_columns([
            'tenant:name',
            'tenant:idnumber',
            'tenant:category',
            'tenant:usercount',
            'tenant:memberlimit',
            'tenant:archived',
            'tenant:loginurl',
            'tenant:actions',
        ]);
        $this->set_default_filters([
            'tenant:name' => ['multisearch' => true],
            'tenant:idnumber' => ['multisearch' => true],
            'tenant:archived',
        ]);
        $this->set_preset_filters(['tenant:archived' => ['value' => 0]]);
        $this->set_multisearch_label(new lang_string('tenantsearch', 'tool_mutenancy'));
        $this->set_default_sorting(['tenant:name' => 'asc']);
    }
}
