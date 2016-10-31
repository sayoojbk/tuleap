<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Enalean, 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuealp\project\Admin\ProjectDescriptionFieldBuilder;
use Tuealp\user\Admin\PendingProjectBuilder;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\ProjectPendingPresenter;
use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Project\DescriptionFieldsFactory;

require_once('pre.php');

$user                             = UserManager::instance()->getCurrentUser();
$forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
    new User_ForgeUserGroupPermissionsDao()
);
$special_access                   = $forge_ugroup_permissions_manager->doesUserHavePermission(
    $user, new User_ForgeUserGroupPermission_ProjectApproval()
);

if (! $special_access) {
    session_require(array('group' => '1', 'admin_flags' => 'A'));
}

$action = $request->getValidated('action', 'string', '');

$event_manager   = EventManager::instance();
$project_manager = ProjectManager::instance();
$csrf_token      = new CSRFSynchronizerToken('/admin/approve-pending.php');

// group public choice
if ($action == 'activate') {
    $csrf_token->check();
    $groups = array();
    if ($request->exist('list_of_groups')) {
        $groups = array_filter(array_map('intval', explode(",", $request->get('list_of_groups'))));
    }
    foreach ($groups as $group_id) {
        $project = $project_manager->getProject($group_id);
        $project_manager->activate($project);
    }
    if ($special_access) {
        $GLOBALS['Response']->redirect('/my/');
    } else {
        $GLOBALS['Response']->redirect('/admin/');
    }

} else if ($action == 'delete') {
    $csrf_token->check();
    $group_id = $request->get('group_id');
    $project  = $project_manager->getProject($group_id);
    group_add_history('deleted', 'x', $project->getID());
    $project_manager->updateStatus($project, Project::STATUS_DELETED);

    $event_manager->processEvent('project_is_deleted', array('group_id' => $group_id));
    if ($special_access) {
        $GLOBALS['Response']->redirect('/my/');
    } else {
        $GLOBALS['Response']->redirect('/admin/');
    }
}

$fields_factory  = new DescriptionFieldsFactory(new DescriptionFieldsDao());
$field_builder   = new ProjectDescriptionFieldBuilder($fields_factory);
$project_builder = new PendingProjectBuilder($project_manager, UserManager::instance(), $field_builder);
$project_list    = $project_builder->build();

$siteadmin = new AdminPageRenderer();
$presenter = new ProjectPendingPresenter($project_list, $csrf_token);

$siteadmin->renderAPresenter(
    $GLOBALS['Language']->getText('admin_approve_pending', 'no_pending'),
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/',
    'project-pending',
    $presenter
);
