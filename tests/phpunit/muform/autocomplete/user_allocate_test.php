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

namespace tool_mutenancy\phpunit\muform\autocomplete;

use tool_mutenancy\muform\autocomplete\user_allocate;

/**
 * User allocation autocomplete source tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\muform\autocomplete\user_allocate
 */
final class user_allocate_test extends \advanced_testcase {
    public function test_source(): void {
        $this->resetAfterTest();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $syscontext = \context_system::instance();
        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/mutenancy:allocate', CAP_ALLOW, $roleid, $syscontext->id);
        $manager = $this->getDataGenerator()->create_user();
        role_assign($roleid, $manager->id, $syscontext);

        $tenant1 = $generator->create_tenant(['name' => 'First tenant', 'idnumber' => 'ten1']);
        $tenant2 = $generator->create_tenant(['name' => 'Second tenant', 'idnumber' => 'ten2']);
        $tenant3 = $generator->create_tenant(['name' => 'Third tenant', 'idnumber' => 'ten3']);
        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        $this->setUser($manager);
        $source = new user_allocate((int)$user0->id);
        $this->assertSame([(int)$user0->id], $source->get_args());
        $expected = [$tenant1->id => 'First tenant', $tenant2->id => 'Second tenant', $tenant3->id => 'Third tenant'];
        $this->assertSame($expected, $source->search('', 50));

        $source = new user_allocate((int)$user1->id);
        $this->assertSame([$tenant2->id => 'Second tenant', $tenant3->id => 'Third tenant'], $source->search('', 50));
        $this->assertSame([$tenant2->id => 'Second tenant'], $source->search('Second', 50));
        $this->assertSame([$tenant2->id => 'Second tenant'], $source->search('2', 50));
        $this->assertNull($source->search('', 1));
        // The current tenant is a valid value.
        $this->assertSame('First tenant', $source->label((string)$tenant1->id));
        $this->assertNull($source->label('-1'));

        $this->setUser($user0);
        $this->expectException(\required_capability_exception::class);
        new user_allocate((int)$user0->id);
    }
}
