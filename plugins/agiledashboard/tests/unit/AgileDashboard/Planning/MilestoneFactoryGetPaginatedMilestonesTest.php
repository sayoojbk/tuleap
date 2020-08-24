<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusAll;
use Tuleap\AgileDashboard\Milestone\Request\RawTopMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\RefinedTopMilestoneRequest;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;
use Tuleap\Tracker\TrackerColor;

final class MilestoneFactoryGetPaginatedMilestonesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ScrumForMonoMilestoneChecker
     */
    private $mono_milestone_checker;
    /**
     * @var \AgileDashboard_Milestone_MilestoneDao|M\LegacyMockInterface|M\MockInterface
     */
    private $milestone_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|TimeframeBuilder
     */
    private $timeframe_builder;

    protected function setUp(): void
    {
        $this->planning_factory       = M::mock(\PlanningFactory::class);
        $this->artifact_factory       = M::mock(\Tracker_ArtifactFactory::class);
        $this->timeframe_builder      = M::mock(TimeframeBuilder::class);
        $this->mono_milestone_checker = M::mock(ScrumForMonoMilestoneChecker::class);
        $this->milestone_dao          = M::mock(\AgileDashboard_Milestone_MilestoneDao::class);

        $this->milestone_factory = new \Planning_MilestoneFactory(
            $this->planning_factory,
            $this->artifact_factory,
            M::spy(\Tracker_FormElementFactory::class),
            M::mock(\AgileDashboard_Milestone_MilestoneStatusCounter::class),
            M::mock(\PlanningPermissionsManager::class),
            $this->milestone_dao,
            $this->mono_milestone_checker,
            $this->timeframe_builder,
            M::mock(MilestoneBurndownFieldChecker::class)
        );
    }

    public function testItReturnsEmptyWhenNoMilestones(): void
    {
        $user        = UserTestBuilder::aUser()->build();
        $project     = \Project::buildForTest();
        $raw_request = new RawTopMilestoneRequest($user, $project, 50, 0, 'asc');
        $request     = RefinedTopMilestoneRequest::withStatusQuery($raw_request, new StatusAll());

        $planning = new \Planning(1, 'Release Planning', 101, 'Irrelevant', 'Irrelevant');
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andReturn($planning);

        $milestones = $this->milestone_factory->getPaginatedTopMilestones($request);

        $this->assertSame(0, $milestones->getTotalSize());
        $this->assertEmpty($milestones->getMilestones());
    }

    public function testItReturnsMilestonesFilteredByStatus(): void
    {
        $user        = UserTestBuilder::aUser()->build();
        $project     = \Project::buildForTest();
        $raw_request = new RawTopMilestoneRequest($user, $project, 50, 0, 'asc');
        $request     = RefinedTopMilestoneRequest::withStatusQuery($raw_request, new StatusAll());

        $planning          = new \Planning(1, 'Release Planning', 101, 'Irrelevant', 'Irrelevant');
        $milestone_tracker = $this->buildTestTracker(15);
        $planning->setPlanningTracker($milestone_tracker);
        $this->planning_factory->shouldReceive('getVirtualTopPlanning')->andReturn($planning);
        $this->mono_milestone_checker->shouldReceive('isMonoMilestoneEnabled')->andReturnFalse();
        $this->milestone_dao->shouldReceive('searchPaginatedTopMilestones')
            ->once()
            ->with(15, $request)
            ->andReturn(
                \TestHelper::arrayToDar(
                    ['id' => 24],
                    ['id' => 25]
                )
            );
        $this->milestone_dao->shouldReceive('foundRows')
            ->once()
            ->andReturn(2);

        $first_artifact  = $this->mockArtifact(24, $milestone_tracker);
        $second_artifact = $this->mockArtifact(25, $milestone_tracker);

        $this->artifact_factory->shouldReceive('getInstanceFromRow')
            ->andReturn($first_artifact, $second_artifact);
        $this->planning_factory->shouldReceive('getPlanningByPlanningTracker')
            ->andReturn($planning);
        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifact')
            ->andReturn(\TimePeriodWithoutWeekEnd::buildFromDuration(1, 1));

        $milestones = $this->milestone_factory->getPaginatedTopMilestones($request);

        $this->assertSame(2, $milestones->getTotalSize());
        $first_milestone = $milestones->getMilestones()[0];
        $this->assertSame(24, $first_milestone->getArtifactId());
        $second_milestone = $milestones->getMilestones()[1];
        $this->assertSame(25, $second_milestone->getArtifactId());
    }

    private function buildTestTracker(int $tracker_id): \Tracker
    {
        return new \Tracker(
            $tracker_id,
            null,
            'Irrelevant',
            'Irrelevant',
            'irrelevant',
            false,
            null,
            null,
            null,
            null,
            true,
            false,
            \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            TrackerColor::default(),
            false
        );
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|\Tracker_Artifact
     */
    private function mockArtifact(int $artifact_id, \Tracker $milestone_tracker)
    {
        $first_artifact = M::mock(\Tracker_Artifact::class);
        $first_artifact->shouldReceive('getId')->andReturn($artifact_id);
        $first_artifact->shouldReceive('userCanView')->andReturnTrue();
        $first_artifact->shouldReceive('getTracker')->andReturn($milestone_tracker);
        $first_artifact->shouldReceive('getAllAncestors')->andReturn([]);
        return $first_artifact;
    }
}