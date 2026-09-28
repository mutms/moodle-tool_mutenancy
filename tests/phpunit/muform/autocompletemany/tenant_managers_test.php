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

namespace tool_mutenancy\phpunit\muform\autocompletemany;

use tool_mutenancy\muform\autocompletemany\tenant_managers;

/**
 * Tenant managers autocomplete source tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\muform\autocompletemany\tenant_managers
 */
final class tenant_managers_test extends \advanced_testcase {
    public function test_source(): void {
        $this->resetAfterTest();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $syscontext = \context_system::instance();
        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/mutenancy:admin', CAP_ALLOW, $roleid, $syscontext->id);

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $manager = $this->getDataGenerator()->create_user(['firstname' => 'Global', 'lastname' => 'Manager']);
        role_assign($roleid, $manager->id, \context_tenant::instance($tenant1->id)->id);

        $user0 = $this->getDataGenerator()->create_user(['firstname' => 'Global', 'lastname' => 'User']);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'firstname' => 'First', 'lastname' => 'User']);
        $user2 = $this->getDataGenerator()->create_user(
            ['tenantid' => $tenant2->id, 'firstname' => 'Second', 'lastname' => 'User']
        );
        $suspended = $this->getDataGenerator()->create_user(['firstname' => 'Suspended', 'lastname' => 'User', 'suspended' => 1]);

        $this->setUser($manager);
        $source = new tenant_managers((int)$tenant1->id);
        $this->assertSame([(int)$tenant1->id], $source->get_args());

        $result = $source->search('', 50, []);
        $this->assertArrayHasKey((string)$user0->id, $result);
        $this->assertArrayHasKey((string)$user1->id, $result);
        $this->assertArrayNotHasKey((string)$user2->id, $result);
        $this->assertSame([(string)$user1->id], array_map('strval', array_keys($source->search('First', 50, []))));
        $this->assertSame([], $source->search('First', 50, [(string)$user1->id]));

        $labels = $source->labels([(string)$user1->id, (string)$user2->id, (string)$suspended->id]);
        $this->assertSame([(string)$user1->id, (string)$suspended->id], array_map('strval', array_keys($labels)));
        // Suspended managers stay allowed, as before.
        $this->assertSame([], $source->validate([(string)$suspended->id]));

        $this->setUser($user0);
        $this->expectException(\required_capability_exception::class);
        new tenant_managers((int)$tenant1->id);
    }
}
