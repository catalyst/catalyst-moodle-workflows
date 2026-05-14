<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Minimal placeholder plugin used only for PHPUnit snapshot creation.
 *
 * This plugin is installed into Moodle during snapshot builds so that
 * moodle-plugin-ci has a valid plugin target. It has no functionality.
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_snapshottest';
$plugin->version   = 2026010100;
$plugin->requires  = 2021051700; // Moodle 3.11 — low enough to work on all supported branches.
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';
