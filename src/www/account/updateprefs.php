<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\CookieManager;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/utils.php';

$cookie_manager = new CookieManager();
$user = UserManager::instance()->getCurrentUser();

// Validate params
session_require(array('isloggedin'=>1));

$request = HTTPRequest::instance();

$csrf = new CSRFSynchronizerToken('/account/index.php');
$csrf->check();

$user_edition_default_format = null;
$requested_default_format    = 'user_text_default_format';
if ($request->existAndNonEmpty($requested_default_format)) {
    $user_edition_default_format = $request->get($requested_default_format);
}

$user_csv_separator = PFUser::DEFAULT_CSV_SEPARATOR;
if ($request->existAndNonEmpty('user_csv_separator')) {
    if ($request->valid(new Valid_WhiteList('user_csv_separator', PFUser::$csv_separators))) {
        $user_csv_separator = $request->get('user_csv_separator');
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_user_csv_separator'));
    }
}

$user_csv_dateformat = PFUser::DEFAULT_CSV_DATEFORMAT;
if ($request->existAndNonEmpty('user_csv_dateformat')) {
    if ($request->valid(new Valid_WhiteList('user_csv_dateformat', PFUser::$csv_dateformats))) {
        $user_csv_dateformat = $request->get('user_csv_dateformat');
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_user_csv_dateformat'));
    }
}

$username_display = null;
if ($request->existAndNonEmpty('username_display')) {
    if ($request->valid(new Valid_WhiteList('username_display', array(UserHelper::PREFERENCES_NAME_AND_LOGIN,
                                                                     UserHelper::PREFERENCES_LOGIN_AND_NAME,
                                                                     UserHelper::PREFERENCES_LOGIN,
                                                                     UserHelper::PREFERENCES_REAL_NAME)))) {
        $username_display = $request->get('username_display');
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_preferences', 'error_username_display'));
    }
}

$form_accessibility_mode = 0;
if ($request->existAndNonEmpty('form_accessibility_mode')) {
    if ($request->valid(new Valid_WhiteList('form_accessibility_mode', [0, 1]))) {
        $form_accessibility_mode = (int) $request->get('form_accessibility_mode');
    } else {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            _('Verify accessiblity mode value')
        );
    }
}
// Perform the update

// Preferences
user_set_preference("user_csv_separator", $user_csv_separator);
user_set_preference("user_csv_dateformat", $user_csv_dateformat);

if ($username_display !== null) {
    user_set_preference("username_display", $username_display);
}

if ($user_edition_default_format) {
    $user->setPreference(PFUser::EDITION_DEFAULT_FORMAT, $user_edition_default_format);
}

if (is_int($form_accessibility_mode)) {
    $user->setPreference(PFUser::ACCESSIBILITY_MODE, $form_accessibility_mode);
}

// Output
session_redirect("/account/index.php");
