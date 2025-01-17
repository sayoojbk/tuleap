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

namespace Tuleap\TestManagement\REST\v1;

use PFUser;
use Tracker;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\LabelFieldNotFoundException;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Semantic\Status\SemanticStatusNotDefinedException;
use Tuleap\Tracker\Semantic\Status\SemanticStatusClosedValueNotFoundException;
use Tuleap\Tracker\Semantic\Status\StatusValueRetriever;

/**
 * @psalm-type StatusAcceptableValue = self::STATUS_CHANGE_CLOSED_VALUE|self::STATUS_CHANGE_OPEN_VALUE|null
 */
class CampaignArtifactUpdateFieldValuesBuilder
{
    private const STATUS_CHANGE_CLOSED_VALUE = 'closed';
    private const STATUS_CHANGE_OPEN_VALUE   = 'open';

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;
    /**
     * @var StatusValueRetriever
     */
    private $status_value_retriever;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        StatusValueRetriever $status_value_retriever
    ) {
        $this->formelement_factory    = $formelement_factory;
        $this->status_value_retriever = $status_value_retriever;
    }

    /**
     * @psalm-param StatusAcceptableValue $change_status
     * @return ArtifactValuesRepresentation[]
     * @throws SemanticStatusNotDefinedException
     * @throws SemanticStatusClosedValueNotFoundException
     *
     * @throws LabelFieldNotFoundException
     */
    public function getFieldValuesForCampaignArtifactUpdate(
        Tracker $tracker,
        PFUser $user,
        string $label,
        ?string $change_status
    ): array {
        $field_values = [
            $this->getLabelValueRepresentation(
                $tracker,
                $user,
                $label
            ),
            $this->getStatusValueRepresentation(
                $tracker,
                $user,
                $change_status
            )
        ];

        return array_filter($field_values);
    }

    /**
     * @psalm-param StatusAcceptableValue $change_status
     *
     * @throws SemanticStatusNotDefinedException
     * @throws SemanticStatusClosedValueNotFoundException
     */
    private function getStatusValueRepresentation(
        Tracker $tracker,
        PFUser $user,
        ?string $change_status
    ): ?ArtifactValuesRepresentation {
        if ($change_status === null) {
            return null;
        }

        $status_field = $tracker->getStatusField();
        if ($status_field === null) {
            throw new SemanticStatusNotDefinedException();
        }

        $status_value = new ArtifactValuesRepresentation();

        if ($change_status === self::STATUS_CHANGE_CLOSED_VALUE) {
            $value = $this->status_value_retriever->getFirstClosedValueUserCanRead($tracker, $user);
        } else {
            $value = $this->status_value_retriever->getFirstOpenValueUserCanRead($tracker, $user);
        }

        $status_value->bind_value_ids = [$value->getId()];
        $status_value->field_id       = $status_field->getId();

        return $status_value;
    }

    /**
     * @throws LabelFieldNotFoundException
     */
    private function getLabelValueRepresentation(
        Tracker $tracker,
        PFUser $user,
        string $label
    ): ArtifactValuesRepresentation {
        $label_field = $this->formelement_factory->getUsedFieldByNameForUser(
            $tracker->getId(),
            CampaignRepresentation::FIELD_NAME,
            $user
        );

        if ($label_field === null) {
            throw new LabelFieldNotFoundException($tracker);
        }

        $label_value           = new ArtifactValuesRepresentation();
        $label_value->field_id = $label_field->getId();
        $label_value->value    = $label;

        return $label_value;
    }
}
