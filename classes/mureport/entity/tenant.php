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

namespace tool_mutenancy\mureport\entity;

use tool_mureport\mureport\action;
use tool_mureport\mureport\cell;
use tool_mureport\mureport\column;
use tool_mureport\mureport\entity;
use tool_mureport\mureport\export_value;
use tool_mureport\mureport\filter;
use tool_mureport\mureport\row;
use tool_mutenancy\local\tenancy;
use tool_mulib\local\sql;
use core\lang_string;
use core\url;
use stdClass;

/**
 * Tenant entity, tenants in tool_mutenancy_tenant.
 *
 * @package     tool_mutenancy
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tenant extends entity {
    #[\Override]
    public function get_default_source(): sql {
        return new sql('{tool_mutenancy_tenant}');
    }

    #[\Override]
    protected function get_default_title(): string {
        return tenancy::get_tenant_string('tenant');
    }

    #[\Override]
    public function get_context_sql(): ?sql {
        $ctx = "{$this->alias}_ctx";
        return new sql(
            "(SELECT $ctx.id FROM {context} $ctx WHERE $ctx.contextlevel = :{$ctx}level AND $ctx.instanceid = {$this->alias}.id)",
            ["{$ctx}level" => CONTEXT_TENANT]
        );
    }

    #[\Override]
    public function get_context_for_key(int|string $key): ?\core\context {
        return \context_tenant::instance((int)$key, IGNORE_MISSING) ?: null;
    }

    #[\Override]
    public function get_column_names(): array {
        return [
            'name', 'idnumber', 'id', 'category', 'sitefullname', 'siteshortname', 'usercount', 'memberlimit', 'archived',
            'loginurl', 'timecreated', 'actions',
        ];
    }

    #[\Override]
    protected function define_columns(string $selector): array {
        $alias = $this->alias;
        $column = match ($selector) {
            'id' => $this->create_id_column(),
            'name' => $this->define_name_column(),
            'idnumber' => $this->create_column('idnumber', new lang_string('tenant_idnumber', 'tool_mutenancy'), column::TYPE_TEXT)
                ->add_field('idnumber', "$alias.idnumber")
                ->set_sortable(true),
            'category' => $this->define_category_column(),
            'sitefullname', 'siteshortname' => $this->create_column(
                $selector,
                new lang_string('tenant_' . $selector, 'tool_mutenancy'),
                column::TYPE_TEXT
            )
                ->add_field($selector, "$alias.$selector")
                ->set_sortable(true),
            'usercount' => $this->define_usercount_column(),
            'memberlimit' => $this->define_memberlimit_column(),
            'archived' => $this->create_column(
                'archived',
                new lang_string('tenant_archived', 'tool_mutenancy'),
                column::TYPE_BOOLEAN
            )
                ->add_field('archived', "$alias.archived")
                ->set_sortable(true),
            'loginurl' => $this->define_loginurl_column(),
            'timecreated' => $this->create_column(
                'timecreated',
                new lang_string('timecreated', 'core_reportbuilder'),
                column::TYPE_TIMESTAMP
            )
                ->add_field('timecreated', "$alias.timecreated")
                ->set_sortable(true),
            'actions' => $this->define_actions_column(),
            default => null,
        };
        return $column ? [$column] : [];
    }

    #[\Override]
    public function get_filter_names(): array {
        return ['name', 'idnumber', 'id', 'archived'];
    }

    #[\Override]
    protected function define_filters(string $selector): array {
        $alias = $this->alias;
        $filter = match ($selector) {
            'id' => $this->create_id_filter(),
            'name' => new filter\text($alias, 'name', new lang_string('tenant_name', 'tool_mutenancy'), "$alias.name"),
            'idnumber' => new filter\text(
                $alias,
                'idnumber',
                new lang_string('tenant_idnumber', 'tool_mutenancy'),
                "$alias.idnumber"
            ),
            'archived' => new filter\boolean(
                $alias,
                'archived',
                new lang_string('tenant_archived', 'tool_mutenancy'),
                "$alias.archived"
            ),
            default => null,
        };
        return $filter ? [$filter] : [];
    }

    /**
     * Define tenant name column, linked to the tenant for tenant viewers.
     *
     * @return column
     */
    private function define_name_column(): column {
        $alias = $this->alias;
        $syscontext = \core\context\system::instance();
        $name = fn(stdClass $fields, bool $escape): string
            => format_string($fields->name, true, ['context' => $syscontext, 'escape' => $escape]);
        return $this->create_column('name', new lang_string('tenant_name', 'tool_mutenancy'), column::TYPE_TEXT)
            ->add_field('name', "$alias.name")
            ->add_field('id', "$alias.id")
            ->set_sortable(true)
            ->add_formatter(
                'name',
                fn(cell $cell): string => $name($cell->get_fields(), true),
                fn(cell $cell): export_value => export_value::text($name($cell->get_fields(), false))
            )
            ->add_formatter(
                'link',
                function (cell $cell) use ($name): string {
                    $fields = $cell->get_fields();
                    if (!has_capability('tool/mutenancy:view', \context_tenant::instance($fields->id))) {
                        return $name($fields, true);
                    }
                    $url = new url('/admin/tool/mutenancy/tenant.php', ['id' => $fields->id]);
                    return \html_writer::link($url, $name($fields, true));
                },
                fn(cell $cell): export_value => export_value::text($name($cell->get_fields(), false))
            )
            ->set_default_formatter('link');
    }

    /**
     * Define tenant category column, linked to category management or the course list.
     *
     * @return column
     */
    private function define_category_column(): column {
        $alias = $this->alias;
        $name = function (stdClass $fields, bool $escape): string {
            if ($fields->categoryname === null) {
                return '';
            }
            $context = \context_coursecat::instance($fields->categoryid);
            return format_string($fields->categoryname, true, ['context' => $context, 'escape' => $escape]);
        };
        return $this->create_column('category', new lang_string('tenant_category', 'tool_mutenancy'), column::TYPE_TEXT)
            ->add_field('categoryname', "(SELECT tcc.name FROM {course_categories} tcc WHERE tcc.id = $alias.categoryid)")
            ->add_field('categoryid', "$alias.categoryid")
            ->set_sortable(true)
            ->add_formatter(
                'link',
                function (cell $cell) use ($name): string {
                    $fields = $cell->get_fields();
                    if ($fields->categoryname === null) {
                        return '';
                    }
                    $context = \context_coursecat::instance($fields->categoryid);
                    $url = null;
                    if (has_capability('moodle/category:manage', $context)) {
                        $url = new url('/course/management.php', ['categoryid' => $fields->categoryid]);
                    } else if (has_capability('moodle/category:viewcourselist', $context)) {
                        $url = new url('/course/index.php', ['categoryid' => $fields->categoryid]);
                    }
                    return $url ? \html_writer::link($url, $name($fields, true)) : $name($fields, true);
                },
                fn(cell $cell): export_value => export_value::text($name($cell->get_fields(), false))
            )
            ->set_default_formatter('link');
    }

    /**
     * Define number of members and associated users column, linked to tenant users.
     *
     * @return column
     */
    private function define_usercount_column(): column {
        $alias = $this->alias;
        return $this->create_column('usercount', new lang_string('tenant_users', 'tool_mutenancy'), column::TYPE_INTEGER)
            ->add_field('usercount', "(SELECT COUNT('x')
                                         FROM {user} tuser
                                    LEFT JOIN {cohort_members} tcm ON tcm.cohortid = $alias.assoccohortid AND tcm.userid = tuser.id
                                        WHERE (tuser.deleted = 0 AND tuser.tenantid IS NULL AND tcm.id IS NOT NULL)
                                              OR (tuser.deleted = 0 AND tuser.tenantid = $alias.id))")
            ->add_field('id', "$alias.id")
            ->set_sortable(true)
            ->add_formatter('link', function (cell $cell): string {
                $fields = $cell->get_fields();
                $count = (int)$fields->usercount;
                if (!has_capability('tool/mutenancy:view', \context_tenant::instance($fields->id))) {
                    return (string)$count;
                }
                return \html_writer::link(new url('/admin/tool/mutenancy/tenant_users.php', ['id' => $fields->id]), $count);
            }, fn(cell $cell): export_value => export_value::number((int)$cell->get_value()))
            ->set_default_formatter('link');
    }

    /**
     * Define members limit column, shown as members / limit.
     *
     * @return column
     */
    private function define_memberlimit_column(): column {
        $alias = $this->alias;
        return $this->create_column('memberlimit', new lang_string('tenant_memberlimit', 'tool_mutenancy'), column::TYPE_INTEGER)
            ->add_field('memberlimit', "$alias.memberlimit")
            ->add_field('membercount', "(SELECT COUNT('x') FROM {user} tm WHERE tm.deleted = 0 AND tm.tenantid = $alias.id)")
            ->set_sortable(true)
            ->add_formatter('usage', function (cell $cell): string {
                $fields = $cell->get_fields();
                if (!$fields->memberlimit) {
                    return '';
                }
                return (int)$fields->membercount . ' / ' . (int)$fields->memberlimit;
            }, fn(cell $cell): export_value
                => $cell->get_value() ? export_value::number((int)$cell->get_value()) : export_value::empty())
            ->set_default_formatter('usage');
    }

    /**
     * Define login URL column with a copy to clipboard button.
     *
     * @return column
     */
    private function define_loginurl_column(): column {
        $alias = $this->alias;
        $loginurl = function (stdClass $fields): ?url {
            if ($fields->archived || $fields->idnumber === null) {
                return null;
            }
            return \tool_mutenancy\local\tenant::get_login_url((int)$fields->id) ?: null;
        };
        return $this->create_column('loginurl', new lang_string('tenant_loginurl', 'tool_mutenancy'), column::TYPE_TEXT)
            ->add_field('id', "$alias.id")
            ->add_field('idnumber', "$alias.idnumber")
            ->add_field('archived', "$alias.archived")
            ->add_formatter(
                'clipboard',
                function (cell $cell) use ($loginurl): string {
                    global $OUTPUT;
                    $url = $loginurl($cell->get_fields());
                    if ($url === null) {
                        return '';
                    }
                    $clipboard = new \tool_mulib\output\url_clipboard($url, get_string('tenant_loginurl_copy', 'tool_mutenancy'));
                    return $OUTPUT->render($clipboard);
                },
                function (cell $cell) use ($loginurl): export_value {
                    $url = $loginurl($cell->get_fields());
                    return $url === null ? export_value::empty() : export_value::link($url, $url->out(false));
                }
            )
            ->set_default_formatter('clipboard');
    }

    /**
     * Define tenant actions column.
     *
     * @return column
     */
    private function define_actions_column(): column {
        $alias = $this->alias;
        $column = $this->create_actions_column('actions', new lang_string('actions'))
            ->add_field('id', "$alias.id");
        $column->add_action(new action('update', new lang_string('edit'), 't/edit'))
            ->set_available(fn(stdClass $fields, row $row): bool
                => has_capability('tool/mutenancy:admin', \context_tenant::instance($fields->id)))
            ->set_url(fn(stdClass $fields): url => new url(
                '/admin/tool/mutenancy/management/tenant_update.php',
                ['id' => $fields->id]
            ))
            ->set_dialog('lg');
        return $column;
    }
}
