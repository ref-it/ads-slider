import { SlideEvents } from "../manager.js";
import { Countdown } from "../utilities/Countdown.js";
import { autosizeText, fillInComponentSafe } from "../utilities/misc.js";
import dayjs from 'dayjs/esm/index.js';
import { UnskippableSlide } from "./UnskippableSlide.js";

export class LastCallSlide extends UnskippableSlide {
    private countdown: Countdown | null = null;
    private deadline: dayjs.Dayjs | null = null;

    initSlide(deadline: dayjs.Dayjs, start: dayjs.Dayjs, name: string) {
        this.deadline = deadline;
        fillInComponentSafe(document.querySelector('#final-round-content .event-name') as HTMLDivElement, name);
        this.countdown = new Countdown(this.deadline.format('YYYY-MM-DDTHH:mm:ss'), document.querySelector('#final-round-container .seconds') as HTMLSpanElement, document.querySelector('#final-round-container .minutes') as HTMLSpanElement, null, null, document.querySelector('#final-round-countdown-bar') as HTMLDivElement, start.format('YYYY-MM-DDTHH:mm:ss'));
    }

    onResume(): void {
        super.onResume();
        this.displaySlide();

        // this has to be done after the slide is visible, otherwise it does not work
        autosizeText(document.querySelector('#final-round-content .event-name') as HTMLDivElement);

        if (!this.deadline || !this.countdown) {
            throw "Initialize the deadline, first";
        }

        Countdown.setCallback(() => {
            this.notifyMediator(SlideEvents.DONE);
        });
        this.countdown?.start();
    }


    onStop(): void {
        super.onStop();
        this.hideSlideWithAnimation('bounceOutUp');
        this.countdown?.stop(true);
    }
}