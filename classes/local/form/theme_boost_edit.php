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
use tool_mulib\muform\element\filemanager;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\text;
use tool_mulib\muform\element\textarea;
use tool_mulib\muform\form;
use tool_mutenancy\local\appearance;
use tool_mutenancy\local\config;

/**
 * Tenant boost theme edit form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class theme_boost_edit extends form {
    #[\Override]
    protected function definition(): void {
        $tenant = $this->get_extra_data()['tenant'];
        $syscontext = \context_system::instance();

        $choices = [];
        $files = get_file_storage()->get_area_files($syscontext->id, 'theme_boost', 'preset', 0, 'itemid, filepath, filename', false);
        foreach ($files as $file) {
            $choices[$file->get_filename()] = $file->get_filename();
        }
        // These are the built-in presets.
        $choices['default.scss'] = 'default.scss';
        $choices['plain.scss'] = 'plain.scss';
        $default = (string)get_config('theme_boost', 'preset');
        $preset = new select('preset', get_string('preset', 'theme_boost'), $choices);
        $label = get_string('preset', 'theme_boost');
        $this->add_override($tenant, $preset, $label, $this->get_default_string($default), isset($choices[$default]) ? $default : null, 'preset_desc');

        foreach (['backgroundimage', 'loginbackgroundimage'] as $name) {
            $element = new filemanager($name, get_string($name, 'theme_boost'), 1, self::get_logo_options()['accepted_types']);
            $this->add_override($tenant, $element, get_string($name, 'theme_boost'), null, null, $name . '_desc');
        }

        $default = (string)get_config('theme_boost', 'brandcolor');
        $brandcolor = new text('brandcolor', get_string('brandcolor', 'theme_boost'), ['type' => 'rawtext', 'width' => 'small']);
        $label = get_string('brandcolor', 'theme_boost');
        $this->add_override($tenant, $brandcolor, $label, $this->get_default_string($default), $default, 'brandcolor_desc');

        if (has_capability('moodle/site:config', $syscontext)) {
            foreach (['scsspre' => 'rawscsspre', 'scss' => 'rawscss'] as $name => $label) {
                $element = new textarea($name, get_string($label, 'theme_boost'), ['type' => 'rawtext', 'rows' => 6]);
                $this->add_override($tenant, $element, get_string($label, 'theme_boost'), null, (string)get_config('theme_boost', $name), $label . '_desc');
            }
        }

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('update')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    /**
     * Add override checkbox, the value element shown only when overriding and the setting description.
     *
     * @param \stdClass $tenant
     * @param element $element value element named after the theme_boost setting
     * @param string $label setting name shown to users
     * @param string|null $defaultstr site default shown to users, null if not shown
     * @param string|null $default site default value, null for files
     * @param string $description theme_boost description string identifier
     */
    private function add_override(
        \stdClass $tenant,
        element $element,
        string $label,
        ?string $defaultstr,
        ?string $default,
        string $description
    ): void {
        $name = $element->get_name();
        $overridden = config::is_overridden($tenant->id, 'theme_boost', $name);

        $text = ($defaultstr === null)
            ? get_string('config_override', 'tool_mutenancy')
            : get_string('config_override_value', 'tool_mutenancy', $defaultstr);
        $override = new checkbox($name . '_override', $label, $text);
        $override->set_default($overridden ? 1 : 0);
        $this->add($override);

        if (!$element instanceof filemanager) {
            $element->set_default($overridden ? (string)config::get($tenant->id, 'theme_boost', $name) : $default);
        }
        $this->add($element);

        $html = markdown_to_html(get_string($description, 'theme_boost')) . \html_writer::div('theme_boost | ' . $name, 'small text-muted');
        $this->add(new inforawhtml($name . '_desc', '', $html));

        $this->get_display_manager()->hide_if($name, $name . '_override', 'notchecked');
    }

    /**
     * Site default shown to users.
     *
     * @param string $default
     * @return string
     */
    private function get_default_string(string $default): string {
        return ($default === '') ? get_string('emptysettingvalue', 'core_admin') : $default;
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        if ($data['brandcolor_override'] && $data['brandcolor'] !== '') {
            if (!appearance::is_valid_color($data['brandcolor'])) {
                $allerrors['brandcolor'][] = get_string('error');
            }
        }
    }

    /**
     * File manager options for boost.
     *
     * @return array
     */
    public static function get_logo_options(): array {
        return [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['.jpg', '.png', '.gif'], // No SVG for security reasons!
        ];
    }
}
