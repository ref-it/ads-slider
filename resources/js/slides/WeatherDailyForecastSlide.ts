import { Manager, SlideEvents } from "../manager.js";
import { Slide } from "./Slide.js";

export class WeatherDailyForecastSlide extends Slide {

    private showSlideFor = 20000;

    private slideTimeout: ReturnType<typeof setTimeout> | null = null;

    onStart(): void {
        super.onStart();
        Manager.Instance.restoreMainColor();
        this.displaySlideWithAnimation('fadeInLeft');
        console.log("WeatherDailyForecast started");
    }

    onResume(): void {
        super.onResume();
        if (!this.slideTimeout) {
            this.slideTimeout = setTimeout(() => {
                this.notifyMediator(SlideEvents.DONE);
            }, this.showSlideFor);
        }
    }

    onStop(): void {
        super.onStop();
        this.clearTimeout();
        this.hideSlideWithAnimation('fadeOutLeft');
    }

    onDestroy(): void {
        super.onDestroy();
        this.div.remove();
        console.log("WeatherDailyForecast destroyed");
    }

    next(): void {
        this.clearTimeout();
        this.notifyMediator(SlideEvents.DONE);
    }

    private clearTimeout() {
        if (this.slideTimeout) {
            clearTimeout(this.slideTimeout);
            this.slideTimeout = null;
        }
    }
}
