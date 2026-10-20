(function () {
    'use strict';

    var dialog = document.getElementById('archiveOrganizationDialog');
    if (!dialog) {
        var card = document.getElementById(window.location.hash.slice(1));
        if (card && card.classList.contains('archive-org-card')) {
            var link = card.querySelector('a');
            if (link) link.focus({ preventScroll: true });
        }
        return;
    }

    function closeArchive() {
        document.body.classList.remove('archive-modal-open');
        window.location.replace(dialog.dataset.closeUrl);
    }

    dialog.querySelector('[data-archive-close]').addEventListener('click', function (event) {
        event.preventDefault();
        closeArchive();
    });
    dialog.addEventListener('cancel', function (event) {
        event.preventDefault();
        closeArchive();
    });
    dialog.addEventListener('click', function (event) {
        if (event.target !== dialog) return;
        var rect = dialog.getBoundingClientRect();
        if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) closeArchive();
    });
    document.body.classList.add('archive-modal-open');
    dialog.showModal();
})();
