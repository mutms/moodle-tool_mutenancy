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

use tool_mureport\mureport\action;
use tool_mureport\mureport\column;
use tool_mureport\mureport\entity\user;
use tool_mureport\mureport\filter;
use tool_mureport\mureport\row;
use tool_mureport\mureport\system_report;
use tool_mutenancy\local\tenancy;
use tool_mulib\local\sql;
use core\exception\invalid_parameter_exception;
use core\lang_string;
use core\url;
use stdClass;

/**
 * Members and associated users of a tenant, for tenant viewers.
 *
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class users extends system_report {
    /**
     * Make report instance.
     *
     * @param int $tenantid
     * @return self
     */
    public static function make_instance(int $tenantid): self {
        return self::from_constraints(['tenantid' => $tenantid]);
    }

    #[\Override]
    public static function get_title(): string {
        return get_string('tenant_users', 'tool_mutenancy');
    }

    #[\Override]
    public static function is_available(): bool {
        return tenancy::is_active();
    }

    /**
     * Returns tenant of the report.
     *
     * @return stdClass
     */
    public function get_tenant(): stdClass {
        $tenant = $this->get_constraint_record('tool_mutenancy_tenant', 'tenantid');
        if (!$tenant) {
            throw new invalid_parameter_exception('Invalid tenant');
        }
        return $tenant;
    }

    #[\Override]
    public function get_context(): \core\context {
        return \context_tenant::instance($this->get_tenant()->id);
    }

    #[\Override]
    protected function check_access(): void {
        $this->require_capability('tool/mutenancy:view');
    }

    #[\Override]
    protected function define(): void {
        global $CFG;
        $this->set_main_entity(new user('auser'));
        $this->add_restriction(
            new sql('auser.deleted = 0 AND auser.id <> :tenantguestid', ['tenantguestid' => $CFG->siteguest]),
            ['auser']
        );
        $this->set_row_muted('auser.suspended = 1', ['auser']);

        $this->set_default_columns([
            'auser:fullname',
            'auser:identityfields',
            'auser:lastaccess',
            'auser:tenantmember',
            'auser:actions',
        ]);
        $this->set_default_filters([
            'auser:fullname' => ['multisearch' => true],
            'auser:firstname',
            'auser:lastname',
            'auser:username' => ['multisearch' => true],
            'auser:idnumber' => ['multisearch' => true],
            'auser:email' => ['multisearch' => true],
            'auser:department',
            'auser:institution',
            'auser:city',
            'auser:country',
            'auser:confirmed',
            'auser:suspended',
            'auser:timecreated',
            'auser:lastaccess',
            'auser:auth',
            'auser:tenantmember',
            'auser:allprofilefields',
        ]);
        $this->set_multisearch_label(new lang_string('searchusers', 'tool_mureport'));
        $this->set_default_sorting(['auser:fullname' => 'asc']);
    }

    #[\Override]
    protected function apply_constraints(): void {
        $tenant = $this->get_tenant();
        if ($tenant->assoccohortid) {
            $this->add_restriction(new sql(
                "(auser.tenantid = :tenantmemberid OR (auser.tenantid IS NULL AND EXISTS (
                    SELECT 'x'
                      FROM {cohort_members} tcm
                     WHERE tcm.cohortid = :tenantcohortid AND tcm.userid = auser.id)))",
                ['tenantmemberid' => $tenant->id, 'tenantcohortid' => $tenant->assoccohortid]
            ), ['auser']);
        } else {
            $this->add_restriction(new sql('auser.tenantid = :tenantmemberid', ['tenantmemberid' => $tenant->id]), ['auser']);
        }
    }

    #[\Override]
    public function get_column_names(): array {
        return array_merge(parent::get_column_names(), ['auser:identityfields', 'auser:tenantmember']);
    }

    #[\Override]
    public function get_filter_names(): array {
        return array_merge(parent::get_filter_names(), ['auser:tenantmember']);
    }

    #[\Override]
    protected function define_filters(string $selector): array {
        if ($selector === 'auser:tenantmember') {
            $title = new lang_string('tenant_member', 'tool_mutenancy');
            return ['auser:tenantmember' => new filter\boolean(
                'auser',
                'tenantmember',
                $title,
                'CASE WHEN auser.tenantid IS NULL THEN 0 ELSE 1 END'
            )];
        }
        return parent::define_filters($selector);
    }

    #[\Override]
    protected function define_columns(string $selector): array {
        if ($selector === 'auser:identityfields') {
            if ($this->is_inspectmode()) {
                $title = new lang_string('identityfields', 'tool_mureport');
                return [new column('auser', 'identityfields', $title, column::TYPE_PLACEHOLDER)];
            }
            $columns = [];
            foreach ($this->get_entity('auser')->get_identity_column_ids($this->get_context()) as $id) {
                $columns += parent::define_columns($id);
            }
            return $columns;
        }
        if ($selector === 'auser:tenantmember') {
            $column = new column('auser', 'tenantmember', new lang_string('tenant_member', 'tool_mutenancy'), column::TYPE_BOOLEAN);
            $column->add_field('tenantmember', 'CASE WHEN auser.tenantid IS NULL THEN 0 ELSE 1 END')->set_sortable(true);
            return ['auser:tenantmember' => $column];
        }
        $columns = parent::define_columns($selector);
        if ($selector === 'auser:fullname') {
            $columns['auser:fullname']->set_default_formatter('picturelinkstatus');
        }
        if ($selector === 'auser:actions' && !$this->is_inspectmode()) {
            $this->define_actions($columns['auser:actions']);
        }
        return $columns;
    }

    /**
     * Replace site user actions with tenant member actions.
     *
     * @param column $column
     */
    private function define_actions(column $column): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/authlib.php');
        $column->remove_all_actions();
        $column->add_field('tenantid', 'auser.tenantid')->add_field('auth', 'auser.auth')
            ->add_field('username', 'auser.username');
        $tenant = $this->get_tenant();
        $cohort = $tenant->assoccohortid ? $DB->get_record('cohort', ['id' => $tenant->assoccohortid]) : null;
        $member = function (stdClass $fields, string $capability, bool $notself = true): bool {
            global $USER;
            if (!$fields->tenantid) {
                return false;
            }
            if ($notself && (is_siteadmin($fields->id) || $fields->id == $USER->id)) {
                return false;
            }
            return has_capability($capability, \context_user::instance($fields->id));
        };
        $url = fn(string $script, array $params = []): \Closure => fn(stdClass $fields): url
            => new url("/admin/tool/mutenancy/management/$script.php", ['id' => $fields->id] + $params);

        $column->add_action(new action('update', new lang_string('edit'), 't/edit'))
            ->set_available(function (stdClass $fields) use ($member): bool {
                if (!$member($fields, 'tool/mutenancy:memberupdate')) {
                    return false;
                }
                $userauth = get_auth_plugin($fields->auth);
                return $userauth->can_edit_profile() && !$userauth->edit_profile_url();
            })
            ->set_url($url('member_update'))
            ->set_dialog(null);
        $column->add_action(new action('suspend', new lang_string('suspenduser', 'admin'), 't/show'))
            ->set_available(fn(stdClass $fields): bool => !$fields->suspended && $member($fields, 'tool/mutenancy:memberupdate'))
            ->set_url($url('member_suspend'))
            ->set_dialog('sm');
        $column->add_action(new action('unsuspend', new lang_string('unsuspenduser', 'admin'), 't/hide'))
            ->set_available(fn(stdClass $fields): bool
                => $fields->suspended && $member($fields, 'tool/mutenancy:memberupdate', false))
            ->set_url($url('member_unsuspend'))
            ->set_dialog('sm');
        $column->add_action(new action('unlock', new lang_string('unlockaccount', 'admin'), 't/unlock'))
            ->set_available(fn(stdClass $fields): bool => !$fields->suspended && !$tenant->archived
                && $member($fields, 'tool/mutenancy:memberupdate', false) && login_is_lockedout($fields))
            ->set_url($url('member_unlock'))
            ->set_dialog('sm');
        $column->add_action(new action('confirm', new lang_string('confirmaccount'), 't/check'))
            ->set_available(fn(stdClass $fields): bool => !$fields->confirmed && $member(
                $fields,
                'tool/mutenancy:memberupdate',
                false
            ))
            ->set_url($url('member_confirm'))
            ->set_dialog('sm');
        $column->add_action(new action('resend', new lang_string('resendemail'), 't/email'))
            ->set_available(fn(stdClass $fields): bool => !$fields->confirmed && !$fields->suspended && !$tenant->archived
                && $member($fields, 'tool/mutenancy:memberupdate', false))
            ->set_url($url('member_resend'))
            ->set_dialog('sm');
        $column->add_action(new action('associateremove', new lang_string('associate_remove', 'tool_mutenancy'), 'e/cancel'))
            ->set_available(function (stdClass $fields) use ($cohort): bool {
                global $DB;
                if ($fields->tenantid || !$cohort || $cohort->component) {
                    return false;
                }
                if (!has_capability('moodle/cohort:assign', \context::instance_by_id($cohort->contextid))) {
                    return false;
                }
                return $DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $fields->id]);
            })
            ->set_url($url('associate_remove', ['tenantid' => $tenant->id]))
            ->set_dialog('lg');
        $column->add_action(new action('allocate', new lang_string('user_allocate', 'tool_mutenancy'), 'i/switch'))
            ->set_available(function (stdClass $fields): bool {
                global $USER;
                if (is_siteadmin($fields->id) || $fields->id == $USER->id) {
                    return false;
                }
                return has_capability('tool/mutenancy:allocate', \core\context\system::instance());
            })
            ->set_url($url('user_allocate'))
            ->set_dialog('lg');
        $column->add_action(new action('delete', new lang_string('delete'), 't/delete'))
            ->set_available(fn(stdClass $fields): bool => $member($fields, 'tool/mutenancy:memberdelete'))
            ->set_url($url('member_delete'))
            ->set_dialog('sm')
            ->set_destructive();
    }
}
