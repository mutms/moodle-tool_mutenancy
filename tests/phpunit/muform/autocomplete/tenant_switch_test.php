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

use tool_mutenancy\local\tenancy;
use tool_mutenancy\muform\autocomplete\tenant_switch;

/**
 * Tenant switching autocomplete source tests.
 *
 * @group       MuTMS
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\muform\autocomplete\tenant_switch
 */
final class tenant_switch_test extends \advanced_testcase {
    public function test_search(): void {
        $this->resetAfterTest();
        tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $cohort = $this->getDataGenerator()->create_cohort();
        $mine = $generator->create_tenant(['name' => 'Zulu mine', 'assoccohortid' => $cohort->id]);
        $alpha = $generator->create_tenant(['name' => 'Alpha']);
        $archived = $generator->create_tenant(['name' => 'Archived', 'archived' => 1]);
        for ($i = 1; $i <= 5; $i++) {
            $generator->create_tenant(['name' => 'Faculty ' . $i]);
        }

        $admin = get_admin();
        $this->setUser($admin);
        cohort_add_member($cohort->id, $admin->id);

        $source = new tenant_switch();
        $this->assertSame([], $source->get_args());

        // No tenant first, then own tenants, then the others.
        $result = $source->search('', 50);
        $this->assertSame([0, (int)$mine->id, (int)$alpha->id], array_slice(array_keys($result), 0, 3));
        $this->assertSame('No tenant', $result[0]);
        $this->assertArrayNotHasKey($archived->id, $result);
        $this->assertCount(8, $result);

        $this->assertSame([(int)$alpha->id => 'Alpha'], $source->search('ALP', 50));
        $this->assertCount(5, $source->search('faculty', 50));
        $this->assertNull($source->search('faculty', 4));

        $this->assertSame('Zulu mine', $source->label((string)$mine->id));
        $this->assertSame('No tenant', $source->label('0'));
        $this->assertNull($source->label((string)$archived->id));
        $this->assertNull($source->validate((string)$alpha->id));
    }

    public function test_access(): void {
        $this->resetAfterTest();

        $this->setAdminUser();
        try {
            new tenant_switch();
            $this->fail('Exception expected');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\core\exception\moodle_exception::class, $e);
        }

        tenancy::activate();
        new tenant_switch();

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\core\exception\moodle_exception::class);
        new tenant_switch();
    }
}
