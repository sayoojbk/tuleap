/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

CREATE TABLE plugin_oauth2_server_app(
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    project_id int(11) NOT NULL,
    name VARCHAR(255) NOT NULL,
    redirect_endpoint TEXT NOT NULL,
    verifier VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE plugin_oauth2_authorization_code(
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    verifier VARCHAR(255) NOT NULL,
    expiration_date INT(11) UNSIGNED NOT NULL,
    INDEX idx_expiration_date (expiration_date)
) ENGINE=InnoDB;
