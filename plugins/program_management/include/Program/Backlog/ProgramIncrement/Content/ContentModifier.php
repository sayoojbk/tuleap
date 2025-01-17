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

namespace Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureCanNotBeRankedWithItselfException;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureHasPlannedUserStoryException;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureNotFoundException;
use Tuleap\ProgramManagement\Program\Backlog\Feature\VerifyIsVisibleFeature;
use Tuleap\ProgramManagement\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\CheckFeatureIsPlannedInProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\CheckProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Program\Backlog\Rank\OrderFeatureRank;
use Tuleap\ProgramManagement\Program\Plan\FeatureCannotBePlannedInProgramIncrementException;
use Tuleap\ProgramManagement\Program\Plan\InvalidFeatureIdInProgramIncrementException;
use Tuleap\ProgramManagement\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\ProgramManagement\Program\ProgramSearcher;
use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;

final class ContentModifier implements ModifyContent
{
    /**
     * @var VerifyPrioritizeFeaturesPermission
     */
    private $permission_verifier;
    /**
     * @var CheckProgramIncrement
     */
    private $program_increment_checker;
    /**
     * @var ProgramSearcher
     */
    private $program_searcher;
    /**
     * @var VerifyIsVisibleFeature
     */
    private $visible_verifier;
    /**
     * @var VerifyCanBePlannedInProgramIncrement
     */
    private $can_be_planned_verifier;
    /**
     * @var OrderFeatureRank
     */
    private $features_rank_orderer;
    /**
     * @var CheckFeatureIsPlannedInProgramIncrement
     */
    private $check_feature_is_planned_in_PI;
    /**
     * @var FeaturePlanner
     */
    private $feature_planner;

    public function __construct(
        VerifyPrioritizeFeaturesPermission $permission_verifier,
        CheckProgramIncrement $program_increment_checker,
        ProgramSearcher $program_searcher,
        VerifyIsVisibleFeature $visible_verifier,
        VerifyCanBePlannedInProgramIncrement $can_be_planned_verifier,
        FeaturePlanner $feature_planner,
        OrderFeatureRank $features_rank_orderer,
        CheckFeatureIsPlannedInProgramIncrement $check_feature_is_planned_in_PI
    ) {
        $this->permission_verifier            = $permission_verifier;
        $this->program_increment_checker      = $program_increment_checker;
        $this->program_searcher               = $program_searcher;
        $this->visible_verifier               = $visible_verifier;
        $this->can_be_planned_verifier        = $can_be_planned_verifier;
        $this->feature_planner                = $feature_planner;
        $this->features_rank_orderer          = $features_rank_orderer;
        $this->check_feature_is_planned_in_PI = $check_feature_is_planned_in_PI;
    }

    public function modifyContent(\PFUser $user, int $program_increment_id, ContentChange $content_change): void
    {
        if ($content_change->potential_feature_id_to_add === null && $content_change->elements_to_order === null) {
            throw new AddOrOrderMustBeSetException();
        }
        $program_increment = ProgramIncrementIdentifier::fromId(
            $this->program_increment_checker,
            $program_increment_id,
            $user
        );
        $program           = $this->program_searcher->getProgramOfProgramIncrement($program_increment->getId());
        $has_permission    = $this->permission_verifier->canUserPrioritizeFeatures($program, $user);

        if (! $has_permission) {
            throw new NotAllowedToPrioritizeException((int) $user->getId(), $program_increment->getId());
        }
        if ($content_change->potential_feature_id_to_add !== null) {
            $this->planFeature($content_change->potential_feature_id_to_add, $program_increment, $user, $program);
        }
        if ($content_change->elements_to_order !== null) {
            $this->reorderFeature($content_change->elements_to_order, $program_increment, $program);
        }
    }

    /**
     * @throws FeatureCannotBePlannedInProgramIncrementException
     * @throws FeatureHasPlannedUserStoryException
     * @throws AddFeatureException
     * @throws ProgramIncrementNotFoundException
     * @throws RemoveFeatureException
     * @throws FeatureNotFoundException
     */
    private function planFeature(
        int $potential_feature_id_to_add,
        ProgramIncrementIdentifier $program_increment,
        \PFUser $user,
        Program $program
    ): void {
        $feature = FeatureIdentifier::fromId($this->visible_verifier, $potential_feature_id_to_add, $user, $program);
        if ($feature === null) {
            throw new FeatureNotFoundException($potential_feature_id_to_add);
        }
        $feature_addition = FeatureAddition::fromFeature(
            $this->can_be_planned_verifier,
            $feature,
            $program_increment,
            $user
        );
        $this->feature_planner->plan($feature_addition);
    }

    /**
     * @throws FeatureCanNotBeRankedWithItselfException
     * @throws InvalidFeatureIdInProgramIncrementException
     */
    private function reorderFeature(
        FeatureElementToOrderInvolvedInChangeRepresentation $feature_to_order_representation,
        ProgramIncrementIdentifier $program_increment,
        Program $program
    ): void {
        $this->checkFeatureCanBeReordered($feature_to_order_representation->ids[0], $program_increment);
        $this->checkFeatureCanBeReordered($feature_to_order_representation->compared_to, $program_increment);
        $this->features_rank_orderer->reorder($feature_to_order_representation, (string) $program_increment->getId(), $program);
    }

    /**
     * @throws InvalidFeatureIdInProgramIncrementException
     */
    private function checkFeatureCanBeReordered(
        int $potential_feature_id_to_manipulate,
        ProgramIncrementIdentifier $program_increment
    ): void {
        $can_be_planned = $this->can_be_planned_verifier->canBePlannedInProgramIncrement(
            $potential_feature_id_to_manipulate,
            $program_increment->getId()
        );
        if (! $can_be_planned) {
            throw new InvalidFeatureIdInProgramIncrementException(
                $potential_feature_id_to_manipulate,
                $program_increment->getId()
            );
        }
        $feature_is_planned = $this->check_feature_is_planned_in_PI->isFeaturePlannedInProgramIncrement($program_increment->getId(), $potential_feature_id_to_manipulate);

        if (! $feature_is_planned) {
            throw new InvalidFeatureIdInProgramIncrementException(
                $potential_feature_id_to_manipulate,
                $program_increment->getId()
            );
        }
    }
}
