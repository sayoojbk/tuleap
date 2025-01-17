<?php
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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Progress;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class MethodBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SemanticProgressDao
     */
    private $dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var MethodBuilder
     */
    private $method_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $tracker;

    protected function setUp(): void
    {
        $this->dao                  = \Mockery::mock(SemanticProgressDao::class);
        $this->form_element_factory = \Mockery::mock(\Tracker_FormElementFactory::class);
        $this->method_builder       = new MethodBuilder($this->form_element_factory, $this->dao);

        $this->tracker = \Mockery::mock(\Tracker::class, ['getId' => 113]);
    }

    public function testItBuildsAnInvalidMethodWhenTotalAndRemainingEffortFieldsAreTheSameField(): void
    {
        $method = $this->method_builder->buildMethodBasedOnEffort(
            $this->tracker,
            1001,
            1001
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodWhenTotalEffortFieldIdTargetsANonNumericField(): void
    {
        $this->mockField(1001, \Tracker_FormElement_Field_Date::class);
        $this->mockField(1002, \Tracker_FormElement_Field_Numeric::class);

        $method = $this->method_builder->buildMethodBasedOnEffort(
            $this->tracker,
            1001,
            1002
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodWhenRemainingEffortFieldIdTargetsANonNumericField(): void
    {
        $this->mockField(1001, \Tracker_FormElement_Field_Numeric::class);
        $this->mockField(1002, \Tracker_FormElement_Field_Date::class);

        $method = $this->method_builder->buildMethodBasedOnEffort(
            $this->tracker,
            1001,
            1002
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAMethodBasedOnEffort(): void
    {
        $this->mockField(1001, \Tracker_FormElement_Field_Numeric::class);
        $this->mockField(1002, \Tracker_FormElement_Field_Numeric::class);

        $method = $this->method_builder->buildMethodBasedOnEffort(
            $this->tracker,
            1001,
            1002
        );

        $this->assertInstanceOf(
            MethodBasedOnEffort::class,
            $method
        );
    }

    public function testItBuildsAMethodBasedOnEffortFromRequest(): void
    {
        $request = \Mockery::mock(\Codendi_Request::class);
        $request->shouldReceive('get')->with('computation-method')->andReturn(MethodBasedOnEffort::getMethodName());
        $request->shouldReceive('get')->with('total-effort-field-id')->andReturn('1001');
        $request->shouldReceive('get')->with('remaining-effort-field-id')->andReturn('1002');

        $this->mockField(1001, \Tracker_FormElement_Field_Numeric::class);
        $this->mockField(1002, \Tracker_FormElement_Field_Numeric::class);

        $method = $this->method_builder->buildMethodFromRequest(
            $this->tracker,
            $request
        );

        $this->assertInstanceOf(
            MethodBasedOnEffort::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodFromRequestWhenTotalEffortIdIsMissing(): void
    {
        $request = \Mockery::mock(\Codendi_Request::class);
        $request->shouldReceive('get')->with('computation-method')->andReturn(MethodBasedOnEffort::getMethodName());
        $request->shouldReceive('get')->with('total-effort-field-id')->andReturn(false);
        $request->shouldReceive('get')->with('remaining-effort-field-id')->andReturn('1002');

        $this->mockField(0, null);
        $this->mockField(1002, \Tracker_FormElement_Field_Numeric::class);

        $method = $this->method_builder->buildMethodFromRequest(
            $this->tracker,
            $request
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodFromRequestWhenRemainingEffortIdIsMissing(): void
    {
        $request = \Mockery::mock(\Codendi_Request::class);
        $request->shouldReceive('get')->with('computation-method')->andReturn(MethodBasedOnEffort::getMethodName());
        $request->shouldReceive('get')->with('total-effort-field-id')->andReturn('1001');
        $request->shouldReceive('get')->with('remaining-effort-field-id')->andReturn(false);

        $this->mockField(1001, \Tracker_FormElement_Field_Numeric::class);
        $this->mockField(0, null);

        $method = $this->method_builder->buildMethodFromRequest(
            $this->tracker,
            $request
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    public function testItBuildsAnInvalidMethodFromRequestWhenABadMethodNameIsProvided(): void
    {
        $request = \Mockery::mock(\Codendi_Request::class);
        $request->shouldReceive('get')->with('computation-method')->andReturn('random-float');

        $method = $this->method_builder->buildMethodFromRequest(
            $this->tracker,
            $request
        );

        $this->assertInstanceOf(
            InvalidMethod::class,
            $method
        );
    }

    private function mockField(int $field_id, ?string $field_type): void
    {
        $mocked_value = ($field_type !== null)
            ? \Mockery::mock($field_type)
            : null;

        $this->form_element_factory->shouldReceive('getUsedFieldByIdAndType')
            ->with(
                $this->tracker,
                $field_id,
                ['int', 'float', 'computed']
            )
            ->once()
            ->andReturn($mocked_value);
    }
}
