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

use tool_mutenancy\muform\autocomplete\tenant_assoccohortid;

/**
 * Associated cohort autocomplete source tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\muform\autocomplete\tenant_assoccohortid
 */
final class tenant_assoccohortid_test extends \advanced_testcase {
    public function test_source(): void {
        $this->resetAfterTest();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $syscontext = \context_system::instance();
        $roleid = create_role('man', 'man', 'man');
        assign_capability('tool/mutenancy:admin', CAP_ALLOW, $roleid, $syscontext->id);

        $tenant1 = $generator->create_tenant();
        $tenant2 = $generator->create_tenant();
        $tenantcontext2 = \context_tenant::instance($tenant2->id);

        $manager = $this->getDataGenerator()->create_user();
        role_assign($roleid, $manager->id, \context_tenant::instance($tenant1->id)->id);

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'First kohort', 'idnumber' => 'koh1']);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Second kohort', 'idnumber' => 'koh2']);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Third kohort', 'idnumber' => 'koh3', 'visible' => 0]);
        $cohort4 = $this->getDataGenerator()->create_cohort(['name' => 'Fourth kohort', 'contextid' => $tenantcontext2->id]);

        $this->setUser($manager);
        $source = new tenant_assoccohortid((int)$tenant1->id);
        $this->assertSame([(int)$tenant1->id], $source->get_args());
        $this->assertSame([$cohort1->id => 'First kohort', $cohort2->id => 'Second kohort'], $source->search('', 50));
        $this->assertSame([$cohort1->id => 'First kohort'], $source->search('First', 50));
        $this->assertSame([$cohort2->id => 'Second kohort'], $source->search('koh2', 50));
        $this->assertNull($source->search('', 1));
        $this->assertNull($source->validate((string)$cohort1->id));
        $this->assertSame('Error', $source->validate((string)$cohort3->id));
        $this->assertSame('Error', $source->validate((string)$cohort4->id));
        $this->assertSame('First kohort', $source->label((string)$cohort1->id));
        $this->assertNull($source->label((string)$cohort4->id));

        $this->setAdminUser();
        $source = new tenant_assoccohortid(0);
        $this->assertCount(3, $source->search('', 50));
        $this->assertNull($source->validate((string)$cohort3->id));
        $this->assertSame('Error', $source->validate((string)$cohort4->id));

        // Existing association is always valid.
        $tenant2 = \tool_mutenancy\local\tenant::update((object)['id' => $tenant2->id, 'assoccohortid' => $cohort4->id]);
        $source = new tenant_assoccohortid((int)$tenant2->id);
        $this->assertNull($source->validate((string)$cohort4->id));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        new tenant_assoccohortid((int)$tenant1->id);
    }
}
