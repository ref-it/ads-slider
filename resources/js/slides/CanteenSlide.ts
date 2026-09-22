import dayjs from 'dayjs/esm/index.js'
import isBetween from 'dayjs/esm/plugin/isBetween/index.js'
dayjs.extend(isBetween);

import { CanteenMeal, CanteenSlideData } from "../types.js";
import { Mediator } from "../patterns/Mediator.js";
import { Manager, SlideEvents } from '../manager.js';
import { Slide } from "./Slide.js";

interface CanteenPage {
    title: string;
    meals: CanteenMeal[];
}

export class CanteenSlide extends Slide {

    // Per-page hold time, not per-canteen - a canteen with many dishes
    // spans several pages, each shown for this long.
    private showPageFor = 15000;
    private mealsPerPage = 4;
    private currentIndex = -1;
    private pages: CanteenPage[] = [];
    private itemsContainer: HTMLDivElement | null = null;
    private titleElement: HTMLDivElement | null = null;
    private slideTimeout: ReturnType<typeof setTimeout> | null = null;
    private studentsLabel = 'Students';
    private employeesLabel = 'Employees';
    private guestsLabel = 'Guests';

    constructor(mediator: Mediator, mainDiv: HTMLDivElement) {
        super(mediator, mainDiv);
    }

    setCanteens(canteens: CanteenSlideData[]) {
        console.log("[Canteen] Set canteens");
        const activeCanteens = canteens.filter((slide) => dayjs().isBetween(slide.startDate, slide.endDate) && !!slide.scheduleable.menu);
        this.pages = activeCanteens.flatMap((slide) => this.buildPages(slide));
        console.log(`[Canteen] After filtering: ${activeCanteens.length} canteens, ${this.pages.length} pages`);
        this.notifyMediator(SlideEvents.DATA_UPDATED);
    }

    private buildPages(slide: CanteenSlideData): CanteenPage[] {
        const menu = slide.scheduleable.menu;
        const meals = menu && menu.lunch.length > 0 ? menu.lunch : (menu?.dinner ?? []);

        if (meals.length === 0) {
            return [];
        }

        const pages: CanteenPage[] = [];
        for (let i = 0; i < meals.length; i += this.mealsPerPage) {
            pages.push({
                title: slide.scheduleable.name,
                meals: meals.slice(i, i + this.mealsPerPage),
            });
        }
        return pages;
    }

    onStart(): void {
        this.itemsContainer = <HTMLDivElement>document.getElementById('canteenItems');
        this.titleElement = <HTMLDivElement>document.getElementById('canteenTitle');
        this.studentsLabel = this.div.dataset.studentsLabel ?? this.studentsLabel;
        this.employeesLabel = this.div.dataset.employeesLabel ?? this.employeesLabel;
        this.guestsLabel = this.div.dataset.guestsLabel ?? this.guestsLabel;
        this.currentIndex = -1;

        if (this.pages.length === 0) {
            console.log("[Canteen] No canteens to display");
            this.notifyMediator(SlideEvents.NOTHING);
            return;
        }

        super.onStart();
        this.displaySlide();
        // The clock's default bottom-right position collides with the meal
        // list; move it out of the way while this slide is showing.
        Manager.Instance.setClockPosition(3);
    }

    onResume(): void {
        super.onResume();
        this.nextPage();
    }

    onPause(): void {
        super.onPause();
        if (this.slideTimeout) {
            clearTimeout(this.slideTimeout);
            this.slideTimeout = null;
        }
    }

    onStop(): void {
        super.onStop();
        if (this.slideTimeout) {
            clearTimeout(this.slideTimeout);
            this.slideTimeout = null;
        }
        Manager.Instance.restoreClockPosition();
        this.hideSlide();
        if (this.itemsContainer) {
            this.itemsContainer.innerHTML = "";
        }
        this.itemsContainer = null;
        this.titleElement = null;
    }

    onDestroy(): void {
        super.onDestroy();
        this.div.remove();
    }

    next(): void {
        this.clearTimeoutIfSet();
        this.onPause();
        this.onResume();
    }

    private clearTimeoutIfSet(): void {
        if (this.slideTimeout) {
            clearTimeout(this.slideTimeout);
            this.slideTimeout = null;
        }
    }

    private mealBox(meal: CanteenMeal): string {
        const boxClass = meal.isVegan
            ? 'canteenMealBox canteenMealBox--vegan'
            : meal.isVegetarian
                ? 'canteenMealBox canteenMealBox--vegetarian'
                : 'canteenMealBox';

        const formatPrice = (value: number) => value.toFixed(2).replace('.', ',') + ' €';

        const priceEntry = (label: string, value: number, primary = false) => `
            <div class="canteenPriceEntry">
                <span class="canteenPriceLabel">${label}</span>
                <span class="canteenPrice${primary ? '' : ' canteenPrice--secondary'}">${formatPrice(value)}</span>
            </div>
        `;

        const prices = meal.prices?.students
            ? `<div class="canteenPriceRow">
                ${priceEntry(this.studentsLabel, meal.prices.students, true)}
                ${priceEntry(this.employeesLabel, meal.prices.employees)}
                ${priceEntry(this.guestsLabel, meal.prices.guests)}
            </div>`
            : '';

        return `
            <div class="${boxClass}">
                <span class="canteenMealName">${meal.name}</span>
                ${prices}
            </div>
        `;
    }

    private showPage(): void {
        if (!this.itemsContainer) {
            throw new Error("CanteenItems container not initialized");
        }

        const page = this.pages[this.currentIndex];

        if (this.titleElement) {
            this.titleElement.textContent = page.title;
        }

        this.itemsContainer.innerHTML = page.meals.map((meal) => this.mealBox(meal)).join('');
    }

    private nextPage(): void {
        console.log("[Canteen] Next page");
        this.currentIndex = this.currentIndex + 1;
        if (this.currentIndex >= this.pages.length) {
            console.log("[Canteen] end of list reached");
            this.notifyMediator(SlideEvents.CYCLE_END);
            return;
        }

        this.showPage();

        this.clearTimeoutIfSet();
        this.slideTimeout = setTimeout(() => {
            this.nextPage();
        }, this.showPageFor);
    }
}
