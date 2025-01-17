<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Program\Backlog\Feature;

use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class FeatureIdentifierTest extends TestCase
{
    public function testItReturnsNullWhenFeatureIsNotVisibleByUser(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $program = new Program(110);

        self::assertNull(FeatureIdentifier::fromId(new VerifyIsVisibleFeatureStub(false), 404, $user, $program));
    }

    public function testItBuildsAValidFeature(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $program = new Program(110);

        $feature = FeatureIdentifier::fromId(new VerifyIsVisibleFeatureStub(), 87, $user, $program);
        self::assertSame(87, $feature->id);
    }
}
