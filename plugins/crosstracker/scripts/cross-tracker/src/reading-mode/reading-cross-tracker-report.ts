/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
import type { TrackerAndProject } from "../type";
import type BackendCrossTrackerReport from "../backend-cross-tracker-report";

export default class ReadingCrossTrackerReport {
    trackers: Map<number, TrackerAndProject>;
    expert_query: string;

    constructor() {
        this.trackers = new Map();
        this.expert_query = "";
    }

    getTrackers(): IterableIterator<TrackerAndProject> {
        return this.trackers.values();
    }

    getTrackerIds(): number[] {
        return [...this.trackers.keys()];
    }

    areTrackersEmpty(): boolean {
        return this.trackers.size <= 0;
    }

    duplicateFromReport(report: BackendCrossTrackerReport): void {
        this.trackers = report.trackers;
        this.expert_query = report.expert_query;
    }
}
