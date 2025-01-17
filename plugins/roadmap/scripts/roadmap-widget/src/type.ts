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

export interface Task {
    readonly id: number;
    readonly title: string;
    readonly xref: string;
    readonly color_name: string;
    readonly progress: number | null;
    readonly progress_error_message: string;
    readonly html_url: string;
    readonly start: Date | null;
    readonly end: Date | null;
    readonly dependencies: Record<string, number[]>;
}

export type TimeScale = "month" | "quarter" | "week";

export interface TimePeriod {
    readonly units: Date[];
    formatShort(unit: Date): string;
    formatLong(unit: Date): string;
    additionalUnits(nb: number): Date[];
    getEvenOddClass(unit: Date): string;
}

export interface TaskDimension {
    readonly left: number;
    readonly width: number;
    readonly index: number;
}
export class TaskDimensionMap extends WeakMap<Task, TaskDimension> {}

export class TasksByNature extends Map<string, Task[]> {}
export class TasksDependencies extends WeakMap<Task, TasksByNature> {}

export class NaturesLabels extends Map<string, string> {}

export class NbUnitsPerYear extends Map<number, number> {}
