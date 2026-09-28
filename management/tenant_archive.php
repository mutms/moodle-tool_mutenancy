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

/**
 * Archive tenant.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutenancy\local\tenancy;
use tool_mulib\muform\handler;
use tool_mutenancy\local\tenant;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

require(__DIR__ . '/../../../../config.php');

$tenantid = required_param('id', PARAM_INT);

require_login();

if (!tenancy::is_active()) {
    redirect(new \core\url('/'));
}

$tenant = $DB->get_record('tool_mutenancy_tenant', ['id' => $tenantid], '*', MUST_EXIST);

$context = context_system::instance();
require_capability('tool/mutenancy:admin', $context);

$PAGE->set_url('/admin/tool/mutenancy/management/tenant_archive.php', ['id' => $tenant->id]);
$PAGE->set_context($context);
$title = \tool_mutenancy\local\tenancy::get_tenant_string('tenant_archive');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$handler = handler::from_request();

$returnurl = new \core\url('/admin/tool/mutenancy/tenant.php', ['id' => $tenant->id]);

if ($tenant->archived) {
    redirect($returnurl);
}

$form = new \tool_mutenancy\local\form\tenant_archive($PAGE->url, [], ['tenant' => $tenant]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    tenant::archive($tenant->id);
    $handler->submitted($returnurl);
}

$handler->render($form);
