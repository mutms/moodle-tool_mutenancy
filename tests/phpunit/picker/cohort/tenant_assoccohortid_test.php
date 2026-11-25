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

namespace tool_mutenancy\phpunit\picker\cohort;

use tool_mutenancy\picker\cohort\tenant_assoccohortid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Associated cohort picker source tests.
 *
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\tool_mutenancy\picker\cohort\tenant_assoccohortid::class)]
#[Group('MuTMS')]
final class tenant_assoccohortid_test extends \advanced_testcase {
    /**
     * Cohorts offered by the source.
     *
     * @param \tool_mulib\picker\cohort\base $source
     * @param string $query
     * @param string[] $exclude
     * @return string[] cohort names indexed with cohort ids, in the order of names
     */
    private function search(\tool_mulib\picker\cohort\base $source, string $query, array $exclude = []): array {
        return array_column($source->search($query, $exclude)['cohorts'], 'name', 'id');
    }

    /**
     * Values the source does not accept.
     *
     * @param \tool_mulib\picker\cohort\base $source
     * @param string[] $values
     * @return string[]
     */
    private function refused(\tool_mulib\picker\cohort\base $source, array $values): array {
        return array_values(array_diff($values, array_map('strval', array_keys($source->labels($values)))));
    }

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
        $this->assertSame([$cohort1->id => 'First kohort', $cohort2->id => 'Second kohort'], $this->search($source, ''));
        $this->assertSame([$cohort1->id => 'First kohort'], $this->search($source, 'First'));
        $this->assertSame([$cohort2->id => 'Second kohort'], $this->search($source, 'koh2'));
        $value = (string)$cohort1->id;
        $this->assertSame([], $this->refused($source, [$value]));
        $value = (string)$cohort3->id;
        $this->assertSame([$value], $this->refused($source, [$value]));
        $value = (string)$cohort4->id;
        $this->assertSame([$value], $this->refused($source, [$value]));
        $value = (string)$cohort1->id;
        $this->assertSame('First kohort', $source->labels([$value])[$value] ?? null);
        $value = (string)$cohort4->id;
        $this->assertNull($source->labels([$value])[$value] ?? null);

        $this->setAdminUser();
        $source = new tenant_assoccohortid(0);
        $this->assertCount(3, $this->search($source, ''));
        $value = (string)$cohort3->id;
        $this->assertSame([], $this->refused($source, [$value]));
        $value = (string)$cohort4->id;
        $this->assertSame([$value], $this->refused($source, [$value]));

        // Existing association is always valid.
        $tenant2 = \tool_mutenancy\local\tenant::update((object)['id' => $tenant2->id, 'assoccohortid' => $cohort4->id]);
        $source = new tenant_assoccohortid((int)$tenant2->id);
        $value = (string)$cohort4->id;
        $this->assertSame([], $this->refused($source, [$value]));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        new tenant_assoccohortid((int)$tenant1->id);
    }
}
