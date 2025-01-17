<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Team\Creation;

use Tuleap\ProgramManagement\Adapter\Team\AtLeastOneTeamShouldBeDefinedException;
use Tuleap\ProgramManagement\Adapter\Team\ProjectIsAProgramException;
use Tuleap\ProgramManagement\Adapter\Team\TeamAccessException;
use Tuleap\ProgramManagement\Adapter\Team\TeamMustHaveExplicitBacklogEnabledException;
use Tuleap\ProgramManagement\Program\ToBeCreatedProgram;

interface BuildTeam
{
    /**
     * @throws AtLeastOneTeamShouldBeDefinedException
     * @throws ProjectIsAProgramException
     * @throws TeamAccessException
     * @throws TeamMustHaveExplicitBacklogEnabledException
     */
    public function buildTeamProject(array $team_ids, ToBeCreatedProgram $program, \PFUser $user): TeamCollection;

    /**
     * @throws ProjectIsAProgramException
     * @throws TeamAccessException
     */
    public function checkProjectIsATeam(int $team_id, \PFUser $user): void;

    /**
     * @throws ProjectIsAProgramException
     */
    public function checkProjectIsATeamForRestTestInitialization(int $team_id, \PFUser $user): void;
}
