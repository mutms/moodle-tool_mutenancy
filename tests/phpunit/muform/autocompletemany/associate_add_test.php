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

use tool_mutenancy\muform\autocompletemany\associate_add;

/**
 * Associate users autocomplete source tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\muform\autocompletemany\associate_add
 */
final class associate_add_test extends \advanced_testcase {
    public function test_source(): void {
        $this->resetAfterTest();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $syscontext = \context_system::instance();
        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/mutenancy:view', CAP_ALLOW, $roleid, $syscontext->id);
        assign_capability('moodle/cohort:assign', CAP_ALLOW, $roleid, $syscontext->id);

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenant3 = $generator->create_tenant();
        $categorycontext = \context_coursecat::instance($tenant1->categoryid);

        $cohort1 = $this->getDataGenerator()->create_cohort(['contextid' => $categorycontext->id]);
        \tool_mutenancy\local\tenant::update((object)['id' => $tenant1->id, 'assoccohortid' => $cohort1->id]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['contextid' => $categorycontext->id, 'component' => 'core_bc']);
        \tool_mutenancy\local\tenant::update((object)['id' => $tenant2->id, 'assoccohortid' => $cohort2->id]);

        $manager = $this->getDataGenerator()->create_user();
        role_assign($roleid, $manager->id, $categorycontext->id);
        role_assign($roleid, $manager->id, \context_tenant::instance($tenant1->id)->id);

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'First', 'lastname' => 'User']);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Second', 'lastname' => 'User']);
        $user3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'firstname' => 'Third', 'lastname' => 'User']);
        cohort_add_member($cohort1->id, $user2->id);

        $this->setUser($manager);
        $source = new associate_add((int)$tenant1->id);
        $result = $source->search('User', 50, []);
        $this->assertArrayHasKey((string)$user1->id, $result);
        $this->assertArrayNotHasKey((string)$user2->id, $result);
        $this->assertArrayNotHasKey((string)$user3->id, $result);
        $labels = $source->labels([(string)$user1->id, (string)$user2->id, (string)$user3->id]);
        $this->assertSame([(string)$user1->id], array_map('strval', array_keys($labels)));

        // Component cohorts and tenants without associated cohort are refused.
        $this->setAdminUser();
        try {
            new associate_add((int)$tenant2->id);
            $this->fail('Exception expected');
        } catch (\core\exception\invalid_parameter_exception $e) {
            $this->assertStringContainsString('component', $e->getMessage());
        }
        $this->expectException(\core\exception\invalid_parameter_exception::class);
        new associate_add((int)$tenant3->id);
    }
}
