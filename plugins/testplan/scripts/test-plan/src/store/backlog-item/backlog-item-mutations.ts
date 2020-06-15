/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { BacklogItemState } from "./type";
import { BacklogItem } from "../../type";

export function beginLoadingBacklogItems(state: BacklogItemState): void {
    state.is_loading = true;
}

export function endLoadingBacklogItems(state: BacklogItemState): void {
    state.is_loading = false;
}

export function addBacklogItems(state: BacklogItemState, collection: BacklogItem[]): void {
    state.backlog_items = state.backlog_items.concat(collection);
}

export function loadingErrorHasBeenCatched(state: BacklogItemState): void {
    state.has_loading_error = true;
}