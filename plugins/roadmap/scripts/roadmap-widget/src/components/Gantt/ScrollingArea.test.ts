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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ScrollingArea from "./ScrollingArea.vue";
import { TimePeriodMonth } from "../../helpers/time-period-month";

describe("ScrollingArea", () => {
    const windowIntersectionObserver = window.IntersectionObserver;
    const elementScrollTo = Element.prototype.scrollTo;

    afterEach(() => {
        window.IntersectionObserver = windowIntersectionObserver;
        Element.prototype.scrollTo = elementScrollTo;
    });

    function aScrollingArea(): Wrapper<ScrollingArea> {
        return shallowMount(ScrollingArea, {
            propsData: {
                time_period: new TimePeriodMonth(
                    new Date("2020-03-31T22:00:00.000Z"),
                    new Date("2020-04-31T22:00:00.000Z"),
                    "en-US"
                ),
                now: new Date(),
            },
        });
    }

    it("Auto scroll to today once mounted", () => {
        const observe = jest.fn();
        const mockIntersectionObserver = jest.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        Element.prototype.scrollTo = (): void => {
            // mock implementation
        };
        const scrollTo = jest.spyOn(Element.prototype, "scrollTo");

        aScrollingArea();

        expect(scrollTo).toHaveBeenCalledWith({
            top: 0,
            left: 0,
            behavior: "smooth",
        });
    });

    it("displays an empty pixel to detect if user has scrolled", () => {
        const observe = jest.fn();
        const mockIntersectionObserver = jest.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const wrapper = aScrollingArea();

        expect(wrapper.vm.$refs.empty_pixel).toBeTruthy();
        expect(observe).toHaveBeenCalledWith(wrapper.vm.$refs.empty_pixel);
    });

    it("emits is_scrolling = true if pixel is not anymore intersecting with the scrolling area", async () => {
        const observe = (): void => {
            // mocking observe
        };
        const mockIntersectionObserver = jest.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const wrapper = aScrollingArea();

        const observerCallback = mockIntersectionObserver.mock.calls[0][0];
        await observerCallback([{ isIntersecting: false, target: wrapper.vm.$refs.empty_pixel }]);

        const events = wrapper.emitted();
        expect(events).toEqual({ is_scrolling: [[true]] });
    });

    it("emits is_scrolling = false if pixel is intersecting with the scrolling area", async () => {
        const observe = (): void => {
            // mocking observe
        };
        const mockIntersectionObserver = jest.fn();
        mockIntersectionObserver.mockReturnValue({
            observe,
        });
        window.IntersectionObserver = mockIntersectionObserver;

        const wrapper = aScrollingArea();

        const observerCallback = mockIntersectionObserver.mock.calls[0][0];
        await observerCallback([{ isIntersecting: true, target: wrapper.vm.$refs.empty_pixel }]);

        const events = wrapper.emitted();
        expect(events).toEqual({ is_scrolling: [[false]] });
    });
});
