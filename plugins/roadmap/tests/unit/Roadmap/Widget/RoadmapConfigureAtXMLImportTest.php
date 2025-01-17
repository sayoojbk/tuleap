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

namespace Tuleap\Roadmap\Widget;

use Mockery;
use SimpleXMLElement;
use TemplateRenderer;
use Tuleap\Roadmap\RoadmapWidgetDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\Widget\Event\ConfigureAtXMLImport;
use Tuleap\Widget\Note\NoteDao;
use Tuleap\Widget\Note\ProjectNote;
use Tuleap\XML\MappingsRegistry;

final class RoadmapConfigureAtXMLImportTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItDoesNothingIfWidgetIsNotRoadmap(): void
    {
        $event = new ConfigureAtXMLImport(
            new ProjectNote(Mockery::mock(NoteDao::class), Mockery::mock(TemplateRenderer::class)),
            new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><widget name="note"></widget>'),
            new MappingsRegistry(),
            ProjectTestBuilder::aProject()->build()
        );

        $configurator = new RoadmapConfigureAtXMLImport();
        $configurator->configure($event);

        self::assertFalse($event->isWidgetConfigured());
    }

    public function testItThrowsAnErrorWhenTrackerIdReferenceIsNotSetInXml(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $event   = new ConfigureAtXMLImport(
            new \Tuleap\Roadmap\RoadmapProjectWidget(
                $project,
                Mockery::mock(RoadmapWidgetDao::class),
                new \Tuleap\Test\DB\DBTransactionExecutorPassthrough(),
                Mockery::mock(TemplateRenderer::class),
                Mockery::mock(NaturePresenterFactory::class),
                Mockery::mock(\TrackerFactory::class),
            ),
            new SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?>
                <widget name="plugin_roadmap_project_widget">
                    <preference name="roadmap">
                      <value name="stuff">T754</value>
                    </preference>
                </widget>'
            ),
            new MappingsRegistry(),
            $project
        );

        $this->expectException(\RuntimeException::class);
        $this->expectErrorMessage("Reference tracker_id for roadmap widget was not found");

        $configurator = new RoadmapConfigureAtXMLImport();
        $configurator->configure($event);
    }

    public function testItThrowsAnErrorWhenTrackerIdReferenceIsNotFoundInRegistery(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $event   = new ConfigureAtXMLImport(
            new \Tuleap\Roadmap\RoadmapProjectWidget(
                $project,
                Mockery::mock(RoadmapWidgetDao::class),
                new \Tuleap\Test\DB\DBTransactionExecutorPassthrough(),
                Mockery::mock(TemplateRenderer::class),
                Mockery::mock(NaturePresenterFactory::class),
                Mockery::mock(\TrackerFactory::class),
            ),
            new SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?>
                <widget name="plugin_roadmap_project_widget">
                    <preference name="roadmap">
                      <value name="tracker_id">T754</value>
                    </preference>
                </widget>'
            ),
            new MappingsRegistry(),
            $project
        );

        $this->expectException(\RuntimeException::class);
        $this->expectErrorMessage("Reference tracker_id for roadmap widget was not found");

        $configurator = new RoadmapConfigureAtXMLImport();
        $configurator->configure($event);
    }

    public function testItConfiguresWidget(): void
    {
        $project  = ProjectTestBuilder::aProject()->build();
        $registry = new MappingsRegistry();
        $registry->addReference("T754", 1234);
        $dao   = Mockery::mock(RoadmapWidgetDao::class);
        $event = new ConfigureAtXMLImport(
            new \Tuleap\Roadmap\RoadmapProjectWidget(
                $project,
                $dao,
                new \Tuleap\Test\DB\DBTransactionExecutorPassthrough(),
                Mockery::mock(TemplateRenderer::class),
                Mockery::mock(NaturePresenterFactory::class),
                Mockery::mock(\TrackerFactory::class),
            ),
            new SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?>
                <widget name="plugin_roadmap_project_widget">
                    <preference name="roadmap">
                      <value name="tracker_id">T754</value>
                      <value name="title">My Roadmap</value>
                    </preference>
                </widget>'
            ),
            $registry,
            $project
        );
        $dao->shouldReceive('insertContent')
            ->once()
            ->with(Mockery::any(), Mockery::any(), "My Roadmap", 1234);

        $configurator = new RoadmapConfigureAtXMLImport();
        $configurator->configure($event);

        self::assertTrue($event->isWidgetConfigured());
    }
}
