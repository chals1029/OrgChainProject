(function () {
    'use strict';

    const form = document.getElementById('soExpenseForm');
    if (!form) return;

    const mainInput = document.getElementById('soReceiptInput');
    const cameraInput = document.getElementById('soCameraInput');
    const dropzone = document.getElementById('soReceiptDropzone');
    const items = document.getElementById('soExpenseItems');
    const rowTemplate = document.getElementById('soExpenseRowTemplate');
    const previewBox = document.getElementById('soReceiptPreview');
    const status = document.getElementById('soReceiptUploadStatus');
    const maxBytes = 5 * 1024 * 1024;
    const maxFiles = 3;
    const imageTypes = new Set(['image/jpeg', 'image/png', 'image/webp']);
    const imageExtensions = new Set(['jpg', 'jpeg', 'png', 'webp']);
    let selectedFiles = [];
    let cameraStream = null;
    let dragDepth = 0;

    const rows = () => Array.from(form.querySelectorAll('[data-expense-row]'));

    const formatBytes = (bytes) => {
        if (!Number.isFinite(bytes) || bytes <= 0) return '0 B';
        if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    };

    const isImage = (file) => {
        const extension = file?.name?.split('.').pop()?.toLowerCase();
        return imageTypes.has(file?.type) || imageExtensions.has(extension);
    };

    const invalidReason = (file) => {
        const extension = file?.name?.split('.').pop()?.toLowerCase();
        const isPdf = file?.type === 'application/pdf' || extension === 'pdf';
        if (!file || (!isImage(file) && !isPdf)) return 'JPG, PNG, WebP, or PDF only';
        if (file.size > maxBytes) return '5 MB maximum per file';
        return '';
    };

    const setStatus = (message, isError = false) => {
        if (!status) return;
        status.textContent = message;
        status.classList.toggle('is-error', isError);
        status.classList.toggle('is-selected', !isError && selectedFiles.length > 0);
    };

    const setFileList = (files) => {
        selectedFiles = Array.from(files || []);
        if (mainInput && typeof DataTransfer !== 'undefined') {
            const transfer = new DataTransfer();
            selectedFiles.forEach((file) => transfer.items.add(file));
            mainInput.files = transfer.files;
        }
        if (mainInput) {
            mainInput.dataset.uploadInvalid = selectedFiles.length ? 'false' : 'true';
            mainInput.setCustomValidity(selectedFiles.length ? '' : 'Upload at least one receipt or supporting document.');
        }
    };

    const clearPreview = () => {
        if (!previewBox) return;
        previewBox.querySelectorAll('[data-object-url]').forEach((image) => {
            URL.revokeObjectURL(image.dataset.objectUrl);
        });
        previewBox.replaceChildren();
        previewBox.hidden = true;
    };

    const renderFiles = () => {
        if (!previewBox) return;
        clearPreview();
        if (!selectedFiles.length) return;

        selectedFiles.forEach((file, index) => {
            const card = document.createElement('div');
            card.className = 'so-receipt-preview';

            if (isImage(file)) {
                const image = document.createElement('img');
                const objectUrl = URL.createObjectURL(file);
                image.src = objectUrl;
                image.alt = 'Receipt photo ' + (index + 1);
                image.dataset.objectUrl = objectUrl;
                card.append(image);
            } else {
                const pdf = document.createElement('div');
                pdf.className = 'so-receipt-preview-file';
                pdf.setAttribute('aria-label', 'PDF supporting document');
                const icon = document.createElement('i');
                icon.className = 'bi bi-file-earmark-pdf-fill';
                icon.setAttribute('aria-hidden', 'true');
                pdf.append(icon);
                card.append(pdf);
            }

            const meta = document.createElement('div');
            meta.className = 'so-receipt-preview-meta';
            const name = document.createElement('span');
            name.textContent = file.name;
            const size = document.createElement('small');
            size.textContent = formatBytes(file.size);
            meta.append(name, size);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'so-receipt-preview-remove';
            remove.dataset.removeFile = 'true';
            remove.dataset.fileIndex = String(index);
            remove.setAttribute('aria-label', 'Remove receipt file ' + (index + 1));
            remove.title = 'Remove file';
            remove.textContent = '×';

            card.append(meta, remove);
            previewBox.append(card);
        });
        previewBox.hidden = false;
    };

    const addFiles = (pickedFiles) => {
        const accepted = [...selectedFiles];
        const rejected = [];
        const seen = new Set(selectedFiles.map((file) => [file.name, file.size, file.lastModified].join('|')));

        Array.from(pickedFiles || []).forEach((file) => {
            const reason = invalidReason(file);
            if (reason) {
                rejected.push(file.name + ' (' + reason + ')');
                return;
            }
            const key = [file.name, file.size, file.lastModified].join('|');
            if (seen.has(key)) return;
            if (accepted.length >= maxFiles) {
                rejected.push(file.name + ' (maximum ' + maxFiles + ' files)');
                return;
            }
            seen.add(key);
            accepted.push(file);
        });

        setFileList(accepted);
        renderFiles();
        if (rejected.length) {
            setStatus(rejected.join(' · '), true);
        } else if (accepted.length) {
            const total = accepted.reduce((sum, file) => sum + file.size, 0);
            setStatus(accepted.length + ' receipt file' + (accepted.length === 1 ? '' : 's') + ' selected · ' + formatBytes(total) + ' total.');
        } else {
            setStatus('No receipt selected.', true);
        }
    };

    const transferHasFiles = (transfer) => {
        if (!transfer) return false;
        if (transfer.files?.length) return true;
        return Array.from(transfer.types || []).includes('Files');
    };

    const filesFromTransfer = (transfer) => {
        if (!transfer) return [];
        const files = Array.from(transfer.files || []).filter(Boolean);
        if (files.length) return files;

        return Array.from(transfer.items || [])
            .filter((item) => item.kind === 'file')
            .map((item) => item.getAsFile?.())
            .filter(Boolean);
    };

    const clearFiles = () => {
        clearPreview();
        setFileList([]);
        setStatus('No receipt selected.');
    };

    const openGallery = () => {
        if (!mainInput) return;
        mainInput.removeAttribute('capture');
        mainInput.accept = 'image/jpeg,image/png,image/webp,application/pdf,.pdf';
        mainInput.click();
    };

    mainInput?.addEventListener('change', () => addFiles(mainInput.files));
    document.getElementById('soUploadGalleryBtn')?.addEventListener('click', (event) => {
        event.stopPropagation();
        openGallery();
    });
    dropzone?.addEventListener('click', (event) => {
        if (!event.target.closest('button')) openGallery();
    });
    dropzone?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openGallery();
        }
    });
    dropzone?.addEventListener('dragenter', (event) => {
        if (!transferHasFiles(event.dataTransfer)) return;
        event.preventDefault();
        dragDepth += 1;
        dropzone.classList.add('is-dragging');
    });
    dropzone?.addEventListener('dragover', (event) => {
        if (!transferHasFiles(event.dataTransfer)) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
        dropzone.classList.add('is-dragging');
    });
    dropzone?.addEventListener('dragleave', (event) => {
        if (!transferHasFiles(event.dataTransfer)) return;
        dragDepth = Math.max(0, dragDepth - 1);
        if (!dragDepth) dropzone.classList.remove('is-dragging');
    });
    dropzone?.addEventListener('drop', (event) => {
        if (!transferHasFiles(event.dataTransfer)) return;
        event.preventDefault();
        event.stopPropagation();
        dragDepth = 0;
        dropzone.classList.remove('is-dragging');
        addFiles(filesFromTransfer(event.dataTransfer));
    });

    // Prevent the browser from navigating to a dropped image/PDF when the
    // pointer leaves the zone by a few pixels. The dropzone handler above
    // still owns drops inside the zone and imports the files into the input.
    document.addEventListener('dragover', (event) => {
        if (transferHasFiles(event.dataTransfer)) event.preventDefault();
    });
    document.addEventListener('drop', (event) => {
        if (!transferHasFiles(event.dataTransfer)) return;
        if (!event.target.closest?.('#soReceiptDropzone')) event.preventDefault();
    });
    previewBox?.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-file]');
        if (!remove) return;
        const index = Number(remove.dataset.fileIndex);
        setFileList(selectedFiles.filter((_, fileIndex) => fileIndex !== index));
        renderFiles();
        if (selectedFiles.length) {
            const total = selectedFiles.reduce((sum, file) => sum + file.size, 0);
            setStatus(selectedFiles.length + ' receipt file' + (selectedFiles.length === 1 ? '' : 's') + ' selected · ' + formatBytes(total) + ' total.');
        } else {
            setStatus('No receipt selected.');
        }
    });

    const modal = () => document.getElementById('soCameraModal');
    const video = () => document.getElementById('soCameraVideo');
    const errorBox = () => document.getElementById('soCameraError');
    const setCameraError = (message) => {
        const box = errorBox();
        if (!box) return;
        box.textContent = message;
        box.hidden = false;
    };

    const stopCamera = () => {
        if (cameraStream) {
            cameraStream.getTracks().forEach((track) => track.stop());
            cameraStream = null;
        }
        const currentVideo = video();
        if (currentVideo) {
            currentVideo.srcObject = null;
            currentVideo.onloadedmetadata = null;
        }
        const snap = document.getElementById('soCameraSnapBtn');
        const label = document.getElementById('soCameraSnapLabel');
        const starting = document.getElementById('soCameraStarting');
        if (snap) snap.disabled = true;
        if (label) label.textContent = 'Starting camera…';
        if (starting) starting.style.display = 'flex';
        const currentModal = modal();
        if (currentModal) {
            currentModal.hidden = true;
            currentModal.style.display = '';
        }
    };

    const appendCameraFile = (file) => {
        if (file) addFiles([file]);
    };

    const openCamera = async () => {
        const currentModal = modal();
        const currentVideo = video();
        if (!currentModal || !currentVideo || !navigator.mediaDevices?.getUserMedia) throw new Error('no-camera-api');

        try {
            const permission = await navigator.permissions?.query({ name: 'camera' });
            if (permission?.state === 'denied') throw new Error('camera-denied');
        } catch (error) {
            if (error?.message === 'camera-denied') {
                setCameraError('Camera access is blocked. Allow it in the browser site settings, or use Upload receipts.');
                throw error;
            }
        }

        if (currentModal.parentElement !== document.body) document.body.appendChild(currentModal);
        const box = errorBox();
        if (box) box.hidden = true;
        currentModal.hidden = false;
        currentModal.style.display = 'flex';

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } },
                audio: false,
            });
        } catch (error) {
            stopCamera();
            throw error;
        }

        currentVideo.srcObject = cameraStream;
        const snap = document.getElementById('soCameraSnapBtn');
        const label = document.getElementById('soCameraSnapLabel');
        const starting = document.getElementById('soCameraStarting');
        const arm = () => {
            if (currentVideo.videoWidth > 0) {
                if (snap) snap.disabled = false;
                if (label) label.textContent = 'Capture Photo';
                if (starting) starting.style.display = 'none';
            }
        };
        currentVideo.onloadedmetadata = arm;
        await currentVideo.play().catch(() => {});
        arm();
    };

    document.getElementById('soOpenCameraBtn')?.addEventListener('click', () => {
        openCamera().catch((error) => {
            if (error?.message !== 'camera-denied') cameraInput?.click();
        });
    });
    document.getElementById('soCameraCloseBtn')?.addEventListener('click', stopCamera);
    document.getElementById('soCameraPickerBtn')?.addEventListener('click', () => {
        stopCamera();
        cameraInput?.click();
    });
    modal()?.addEventListener('click', (event) => {
        if (event.target === modal()) stopCamera();
    });
    document.getElementById('soCameraSnapBtn')?.addEventListener('click', () => {
        const currentVideo = video();
        if (!currentVideo?.videoWidth) {
            setCameraError('Camera is still starting. Wait a moment, then capture again.');
            return;
        }
        const canvas = document.createElement('canvas');
        canvas.width = currentVideo.videoWidth;
        canvas.height = currentVideo.videoHeight;
        canvas.getContext('2d').drawImage(currentVideo, 0, 0);
        canvas.toBlob((blob) => {
            stopCamera();
            if (blob) appendCameraFile(new File([blob], 'receipt-' + Date.now() + '.jpg', { type: 'image/jpeg' }));
        }, 'image/jpeg', 0.92);
    });
    cameraInput?.addEventListener('change', () => {
        const file = cameraInput.files?.[0];
        if (file) appendCameraFile(file);
        cameraInput.value = '';
    });

    const bindRow = (row) => {
        row.querySelector('[data-remove-row]')?.addEventListener('click', () => {
            row.remove();
            syncItemLabels();
        });
    };

    const syncItemLabels = () => {
        const currentRows = rows();
        currentRows.forEach((row, position) => {
            const label = row.querySelector('.so-item-number');
            if (label) label.textContent = 'Item ' + (position + 1);
        });
        if (items) {
            items.classList.toggle('is-scrollable', currentRows.length >= 3);
        }
        const countBadge = document.getElementById('soItemsCountBadge');
        if (countBadge) {
            countBadge.textContent = currentRows.length === 1
                ? '1 item'
                : `${currentRows.length} items${currentRows.length >= 3 ? ' · Scrollable' : ''}`;
        }
    };

    rows().forEach(bindRow);
    syncItemLabels();

    document.getElementById('soAddExpenseRow')?.addEventListener('click', () => {
        const currentRows = rows();
        if (!items || !rowTemplate || currentRows.length >= 20) return;
        const index = currentRows.reduce((max, row) => Math.max(max, Number(row.dataset.rowIndex) || 0), 0) + 1;
        const key = window.crypto?.randomUUID
            ? window.crypto.randomUUID()
            : String(Date.now()) + '-' + Math.random().toString(16).slice(2);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = rowTemplate.innerHTML
            .replaceAll('__INDEX__', String(index))
            .replaceAll('__NUMBER__', String(index + 1))
            .replaceAll('__REQUEST_KEY__', key)
            .trim();
        const row = wrapper.firstElementChild;
        if (!row) return;
        items.appendChild(row);
        bindRow(row);
        syncItemLabels();
        if (rows().length >= 3) {
            items.scrollTo({ top: items.scrollHeight, behavior: 'smooth' });
        } else {
            row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });

    form.addEventListener('submit', (event) => {
        if (selectedFiles.length && mainInput?.dataset.uploadInvalid !== 'true') return;
        event.preventDefault();
        setStatus(selectedFiles.length ? 'Choose valid JPG, PNG, WebP, or PDF files up to 5 MB each.' : 'Upload at least one receipt or supporting document.', true);
        mainInput?.focus();
        document.getElementById('soReceiptCapture')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    form.addEventListener('reset', () => {
        stopCamera();
        window.setTimeout(() => {
            rows().slice(1).forEach((row) => row.remove());
            clearFiles();
            syncItemLabels();
        }, 0);
    });

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) stopCamera();
    });
})();
