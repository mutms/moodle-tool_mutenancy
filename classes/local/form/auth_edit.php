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

namespace tool_mutenancy\local\form;

use tool_mulib\muform\element;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\editor;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\text;
use tool_mulib\muform\form;
use tool_mutenancy\local\config;

/**
 * Tenant auth edit form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class auth_edit extends form {
    #[\Override]
    protected function definition(): void {
        $tenant = $this->get_extra_data()['tenant'];
        $syscontext = \context_system::instance();

        $disabled = get_string('disabled', 'core_admin');
        if (has_capability('moodle/site:config', $syscontext)) {
            // Adding random new accounts cannot be allowed by tenant managers!
            $default = get_config('core', 'registerauth');
            $defaultstr = $default ? get_string('pluginname', 'auth_' . $default) : $disabled;
            $options = ['' => $disabled];
            if (is_enabled_auth('email')) {
                $options['email'] = get_string('pluginname', 'auth_email');
            }
            $element = new select('registerauth', get_string('selfregistration', 'core_auth'), $options);
            $default = isset($options[$default]) ? $default : '';
            $this->add_override($tenant, $element, get_string('selfregistration', 'core_auth'), $defaultstr, $default, get_string('selfregistration_help', 'auth'));
        } else {
            if (config::is_overridden($tenant->id, 'core', 'registerauth')) {
                $auth = config::get($tenant->id, 'core', 'registerauth');
                $auth = $auth ? get_string('pluginname', 'auth_' . $auth) : $disabled;
            } else {
                $default = get_config('core', 'registerauth');
                $defaultstr = $default ? get_string('pluginname', 'auth_' . $default) : $disabled;
                $auth = get_string('config_default_value', 'tool_mutenancy', $defaultstr);
            }
            $this->add(new info('registerauth_static', get_string('selfregistration', 'core_auth'), $auth, info::PLAIN));
        }

        $default = get_config('core', 'showloginform');
        $options = ['1' => get_string('yes'), '0' => get_string('no')];
        $element = new select('showloginform', get_string('showloginform', 'core_auth'), $options);
        $defaultstr = $default ? get_string('yes') : get_string('no');
        $this->add_override($tenant, $element, get_string('showloginform', 'core_auth'), $defaultstr, (string)(int)$default, get_string('showloginform_desc', 'auth'));

        foreach (['allowemailaddresses', 'denyemailaddresses'] as $name) {
            $default = (string)get_config('core', $name);
            $defaultstr = ($default === '') ? get_string('emptysettingvalue', 'core_admin') : $default;
            $element = new text($name, get_string($name, 'core_admin'), ['width' => 'full']);
            $this->add_override($tenant, $element, get_string($name, 'core_admin'), $defaultstr, $default, get_string('config' . $name, 'core_admin'));
        }

        $default = (string)get_config('core', 'auth_instructions');
        $defaultstr = ($default === '') ? get_string('emptysettingvalue', 'core_admin') : shorten_text(html_to_text($default), 20);
        $element = new editor('auth_instructions', get_string('instructions', 'core_auth'), 0, false, ['rows' => 6]);
        $this->add_override($tenant, $element, get_string('instructions', 'core_auth'), $defaultstr, $default, get_string('authinstructions', 'core_auth'));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('update')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    /**
     * Add override checkbox, the value element shown only when overriding and the setting description.
     *
     * @param \stdClass $tenant
     * @param element $element value element named after the core setting
     * @param string $label setting name shown to users
     * @param string $defaultstr site default shown to users
     * @param string $default site default value
     * @param string $description setting description in Markdown
     */
    private function add_override(
        \stdClass $tenant,
        element $element,
        string $label,
        string $defaultstr,
        string $default,
        string $description
    ): void {
        $name = $element->get_name();
        $overridden = config::is_overridden($tenant->id, 'core', $name);

        $override = new checkbox($name . '_override', $label, get_string('config_override_value', 'tool_mutenancy', $defaultstr));
        $override->set_default($overridden ? 1 : 0);
        $this->add($override);

        $element->set_default($overridden ? (string)config::get($tenant->id, 'core', $name) : $default);
        $this->add($element);

        $html = markdown_to_html($description) . \html_writer::div(s($name), 'small text-muted');
        $this->add(new inforawhtml($name . '_desc', '', $html));

        $this->get_display_manager()->hide_if($name, $name . '_override', 'notchecked');
    }
}
