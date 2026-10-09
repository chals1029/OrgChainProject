(() => {
    'use strict';
    let root;
    let data;
    let dialog;
    let loading = false;
    let dayKey = null;
    let dayLabel = '';
    const mobileView = window.matchMedia('(max-width: 540px)');

    function syncViewControls() {
        const mode = root.dataset.view === 'auto'
            ? (mobileView.matches ? 'agenda' : 'month')
            : root.dataset.view;
        root.querySelectorAll('[data-cal-view]').forEach(link => {
            if (link.dataset.calView === mode) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });
    }

    function initialize() {
        root = document.getElementById('orgCalendarRoot');
        if (!root) return;
        data = JSON.parse(root.querySelector('#orgCalendarData').textContent);
        dialog = root.querySelector('#orgCalendarDialog');
        root.querySelector('input[name="view"]').value = root.dataset.view;
        syncViewControls();
        dayKey = null;
        dayLabel = '';
    }

    function filterUrl(form) {
        const url = new URL(form.action, window.location.href);
        new FormData(form).forEach((value, key) => {
            if (value !== '' && value !== 'all' && value !== 'auto') url.searchParams.set(key, value);
        });
        return url;
    }

    async function navigate(url) {
        if (loading) return;
        loading = true;
        const focused = document.activeElement;
        let focusSelector = '#orgCalendarMonth';
        if (root.contains(focused)) {
            if (focused.name) focusSelector = `#orgCalendarFilters [name="${CSS.escape(focused.name)}"]`;
            else if (focused.matches('[data-cal-view]')) focusSelector = `[data-cal-view="${focused.dataset.calView}"]`;
            else if (focused.matches('[data-cal-today]')) focusSelector = '[data-cal-today]';
            else if (focused.matches('.cal-nav[aria-label]')) focusSelector = `.cal-nav[aria-label="${CSS.escape(focused.getAttribute('aria-label'))}"]`;
        }
        root.setAttribute('aria-busy', 'true');
        root.querySelector('#orgCalendarFeedback').textContent = 'Loading schedule…';
        root.querySelectorAll('#orgCalendarFilters input, #orgCalendarFilters select, #orgCalendarFilters button').forEach(control => { control.disabled = true; });
        try {
            const response = await fetch(url.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('Calendar navigation failed.');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const replacement = page.getElementById('orgCalendarRoot');
            if (!replacement) throw new Error('Calendar response has no schedule.');
            // Validate before replacing the working calendar.
            JSON.parse(replacement.querySelector('#orgCalendarData').textContent);
            if (dialog.open) dialog.close();
            root.replaceWith(replacement);
            if (url.href !== window.location.href) history.pushState({}, '', url.href);
            initialize();
            const focusTarget = root.querySelector(focusSelector) || root.querySelector('#orgCalendarMonth');
            if (focusTarget.matches('h2')) focusTarget.tabIndex = -1;
            focusTarget.focus({ preventScroll: true });
            root.querySelector('#orgCalendarFeedback').textContent = `${root.querySelector('#orgCalendarMonth').textContent} schedule loaded.`;
        } catch (error) {
            // The ordinary GET route remains the fallback (including expired login).
            window.location.assign(url.href);
        } finally {
            loading = false;
            root.removeAttribute('aria-busy');
        }
    }

    function dayEvents() {
        return (data.days[dayKey] || []).map(id => data.events[id]).filter(Boolean);
    }

    function eventChoice(event) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `cal-event cal-scope-${event.scope_key}`;
        button.dataset.calChoice = event.id;
        const title = document.createElement('strong');
        title.textContent = event.title;
        const meta = document.createElement('span');
        meta.className = 'cal-event-meta';
        meta.textContent = `${event.date_label} · ${event.time_label} · ${event.location}`;
        const tags = document.createElement('span');
        tags.className = 'cal-event-tags';
        const status = document.createElement('span');
        status.className = `cal-status cal-status-${event.status_key}`;
        status.textContent = event.status;
        const timing = document.createElement('span');
        timing.className = `cal-timing cal-timing-${event.timing_key}`;
        timing.textContent = event.timing_label;
        tags.append(status, timing);
        button.append(title, meta, tags);
        return button;
    }

    function showDayList() {
        const items = dayEvents();
        const list = root.querySelector('#calDialogList');
        root.querySelector('#calDialogTitle').textContent = dayLabel;
        list.replaceChildren();
        if (items.length) {
            items.forEach(event => list.append(eventChoice(event)));
        } else {
            const empty = document.createElement('p');
            empty.className = 'cal-side-empty';
            empty.textContent = 'No activities match this day and your current filters.';
            list.append(empty);
        }
        list.hidden = false;
        root.querySelector('#calDialogDetails').hidden = true;
        root.querySelector('#calDetailLink').hidden = true;
        root.querySelector('#calDialogBack').hidden = true;
    }

    function showDetails(id) {
        const event = data.events[id];
        if (!event) return;
        root.querySelector('#calDialogTitle').textContent = event.title;
        root.querySelector('#calDialogList').hidden = true;
        root.querySelector('#calDialogDetails').hidden = false;
        const status = root.querySelector('#calDetailStatus');
        status.className = `cal-status cal-status-${event.status_key}`;
        status.textContent = event.status;
        const timing = root.querySelector('#calDetailTiming');
        timing.className = `cal-timing cal-timing-${event.timing_key}`;
        timing.textContent = event.timing_label;
        root.querySelector('#calDetailDate').textContent = event.date_label;
        root.querySelector('#calDetailTime').textContent = event.time_label;
        root.querySelector('#calDetailVenue').textContent = event.location;
        root.querySelector('#calDetailScope').textContent = event.scope_key === 'off' ? 'Off-campus' : 'In-campus';
        root.querySelector('#calDetailNote').textContent = event.note || 'No additional details provided.';
        const link = root.querySelector('#calDetailLink');
        link.href = event.detail_url;
        link.hidden = false;
        root.querySelector('#calDialogBack').hidden = !dayKey || dayEvents().length < 2;
    }

    document.addEventListener('submit', event => {
        if (!root || event.target !== root.querySelector('#orgCalendarFilters')) return;
        event.preventDefault();
        navigate(filterUrl(event.target));
    });

    document.addEventListener('change', event => {
        if (!root || !event.target.matches('#orgCalendarFilters select') || !root.contains(event.target)) return;
        navigate(filterUrl(event.target.form));
    });

    document.addEventListener('click', event => {
        if (!root || !(event.target instanceof Element) || !root.contains(event.target)) return;
        const navigation = event.target.closest('[data-cal-nav]');
        if (navigation) {
            if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            const url = new URL(navigation.href, window.location.href);
            url.searchParams.set('view', navigation.dataset.calView || root.dataset.view);
            navigate(url);
            return;
        }
        if (event.target.closest('[data-cal-close]')) {
            dialog.close();
            return;
        }
        if (event.target.closest('#calDialogBack')) {
            showDayList();
            return;
        }
        const choice = event.target.closest('[data-cal-choice]');
        if (choice) {
            showDetails(choice.dataset.calChoice);
            return;
        }
        const eventButton = event.target.closest('[data-cal-event]');
        if (eventButton) {
            dayKey = null;
            dayLabel = '';
            showDetails(eventButton.dataset.calEvent);
            dialog.showModal();
            return;
        }
        const day = event.target.closest('[data-cal-day]');
        if (day) {
            root.querySelectorAll('.cal-day.is-selected').forEach(cell => cell.classList.remove('is-selected'));
            day.classList.add('is-selected');
            dayKey = day.dataset.calDay;
            dayLabel = day.dataset.dateLabel;
            const items = dayEvents();
            if (items.length === 1) showDetails(items[0].id);
            else showDayList();
            dialog.showModal();
            return;
        }
        if (event.target === dialog) {
            const rect = dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
        }
    });

    window.addEventListener('popstate', () => window.location.reload());
    mobileView.addEventListener('change', () => { if (root) syncViewControls(); });
    initialize();
})();
