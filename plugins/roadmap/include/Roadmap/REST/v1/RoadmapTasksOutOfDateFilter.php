<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Tracker;
use Tracker_Semantic_Status;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\SemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

class RoadmapTasksOutOfDateFilter
{
    /**
     * @var SemanticStatusRetriever
     */
    private $semantic_status_retriever;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var TimeframeBuilder
     */
    private $timeframe_builder;

    public function __construct(
        SemanticStatusRetriever $semantic_status_retriever,
        TimeframeBuilder $timeframe_builder,
        LoggerInterface $logger
    ) {
        $this->semantic_status_retriever = $semantic_status_retriever;
        $this->timeframe_builder         = $timeframe_builder;
        $this->logger                    = $logger;
    }

    /**
     * @param Artifact[] $artifacts
     * @return Artifact[]
     */
    public function filterOutOfDateArtifacts(
        array $artifacts,
        Tracker $tracker,
        DateTimeImmutable $now,
        \PFUser $user
    ): array {
        $semantic_status = $this->semantic_status_retriever->retrieveSemantic($tracker);
        return array_filter($artifacts, function ($artifact) use ($semantic_status, $user, $now) {
            $status_field = $semantic_status->getField();
            if ($status_field === null || $semantic_status->isOpen($artifact)) {
                return true;
            }

            return ! $this->hasBeenClosedMoreThanOneYearAgo($artifact, $semantic_status, $status_field, $now) &&
                ! $this->isEndDateMoreThanOneYearAgo($artifact, $user, $now);
        });
    }

    private function hasBeenClosedMoreThanOneYearAgo(
        Artifact $artifact,
        \Tracker_Semantic_Status $semantic_status,
        \Tracker_FormElement_Field_List $status_field,
        DateTimeImmutable $now
    ): bool {
        $changesets = array_reverse($artifact->getChangesets());
        foreach ($changesets as $changeset) {
            if (! $changeset->canHoldValue()) {
                continue;
            }

            $status_value = $changeset->getValue($status_field);
            if ($status_value === null) {
                continue;
            }

            if (! $status_value->hasChanged()) {
                continue;
            }

            $value = $status_value->getValue();
            if ($value === null) {
                continue;
            }

            if ($this->isAnOpenValue((int) $value[0], $semantic_status)) {
                continue;
            }

            $close_date   = new DateTimeImmutable('@' . $changeset->getSubmittedOn());
            $closed_since = $now->diff($close_date);
            if ($closed_since->days > 365) {
                return true;
            }

            return false;
        }

        $this->logger->error(
            sprintf(
                "[Roadmap widget] Artifact #%s is closed but we can't find the changeset where the action has been performed. Hence, it won't be displayed.",
                $artifact->getId()
            )
        );
        return true;
    }

    private function isEndDateMoreThanOneYearAgo(Artifact $artifact, \PFUser $user, DateTimeImmutable $now): bool
    {
        $time_period   = $this->timeframe_builder->buildTimePeriodWithoutWeekendForArtifactForREST($artifact, $user);
        $task_end_date = $this->getDateTheTaskEnds($time_period);

        if ($task_end_date === null) {
            return false;
        }

        $diff_from_now = $now->diff($task_end_date);

        return $diff_from_now->days > 365;
    }

    private function isAnOpenValue(int $value_id, Tracker_Semantic_Status $semantic_status): bool
    {
        return in_array($value_id, $semantic_status->getOpenValues());
    }

    private function getDateTheTaskEnds(\TimePeriodWithoutWeekEnd $time_period): ?DateTimeImmutable
    {
        $start_date = $time_period->getStartDate();
        $end_date   = $time_period->getEndDate();

        if ($start_date === null && $end_date === null) {
            return null;
        }

        if ($end_date === null) {
            return new DateTimeImmutable('@' . $start_date);
        }

        return new DateTimeImmutable('@' . $end_date);
    }
}
