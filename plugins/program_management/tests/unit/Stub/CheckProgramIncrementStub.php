<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\CheckProgramIncrement;

final class CheckProgramIncrementStub implements CheckProgramIncrement
{
    /** @var bool */
    private $is_allowed;

    public function __construct(bool $is_allowed = true)
    {
        $this->is_allowed = $is_allowed;
    }

    public function checkIsAProgramIncrement(int $program_increment_id, \PFUser $user): void
    {
        if (! $this->is_allowed) {
            throw new ProgramTrackerNotFoundException(1);
        }
    }
}
