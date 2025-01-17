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

namespace Tuleap\Tracker\Semantic\Progress;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SemanticProgressDuplicatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface|SemanticProgressDao
     */
    private $dao;
    /**
     * @var SemanticProgressDuplicator
     */
    private $duplicator;

    protected function setUp(): void
    {
        $this->dao        = \Mockery::mock(SemanticProgressDao::class);
        $this->duplicator = new SemanticProgressDuplicator($this->dao);
    }

    public function testItDoesNotDuplicateIfThereIsNoExistingConfig(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(null);

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->duplicator->duplicate(1, 2, [
            ['from' => 101, 'to' => 1001],
            ['from' => 102, 'to' => 1002]
        ]);
    }



    public function testItDoesNotDuplicateIfThereIsNoRemainingEffortFieldInConfig(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['total_effort_field_id' => 101, 'remaining_effort_field_id' => null]);

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->duplicator->duplicate(1, 2, [
            ['from' => 101, 'to' => 1001],
            ['from' => 102, 'to' => 1002]
        ]);
    }

    public function testItDoesNotDuplicateIfThereIsNoTotalEffortFieldInMapping(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['total_effort_field_id' => null, 'remaining_effort_field_id' => 102]);

        $this->dao
            ->shouldReceive('save')
            ->never();

        $this->duplicator->duplicate(1, 2, [
            ['from' => 101, 'to' => 1001],
            ['from' => 102, 'to' => 1002]
        ]);
    }

    public function testItDuplicates(): void
    {
        $this->dao
            ->shouldReceive('searchByTrackerId')
            ->with(1)
            ->once()
            ->andReturn(['total_effort_field_id' => 101, 'remaining_effort_field_id' => 102]);

        $this->dao
            ->shouldReceive('save')
            ->with(2, 1001, 1002)
            ->once();

        $this->duplicator->duplicate(
            1,
            2,
            [
                ['from' => 101, 'to' => 1001],
                ['from' => 102, 'to' => 1002]
            ]
        );
    }
}
