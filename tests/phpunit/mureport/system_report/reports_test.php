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
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_mutenancy\phpunit\mureport\system_report;

use tool_mutenancy\local\tenancy;
use tool_mutenancy\mureport\system_report\tenants;
use tool_mutenancy\mureport\system_report\users;
use tool_mureport\mureport\request;
use tool_mureport\mureport\system_report;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tenant reports tests.
 *
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(tenants::class)]
#[CoversClass(users::class)]
#[CoversClass(\tool_mutenancy\mureport\entity\tenant::class)]
#[Group('MuTMS')]
final class reports_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Returns exported rows.
     *
     * @param system_report $report
     * @param request $request
     * @return array
     */
    private function get_rows(system_report $report, request $request = new request([])): array {
        $report->require_access();
        return $report->get_query()->get_page($request)->export_rows();
    }

    /**
     * Returns plain texts of a column.
     *
     * @param array $rows
     * @param string $column
     * @return string[]
     */
    private function get_texts(array $rows, string $column): array {
        return array_map(fn(array $row): string => strip_tags($row['cells'][$column]), $rows);
    }

    public function test_tenants(): void {
        global $DB;
        tenancy::activate();
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $tenant1 = $generator->create_tenant(['name' => 'Alpha', 'idnumber' => 'ta', 'memberlimit' => 10]);
        $tenant2 = $generator->create_tenant(['name' => 'Beta', 'idnumber' => 'tb']);
        $tenant3 = $generator->create_tenant(['name' => 'Gamma', 'idnumber' => 'tg']);
        $DB->set_field('tool_mutenancy_tenant', 'archived', 1, ['id' => $tenant3->id]);
        $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        $this->setAdminUser();
        $report = tenants::make_instance();
        $rows = $this->get_rows($report);
        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $this->get_texts($rows, 'tenant:name'));
        $this->assertStringContainsString('/admin/tool/mutenancy/tenant.php?id=' . $tenant1->id, $rows[0]['cells']['tenant:name']);
        $this->assertSame('1 / 10', $rows[0]['cells']['tenant:memberlimit']);
        $this->assertSame('', $rows[1]['cells']['tenant:memberlimit']);
        $this->assertSame('1', strip_tags($rows[0]['cells']['tenant:usercount']));
        $this->assertSame([false, false, true], array_column($rows, 'muted'));
        $this->assertSame('', $rows[2]['cells']['tenant:loginurl']);
        $this->assertSame(['update'], array_column($rows[0]['actions']['tenant:actions'], 'name'));
        $rows = $this->get_rows($report, new request(['tenant:archived' => ['value' => 0]]));
        $this->assertSame(['Alpha', 'Beta'], $this->get_texts($rows, 'tenant:name'));
        $rows = $this->get_rows($report, new request([], null, 0, null, 'tb'));
        $this->assertSame(['Beta'], $this->get_texts($rows, 'tenant:name'));
        $this->assertSame($tenant2->id, $DB->get_field('tool_mutenancy_tenant', 'id', ['idnumber' => 'tb']));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\core\exception\required_capability_exception::class);
        $this->get_rows(tenants::make_instance());
    }

    public function test_users(): void {
        tenancy::activate();
        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $cohort = $this->getDataGenerator()->create_cohort(['idnumber' => 'assoc']);
        $tenant = $generator->create_tenant(['name' => 'Alpha', 'assoccohort' => 'assoc']);
        $other = $generator->create_tenant(['name' => 'Beta']);
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Alpha', 'tenantid' => $tenant->id]);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Bob', 'lastname' => 'Beta', 'tenantid' => $tenant->id,
            'suspended' => 1]);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Cyril', 'lastname' => 'Gamma']);
        $this->getDataGenerator()->create_user(['firstname' => 'Dan', 'lastname' => 'Delta', 'tenantid' => $other->id]);
        cohort_add_member($cohort->id, $user3->id);

        $this->setAdminUser();
        $report = users::make_instance((int)$tenant->id);
        $rows = $this->get_rows($report);
        $this->assertSame(['Anna Alpha', 'Bob Beta', 'Cyril Gamma'], $this->get_texts($rows, 'auser:fullname'));
        $this->assertSame(['Yes', 'Yes', 'No'], $this->get_texts($rows, 'auser:tenantmember'));
        $this->assertSame([false, true, false], array_column($rows, 'muted'));
        $actions = fn(array $row): array => array_column($row['actions']['auser:actions'], 'name');
        $this->assertSame(['update', 'suspend', 'allocate', 'delete'], $actions($rows[0]));
        $this->assertSame(['update', 'unsuspend', 'allocate', 'delete'], $actions($rows[1]));
        $this->assertSame(['associateremove', 'allocate'], $actions($rows[2]));
        $rows = $this->get_rows($report, new request(['auser:tenantmember' => ['value' => 0]]));
        $this->assertSame(['Cyril Gamma'], $this->get_texts($rows, 'auser:fullname'));

        $rows = $this->get_rows(users::make_instance((int)$other->id));
        $this->assertSame(['Dan Delta'], $this->get_texts($rows, 'auser:fullname'));

        $viewer = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mutenancy:view', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $viewer->id, \context_tenant::instance($tenant->id));
        $this->setUser($viewer);
        $rows = $this->get_rows(users::make_instance((int)$tenant->id));
        $this->assertSame([[], [], []], array_map(fn(array $row): array => $row['actions']['auser:actions'], $rows));

        $this->setUser($user3);
        $this->expectException(\core\exception\required_capability_exception::class);
        $this->get_rows(users::make_instance((int)$tenant->id));
    }
}
