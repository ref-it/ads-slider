import dayjs from 'dayjs/esm/index.js'
import _ from '../localization.js';

export function addLeadingZero(number: number): string {
    return `0${number}`.slice(-2);
}

export function getFormattedTime(hours: number, minutes: number): string {
    return `${addLeadingZero(hours)}:${addLeadingZero(minutes)}`;
}

/**
 * @param eventDate
 * @param alwaysFull skip the abbreviated fallbacks (kept for narrow displays, e.g. MiniSlides) and always spell out the weekday/month in full
 * @returns the formatted start date of the event or today in the local language
 */
export function getDisplayDate(eventDate: dayjs.Dayjs, locale = "en", alwaysFull = false): string {
    const now = dayjs();
    if (eventDate.isSame(now, 'day') && now.hour() > 6) {
        return _._('today', locale) + "!";
    }
    switch (locale) {
        case "de":
            {
                const longVersion = eventDate.format('dddd, D. MMMM');
                if (alwaysFull || longVersion.length < 17)
                    return longVersion;
                const mediumVersion = eventDate.format('dd, D. MMMM');
                if (mediumVersion.length < 17)
                    return mediumVersion;
                return eventDate.format('dddd, D. MMM');
            }
        case "it":
            {
                const longVersion = eventDate.format('dddd D MMMM');
                if (alwaysFull || longVersion.length < 17)
                    return longVersion;
                const mediumVersion = eventDate.format('ddd. D MMMM');
                if (mediumVersion.length < 17)
                    return mediumVersion;
                return eventDate.format('dddd D MMM.');
            }
        case "en":
        default:
            {
                const longVersion = eventDate.format('dddd D MMMM');
                if (alwaysFull || longVersion.length < 17)
                    return longVersion;
                const mediumVersion = eventDate.format('dd. D MMMM');
                if (mediumVersion.length < 17)
                    return mediumVersion;
                return eventDate.format('dddd D MMM');
            }
    }
}