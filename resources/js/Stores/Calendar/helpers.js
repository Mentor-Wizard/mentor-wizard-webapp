/**
 * Adjusts the current date based on the specified direction and view type
 * @param {Date} currentDate - The current date
 * @param {string} currentTab - The current view tab ('Day view', 'Week view', 'Month view')
 * @param {string} direction - Direction to adjust ('previous', 'next', 'exact date')
 * @param {string|null} exactDate - Exact date string when direction is 'exact date'
 * @returns {Object} - Object containing the new date and potentially updated tab
 */
export const adjustDate = (currentDate, currentTab, direction, exactDate = null) => {
    const newDate = new Date(currentDate);
    let newTab = currentTab;

    switch (currentTab) {
        case 'Day view':
            if (direction === 'previous') {
                newDate.setDate(currentDate.getDate() - 1);
            } else if (direction === 'next') {
                newDate.setDate(currentDate.getDate() + 1);
            } else if (direction === "exact date") {
                const targetDate = new Date(exactDate);
                newDate.setDate(targetDate.getDate());
                newDate.setMonth(targetDate.getMonth());
                newDate.setFullYear(targetDate.getFullYear());
            }
            break;
        case 'Week view':
            if (direction === 'previous') {
                newDate.setDate(currentDate.getDate() - 7);
            } else if (direction === 'next') {
                newDate.setDate(currentDate.getDate() + 7);
            } else if (direction === "exact date") {
                const targetDate = new Date(exactDate);
                newDate.setDate(targetDate.getDate());
                newDate.setMonth(targetDate.getMonth());
                newDate.setFullYear(targetDate.getFullYear());
                newTab = 'Day view';
            }
            break;
        case 'Month view':
            if (direction === 'previous') {
                newDate.setMonth(currentDate.getMonth() - 1);
            } else if (direction === 'next') {
                newDate.setMonth(currentDate.getMonth() + 1);
            } else if (direction === "exact date") {
                const targetDate = new Date(exactDate);
                newDate.setDate(targetDate.getDate());
                newDate.setMonth(targetDate.getMonth());
                newDate.setFullYear(targetDate.getFullYear());
                newTab = 'Day view';
            }
            break;
        default:
            console.error('Invalid tab specified:', currentTab);
            return { date: currentDate, tab: currentTab };
    }

    return { date: newDate, tab: newTab };
};

/**
 * Formats the week range label for a given date, e.g. "02 серп - 08 серп"
 * Works in plain JavaScript; types are expressed via JSDoc.
 * @param {Date|string|number} currentDate
 * @param {string} [locale="uk-UA"]
 * @returns {string}
 */

export const formatWeekRange = (currentDate,locale) => {
    const d = new Date(currentDate);
    const day = d.getDay() === 0 ? 7 : d.getDay();
    const start = new Date(d);
    start.setHours(0, 0, 0, 0);
    start.setDate(d.getDate() - (day - 1));

    const end = new Date(start);
    end.setDate(start.getDate() + 6);
    const localeValue = typeof locale === 'string' ? locale : String(locale || "uk-UA");
    const monthFormatted = new Intl.DateTimeFormat(localeValue, {month: "short"});
    const pad2 = (n) => String(n).padStart(2, "0");
    const cleanMonth = (m) => m.replace(/\.$/, "");
    const startLabel = `${pad2(start.getDate())} ${cleanMonth(monthFormatted.format(start))}`;
    const endLabel = `${pad2(end.getDate())} ${cleanMonth(monthFormatted.format(end))}`;
    return `${startLabel} - ${endLabel}`;
}
