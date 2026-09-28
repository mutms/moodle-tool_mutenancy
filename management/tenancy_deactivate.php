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
 * Multi-tenancy de-activation.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutenancy\local\tenancy;
use tool_mulib\muform\handler;

/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var moodle_database $DB */


require(__DIR__ . '/../../../../config.php');

require_login();
$syscontext = context_system::instance();
require_capability('moodle/site:config', $syscontext);

$PAGE->set_url('/admin/tool/mutenancy/management/tenancy_deactivate.php');
$PAGE->set_context($syscontext);
$title = get_string('tenancy_deactivate', 'tool_mutenancy');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$handler = handler::from_request();

$returnurl = new \core\url('/admin/tool/mutenancy/index.php');

$tenantcount = $DB->count_records('tool_mutenancy_tenant', []);

if (!tenancy::is_active() || $tenantcount) {
    throw new \core\exception\invalid_parameter_exception('Multi-tenancy is not active');
}

$form = new \tool_mutenancy\local\form\tenancy_deactivate($PAGE->url, []);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    tenancy::deactivate();
    $handler->submitted($returnurl);
}

$handler->render($form);
