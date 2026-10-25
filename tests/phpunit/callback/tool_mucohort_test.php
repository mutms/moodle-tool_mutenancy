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

namespace tool_mutenancy\phpunit\callback;

use tool_mutenancy\callback\tool_mucohort;
use tool_mutenancy\local\tenancy;
use tool_mutenancy\local\tenant;

/**
 * Tests of tool_mucohort hook callbacks.
 *
 * @group      MuTMS
 * @package    tool_mutenancy
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_mutenancy\callback\tool_mucohort
 */
final class tool_mucohort_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        if (!\tool_mulib\local\mulib::is_mucohort_available()) {
            $this->markTestSkipped('tool_mucohort is not installed');
        }
    }

    /**
     * Returns usage items as "section: name|url|visible" strings.
     *
     * @param \stdClass $cohort
     * @return string[]
     */
    private function get_usage(\stdClass $cohort): array {
        $hook = new \tool_mucohort\hook\cohort_usage($cohort);
        tool_mucohort::cohort_usage($hook);
        $result = [];
        foreach ($hook->get_sections() as $section => $items) {
            foreach ($items as $item) {
                $result[] = $section . ': ' . $item->name . '|' . ($item->url ? 'url' : '') . '|' . ($item->visible ? 'visible' : 'hidden');
            }
        }
        return $result;
    }

    public function test_cohort_owner(): void {
        global $DB;

        tenancy::activate();
        $tenant = tenant::create((object)['name' => 'Tenant <b>A</b>', 'idnumber' => 'ta']);
        $cohort = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);

        // The callback is registered in db/hooks.php.
        $this->setAdminUser();
        $hook = new \tool_mucohort\hook\cohort_owner($cohort);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertSame('Tenant cohort of Tenant A', $hook->get_description());
        $this->assertStringContainsString('/admin/tool/mutenancy/tenant.php?id=' . $tenant->id, $hook->get_url()->out(false));

        $this->setUser($this->getDataGenerator()->create_user());
        $hook = new \tool_mucohort\hook\cohort_owner($cohort);
        tool_mucohort::cohort_owner($hook);
        $this->assertSame('Tenant cohort of Tenant A', $hook->get_description());
        $this->assertNull($hook->get_url());

        $hook = new \tool_mucohort\hook\cohort_owner($this->getDataGenerator()->create_cohort());
        tool_mucohort::cohort_owner($hook);
        $this->assertNull($hook->get_description());
    }

    public function test_cohort_usage(): void {
        global $DB;

        tenancy::activate();
        $assoc = $this->getDataGenerator()->create_cohort();
        $tenant1 = tenant::create((object)['name' => 'Tenant 1', 'idnumber' => 't1', 'assoccohortid' => $assoc->id]);
        $tenant2 = tenant::create((object)['name' => 'Tenant 2', 'idnumber' => 't2', 'assoccohortid' => $assoc->id]);
        $cohort1 = $DB->get_record('cohort', ['id' => $tenant1->cohortid], '*', MUST_EXIST);

        $tenantsection = get_string('cohort_usage_tenant', 'tool_mutenancy');
        $assocsection = get_string('cohort_usage_associate', 'tool_mutenancy');
        $this->setAdminUser();
        $this->assertSame([$tenantsection . ': Tenant 1|url|visible'], $this->get_usage($cohort1));
        $expected = [$assocsection . ': Tenant 1|url|visible', $assocsection . ': Tenant 2|url|visible'];
        $this->assertSame($expected, $this->get_usage($assoc));
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame([$assocsection . ': Tenant 1||hidden', $assocsection . ': Tenant 2||hidden'], $this->get_usage($assoc));
    }
}
