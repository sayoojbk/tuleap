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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\TagPush;

use PHPUnit\Framework\TestCase;
use Tuleap\Gitlab\Repository\Webhook\InvalidValueFormatException;
use Tuleap\Gitlab\Repository\Webhook\MissingKeyException;

class TagPushWebhookDataBuilderTest extends TestCase
{
    public function testItThrowsAnExceptionIfRefIsNotInContent(): void
    {
        $webhook_data = [
            "project" => ["id" => 123456, "web_url" => "https://example.com/path/repo01"]
        ];

        $builder = new TagPushWebhookDataBuilder();

        $this->expectException(MissingKeyException::class);

        $builder->build(
            "Tag Push Hook",
            123456,
            "https://example.com/path/repo01",
            $webhook_data
        );
    }

    public function testItThrowsAnExceptionIfRefIsNotAString(): void
    {
        $webhook_data = [
            "project" => ["id" => 123456, "web_url" => "https://example.com/path/repo01"],
            "ref" => 1.0,
        ];

        $builder = new TagPushWebhookDataBuilder();

        $this->expectException(InvalidValueFormatException::class);

        $builder->build(
            "Tag Push Hook",
            123456,
            "https://example.com/path/repo01",
            $webhook_data
        );
    }

    public function testItRetrievesTagPushWebhookData(): void
    {
        $webhook_data = [
            "project" => ["id" => 123456, "web_url" => "https://example.com/path/repo01"],
            "ref" => "refs/tags/v1.0",
        ];

        $builder = new TagPushWebhookDataBuilder();

        $tag_push_webhook_data = $builder->build(
            "Tag Push Hook",
            123456,
            "https://example.com/path/repo01",
            $webhook_data
        );

        $this->assertSame("Tag Push Hook", $tag_push_webhook_data->getEventName());
        $this->assertSame(123456, $tag_push_webhook_data->getGitlabProjectId());
        $this->assertSame("https://example.com/path/repo01", $tag_push_webhook_data->getGitlabWebUrl());
        $this->assertInstanceOf(TagPushWebhookData::class, $tag_push_webhook_data);
        $this->assertSame("refs/tags/v1.0", $tag_push_webhook_data->getRef());
    }
}
