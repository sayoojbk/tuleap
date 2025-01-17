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

use Tuleap\Tracker\Semantic\IDuplicateSemantic;

class SemanticProgressDuplicator implements IDuplicateSemantic
{
    /**
     * @var SemanticProgressDao
     */
    private $dao;

    public function __construct(SemanticProgressDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param int|string   $from_tracker_id
     * @param int|string   $to_tracker_id
     */
    public function duplicate($from_tracker_id, $to_tracker_id, array $field_mapping): void
    {
        $row = $this->dao->searchByTrackerId((int) $from_tracker_id);
        if ($row === null) {
            return;
        }

        $from_total_effort_field_id     = $row['total_effort_field_id'];
        $from_remaining_effort_field_id = $row['remaining_effort_field_id'];

        $to_total_effort_field_id     = null;
        $to_remaining_effort_field_id = null;
        foreach ($field_mapping as $mapping) {
            if ((int) $mapping['from'] === $from_total_effort_field_id) {
                $to_total_effort_field_id = (int) $mapping['to'];
            }
            if ((int) $mapping['from'] === $from_remaining_effort_field_id) {
                $to_remaining_effort_field_id = (int) $mapping['to'];
            }
        }

        if ($to_total_effort_field_id === null || $to_remaining_effort_field_id === null) {
            return;
        }

        $this->dao->save((int) $to_tracker_id, $to_total_effort_field_id, $to_remaining_effort_field_id);
    }
}
