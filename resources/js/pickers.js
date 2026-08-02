import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

const datePickerSelector = '[data-picker="date"]';
const timePickerSelector = '[data-picker="time"]';

function initDatePicker(element) {
    if (element._flatpickr) {
        return;
    }

    const options = {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'M j, Y',
        allowInput: true,
        disableMobile: true,
    };

    if (element.dataset.maxDate === 'today') {
        options.maxDate = 'today';
    } else if (element.dataset.maxDate) {
        options.maxDate = element.dataset.maxDate;
    }

    if (element.dataset.minDate) {
        options.minDate = element.dataset.minDate;
    }

    flatpickr(element, options);
}

function initTimePicker(element) {
    if (element._flatpickr) {
        return;
    }

    flatpickr(element, {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        time_24hr: true,
        allowInput: true,
        disableMobile: true,
    });
}

function initPickers(root = document) {
    root.querySelectorAll(datePickerSelector).forEach(initDatePicker);
    root.querySelectorAll(timePickerSelector).forEach(initTimePicker);
}

document.addEventListener('DOMContentLoaded', () => initPickers());

export { initPickers };
