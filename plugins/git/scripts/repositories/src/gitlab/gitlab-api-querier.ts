/**
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import type { GitLabCredentials } from "../type";
import { del, get, patch, post, recursiveGet } from "tlp";
import type { GitLabData, GitLabDataWithToken, Repository } from "../type";
import type { RepositoryCallback } from "../api/rest-querier";

export interface GitLabRepositoryDeletion {
    repository_id: number;
    project_id: number;
}

export interface GitLabRepositoryCreation {
    project_id: number;
    gitlab_repository_id: number;
    gitlab_server_url: string;
    gitlab_bot_api_token: string;
}

export interface GitLabRepositoryUpdate {
    update_bot_api_token?: GitLabDataWithToken;
    generate_new_secret?: GitLabData;
}

export function getAsyncGitlabRepositoryList(credentials: GitLabCredentials): Promise<Response> {
    const headers = new Headers();
    headers.append("Authorization", "Bearer " + credentials.token);

    return get(credentials.server_url, { headers, mode: "cors", cache: "default" });
}

export function deleteIntegrationGitlab(
    repository_deletion: GitLabRepositoryDeletion
): Promise<Response> {
    return del(
        "/api/v1/gitlab_repositories/" +
            encodeURIComponent(repository_deletion.repository_id) +
            "?project_id=" +
            encodeURIComponent(repository_deletion.project_id)
    );
}

export function buildGitlabCollectionCallback(displayCallback: RepositoryCallback) {
    return (repositories: Array<Repository>): Array<Repository> => {
        displayCallback(repositories);
        return repositories;
    };
}

export function getGitlabRepositoryList(
    project_id: number,
    order_by: string,
    displayCallback: RepositoryCallback
): Promise<Array<Repository>> {
    return recursiveGet("/api/projects/" + project_id + "/gitlab_repositories", {
        params: {
            query: JSON.stringify({
                scope: "project",
            }),
            order_by,
            limit: 50,
            offset: 0,
        },
        getCollectionCallback: buildGitlabCollectionCallback(displayCallback),
    });
}

export function postGitlabRepository(repository: GitLabRepositoryCreation): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    const body = JSON.stringify(repository);

    return post("/api/gitlab_repositories", {
        headers,
        body,
    });
}

export function patchGitlabRepository(body: GitLabRepositoryUpdate): Promise<Response> {
    const headers = {
        "content-type": "application/json",
    };

    return patch("/api/gitlab_repositories", {
        headers,
        body: JSON.stringify(body),
    });
}
