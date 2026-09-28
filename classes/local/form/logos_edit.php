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

use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\filemanager;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;
use tool_mutenancy\local\config;

/**
 * Tenant appearance edit form.
 *
 * @package     tool_mutenancy
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class logos_edit extends form {
    #[\Override]
    protected function definition(): void {
        $tenant = $this->get_extra_data()['tenant'];

        foreach (['logo', 'logocompact', 'favicon'] as $name) {
            $options = ($name === 'favicon') ? self::get_favicon_options() : self::get_logo_options();

            $override = new checkbox($name . '_override', get_string($name, 'core_admin'), get_string('config_override', 'tool_mutenancy'));
            $override->set_default(config::is_overridden($tenant->id, 'core_admin', $name) ? 1 : 0);
            $this->add($override);

            $this->add(new filemanager($name, get_string($name, 'core_admin'), 1, $options['accepted_types']));

            $html = markdown_to_html(get_string($name . '_desc', 'core_admin')) . \html_writer::div('core_admin | ' . $name, 'small text-muted');
            $this->add(new inforawhtml($name . '_desc', '', $html));

            $this->get_display_manager()->hide_if($name, $name . '_override', 'notchecked');
        }

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('update')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    /**
     * File manager options for logos.
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

    /**
     * File manager options for favicons.
     *
     * @return array
     */
    public static function get_favicon_options(): array {
        return [
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' => ['.jpg', '.png', '.ico', '.gif'], // No SVG for security reasons!
        ];
    }
}
