(function () {
    'use strict';

    const wrap = document.querySelector('.org-bell-wrap');
    if (!wrap) return;

    const list = wrap.querySelector('.org-notif-list');
    const empty = document.getElementById('orgNotifEmptyState');
    const badge = document.getElementById('orgBellBadge');
    const count = document.getElementById('orgNotifCount');
    const markRead = document.getElementById('orgNotifMarkReadBtn');
    const storageKey = 'org_office_read_notifs_v2:' + wrap.dataset.officeId;
    const readIds = new Set();
    let fetching = false;

    function loadReadIds() {
        try {
            const ids = JSON.parse(localStorage.getItem(storageKey) || '[]');
            if (Array.isArray(ids)) ids.forEach((id) => { if (typeof id === 'string') readIds.add(id); });
        } catch (error) { /* Keep the in-memory read state when storage is unavailable. */ }
    }

    function saveReadIds() {
        loadReadIds();
        try { localStorage.setItem(storageKey, JSON.stringify([...readIds])); } catch (error) { /* Continue in memory. */ }
    }

    function updateCounts() {
        const unread = list.querySelectorAll('.org-notif-item').length;
        badge.hidden = unread === 0;
        badge.style.display = unread === 0 ? 'none' : '';
        badge.textContent = Math.min(unread, 99);
        count.textContent = unread + ' new';
        markRead.disabled = unread === 0;
        empty.hidden = unread !== 0;
    }

    function filterRead() {
        list.querySelectorAll('.org-notif-item').forEach((item) => {
            if (readIds.has(item.dataset.notifId)) item.remove();
        });
        updateCounts();
    }

    function element(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function makeItem(notification) {
        const item = element('a', 'org-notif-item');
        item.href = notification.url;
        item.dataset.notifId = notification.id;
        const type = notification.type;
        const icon = element('span', 'org-notif-item-icon ' + (type === 'announcement' ? 'is-announcement' : type === 'renewal' ? 'is-renewal' : 'is-activity'));
        icon.setAttribute('aria-hidden', 'true');
        icon.appendChild(element('i', 'bi ' + (type === 'announcement' ? 'bi-megaphone-fill' : type === 'renewal' ? 'bi-arrow-repeat' : type === 'report' ? 'bi-file-earmark-bar-graph' : 'bi-file-earmark-check-fill')));
        const content = element('span', 'org-notif-item-content');
        const meta = element('span', 'org-notif-item-meta');
        meta.appendChild(element('span', 'org-notif-tag', type === 'activity' ? notification.detail.toUpperCase() : type.toUpperCase()));
        const time = element('time', '', notification.time_label);
        time.dateTime = notification.at;
        meta.appendChild(time);
        content.appendChild(meta);
        content.appendChild(element('strong', 'org-notif-item-title', notification.title));
        if (type === 'renewal' || type === 'report') content.appendChild(element('span', 'org-notif-item-detail', notification.detail));
        const chevron = element('i', 'bi bi-chevron-right org-notif-chevron');
        chevron.setAttribute('aria-hidden', 'true');
        item.append(icon, content, chevron);
        return item;
    }

    function validNotification(notification) {
        if (!notification || !['activity', 'announcement', 'renewal', 'report'].includes(notification.type)) return false;
        if (!['id', 'title', 'detail', 'url', 'at', 'time_label'].every((key) => typeof notification[key] === 'string')) return false;
        try {
            const url = new URL(notification.url, window.location.href);
            return (url.protocol === 'http:' || url.protocol === 'https:') && url.origin === window.location.origin;
        } catch (error) { return false; }
    }

    async function poll() {
        if (document.hidden || fetching) return;
        fetching = true;
        try {
            const response = await fetch(wrap.dataset.notificationsUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin', cache: 'no-store', redirect: 'error'
            });
            if (!response.ok) return;
            const data = await response.json();
            if (!Array.isArray(data.notifications) || !data.notifications.every(validNotification)) return;
            loadReadIds();
            const focusedId = list.contains(document.activeElement) ? document.activeElement.dataset.notifId : null;
            const items = data.notifications.filter((notification) => !readIds.has(notification.id)).map(makeItem);
            list.replaceChildren(...items, empty);
            updateCounts();
            if (focusedId) {
                const focused = items.find((item) => item.dataset.notifId === focusedId);
                if (focused) focused.focus({ preventScroll: true });
            }
        } catch (error) { /* Preserve the current list on network, authentication, or parsing failures. */ }
        finally { fetching = false; }
    }

    list.addEventListener('click', (event) => {
        const item = event.target.closest('.org-notif-item');
        if (!item || !list.contains(item)) return;
        readIds.add(item.dataset.notifId);
        saveReadIds();
        item.remove();
        updateCounts();
    });
    markRead.addEventListener('click', (event) => {
        event.stopPropagation();
        list.querySelectorAll('.org-notif-item').forEach((item) => readIds.add(item.dataset.notifId));
        saveReadIds();
        filterRead();
    });
    window.addEventListener('storage', (event) => {
        if (event.key === storageKey) {
            loadReadIds();
            filterRead();
        }
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
    loadReadIds();
    filterRead();
    if (!document.hidden) poll();
    window.setInterval(poll, 30000);
})();
