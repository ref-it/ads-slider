import dayjs from 'dayjs/esm/index.js'
import { addLeadingZero } from "./time.js";
import { hideElement, showElement } from "./misc.js";

export class Countdown {
    private static countdownIsRunning = false;
    private static countdownRuntimeIsRunning = false;
    private static previousTimeStamp: DOMHighResTimeStamp | null = null;
    private static timeRemaining = 1;
    private static endDateTime = 0;

    private static callback: (() => void) | null = null;

    private static days = 0;
    private static hours = 0;
    private static minutes = 0;
    private static seconds = 0;

    private static s: HTMLSpanElement;
    private static m: HTMLSpanElement | null = null;
    private static h: HTMLSpanElement | null = null;
    private static d: HTMLSpanElement | null = null;
    private static bar: HTMLElement | null = null;
    private static barStartDateTime: number | null = null;
    private static totalDuration = 1;

    // barStartDate is the actual start of the tracked period (e.g. the Happy
    // Hour's start time), used to size the bar's total range. Without it, the
    // bar's range would be "now until end", so activating the monitor midway
    // through would wrongly show a full bar instead of the true elapsed share.
    constructor(endDate: string, seconds: HTMLSpanElement, minutes: HTMLSpanElement | null = null, hours: HTMLSpanElement | null = null, days: HTMLSpanElement | null = null, bar: HTMLElement | null = null, barStartDate: string | null = null) {
        this.setEndDate(endDate);
        Countdown.s = seconds;
        Countdown.m = minutes;
        Countdown.h = hours;
        Countdown.d = days;
        Countdown.bar = bar;
        Countdown.barStartDateTime = barStartDate ? new Date(barStartDate).getTime() : null;

        Countdown.countdownIsRunning = false;
        Countdown.countdownRuntimeIsRunning = false;
        Countdown.previousTimeStamp = null;
    }

    static setCallback(cb: () => void) {
        Countdown.callback = cb;
    }

    setEndDate(endDate: string): void {
        Countdown.endDateTime = new Date(endDate).getTime();
    }

    static isRunning(): boolean {
        return Countdown.countdownIsRunning;
    }

    start(): void {
        if (Countdown.countdownRuntimeIsRunning) {
            return;
        }
        Countdown.countdownIsRunning = true;
        Countdown.totalDuration = Countdown.barStartDateTime !== null
            ? Math.max((Countdown.endDateTime - Countdown.barStartDateTime) / 1000, 1)
            : Math.max((Countdown.endDateTime - Date.now()) / 1000, 1);
        //const $countdownDiv = $('.countdown');
        //$countdownDiv.css('color', 'inherit');

        if (Countdown.s) {
            showElement(Countdown.s);
        }
        if (Countdown.m) {
            showElement(Countdown.m);
        }
        if (Countdown.h) {
            showElement(Countdown.h);
        }
        if (Countdown.d) {
            showElement(Countdown.d);
        }

        requestAnimationFrame(Countdown.calculate);
    }

    stop(ignoreCallback = false): void {
        Countdown.countdownIsRunning = false;
        Countdown.cleanUp();
        if (!ignoreCallback && Countdown.callback) {
            Countdown.callback();
            Countdown.callback = null;
        }
    }

    private static cleanUp() {
        document.body.style.removeProperty('background'); // color is defined in background-color
        if (Countdown.bar) {
            Countdown.bar.style.removeProperty('height');
        }
    }

    private static calculate(timestamp: DOMHighResTimeStamp): void {
        if (!Countdown.countdownIsRunning) {
            Countdown.countdownRuntimeIsRunning = false;
            return;
        }
        Countdown.countdownRuntimeIsRunning = true;
        if (Countdown.previousTimeStamp === null) {
            Countdown.previousTimeStamp = timestamp;
            Countdown.computeUpdate();
        }
        const tick = timestamp - Countdown.previousTimeStamp;

        if (tick >= 1000) {
            Countdown.computeUpdate();
            Countdown.previousTimeStamp = timestamp;
        }
        if (Countdown.timeRemaining > 0) {
            requestAnimationFrame(Countdown.calculate); // 10 Seconds
        } else {
            Countdown.countdownRuntimeIsRunning = false;
        }
    }

    private static computeUpdate() {
        console.debug("[Update timer]");
        const startDate: number = dayjs().toDate().getTime();
        Countdown.timeRemaining = (Countdown.endDateTime - startDate) / 1000;
        if (Countdown.timeRemaining > 0 && Countdown.countdownIsRunning) {
            if (Countdown.bar) {
                // Dedicated shrinking bar (e.g. the Happy Hour band) already visualizes
                // the remaining time, so the whole-page background sweep below is skipped.
                const fraction = Math.max(0, Math.min(1, Countdown.timeRemaining / Countdown.totalDuration));
                Countdown.bar.style.height = `${fraction * 100}%`;
            } else {
                // #141824 keeps the gradient's blue-grey hue but stays dark enough that
                // the red urgency text (main-color/red) still clears WCAG AA contrast (>=3:1)
                document.body.style.background = `linear-gradient(90deg, #000000 ${100 - Countdown.timeRemaining / 9}%, #141824 0%)`;
            }
            // Skipped when a dedicated bar exists (e.g. Happy Hour): that bar already
            // shows urgency, and red text would sit on the band's own red/grey fill
            // with too little contrast to stay legible.
            if (!Countdown.bar) {
                if (Countdown.timeRemaining <= 300 /* 5 minutes */) {
                    if (Countdown.s) {
                        (Countdown.s.parentNode as HTMLDivElement).style.color = 'red';
                    }
                } else {
                    if (Countdown.s) {
                        (Countdown.s.parentNode as HTMLDivElement).style.removeProperty('color');
                    }
                }
            }
            Countdown.days = Math.floor(Countdown.timeRemaining / 86400);
            Countdown.timeRemaining %= 86400;

            Countdown.hours = Math.floor(Countdown.timeRemaining / 3600);
            Countdown.timeRemaining %= 3600;
            if (Countdown.h) {
                // Based on the countdown's total length, not the remaining time, so the
                // format (00:00:00 vs 00:00) doesn't change partway through the countdown.
                if (Countdown.totalDuration <= 3600) {
                    hideElement(Countdown.h);
                } else {
                    showElement(Countdown.h);
                }
            }
            if (Countdown.d && Countdown.days < 1) {
                hideElement(Countdown.d);
            }

            Countdown.minutes = Math.floor(Countdown.timeRemaining / 60);
            Countdown.timeRemaining %= 60;

            Countdown.seconds = Math.floor(Countdown.timeRemaining);

            if (Countdown.d) {
                Countdown.d.innerText = String(Countdown.days);
            }
            if (Countdown.h) {
                Countdown.h.innerText = addLeadingZero(Countdown.hours);
            }
            if (Countdown.m) {
                Countdown.m.innerText = addLeadingZero(Countdown.minutes);
            }
            if (Countdown.s) {
                Countdown.s.innerText = addLeadingZero(Countdown.seconds);
            }
        } else {
            // Time's up
            console.log("Countdown expired");
            Countdown.countdownIsRunning = false;
            Countdown.cleanUp();
            if (Countdown.callback) {
                Countdown.callback();
                Countdown.callback = null;
            }
            //setTimeout(evaluateEvents, 1200, true);
        }
    }
}