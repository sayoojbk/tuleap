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

namespace Tuleap\Reference\ByNature;

use Tuleap\Reference\ByNature\Wiki\CrossReferenceWikiOrganizer;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;

class CrossReferenceByNatureInCoreOrganizer
{
    /**
     * @var CrossReferenceWikiOrganizer
     */
    private $wiki_organizer;

    public function __construct(
        CrossReferenceWikiOrganizer $wiki_organizer
    ) {
        $this->wiki_organizer = $wiki_organizer;
    }

    public function organizeCoreReferences(CrossReferenceByNatureOrganizer $by_nature_organizer): void
    {
        foreach ($by_nature_organizer->getCrossReferencePresenters() as $cross_reference_presenter) {
            if ($cross_reference_presenter->type === \ReferenceManager::REFERENCE_NATURE_WIKIPAGE) {
                $this->wiki_organizer->organizeWikiReference($cross_reference_presenter, $by_nature_organizer);
            }
        }
    }
}
