/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

export interface TroveCatData {
    id: string;
    shortname: string;
    fullname: string;
    children: Array<TroveCatData>;
}

export interface FieldData {
    group_desc_id: string;
    desc_name: string;
    desc_type: string;
    desc_description: string;
    desc_required: string;
}

export interface FieldProperties {
    field_id: string;
    value: string;
}

export interface TroveCatProperties {
    category_id: number;
    value_id: number;
}

export interface TemplateData {
    title: string;
    description: string;
    id: string;
    glyph: string;
    is_built_in: boolean;
}

export interface ProjectProperties {
    shortname: string;
    label: string;
    is_public: boolean;
    allow_restricted?: boolean;
    xml_template_name?: string | null;
    template_id?: number | null;
    categories: Array<TroveCatProperties>;
    description: string;
    fields: Array<FieldProperties>;
}

export interface ProjectNameProperties {
    slugified_name: string;
    name: string;
}
