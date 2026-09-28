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
 * Update auth editing favicon.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mutenancy\local\tenancy;
use tool_mulib\muform\handler;
use tool_mutenancy\local\config;

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

$syscontext = context_system::instance();
$context = context_tenant::instance($tenant->id);
require_capability('tool/mutenancy:configauth', $context);

$PAGE->set_url('/admin/tool/mutenancy/management/auth_edit.php', ['id' => $tenant->id]);
$PAGE->set_context($context);
$title = get_string('auth_edit', 'tool_mutenancy');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$handler = handler::from_request();

$returnurl = new \core\url('/admin/tool/mutenancy/tenant_auth.php', ['id' => $tenant->id]);

$form = new \tool_mutenancy\local\form\auth_edit($PAGE->url, [], ['tenant' => $tenant]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    if (has_capability('moodle/site:config', $syscontext)) {
        if (isset($data->registerauth_override)) {
            if ($data->registerauth_override) {
                config::override($tenant->id, 'registerauth', (string)$data->registerauth, 'core');
            } else {
                config::override($tenant->id, 'registerauth', null, 'core');
            }
        }
    }

    if (isset($data->showloginform_override)) {
        if ($data->showloginform_override) {
            config::override($tenant->id, 'showloginform', (string)$data->showloginform, 'core');
        } else {
            config::override($tenant->id, 'showloginform', null, 'core');
        }
    }

    if (isset($data->allowemailaddresses_override)) {
        if ($data->allowemailaddresses_override) {
            $data->allowemailaddresses = trim($data->allowemailaddresses);
            config::override($tenant->id, 'allowemailaddresses', $data->allowemailaddresses, 'core');
        } else {
            config::override($tenant->id, 'allowemailaddresses', null, 'core');
        }
    }

    if (isset($data->denyemailaddresses_override)) {
        if ($data->denyemailaddresses_override) {
            $data->denyemailaddresses = trim($data->denyemailaddresses);
            config::override($tenant->id, 'denyemailaddresses', $data->denyemailaddresses, 'core');
        } else {
            config::override($tenant->id, 'denyemailaddresses', null, 'core');
        }
    }

    if (isset($data->auth_instructions_override)) {
        if ($data->auth_instructions_override) {
            $text = clean_text($data->auth_instructions);
            if (trim($text) === '') {
                $text = '';
            }
            config::override($tenant->id, 'auth_instructions', $text, 'core');
        } else {
            config::override($tenant->id, 'auth_instructions', null, 'core');
        }
    }

    \tool_mutenancy\event\auth_updated::create_from_tenant($tenant)->trigger();

    $handler->submitted($returnurl);
}

$handler->render($form);
