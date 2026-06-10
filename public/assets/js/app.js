(function () {
    const data = window.SPMT_DATA || {};

    function ensureGlobalPopup() {
        var popup = document.getElementById('spmtGlobalPopup');
        if (popup) return popup;
        popup = document.createElement('div');
        popup.id = 'spmtGlobalPopup';
        popup.className = 'spmt-popup';
        popup.setAttribute('aria-hidden', 'true');
        popup.hidden = true;
        popup.innerHTML = '' +
            '<div class="spmt-popup__backdrop" data-spmt-popup-cancel></div>' +
            '<div class="spmt-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="spmtPopupTitle" tabindex="-1">' +
                '<button type="button" class="spmt-popup__close" aria-label="Tutup" data-spmt-popup-cancel><i class="fa-solid fa-xmark"></i></button>' +
                '<div class="spmt-popup__icon"><i class="fa-solid fa-circle-info"></i></div>' +
                '<h3 id="spmtPopupTitle">Informasi</h3>' +
                '<p class="spmt-popup__message"></p>' +
                '<div class="spmt-popup__actions">' +
                    '<button type="button" class="btn btn--ghost btn--lg spmt-popup__cancel" data-spmt-popup-cancel>Batal</button>' +
                    '<button type="button" class="btn btn--primary btn--lg spmt-popup__ok" data-spmt-popup-ok>OK</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(popup);
        return popup;
    }

    function normalizePopupType(type) {
        type = String(type || 'info').toLowerCase();
        if (type === 'danger') return 'error';
        if (type === 'warning' || type === 'error' || type === 'success' || type === 'confirm') return type;
        return 'info';
    }

    function popupTitle(type, fallback) {
        if (fallback) return fallback;
        type = normalizePopupType(type);
        if (type === 'success') return 'Berhasil';
        if (type === 'error') return 'Gagal';
        if (type === 'warning') return 'Perhatian';
        if (type === 'confirm') return 'Konfirmasi';
        return 'Informasi';
    }

    function popupIcon(type) {
        type = normalizePopupType(type);
        if (type === 'success') return 'fa-circle-check';
        if (type === 'error') return 'fa-circle-xmark';
        if (type === 'warning' || type === 'confirm') return 'fa-triangle-exclamation';
        return 'fa-circle-info';
    }

    function showGlobalPopup(options) {
        options = options || {};
        var popup = ensureGlobalPopup();
        var type = normalizePopupType(options.type || 'info');
        var isConfirm = !!options.confirm;
        var dialog = popup.querySelector('.spmt-popup__dialog');
        var icon = popup.querySelector('.spmt-popup__icon i');
        var title = popup.querySelector('#spmtPopupTitle');
        var message = popup.querySelector('.spmt-popup__message');
        var cancelBtn = popup.querySelector('.spmt-popup__cancel');
        var okBtn = popup.querySelector('.spmt-popup__ok');

        popup.className = 'spmt-popup spmt-popup--' + (isConfirm ? 'confirm' : type);
        if (icon) icon.className = 'fa-solid ' + popupIcon(isConfirm ? 'confirm' : type);
        if (title) title.textContent = popupTitle(isConfirm ? 'confirm' : type, options.title || '');
        if (message) message.textContent = String(options.message || '');
        if (cancelBtn) cancelBtn.hidden = !isConfirm;
        if (okBtn) okBtn.textContent = options.okText || (isConfirm ? 'Ya' : 'OK');

        popup.hidden = false;
        popup.setAttribute('aria-hidden', 'false');
        document.body.classList.add('has-spmt-popup');
        window.setTimeout(function () { if (dialog && dialog.focus) dialog.focus(); }, 0);

        return new Promise(function (resolve) {
            var settled = false;
            function close(result) {
                if (settled) return;
                settled = true;
                popup.hidden = true;
                popup.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('has-spmt-popup');
                popup.removeEventListener('click', onClick);
                document.removeEventListener('keydown', onKeydown);
                resolve(result);
            }
            function onClick(event) {
                if (event.target.closest('[data-spmt-popup-ok]')) close(true);
                else if (event.target.closest('[data-spmt-popup-cancel]')) close(false);
            }
            function onKeydown(event) {
                if (event.key === 'Escape') close(false);
                if (event.key === 'Enter' && !isConfirm) close(true);
            }
            popup.addEventListener('click', onClick);
            document.addEventListener('keydown', onKeydown);
        });
    }

    window.spmtPopup = function (message, type, title) {
        return showGlobalPopup({ message: message, type: type || 'info', title: title || '' });
    };
    window.spmtConfirm = function (message, title) {
        return showGlobalPopup({ message: message, type: 'confirm', title: title || 'Konfirmasi', confirm: true, okText: 'Ya' });
    };

    function bootServerPopup() {
        var flash = data.flash_popup || data.flashPopup || null;
        if (!flash || !flash.message) {
            var node = document.querySelector('.detail-flash, .frame > .flash, .js-log-toast span, .account-modal__flash');
            if (node && node.textContent.trim()) {
                flash = { type: node.closest('.flash--error, .log-toast--error') ? 'error' : 'success', message: node.textContent.trim() };
            }
        }
        if (!flash || !flash.message) {
            var accountErrors = Array.prototype.map.call(document.querySelectorAll('.account-field em'), function (node) {
                return node.textContent.trim();
            }).filter(Boolean);
            if (accountErrors.length) {
                flash = { type: 'error', message: accountErrors.join('\n') };
            }
        }
        if (flash && flash.message) {
            document.querySelectorAll('.detail-flash, .frame > .flash, .js-log-toast, .account-modal__flash').forEach(function (node) {
                node.hidden = true;
                node.style.display = 'none';
            });
            window.setTimeout(function () { showGlobalPopup({ type: flash.type || 'info', message: flash.message }); }, 120);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootServerPopup);
    } else {
        bootServerPopup();
    }

    document.querySelectorAll('img[data-fallback-src]').forEach(function (img) {
        img.addEventListener('error', function () {
            var fallback = img.getAttribute('data-fallback-src') || 'public/assets/images/inv-default.jpg';
            if (fallback && img.getAttribute('src') !== fallback) {
                img.setAttribute('src', fallback);
            }
        });
    });

    document.querySelectorAll('.js-route').forEach(function (button) {
        button.addEventListener('click', function () {
            const page = button.getAttribute('data-page');
            if (page) {
                window.location.href = 'index.php?page=' + encodeURIComponent(page);
            }
        });
    });

    (function () {
        var activeCameraModal = null;
        var objectUrlMap = new WeakMap();

        function formatFileSize(bytes) {
            if (!bytes) return '';
            var units = ['B', 'KB', 'MB', 'GB'];
            var size = bytes;
            var index = 0;
            while (size >= 1024 && index < units.length - 1) {
                size = size / 1024;
                index += 1;
            }
            return (index === 0 ? size : size.toFixed(1)) + ' ' + units[index];
        }

        function stopStream(video) {
            if (video && video.srcObject) {
                video.srcObject.getTracks().forEach(function (track) { track.stop(); });
                video.srcObject = null;
            }
        }

        function syncFilesToInput(sourceFiles, targetInput) {
            if (!sourceFiles || !sourceFiles.length || !targetInput) return false;
            try {
                var dt = new DataTransfer();
                Array.prototype.forEach.call(sourceFiles, function (file) { dt.items.add(file); });
                targetInput.files = dt.files;
                targetInput.dispatchEvent(new Event('change', { bubbles: true }));
                return true;
            } catch (error) {
                console.warn('Tidak bisa menyalin file ke input utama.', error);
                return false;
            }
        }

        function resetPreview(upload) {
            if (!upload) return;
            var preview = upload.querySelector('.js-doc-preview');
            var placeholder = upload.querySelector('.doc-upload__placeholder');
            var previewImage = upload.querySelector('.js-preview-image');
            var previewThumbWrap = upload.querySelector('.doc-upload__preview-thumb-wrap');
            var previewName = upload.querySelector('.js-preview-name');
            var previewSize = upload.querySelector('.js-preview-size');
            var existingFilePath = upload.querySelector('.js-existing-file-path');
            var removeFileFlag = upload.querySelector('.js-remove-file-flag');
            var fileInput = upload.querySelector('.js-file-input');
            var pickerInput = upload.querySelector('.js-picker-input');
            var cameraInput = upload.querySelector('.js-camera-input');
            var existingFilePath = upload.querySelector('.js-existing-file-path');
            var removeFileFlag = upload.querySelector('.js-remove-file-flag');

            if (previewImage) {
                var oldUrl = objectUrlMap.get(previewImage);
                if (oldUrl) {
                    URL.revokeObjectURL(oldUrl);
                    objectUrlMap.delete(previewImage);
                }
                previewImage.removeAttribute('src');
            }
            if (previewThumbWrap) previewThumbWrap.hidden = true;
            if (previewName) previewName.textContent = 'Belum ada file';
            if (previewSize) previewSize.textContent = '';
            if (placeholder) placeholder.textContent = placeholder.getAttribute('data-default-text') || 'Pilih File';
            if (preview) preview.hidden = true;
            if (fileInput) fileInput.value = '';
            if (pickerInput) pickerInput.value = '';
            if (cameraInput) cameraInput.value = '';
            if (removeFileFlag) removeFileFlag.value = existingFilePath && existingFilePath.value ? '1' : '0';
        }

        function updateExistingDisplay(upload, src, name) {
            if (!upload || !src) return;
            var preview = upload.querySelector('.js-doc-preview');
            var placeholder = upload.querySelector('.doc-upload__placeholder');
            var previewImage = upload.querySelector('.js-preview-image');
            var previewThumbWrap = upload.querySelector('.doc-upload__preview-thumb-wrap');
            var previewName = upload.querySelector('.js-preview-name');
            var previewSize = upload.querySelector('.js-preview-size');
            var existingFilePath = upload.querySelector('.js-existing-file-path');
            var removeFileFlag = upload.querySelector('.js-remove-file-flag');
            var removeFileFlag = upload.querySelector('.js-remove-file-flag');

            if (placeholder) placeholder.textContent = name || 'Dokumentasi terpilih';
            if (previewName) previewName.textContent = name || 'Dokumentasi tersimpan';
            if (previewSize) previewSize.textContent = 'Dokumentasi siap dikirim';
            if (previewImage) previewImage.src = src;
            if (previewThumbWrap) previewThumbWrap.hidden = false;
            if (preview) preview.hidden = false;
            if (removeFileFlag) removeFileFlag.value = '0';
        }

        function updateSelectedDisplay(upload, file) {
            if (!upload || !file) return;
            var preview = upload.querySelector('.js-doc-preview');
            var placeholder = upload.querySelector('.doc-upload__placeholder');
            var previewImage = upload.querySelector('.js-preview-image');
            var previewThumbWrap = upload.querySelector('.doc-upload__preview-thumb-wrap');
            var previewName = upload.querySelector('.js-preview-name');
            var previewSize = upload.querySelector('.js-preview-size');
            var existingFilePath = upload.querySelector('.js-existing-file-path');
            var removeFileFlag = upload.querySelector('.js-remove-file-flag');

            if (placeholder) placeholder.textContent = file.name;
            if (previewName) previewName.textContent = file.name;
            if (previewSize) previewSize.textContent = formatFileSize(file.size);

            if (previewImage) {
                var oldUrl = objectUrlMap.get(previewImage);
                if (oldUrl) URL.revokeObjectURL(oldUrl);
                var nextUrl = URL.createObjectURL(file);
                objectUrlMap.set(previewImage, nextUrl);
                previewImage.src = nextUrl;
            }
            if (previewThumbWrap) previewThumbWrap.hidden = false;

            if (existingFilePath) existingFilePath.value = '';
            if (removeFileFlag) removeFileFlag.value = '0';
            if (preview) preview.hidden = false;
        }

        function ensureCameraModal() {
            if (activeCameraModal) return activeCameraModal;
            var modal = document.createElement('div');
            modal.className = 'doc-camera-modal';
            modal.hidden = true;
            modal.innerHTML = '' +
                '<div class="doc-camera-modal__dialog" role="dialog" aria-modal="true" aria-label="Kamera dan preview foto">' +
                    '<h3 class="doc-camera-modal__title">Ambil Foto</h3>' +
                    '<div class="doc-camera-modal__stage">' +
                        '<video autoplay playsinline></video>' +
                        '<canvas hidden></canvas>' +
                        '<img class="doc-camera-modal__preview-image" alt="Preview foto" hidden>' +
                    '</div>' +
                    '<p class="doc-camera-modal__hint">Ambil foto atau pilih file, cek preview dulu, lalu simpan ke form.</p>' +
                    '<div class="doc-camera-modal__actions">' +
                        '<button type="button" class="btn btn--ghost js-camera-close">Tutup</button>' +
                        '<button type="button" class="btn btn--primary js-camera-retake" hidden>Ulangi</button>' +
                        '<button type="button" class="btn btn--primary js-camera-capture">Ambil Foto</button>' +
                        '<button type="button" class="btn btn--accent js-camera-use" hidden>Gunakan Foto</button>' +
                    '</div>' +
                '</div>';
            document.body.appendChild(modal);
            activeCameraModal = modal;
            return modal;
        }

        function showFilePreviewModal(file, targetInput, upload, allowRetry) {
            if (!file || !targetInput || !upload) return;
            var modal = ensureCameraModal();
            var title = modal.querySelector('.doc-camera-modal__title');
            var hint = modal.querySelector('.doc-camera-modal__hint');
            var video = modal.querySelector('video');
            var canvas = modal.querySelector('canvas');
            var image = modal.querySelector('.doc-camera-modal__preview-image');
            var captureBtn = modal.querySelector('.js-camera-capture');
            var useBtn = modal.querySelector('.js-camera-use');
            var retakeBtn = modal.querySelector('.js-camera-retake');
            var closeBtn = modal.querySelector('.js-camera-close');
            var fileUrl = URL.createObjectURL(file);

            stopStream(video);
            title.textContent = 'Preview Foto';
            hint.textContent = 'Periksa foto terlebih dulu. Jika sudah sesuai, klik Gunakan Foto.';
            video.hidden = true;
            canvas.hidden = true;
            image.hidden = false;
            image.src = fileUrl;
            captureBtn.hidden = true;
            useBtn.hidden = false;
            retakeBtn.hidden = !allowRetry;
            modal.hidden = false;

            function cleanup() {
                URL.revokeObjectURL(fileUrl);
                image.removeAttribute('src');
                image.hidden = true;
                modal.hidden = true;
                closeBtn.onclick = null;
                retakeBtn.onclick = null;
                useBtn.onclick = null;
                modal.onclick = null;
            }

            closeBtn.onclick = cleanup;
            modal.onclick = function (event) {
                if (event.target === modal) cleanup();
            };
            retakeBtn.onclick = function () {
                cleanup();
                if (allowRetry) allowRetry();
            };
            useBtn.onclick = function () {
                if (!syncFilesToInput([file], targetInput)) {
                    cleanup();
                    return;
                }
                updateSelectedDisplay(upload, file);
                cleanup();
            };
        }

        function openCameraModal(targetInput, fallbackInput, upload) {
            if (!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) {
                fallbackInput.click();
                return;
            }

            var modal = ensureCameraModal();
            var title = modal.querySelector('.doc-camera-modal__title');
            var hint = modal.querySelector('.doc-camera-modal__hint');
            var video = modal.querySelector('video');
            var canvas = modal.querySelector('canvas');
            var image = modal.querySelector('.doc-camera-modal__preview-image');
            var captureBtn = modal.querySelector('.js-camera-capture');
            var useBtn = modal.querySelector('.js-camera-use');
            var retakeBtn = modal.querySelector('.js-camera-retake');
            var closeBtn = modal.querySelector('.js-camera-close');
            var streamRef = null;
            var capturedBlob = null;

            title.textContent = 'Ambil Foto';
            hint.textContent = 'Gunakan kamera perangkat untuk mengambil foto, lalu cek preview sebelum disimpan.';

            function closeModal() {
                stopStream(video);
                capturedBlob = null;
                image.hidden = true;
                image.removeAttribute('src');
                canvas.hidden = true;
                video.hidden = false;
                useBtn.hidden = true;
                retakeBtn.hidden = true;
                captureBtn.hidden = false;
                modal.hidden = true;
                closeBtn.onclick = null;
                retakeBtn.onclick = null;
                useBtn.onclick = null;
                captureBtn.onclick = null;
                modal.onclick = null;
                streamRef = null;
            }

            function showStream() {
                image.hidden = true;
                image.removeAttribute('src');
                canvas.hidden = true;
                video.hidden = false;
                useBtn.hidden = true;
                retakeBtn.hidden = true;
                captureBtn.hidden = false;
            }

            closeBtn.onclick = closeModal;
            modal.onclick = function (event) {
                if (event.target === modal) closeModal();
            };

            captureBtn.onclick = function () {
                if (!streamRef) return;
                var width = video.videoWidth || 1600;
                var height = video.videoHeight || 1000;
                canvas.width = width;
                canvas.height = height;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, width, height);
                var previewUrl = canvas.toDataURL('image/jpeg', 0.92);
                image.src = previewUrl;
                image.hidden = false;
                canvas.hidden = true;
                video.hidden = true;
                captureBtn.hidden = true;
                retakeBtn.hidden = false;
                useBtn.hidden = false;
                canvas.toBlob(function (blob) {
                    capturedBlob = blob;
                }, 'image/jpeg', 0.92);
            };

            retakeBtn.onclick = function () {
                capturedBlob = null;
                showStream();
            };

            useBtn.onclick = function () {
                if (!capturedBlob) return;
                var fileName = 'kamera-' + new Date().toISOString().replace(/[:.]/g, '-') + '.jpg';
                var file = new File([capturedBlob], fileName, { type: 'image/jpeg' });
                if (!syncFilesToInput([file], targetInput)) {
                    closeModal();
                    fallbackInput.click();
                    return;
                }
                updateSelectedDisplay(upload, file);
                closeModal();
            };

            navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1200 }, aspectRatio: { ideal: 1.6 } }, audio: false })
                .then(function (stream) {
                    streamRef = stream;
                    video.srcObject = stream;
                    modal.hidden = false;
                    showStream();
                })
                .catch(function () {
                    closeModal();
                    fallbackInput.click();
                });
        }

        document.querySelectorAll('.js-doc-upload').forEach(function (upload) {
            var fileInput = upload.querySelector('.js-file-input');
            var pickerInput = upload.querySelector('.js-picker-input');
            var cameraInput = upload.querySelector('.js-camera-input');
            var existingFilePath = upload.querySelector('.js-existing-file-path');
            var removeFileFlag = upload.querySelector('.js-remove-file-flag');
            var fileTrigger = upload.querySelector('.js-file-trigger');
            var cameraTrigger = upload.querySelector('.js-camera-trigger');
            var removePreviewBtn = upload.querySelector('.js-preview-remove');
            var placeholder = upload.querySelector('.doc-upload__placeholder');

            if (placeholder && !placeholder.getAttribute('data-default-text')) {
                placeholder.setAttribute('data-default-text', placeholder.textContent.trim() || 'Pilih File');
            }

            resetPreview(upload);
            var existingSrc = upload.getAttribute('data-existing-src') || '';
            var existingName = upload.getAttribute('data-existing-name') || '';
            var existingFilePath = upload.querySelector('.js-existing-file-path');
            if (existingSrc && existingFilePath && existingFilePath.value) {
                updateExistingDisplay(upload, existingSrc, existingName);
            }

            if (fileInput) {
                fileInput.addEventListener('change', function () {
                    if (fileInput.files && fileInput.files[0]) {
                        updateSelectedDisplay(upload, fileInput.files[0]);
                    } else {
                        resetPreview(upload);
            var existingSrc = upload.getAttribute('data-existing-src') || '';
            var existingName = upload.getAttribute('data-existing-name') || '';
            var existingFilePath = upload.querySelector('.js-existing-file-path');
            if (existingSrc && existingFilePath && existingFilePath.value) {
                updateExistingDisplay(upload, existingSrc, existingName);
            }
                    }
                });
            }

            if (removePreviewBtn) {
                removePreviewBtn.addEventListener('click', function () {
                    resetPreview(upload);
            var existingSrc = upload.getAttribute('data-existing-src') || '';
            var existingName = upload.getAttribute('data-existing-name') || '';
            var existingFilePath = upload.querySelector('.js-existing-file-path');
            if (existingSrc && existingFilePath && existingFilePath.value) {
                updateExistingDisplay(upload, existingSrc, existingName);
            }
                });
            }

            if (fileTrigger && pickerInput) {
                fileTrigger.addEventListener('click', function () {
                    pickerInput.click();
                });
            }

            if (pickerInput && fileInput) {
                pickerInput.addEventListener('change', function () {
                    if (pickerInput.files && pickerInput.files[0]) {
                        showFilePreviewModal(pickerInput.files[0], fileInput, upload, function () {
                            pickerInput.value = '';
                            pickerInput.click();
                        });
                    }
                });
            }

            if (cameraInput && fileInput) {
                cameraInput.addEventListener('change', function () {
                    if (cameraInput.files && cameraInput.files[0]) {
                        showFilePreviewModal(cameraInput.files[0], fileInput, upload, function () {
                            cameraInput.value = '';
                            cameraInput.click();
                        });
                    }
                });
            }

            if (cameraTrigger && fileInput && cameraInput) {
                cameraTrigger.addEventListener('click', function () {
                    openCameraModal(fileInput, cameraInput, upload);
                });
            }
        });
    })();

    if (typeof Chart === 'undefined') {
        return;
    }

    Chart.defaults.font.family = 'Poppins, sans-serif';
    Chart.defaults.color = '#555';
    Chart.defaults.plugins.legend.display = false;

    const gridColor = 'rgba(27, 62, 111, 0.12)';

    if (document.getElementById('cctvChart') && Array.isArray(data.cctv_breakdown)) {
        var cctvCanvas = document.getElementById('cctvChart');
        var cctvTooltip = document.createElement('div');
        cctvTooltip.className = 'cctv-chart-tooltip';
        cctvTooltip.setAttribute('aria-hidden', 'true');
        var cctvWrap = cctvCanvas.closest ? cctvCanvas.closest('.donut-card__chart-wrap--cctv') : null;
        if (cctvWrap) cctvWrap.appendChild(cctvTooltip);

        new Chart(cctvCanvas, {
            type: 'doughnut',
            data: {
                labels: data.cctv_breakdown.map(item => item.label),
                datasets: [{
                    data: data.cctv_breakdown.map(item => item.value),
                    backgroundColor: data.cctv_breakdown.map(item => item.color),
                    borderWidth: 0,
                    cutout: '56%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: false,
                        external: function (context) {
                            if (!cctvTooltip || !cctvWrap) return;
                            var tooltip = context.tooltip;
                            if (!tooltip || tooltip.opacity === 0) {
                                cctvTooltip.classList.remove('is-visible');
                                cctvTooltip.setAttribute('aria-hidden', 'true');
                                return;
                            }
                            var point = tooltip.dataPoints && tooltip.dataPoints.length ? tooltip.dataPoints[0] : null;
                            var label = point ? String(point.label || '') : '';
                            var value = point ? String(point.formattedValue || point.raw || '0') : '0';
                            cctvTooltip.innerHTML = '<strong>' + label + '</strong><span>' + value + ' CCTV</span>';
                            cctvTooltip.classList.add('is-visible');
                            cctvTooltip.setAttribute('aria-hidden', 'false');

                            var left = tooltip.caretX;
                            var top = tooltip.caretY;
                            var tooltipWidth = cctvTooltip.offsetWidth || 120;
                            var tooltipHeight = cctvTooltip.offsetHeight || 48;
                            var wrapWidth = cctvWrap.clientWidth || cctvCanvas.clientWidth || 220;
                            var wrapHeight = cctvWrap.clientHeight || cctvCanvas.clientHeight || 220;

                            left = Math.max(tooltipWidth / 2 + 6, Math.min(wrapWidth - tooltipWidth / 2 - 6, left));
                            top = top < wrapHeight * 0.45 ? top + tooltipHeight + 18 : top - 18;
                            top = Math.max(tooltipHeight + 6, Math.min(wrapHeight - 6, top));

                            cctvTooltip.style.left = left + 'px';
                            cctvTooltip.style.top = top + 'px';
                        }
                    }
                }
            }
        });
    }

    if (document.getElementById('keluhanChart') && data.complaint_chart) {
        var complaintValues = [];
        if (Array.isArray(data.complaint_chart.series)) {
            data.complaint_chart.series.forEach(function (series) {
                if (Array.isArray(series.data)) {
                    complaintValues = complaintValues.concat(series.data);
                }
            });
        }
        var complaintMaxValue = Math.max.apply(null, complaintValues.length ? complaintValues : [0]);
        var complaintDynamicMax = complaintMaxValue <= 5 ? 5 : Math.ceil(complaintMaxValue / 5) * 5;
        var complaintStepSize = complaintDynamicMax <= 10 ? 1 : Math.max(1, Math.ceil(complaintDynamicMax / 5));

        var isSmallChartScreen = window.matchMedia && window.matchMedia('(max-width: 640px)').matches;

        var complaintItems = Array.isArray(data.complaint_chart.items) ? data.complaint_chart.items : [];
        var complaintChartLabels = complaintItems.length ? complaintItems.map(function (item) {
            return item.short_label || item.label || '-';
        }) : data.complaint_chart.labels;
        var complaintFullLabels = complaintItems.length ? complaintItems.map(function (item) {
            return item.label || item.short_label || '-';
        }) : data.complaint_chart.labels;

        new Chart(document.getElementById('keluhanChart'), {
            type: 'bar',
            data: {
                labels: complaintChartLabels,
                datasets: data.complaint_chart.series.map(function (series) {
                    return {
                        label: series.label,
                        data: series.data,
                        backgroundColor: series.color,
                        borderRadius: 4,
                        maxBarThickness: isSmallChartScreen ? 22 : 34,
                        barPercentage: isSmallChartScreen ? 0.72 : 0.84,
                        categoryPercentage: isSmallChartScreen ? 0.7 : 0.78
                    };
                })
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'nearest', intersect: false },
                plugins: {
                    tooltip: {
                        enabled: true,
                        displayColors: true,
                        backgroundColor: 'rgba(15, 36, 64, 0.94)',
                        titleMarginBottom: 6,
                        bodySpacing: 4,
                        padding: isSmallChartScreen ? 9 : 12,
                        caretPadding: 8,
                        callbacks: {
                            title: function (items) {
                                if (!items || !items.length) return '';
                                var index = typeof items[0].dataIndex === 'number' ? items[0].dataIndex : -1;
                                return index >= 0 && complaintFullLabels[index] ? String(complaintFullLabels[index]) : String(items[0].label || '');
                            },
                            label: function (context) {
                                var label = context.dataset && context.dataset.label ? context.dataset.label + ': ' : '';
                                var value = typeof context.parsed.y !== 'undefined' ? context.parsed.y : context.raw;
                                return label + value + ' keluhan';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false, drawTicks: false },
                        border: { display: false },
                        ticks: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        max: complaintDynamicMax,
                        ticks: { stepSize: complaintStepSize, color: '#666', precision: 0, font: { size: isSmallChartScreen ? 10 : 12 } },
                        grid: { color: gridColor }
                    }
                }
            }
        });
    }

    function buildFlowChart(canvasId, values) {
        const target = document.getElementById(canvasId);
        if (!target || !values) return;

        var desiredFlowOrder = ['BARANG MASUK', 'BARANG KELUAR'];
        var rawLabels = Array.isArray(values.labels) ? values.labels : [];
        var rawData = Array.isArray(values.data) ? values.data : [];
        var flowRows = desiredFlowOrder.map(function (label) {
            var index = rawLabels.findIndex(function (item) {
                return String(item || '').toUpperCase() === label;
            });
            return {
                label: label,
                value: index >= 0 ? Number(rawData[index] || 0) : 0
            };
        });
        rawLabels.forEach(function (label, index) {
            var normalized = String(label || '').toUpperCase();
            if (desiredFlowOrder.indexOf(normalized) === -1) {
                flowRows.push({ label: String(label || '-'), value: Number(rawData[index] || 0) });
            }
        });

        const flowLabels = flowRows.map(function (row) { return row.label; });
        const flowData = flowRows.map(function (row) { return row.value; });
        const maxValue = Math.max.apply(null, flowData.length ? flowData : [0]);
        const dynamicMax = maxValue <= 5 ? 5 : Math.ceil(maxValue / 5) * 5;
        const stepSize = dynamicMax <= 10 ? 1 : Math.max(1, Math.ceil(dynamicMax / 5));

        var isSmallFlowScreen = window.matchMedia && window.matchMedia('(max-width: 640px)').matches;
        var isTabletFlowScreen = window.matchMedia && window.matchMedia('(max-width: 1024px)').matches;
        var flowBarThickness = isSmallFlowScreen ? 28 : (isTabletFlowScreen ? 34 : 40);
        var flowBarPercentage = isSmallFlowScreen ? 0.56 : (isTabletFlowScreen ? 0.62 : 0.68);
        var flowCategoryPercentage = isSmallFlowScreen ? 0.58 : (isTabletFlowScreen ? 0.66 : 0.72);

        new Chart(target, {
            type: 'bar',
            data: {
                labels: flowLabels,
                datasets: [{
                    data: flowData,
                    backgroundColor: '#9184E8',
                    borderWidth: 0,
                    borderSkipped: 'bottom',
                    borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
                    maxBarThickness: flowBarThickness,
                    barPercentage: flowBarPercentage,
                    categoryPercentage: flowCategoryPercentage,
                    order: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: isSmallFlowScreen ? 4 : 8,
                        right: isSmallFlowScreen ? 2 : 6,
                        bottom: isSmallFlowScreen ? 0 : 4,
                        left: isSmallFlowScreen ? 0 : 4
                    }
                },
                interaction: { mode: 'nearest', intersect: false },
                plugins: {
                    tooltip: {
                        enabled: true,
                        displayColors: true,
                        backgroundColor: 'rgba(15, 36, 64, 0.94)',
                        padding: isSmallFlowScreen ? 8 : 10,
                        caretPadding: 8,
                        callbacks: {
                            title: function () { return ''; },
                            label: function (context) {
                                var value = typeof context.parsed.y !== 'undefined' ? context.parsed.y : context.raw;
                                return String(value);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        offset: true,
                        grid: { display: false, drawTicks: false },
                        ticks: {
                            color: '#555',
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0,
                            padding: isSmallFlowScreen ? 6 : 10,
                            font: {
                                family: 'Montserrat, sans-serif',
                                size: isSmallFlowScreen ? 10 : (isTabletFlowScreen ? 12 : 14),
                                weight: '700'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: dynamicMax,
                        ticks: {
                            stepSize: stepSize,
                            color: '#666',
                            precision: 0,
                            padding: 6,
                            font: { size: isSmallFlowScreen ? 10 : 12 }
                        },
                        grid: { color: gridColor, drawTicks: false, z: 0 },
                        border: { display: false }
                    }
                }
            }
        });
    }

    buildFlowChart('arusChart', data.inventory_flow);
    buildFlowChart('logChart', data.inventory_flow);
})();

(function () {
    if (document.querySelector('.modal.is-open')) {
        document.body.classList.add('has-modal-open');
    }

    document.querySelectorAll('.js-open-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            var id = button.getAttribute('data-modal');
            var modal = id ? document.getElementById(id) : null;
            if (modal) {
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('has-modal-open');
            }
            var menu = button.closest('.topbar__menu');
            if (menu) {
                menu.hidden = true;
                var menuButton = menu.parentElement ? menu.parentElement.querySelector('[aria-expanded]') : null;
                if (menuButton) menuButton.setAttribute('aria-expanded', 'false');
            }
        });
    });

    document.querySelectorAll('.js-close-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            var modal = button.closest('.modal');
            if (modal) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('has-modal-open');
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            document.querySelectorAll('.modal.is-open').forEach(function (modal) {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('has-modal-open');
            });
        }
    });

    document.querySelectorAll('.form-switcher__btn').forEach(function (button) {
        button.addEventListener('click', function () {
            var targetId = button.getAttribute('data-target');
            var wrap = button.closest('.modal__dialog');
            if (!wrap || !targetId) return;
            wrap.querySelectorAll('.form-switcher__btn').forEach(function (btn) { btn.classList.remove('is-active'); });
            wrap.querySelectorAll('.form-pane').forEach(function (pane) { pane.classList.remove('is-active'); });
            button.classList.add('is-active');
            var pane = wrap.querySelector('#' + targetId);
            if (pane) pane.classList.add('is-active');
        });
    });

    var exportMenu = document.querySelector('.js-export-menu');
    var exportToggle = document.querySelector('.js-export-toggle');
    if (exportMenu && exportToggle) {
        exportToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            exportMenu.classList.toggle('is-open');
        });
        document.addEventListener('click', function (event) {
            if (!exportMenu.contains(event.target)) {
                exportMenu.classList.remove('is-open');
            }
        });
    }

    var selector = document.querySelector('.js-other-selector');
    var detail = window.SPMT_INVENTORY_DETAIL || { otherItems: [] };
    if (selector) {
        selector.addEventListener('change', function () {
            var index = selector.options[selector.selectedIndex].getAttribute('data-index');
            var item = typeof index === 'string' && index !== '' ? detail.otherItems[parseInt(index, 10)] : null;
            var keyInput = document.getElementById('otherItemKey');
            if (keyInput) keyInput.value = selector.value || '';
            var byId = function(id){ return document.getElementById(id); };
            if (byId('otherIdInventaris')) byId('otherIdInventaris').value = item && item.id_inventaris ? item.id_inventaris : '';
            if (byId('otherJenisPerangkat')) byId('otherJenisPerangkat').value = item && item.jenis_perangkat ? item.jenis_perangkat : '';
            if (byId('otherMerkPerangkat')) byId('otherMerkPerangkat').value = item && item.merk_perangkat ? item.merk_perangkat : '';
            if (byId('otherUnitKerja')) byId('otherUnitKerja').value = item && item.unit_kerja ? item.unit_kerja : '';
            if (byId('otherUser')) byId('otherUser').value = item && item.user ? item.user : '';
            if (byId('otherStatus')) {
                var statusValue = item && item.status ? String(item.status).toUpperCase() : 'AKTIF';
                byId('otherStatus').value = statusValue === 'RUSAK' ? 'RUSAK' : 'AKTIF';
            }
            if (byId('otherGambarExisting')) byId('otherGambarExisting').value = item && item.gambar ? item.gambar : '';
            if (byId('otherGambarPreviewText')) byId('otherGambarPreviewText').textContent = 'Gambar saat ini: ' + (item && item.gambar ? item.gambar : '-');
            if (byId('otherGambarFile')) byId('otherGambarFile').value = '';
        });
    }

    document.querySelectorAll('.js-image-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var targetId = input.getAttribute('data-preview-target');
            var preview = targetId ? document.getElementById(targetId) : null;
            if (!preview) return;
            if (input.files && input.files[0]) {
                preview.textContent = 'File dipilih: ' + input.files[0].name;
            } else if (targetId === 'addOtherImagePreview') {
                preview.textContent = 'Belum ada gambar dipilih.';
            }
        });
    });

    document.querySelectorAll('.js-confirm-delete').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.getAttribute('data-confirmed-submit') === '1') {
                form.removeAttribute('data-confirmed-submit');
                return;
            }
            event.preventDefault();
            var message = form.getAttribute('data-confirm-message') || 'Yakin mau hapus?';
            window.spmtConfirm(message).then(function (ok) {
                if (!ok) return;
                form.setAttribute('data-confirmed-submit', '1');
                if (form.requestSubmit) form.requestSubmit();
                else form.submit();
            });
        });
    });

    document.querySelectorAll('.js-confirm-delete-item').forEach(function (button) {
        button.addEventListener('click', function (event) {
            var keyInput = document.getElementById('otherItemKey');
            if (!keyInput || !keyInput.value) {
                event.preventDefault();
                window.spmtPopup('Pilih perangkat lain yang ingin dihapus terlebih dahulu.', 'warning');
                return;
            }
            var form = button.closest('form');
            if (form && form.getAttribute('data-confirmed-submit') === '1') {
                form.removeAttribute('data-confirmed-submit');
                return;
            }
            event.preventDefault();
            var message = button.getAttribute('data-confirm-message') || 'Yakin mau hapus?';
            window.spmtConfirm(message).then(function (ok) {
                if (!ok || !form) return;
                form.setAttribute('data-confirmed-submit', '1');
                if (form.requestSubmit) form.requestSubmit(button);
                else form.submit();
            });
        });
    });
    var editOtherPane = document.getElementById('editOtherPane');
    if (editOtherPane) {
        var editOtherForm = editOtherPane.querySelector('form');
        if (editOtherForm) {
            editOtherForm.addEventListener('submit', function (event) {
                var submitter = event.submitter || document.activeElement;
                var actionValue = submitter && submitter.getAttribute('value') ? submitter.getAttribute('value') : 'save_inventory_edit';
                var keyInput = document.getElementById('otherItemKey');
                if (actionValue === 'save_inventory_edit' && (!keyInput || !keyInput.value)) {
                    event.preventDefault();
                    window.spmtPopup('Pilih perangkat lain yang ingin diedit terlebih dahulu.', 'warning');
                }
            });
        }
    }

    if (detail.focusItem) {
        var row = document.querySelector('[data-row-key="' + detail.focusItem.replace(/"/g, '\"') + '"]');
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
})();

(function () {
    var detailModal = document.getElementById('complaintDetailModal');
    var imageViewer = document.getElementById('complaintImageViewer');
    var activeImageSrc = '';
    var activeImageTitle = 'Dokumentasi Tiket';

    if (!detailModal && !imageViewer) {
        return;
    }

    function setText(id, value, fallback) {
        var el = document.getElementById(id);
        if (!el) return;
        var finalValue = value && String(value).trim() !== '' ? String(value) : (fallback || '-');
        el.textContent = finalValue;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderHistory(items) {
        var list = document.getElementById('complaintHistoryList');
        if (!list) return;
        if (!Array.isArray(items) || items.length === 0) {
            list.innerHTML = '<div class="complaint-history-empty">Belum ada riwayat perubahan tiket.</div>';
            return;
        }
        list.innerHTML = items.map(function (item) {
            var oldNotes = item.old_notes && String(item.old_notes).trim() !== '' ? escapeHtml(item.old_notes) : '<em>Tidak ada catatan sebelumnya</em>';
            var newNotes = item.new_notes && String(item.new_notes).trim() !== '' ? escapeHtml(item.new_notes) : '<em>Tidak ada catatan penanganan</em>';
            return '' +
                '<article class="complaint-history-item">' +
                    '<div class="complaint-history-item__top">' +
                        '<strong>' + escapeHtml(item.old_status || '-') + ' → ' + escapeHtml(item.new_status || '-') + '</strong>' +
                        '<span>' + escapeHtml(item.changed_at_label || item.changed_at || '-') + '</span>' +
                    '</div>' +
                    '<div class="complaint-history-item__meta">Oleh: ' + escapeHtml(item.changed_by || 'User IT') + '</div>' +
                    '<div class="complaint-history-item__notes">' +
                        '<div><span>Catatan lama</span><p>' + oldNotes + '</p></div>' +
                        '<div><span>Catatan baru</span><p>' + newNotes + '</p></div>' +
                    '</div>' +
                '</article>';
        }).join('');
    }

    function resizeComplaintNotesTextarea(textarea) {
        if (!textarea) return;
        textarea.style.height = 'auto';
        textarea.style.height = Math.max(textarea.scrollHeight, 104) + 'px';
    }

    function openDetailModal(payload) {
        if (!detailModal || !payload) return;
        setText('complaintDetailTitle', payload.ticket_no, '-');
        setText('complaintDetailDatetime', payload.datetime, '-');
        setText('complaintDetailStatus', payload.status, '-');
        setText('complaintDetailName', payload.name, '-');
        setText('complaintDetailEmail', payload.email, '-');
        setText('complaintDetailDivision', payload.division, '-');
        setText('complaintDetailItem', payload.item, '-');
        setText('complaintDetailLocation', payload.location, '-');
        setText('complaintDetailHandledBy', payload.handled_by, 'Belum diambil tim IT');
        setText('complaintDetailEmailStatus', payload.email_status, 'Belum dikirim');
        setText('complaintDetailDescription', payload.description, '-');
        setText('complaintDetailNotes', payload.notes, 'Belum ada catatan penanganan.');
        renderHistory(payload.history || []);

        var imageWrap = document.getElementById('complaintDetailImageWrap');
        var image = document.getElementById('complaintDetailImage');
        activeImageSrc = payload.doc_image || '';
        activeImageTitle = payload.ticket_no ? 'Dokumentasi ' + payload.ticket_no : 'Dokumentasi Tiket';
        if (imageWrap && image) {
            if (activeImageSrc) {
                image.src = activeImageSrc;
                image.alt = activeImageTitle;
                imageWrap.hidden = false;
            } else {
                image.removeAttribute('src');
                imageWrap.hidden = true;
            }
        }

        // Populate the modal's action form
        var modalFormTicketId = document.getElementById('complaintModalTicketId');
        var modalFormStatus = document.getElementById('complaintModalStatus');
        var modalFormPIC = document.getElementById('complaintModalPIC');
        var modalFormCatatan = document.getElementById('complaintModalCatatan');
        var modalFormEmailStatus = document.getElementById('complaintModalEmailStatusInfo');

        if (modalFormTicketId) modalFormTicketId.value = payload.id || '0';
        if (modalFormStatus) modalFormStatus.value = payload.status || 'NOT YET';
        if (modalFormPIC) modalFormPIC.value = payload.handled_by_user_id || '';
        if (modalFormCatatan) {
            modalFormCatatan.value = payload.notes || '';
            resizeComplaintNotesTextarea(modalFormCatatan);
        }
        if (modalFormEmailStatus) {
            var emailStatusHtml = 'Validasi: email pelapor dicek sebelum dikirim.';
            if (payload.email_status && payload.email_status !== 'Belum dikirim') {
                emailStatusHtml += '<br>Status email: <strong>' + escapeHtml(payload.email_status) + '</strong>';
            }
            modalFormEmailStatus.innerHTML = emailStatusHtml;
        }

        // Reset history toggle state to collapsed
        var toggleHistoryBtn = document.querySelector('.js-toggle-complaint-history');
        var historyWrap = document.getElementById('complaintHistoryListWrap');
        if (toggleHistoryBtn && historyWrap) {
            toggleHistoryBtn.setAttribute('aria-expanded', 'false');
            historyWrap.style.display = 'none';
            var chevron = toggleHistoryBtn.querySelector('.complaint-modal__history-chevron');
            if (chevron) {
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
            }
        }

        detailModal.hidden = false;
        detailModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('has-modal-open');
    }

    function closeDetailModal() {
        if (!detailModal) return;
        detailModal.hidden = true;
        detailModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('has-modal-open');
    }

    function openImageViewer(src, title) {
        if (!imageViewer || !src) return;
        var titleEl = document.getElementById('complaintImageTitle');
        var imgEl = document.getElementById('complaintImageViewerImg');
        if (titleEl) titleEl.textContent = title || 'Dokumentasi Tiket';
        if (imgEl) {
            imgEl.src = src;
            imgEl.alt = title || 'Dokumentasi Tiket';
        }
        imageViewer.hidden = false;
        imageViewer.setAttribute('aria-hidden', 'false');
        document.body.classList.add('has-modal-open');
    }

    function closeImageViewer() {
        if (!imageViewer) return;
        var imgEl = document.getElementById('complaintImageViewerImg');
        if (imgEl) imgEl.removeAttribute('src');
        imageViewer.hidden = true;
        imageViewer.setAttribute('aria-hidden', 'true');
        if (detailModal && detailModal.hidden !== true) {
            document.body.classList.add('has-modal-open');
        } else {
            document.body.classList.remove('has-modal-open');
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.js-open-complaint-detail');
        if (button) {
            var raw = button.getAttribute('data-complaint') || '{}';
            try {
                openDetailModal(JSON.parse(raw));
            } catch (error) {
                console.warn('Gagal membaca detail tiket.', error);
            }
        }
    });

    document.querySelectorAll('.js-close-complaint-modal').forEach(function (button) {
        button.addEventListener('click', function () {
            closeDetailModal();
        });
    });

    var complaintNotesTextarea = document.getElementById('complaintModalCatatan');
    if (complaintNotesTextarea) {
        complaintNotesTextarea.addEventListener('input', function () {
            resizeComplaintNotesTextarea(complaintNotesTextarea);
        });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.js-open-complaint-image');
        if (button) {
            openImageViewer(button.getAttribute('data-image-src') || '', button.getAttribute('data-image-title') || 'Dokumentasi Tiket');
        }
    });

    var detailImageButton = document.querySelector('.js-open-complaint-image-from-detail');
    if (detailImageButton) {
        detailImageButton.addEventListener('click', function () {
            if (activeImageSrc) {
                openImageViewer(activeImageSrc, activeImageTitle);
            }
        });
    }

    document.querySelectorAll('.js-close-complaint-image').forEach(function (button) {
        button.addEventListener('click', function () {
            closeImageViewer();
        });
    });

    var toggleHistoryBtn = document.querySelector('.js-toggle-complaint-history');
    if (toggleHistoryBtn) {
        toggleHistoryBtn.addEventListener('click', function () {
            var wrap = document.getElementById('complaintHistoryListWrap');
            var chevron = toggleHistoryBtn.querySelector('.complaint-modal__history-chevron');
            var isExpanded = toggleHistoryBtn.getAttribute('aria-expanded') === 'true';
            
            if (isExpanded) {
                toggleHistoryBtn.setAttribute('aria-expanded', 'false');
                if (wrap) wrap.style.display = 'none';
                if (chevron) {
                    chevron.classList.remove('fa-chevron-up');
                    chevron.classList.add('fa-chevron-down');
                }
            } else {
                toggleHistoryBtn.setAttribute('aria-expanded', 'true');
                if (wrap) wrap.style.display = 'block';
                if (chevron) {
                    chevron.classList.remove('fa-chevron-down');
                    chevron.classList.add('fa-chevron-up');
                }
            }
        });
    }

    // Google Form Integration controls
    var gformModal = document.getElementById('gformSettingsModal');
    function openGformSettingsModal() {
        if (!gformModal) return;
        gformModal.hidden = false;
        gformModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('has-modal-open');
    }
    function closeGformSettingsModal() {
        if (!gformModal) return;
        gformModal.hidden = true;
        gformModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('has-modal-open');
    }

    document.querySelectorAll('.js-open-gform-settings').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            openGformSettingsModal();
        });
    });

    document.querySelectorAll('.js-close-gform-settings').forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            closeGformSettingsModal();
        });
    });

    // Sync GForm button AJAX execution
    var syncBtn = document.querySelector('.js-sync-gform-btn');
    if (syncBtn && window.fetch) {
        var parentForm = syncBtn.closest('form');
        if (parentForm) {
            parentForm.addEventListener('submit', function (event) {
                event.preventDefault();
                
                // Set loading state
                syncBtn.disabled = true;
                var originalText = syncBtn.innerHTML;
                syncBtn.innerHTML = '<i class="fa-solid fa-sync fa-spin"></i> SYNCING...';

                fetch('index.php?page=data-keluhan&action=ajax_sync_gform', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                })
                .then(function (response) {
                    return response.ok ? response.json() : Promise.reject(new Error('Network response not ok'));
                })
                .then(function (res) {
                    syncBtn.disabled = false;
                    syncBtn.innerHTML = originalText;

                    if (res && res.success) {
                        window.spmtPopup(res.message, 'success').then(function () {
                            window.location.reload();
                        });
                    } else {
                        var errorMsg = (res && res.message) ? res.message : 'Terjadi kesalahan saat sinkronisasi.';
                        window.spmtPopup(errorMsg, 'error');
                    }
                })
                .catch(function (err) {
                    syncBtn.disabled = false;
                    syncBtn.innerHTML = originalText;
                    window.spmtPopup('Gagal menghubungi server untuk sinkronisasi. Coba lagi nanti.', 'error');
                    console.error('GForm Sync Error:', err);
                });
            });
        }
    }

    // Auto-sync on page load and periodically
    window.reloadComplaintTableAsynchronously = function () {
        console.log("Reloading table data asynchronously...");
        var currentUrl = window.location.href;
        fetch(currentUrl, { cache: 'no-store' })
            .then(function (response) {
                return response.ok ? response.text() : Promise.reject(new Error('Failed to reload page content'));
            })
            .then(function (html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newTable = doc.getElementById('complaintTable');
                var oldTable = document.getElementById('complaintTable');
                if (newTable && oldTable) {
                    oldTable.innerHTML = newTable.innerHTML;
                    oldTable.setAttribute('data-max-id', newTable.getAttribute('data-max-id') || '0');
                    console.log("Table content updated asynchronously.");
                }
            })
            .catch(function (err) {
                console.error("Asynchronous table reload failed:", err);
            });
    };

    function runAutoSync() {
        if (!document.getElementById('complaintTable')) return;

        fetch('index.php?page=data-keluhan&action=ajax_sync_gform', {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        })
        .then(function (response) {
            return response.ok ? response.json() : null;
        })
        .then(function (res) {
            if (res && res.success && res.imported > 0) {
                // Update table component asynchronously without full page reload
                window.reloadComplaintTableAsynchronously();
            }
        })
        .catch(function (err) {
            console.warn('Auto-sync failed:', err);
        });
    }

    // Run auto-sync 1.5s after load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            window.setTimeout(runAutoSync, 1500);
        });
    } else {
        window.setTimeout(runAutoSync, 1500);
    }

    // Periodic sync check every 30s
    window.setInterval(function () {
        runAutoSync();
    }, 30000);

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (imageViewer && imageViewer.hidden !== true) {
            closeImageViewer();
            return;
        }
        if (detailModal && detailModal.hidden !== true) {
            closeDetailModal();
            return;
        }
        if (gformModal && gformModal.hidden !== true) {
            closeGformSettingsModal();
        }
    });
})();

(function () {
    var dateEl = document.getElementById('realtimeDate');
    var timeEl = document.getElementById('realtimeTime');
    var hiddenDate = document.getElementById('submittedTanggal');
    var hiddenTime = document.getElementById('submittedJam');
    if (dateEl || timeEl || hiddenDate || hiddenTime) {
        var two = function (value) { return String(value).padStart(2, '0'); };
        var monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        var getJakartaNow = function () {
            var now = new Date();
            var jakarta = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
            return jakarta;
        };
        var formatNow = function () {
            var now = getJakartaNow();
            if (dateEl) {
                dateEl.textContent = two(now.getDate()) + ' ' + monthNames[now.getMonth()] + ' ' + now.getFullYear();
            }
            if (timeEl) {
                timeEl.textContent = two(now.getHours()) + ':' + two(now.getMinutes()) + ':' + two(now.getSeconds());
            }
            if (hiddenDate) {
                hiddenDate.value = now.getFullYear() + '-' + two(now.getMonth() + 1) + '-' + two(now.getDate());
            }
            if (hiddenTime) {
                hiddenTime.value = two(now.getHours()) + ':' + two(now.getMinutes()) + ':' + two(now.getSeconds());
            }
        };
        formatNow();
        window.setInterval(formatNow, 1000);
    }

    var syncDivisionLabel = function (divisionSelect) {
        var hiddenLabelId = divisionSelect.getAttribute('data-target-label');
        var labelInput = hiddenLabelId ? document.getElementById(hiddenLabelId) : null;
        var selected = divisionSelect.options[divisionSelect.selectedIndex];
        var divisionLabel = selected ? selected.getAttribute('data-division-label') : '';
        if (labelInput) {
            labelInput.value = divisionLabel || '';
        }
    };

    document.querySelectorAll('.js-division-select').forEach(function (select) {
        select.addEventListener('change', function () {
            syncDivisionLabel(select);
        });
        syncDivisionLabel(select);
    });

})();

(function () {
    var filterToggle = document.querySelector('.js-toggle-log-filters');
    var filterPanel = document.querySelector('.js-log-filters-panel');
    if (filterToggle && filterPanel) {
        filterToggle.addEventListener('click', function () {
            filterPanel.classList.toggle('is-open');
        });
    }

    var liveSearch = document.querySelector('.js-log-live-search');
    var logTableBody = document.querySelector('.js-log-table-body');
    if (liveSearch && logTableBody) {
        var rows = Array.prototype.slice.call(logTableBody.querySelectorAll('tr[data-log-row]'));
        var emptyStateRow = logTableBody.querySelector('.js-log-empty-search');
        var rafSearch = null;
        var buildHaystack = function (row) {
            return (row.getAttribute('data-search-text') || row.textContent || '').toLowerCase().replace(/\s+/g, ' ').trim();
        };
        var filterRows = function () {
            var keyword = (liveSearch.value || '').toLowerCase().replace(/\s+/g, ' ').trim();
            var tokens = keyword === '' ? [] : keyword.split(' ');
            var visibleCount = 0;
            rows.forEach(function (row) {
                var haystack = buildHaystack(row);
                var match = tokens.length === 0 || tokens.every(function (token) { return haystack.indexOf(token) !== -1; });
                row.hidden = !match;
                if (match) visibleCount += 1;
            });
            if (emptyStateRow) {
                emptyStateRow.hidden = visibleCount !== 0;
            }
        };
        liveSearch.addEventListener('input', function () {
            if (rafSearch) {
                window.cancelAnimationFrame(rafSearch);
            }
            rafSearch = window.requestAnimationFrame(filterRows);
        });
        filterRows();
    }

    var modal = document.getElementById('logBarangModal');
    var openBtn = document.querySelector('.js-open-log-modal');
    var closeBtns = document.querySelectorAll('.js-close-log-modal');
    var modalTitle = document.getElementById('logModalTitle');
    var formAction = document.getElementById('logFormAction');
    var idInput = document.getElementById('logId');
    var tanggalInput = document.getElementById('logTanggal');
    var namaInput = document.getElementById('logNamaBarang');
    var statusInput = document.getElementById('logStatus');
    var qtyInput = document.getElementById('logQty');
    var noPoInput = document.getElementById('logNoPo');
    var divisiInput = document.getElementById('logDivisi');
    var keteranganInput = document.getElementById('logKeterangan');
    var pdfInput = document.getElementById('logPdf');
    var pdfHint = document.getElementById('logPdfHint');

    function openModal() {
        if (!modal) return;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        document.body.classList.add('has-modal-open');
    }
    function closeModal() {
        if (modal) {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
        }
        document.body.classList.remove('modal-open');
        document.body.classList.remove('has-modal-open');
        resetForm();
    }
    function resetForm() {
        if (!formAction) return;
        formAction.value = 'save_log_barang';
        if (modalTitle) modalTitle.textContent = 'Tambah Log Barang';
        if (idInput) idInput.value = '';
        if (tanggalInput) tanggalInput.value = new Date().toISOString().slice(0, 10);
        if (namaInput) namaInput.value = '';
        if (statusInput) statusInput.value = 'MASUK';
        if (qtyInput) qtyInput.value = '1';
        if (noPoInput) noPoInput.value = '';
        if (divisiInput) divisiInput.value = '';
        if (keteranganInput) keteranganInput.value = '';
        if (pdfInput) pdfInput.value = '';
        if (pdfHint) pdfHint.textContent = 'Upload PDF jika ada.';
    }

    if (modal) {
        var shouldAutoOpen = modal.getAttribute('data-auto-open') === '1';
        if (!shouldAutoOpen) {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            document.body.classList.remove('has-modal-open');
        }
    }

    if (openBtn) {
        openBtn.addEventListener('click', function (event) {
            event.preventDefault();
            resetForm();
            openModal();
        });
    }

    closeBtns.forEach(function (btn) { btn.addEventListener('click', function (event) {
        event.preventDefault();
        closeModal();
    }); });
    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeModal();
        });
    }

    document.querySelectorAll('.js-edit-log-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!formAction) return;
            formAction.value = 'edit_log_barang';
            if (modalTitle) modalTitle.textContent = 'Edit Log Barang';
            if (idInput) idInput.value = btn.getAttribute('data-id') || '';
            if (tanggalInput) tanggalInput.value = btn.getAttribute('data-tanggal') || '';
            if (namaInput) namaInput.value = btn.getAttribute('data-nama') || '';
            if (statusInput) statusInput.value = btn.getAttribute('data-status') || 'MASUK';
            if (qtyInput) qtyInput.value = btn.getAttribute('data-qty') || '1';
            if (noPoInput) noPoInput.value = btn.getAttribute('data-no-po') || '';
            if (divisiInput) divisiInput.value = btn.getAttribute('data-divisi') || '';
            if (keteranganInput) keteranganInput.value = btn.getAttribute('data-keterangan') || '';
            if (pdfInput) pdfInput.value = '';
            if (pdfHint) {
                var pdfName = btn.getAttribute('data-pdf-name') || '';
                pdfHint.textContent = pdfName ? 'File saat ini: ' + pdfName : 'Upload PDF jika ingin menambahkan file.';
            }
            openModal();
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && !modal.hidden) {
            closeModal();
        }
    });

    var logToast = document.querySelector('.js-log-toast');
    var closeLogToast = document.querySelector('.js-close-log-toast');
    if (logToast) {
        var hideToast = function () {
            logToast.hidden = true;
            logToast.style.display = 'none';
        };
        if (closeLogToast) {
            closeLogToast.addEventListener('click', hideToast);
        }
        window.setTimeout(hideToast, 3200);
    }
})();


(function () {
    var emailInput = document.querySelector('input[name="email_pelapor"]');
    var verifyToggle = document.querySelector('.js-email-verified-toggle');
    var headerCheck = document.querySelector('.support-banner__checkbox');
    var headerMail = document.querySelector('.support-banner__mail');
    if (emailInput && verifyToggle) {
        var isValidEmail = function (value) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test((value || '').trim());
        };
        var syncEmailState = function (forceAutoCheck) {
            var value = (emailInput.value || '').trim();
            if (headerMail) {
                headerMail.textContent = value || 'Masukkan email aktif';
            }
            var valid = isValidEmail(value);
            if (forceAutoCheck) {
                verifyToggle.checked = valid;
            } else if (!valid) {
                verifyToggle.checked = false;
            }
            if (headerCheck) {
                headerCheck.classList.toggle('is-checked', !!verifyToggle.checked && valid);
            }
        };
        emailInput.addEventListener('input', function () { syncEmailState(true); });
        verifyToggle.addEventListener('change', function () { syncEmailState(false); });
        syncEmailState(false);
    }
})();

(function () {
    var filterButton = document.querySelector('.js-toggle-complaint-filters');
    var filterOverlay = document.getElementById('complaintFilterOverlay');
    var searchInput = document.querySelector('.js-complaint-live-search');
    var filterForm = document.querySelector('.js-complaint-filter-form');
    var filterInputs = filterForm ? filterForm.querySelectorAll('.js-complaint-filter-input') : [];
    var rows = Array.prototype.slice.call(document.querySelectorAll('.js-complaint-row'));
    var countEl = document.querySelector('.js-complaint-count');
    var emptyRow = document.querySelector('.js-complaint-empty-row');
    var exportDropdown = document.querySelector('.js-export-dropdown');
    var exportToggle = document.querySelector('.js-toggle-export-menu');
    var liveDateEl = document.querySelector('.js-live-date');
    var liveTimeEl = document.querySelector('.js-live-time');

    function openFilter() {
        if (!filterOverlay) return;
        filterOverlay.hidden = false;
        filterOverlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('has-modal-open');
    }

    function closeFilter() {
        if (!filterOverlay) return;
        filterOverlay.hidden = true;
        filterOverlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('has-modal-open');
    }

    function normalizeText(value) {
        return String(value || '').toLowerCase().trim();
    }

    function ensureEmptyRow() {
        if (emptyRow || !rows.length) return;
        var tbody = document.querySelector('.js-complaint-table-body');
        if (!tbody) return;
        emptyRow = document.createElement('tr');
        emptyRow.className = 'js-complaint-empty-row';
        emptyRow.hidden = true;
        emptyRow.innerHTML = '<td colspan="11"><div class="table-empty-state">Belum ada tiket yang cocok dengan filter.</div></td>';
        tbody.appendChild(emptyRow);
    }

    function applyLiveFilter() {
        ensureEmptyRow();
        var searchValue = normalizeText(searchInput && searchInput.value);
        var statusValue = normalizeText(filterForm && filterForm.elements['complaint_status'] ? filterForm.elements['complaint_status'].value : '');
        var divisionValue = normalizeText(filterForm && filterForm.elements['complaint_division'] ? filterForm.elements['complaint_division'].value : '');
        var dateFromValue = filterForm && filterForm.elements['complaint_date_from'] ? String(filterForm.elements['complaint_date_from'].value || '').trim() : '';
        var dateToValue = filterForm && filterForm.elements['complaint_date_to'] ? String(filterForm.elements['complaint_date_to'].value || '').trim() : '';
        var hasActiveFilter = Boolean(searchValue || statusValue || divisionValue || dateFromValue || dateToValue);
        var visibleCount = 0;

        rows.forEach(function (row) {
            var rowSearch = normalizeText(row.getAttribute('data-search'));
            var rowStatus = normalizeText(row.getAttribute('data-status'));
            var rowDivision = normalizeText(row.getAttribute('data-division'));
            var rowDate = String(row.getAttribute('data-date') || '').trim();
            var isVisible = true;

            if (hasActiveFilter) {
                if (searchValue && rowSearch.indexOf(searchValue) === -1) isVisible = false;
                if (isVisible && statusValue && rowStatus !== statusValue) isVisible = false;
                if (isVisible && divisionValue && rowDivision !== divisionValue) isVisible = false;
                if (isVisible && dateFromValue && rowDate !== '' && rowDate < dateFromValue) isVisible = false;
                if (isVisible && dateToValue && rowDate !== '' && rowDate > dateToValue) isVisible = false;
            }

            row.hidden = !isVisible;
            if (isVisible) visibleCount += 1;
        });

        if (countEl) countEl.textContent = String(visibleCount);
        if (emptyRow) {
            var emptyText = emptyRow.querySelector('.table-empty-state');
            if (emptyText) {
                emptyText.textContent = hasActiveFilter
                    ? 'Belum ada tiket yang cocok dengan filter.'
                    : 'Belum ada data tiket IT support.';
            }
            if (!rows.length) {
                emptyRow.hidden = false;
            } else {
                emptyRow.hidden = !hasActiveFilter || visibleCount !== 0;
            }
        }
    }

    function syncHiddenFilters() {
        if (!searchInput || !searchInput.form || !filterForm) return;
        ['complaint_status', 'complaint_division', 'complaint_date_from', 'complaint_date_to'].forEach(function (name) {
            var source = filterForm.elements[name];
            var target = searchInput.form.elements[name];
            if (source && target) target.value = source.value || '';
        });
    }

    function syncLiveClock() {
        if (!liveDateEl && !liveTimeEl) return;
        var now = new Date();
        if (liveDateEl) {
            liveDateEl.textContent = now.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            });
        }
        if (liveTimeEl) {
            liveTimeEl.textContent = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
        }
    }

    if (filterButton && filterOverlay) {
        filterButton.addEventListener('click', function () {
            openFilter();
            syncHiddenFilters();
            applyLiveFilter();
        });
        filterOverlay.querySelectorAll('.js-close-complaint-filters').forEach(function (button) {
            button.addEventListener('click', function () { closeFilter(); });
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            applyLiveFilter();
        });
    }

    if (filterInputs.length) {
        Array.prototype.forEach.call(filterInputs, function (input) {
            input.addEventListener('input', function () {
                syncHiddenFilters();
                applyLiveFilter();
            });
            input.addEventListener('change', function () {
                syncHiddenFilters();
                applyLiveFilter();
            });
        });
    }

    if (filterForm) {
        filterForm.addEventListener('submit', function () {
            syncHiddenFilters();
        });
    }

    if (exportDropdown && exportToggle) {
        exportToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            var menu = exportDropdown.querySelector('.export-dropdown__menu');
            if (!menu) return;
            var isHidden = menu.hidden;
            menu.hidden = !isHidden;
            exportToggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            exportDropdown.classList.toggle('is-open', isHidden);
        });
        document.addEventListener('click', function (event) {
            if (!exportDropdown.contains(event.target)) {
                var menu = exportDropdown.querySelector('.export-dropdown__menu');
                if (menu) menu.hidden = true;
                exportToggle.setAttribute('aria-expanded', 'false');
                exportDropdown.classList.remove('is-open');
            }
        });
    }

    syncHiddenFilters();
    applyLiveFilter();
    syncLiveClock();
    window.setInterval(syncLiveClock, 1000);
})();


(function () {
    var popup = document.getElementById('supportPopup');
    if (!popup) return;

    function closePopup() {
        popup.classList.remove('is-open');
        setTimeout(function () {
            if (popup && popup.parentNode) popup.parentNode.removeChild(popup);
        }, 120);
    }

    popup.querySelectorAll('[data-popup-close]').forEach(function (button) {
        button.addEventListener('click', closePopup);
    });

    var backdrop = popup.querySelector('.support-popup__backdrop');
    if (backdrop) backdrop.addEventListener('click', closePopup);

    document.addEventListener('keydown', function escHandler(event) {
        if (event.key === 'Escape' && document.getElementById('supportPopup')) {
            closePopup();
        }
    });
})();


(function () {
    var form = document.querySelector('.js-support-form');
    if (!form) return;
    form.addEventListener('submit', function () {
        form.classList.add('is-submitting');
        var button = form.querySelector('.support-submit-btn');
        if (button) {
            if (!button.getAttribute('data-default-label')) {
                button.setAttribute('data-default-label', button.textContent);
            }
            button.textContent = 'MENGIRIM...';
            window.setTimeout(function () {
                form.classList.remove('is-submitting');
                button.disabled = false;
                var label = button.getAttribute('data-default-label');
                if (label) button.textContent = label;
            }, 3500);
        }
    });
})();

(function () {
    var liveNodes = document.querySelectorAll('[data-report-live-updated]');
    if (!liveNodes.length) return;

    function pad(value) {
        return String(value).padStart(2, '0');
    }

    function monthName(monthIndex) {
        var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return months[monthIndex] || '';
    }

    function syncReportUpdated() {
        var now = new Date();
        var value = pad(now.getDate()) + ' ' + monthName(now.getMonth()) + ' ' + now.getFullYear() + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
        liveNodes.forEach(function (node) {
            node.textContent = value;
        });
    }

    syncReportUpdated();
    window.setInterval(syncReportUpdated, 1000);
})();

(function () {
    var notificationButton = document.querySelector('.js-toggle-notifications');
    var notificationMenu = document.querySelector('.js-notification-menu');
    var profileButton = document.querySelector('.js-toggle-profile');
    var profileMenu = document.querySelector('.js-profile-menu');
    var searchInput = document.querySelector('.js-global-search');
    var searchResults = document.querySelector('.js-global-search-results');

    function closeMenu(menu, button) {
        if (!menu) return;
        menu.hidden = true;
        if (button) button.setAttribute('aria-expanded', 'false');
    }
    function toggleMenu(menu, button, otherMenu, otherButton) {
        if (!menu || !button) return;
        var willOpen = menu.hidden;
        closeMenu(otherMenu, otherButton);
        menu.hidden = !willOpen;
        button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    if (notificationButton) {
        notificationButton.addEventListener('click', function (event) {
            event.stopPropagation();
            toggleMenu(notificationMenu, notificationButton, profileMenu, profileButton);
        });
    }
    if (profileButton) {
        profileButton.addEventListener('click', function (event) {
            event.stopPropagation();
            toggleMenu(profileMenu, profileButton, notificationMenu, notificationButton);
        });
    }
    document.addEventListener('click', function (event) {
        if (notificationMenu && !notificationMenu.contains(event.target) && event.target !== notificationButton) closeMenu(notificationMenu, notificationButton);
        if (profileMenu && !profileMenu.contains(event.target) && event.target !== profileButton) closeMenu(profileMenu, profileButton);
        if (searchResults && searchInput && !searchResults.contains(event.target) && event.target !== searchInput) searchResults.hidden = true;
    });

    var allowedPages = (window.SPMT_DATA && Array.isArray(window.SPMT_DATA.accessible_pages)) ? window.SPMT_DATA.accessible_pages : [];
    function canUsePage(page) {
        return !allowedPages.length || allowedPages.indexOf(page) !== -1;
    }
    var routes = [
        { title: 'Dashboard', url: 'index.php?page=dashboard', page: 'dashboard', icon: 'fa-solid fa-house', keywords: 'dashboard ringkasan inventaris grafik' },
        { title: 'Inventaris Baru', url: 'index.php?page=inventory-step-1', page: 'inventory-step-1', icon: 'fa-regular fa-square-plus', keywords: 'input inventaris baru tambah barang pc perangkat' },
        { title: 'Data Inventaris', url: 'index.php?page=data-inventaris', page: 'data-inventaris', icon: 'fa-solid fa-database', keywords: 'data inventaris aset pc user perangkat' },
        { title: 'IT Support Issue', url: 'index.php?page=data-keluhan', page: 'data-keluhan', icon: 'fa-solid fa-user-group', keywords: 'it support keluhan tiket issue request form' },
        { title: 'Log Barang', url: 'index.php?page=log-barang', page: 'log-barang', icon: 'fa-solid fa-right-from-bracket', keywords: 'log barang masuk keluar po' },
        { title: 'Routine Monitoring', url: 'index.php?page=routine-monitoring', page: 'routine-monitoring', icon: 'fa-solid fa-clipboard-check', keywords: 'routine monitoring checklist gate cctv server harian mingguan kondisi baik kurang baik buruk' },
        { title: 'Laporan', url: 'index.php?page=laporan', page: 'laporan', icon: 'fa-regular fa-file-lines', keywords: 'laporan export pdf excel' },
        { title: 'Setting Akun', url: 'index.php?page=account-settings', page: 'account-settings', icon: 'fa-solid fa-gear', keywords: 'setting akun profile nama email password' }
    ].filter(function (item) { return canUsePage(item.page); });

    function normalize(value) { return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim(); }
    function currentPageRows(query) {
        var out = [];
        if (!query) return out;
        if (canUsePage('data-keluhan')) {
        document.querySelectorAll('.js-complaint-row').forEach(function (row) {
            var text = normalize(row.getAttribute('data-search') || row.textContent || '');
            if (text.indexOf(query) !== -1) {
                var ticket = row.querySelector('.complaint-ticket');
                var id = row.getAttribute('data-ticket-id') || '';
                out.push({ title: 'Tiket ' + (ticket ? ticket.textContent.trim() : 'IT Support'), url: 'index.php?page=data-keluhan&focus_ticket=' + encodeURIComponent(id), icon: 'fa-solid fa-bell', keywords: text });
            }
        });
        }
        document.querySelectorAll('[data-log-row]').forEach(function (row) {
            var text = normalize(row.getAttribute('data-search-text') || row.textContent || '');
            if (text.indexOf(query) !== -1) out.push({ title: 'Log Barang: ' + (row.textContent || '').replace(/\s+/g, ' ').trim().slice(0, 70), url: 'index.php?page=log-barang&log_search=' + encodeURIComponent(query), icon: 'fa-solid fa-right-from-bracket', keywords: text });
        });
        return out.slice(0, 6);
    }
    function renderSearchResults() {
        if (!searchInput || !searchResults) return;
        var q = normalize(searchInput.value);
        if (!q) { searchResults.hidden = true; searchResults.innerHTML = ''; return; }
        var matches = routes.filter(function (item) { return normalize(item.title + ' ' + item.keywords).indexOf(q) !== -1; }).concat(currentPageRows(q)).slice(0, 10);
        if (!matches.length) {
            searchResults.innerHTML = '<div class="global-search-empty">Tidak ada hasil. Coba kata kunci lain.</div>';
        } else {
            searchResults.innerHTML = matches.map(function (item) {
                return '<a class="global-search-item" href="' + item.url + '"><i class="' + item.icon + '"></i><span>' + item.title.replace(/[<>&]/g, function (c) { return {'<':'&lt;','>':'&gt;','&':'&amp;'}[c]; }) + '</span></a>';
            }).join('');
        }
        searchResults.hidden = false;
    }
    if (searchInput) {
        searchInput.addEventListener('input', renderSearchResults);
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                var first = searchResults && searchResults.querySelector('a');
                if (first) window.location.href = first.href;
            }
        });
    }

    var params = new URLSearchParams(window.location.search);
    var focusTicket = params.get('focus_ticket');
    if (focusTicket) {
        var target = document.querySelector('.js-complaint-row[data-ticket-id="' + CSS.escape(focusTicket) + '"]');
        if (target) {
            target.classList.add('is-focused');
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            var detailButton = target.querySelector('.js-open-complaint-detail');
            if (detailButton) window.setTimeout(function () { detailButton.click(); }, 350);
        }
    }
})();

(function () {
    var button = document.querySelector('.js-toggle-notifications');
    var menu = document.querySelector('.js-notification-menu');
    if (!button || !menu || !window.fetch) return;

    var badge = document.querySelector('.js-notification-badge');
    var countText = document.querySelector('.js-notification-count-text');
    var lastCount = parseInt(button.getAttribute('data-notification-count') || '0', 10) || 0;

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char];
        });
    }

    function setNotificationCount(count) {
        count = Math.max(0, parseInt(count || 0, 10) || 0);
        button.setAttribute('data-notification-count', String(count));
        if (badge) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = count <= 0;
        }
        if (countText) countText.textContent = String(count);
        lastCount = count;
    }

    function renderNotifications(payload) {
        var count = parseInt(payload && payload.count ? payload.count : 0, 10) || 0;
        var items = payload && Array.isArray(payload.items) ? payload.items : [];
        setNotificationCount(count);

        var header = menu.querySelector('.topbar__menu-header');
        var html = header ? header.outerHTML : '<div class="topbar__menu-header"><strong>IT Support baru</strong><span><span class="js-notification-count-text">' + count + '</span> notifikasi</span></div>';
        if (items.length) {
            html += items.map(function (item) {
                var id = encodeURIComponent(item.id || '0');
                return '<a class="notification-item" href="index.php?page=data-keluhan&focus_ticket=' + id + '&mark_notification_read=' + id + '">' +
                    '<span class="notification-item__ticket">' + escapeHtml(item.ticket_no || '-') + '</span>' +
                    '<strong>' + escapeHtml(item.nama || 'Pelapor') + '</strong>' +
                    '<small>' + escapeHtml(item.divisi || '-') + ' - ' + escapeHtml(item.barang || '-') + '</small>' +
                    '<em>' + escapeHtml(item.tanggal_dan_jam || '') + '</em>' +
                    '</a>';
            }).join('') + '<a class="topbar__menu-footer" href="index.php?page=data-keluhan&complaint_status=NOT+YET&mark_all_notifications=1">Lihat semua tiket baru</a>';
        } else {
            html += '<div class="notification-empty">Belum ada form IT Support baru.</div>';
        }
        menu.innerHTML = html;
        countText = document.querySelector('.js-notification-count-text');
        setNotificationCount(count);
    }

    function refreshNotifications() {
        if (!canUsePage('data-keluhan')) return;
        fetch('index.php?page=dashboard&ajax=it_support_notifications', { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(function (response) { return response.ok ? response.json() : null; })
            .then(function (payload) {
                if (payload) {
                    renderNotifications(payload);
                    var table = document.getElementById('complaintTable');
                    var shouldReload = payload.has_new_imports;
                    if (!shouldReload && table && Array.isArray(payload.items)) {
                        var currentMaxId = parseInt(table.getAttribute('data-max-id') || '0', 10) || 0;
                        payload.items.forEach(function (item) {
                            var itemId = parseInt(item.id || '0', 10) || 0;
                            if (itemId > currentMaxId) {
                                shouldReload = true;
                            }
                        });
                    }
                    if (shouldReload && table) {
                        if (typeof window.reloadComplaintTableAsynchronously === 'function') {
                            window.reloadComplaintTableAsynchronously();
                        }
                    }
                }
            })
            .catch(function () {});
    }

    menu.addEventListener('click', function (event) {
        var item = event.target.closest ? event.target.closest('.notification-item') : null;
        if (!item || !menu.contains(item)) return;
        var current = parseInt(button.getAttribute('data-notification-count') || '0', 10) || 0;
        setNotificationCount(current - 1);
        item.remove();
        if (!menu.querySelector('.notification-item')) {
            var footer = menu.querySelector('.topbar__menu-footer');
            if (footer) footer.remove();
            if (!menu.querySelector('.notification-empty')) {
                menu.insertAdjacentHTML('beforeend', '<div class="notification-empty">Belum ada form IT Support baru.</div>');
            }
        }
    });

    window.setInterval(refreshNotifications, 15000);
    window.addEventListener('focus', refreshNotifications);
})();

(function () {
    function resetSubmitState(root) {
        var scope = root || document;
        scope.querySelectorAll('form.is-submitting, form[data-submit-locked="1"]').forEach(function (form) {
            form.classList.remove('is-submitting');
            form.removeAttribute('data-submit-locked');
        });
        scope.querySelectorAll('button[type="submit"][disabled], input[type="submit"][disabled]').forEach(function (button) {
            button.disabled = false;
            var originalLabel = button.getAttribute('data-default-label');
            if (originalLabel && button.tagName.toLowerCase() === 'button') button.textContent = originalLabel;
        });
    }

    function cleanupClosedModals() {
        var openModals = document.querySelectorAll('.modal.is-open');
        document.body.classList.toggle('has-modal-open', openModals.length > 0);
        document.querySelectorAll('.modal').forEach(function (modal) {
            modal.setAttribute('aria-hidden', modal.classList.contains('is-open') ? 'false' : 'true');
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || !form.matches || !form.matches('form')) return;
        if (event.defaultPrevented || !form.checkValidity()) {
            resetSubmitState(form);
            return;
        }
        form.setAttribute('data-submit-locked', '1');
        form.classList.add('is-submitting');
        window.setTimeout(function () {
            if (document.visibilityState !== 'hidden') {
                resetSubmitState(form);
                cleanupClosedModals();
            }
        }, 3500);
    });

    document.addEventListener('invalid', function (event) {
        event.preventDefault();
        if (event.target && event.target.form) resetSubmitState(event.target.form);
        var input = event.target;
        var form = input && input.form ? input.form : null;
        if (form && form.getAttribute('data-validation-popup-open') !== '1') {
            form.setAttribute('data-validation-popup-open', '1');
            var label = input.getAttribute('aria-label') || input.getAttribute('placeholder') || input.name || 'Field';
            var message = input.validationMessage || 'Mohon lengkapi data yang wajib diisi.';
            window.spmtPopup(label + ': ' + message, 'warning').then(function () {
                form.removeAttribute('data-validation-popup-open');
                if (input.focus) input.focus();
            });
        }
    }, true);

    document.addEventListener('click', function (event) {
        var link = event.target && event.target.closest ? event.target.closest('.pagination a[href], .detail-title-wrap__nav[href]') : null;
        if (!link) return;
        var href = link.getAttribute('href');
        if (!href || href === '#') return;
        resetSubmitState(document);
        cleanupClosedModals();
        window.location.href = href;
    }, true);

    window.addEventListener('pageshow', function () { resetSubmitState(document); cleanupClosedModals(); });
    window.addEventListener('focus', function () { resetSubmitState(document); cleanupClosedModals(); });
    document.addEventListener('DOMContentLoaded', function () { resetSubmitState(document); cleanupClosedModals(); });
})();

(function () {
    var form = document.querySelector('.js-cctv-form');
    if (!form) return;

    var idInput = document.getElementById('cctvId');
    var lokasiInput = document.getElementById('cctvLokasi');
    var jumlahInput = document.getElementById('cctvJumlah');
    var colorInput = document.getElementById('cctvColor');
    var deleteButton = form.querySelector('.js-delete-cctv');
    var title = document.getElementById('cctvModalTitle');

    function setModeAdd() {
        if (idInput) idInput.value = '';
        if (lokasiInput) lokasiInput.value = '';
        if (jumlahInput) jumlahInput.value = '0';
        if (colorInput) colorInput.value = '#5B8DEF';
        if (deleteButton) deleteButton.disabled = true;
        if (title) title.textContent = 'Tambah Data CCTV';
    }

    function setModeEdit(button) {
        if (!button) return;
        if (idInput) idInput.value = button.getAttribute('data-id') || '';
        if (lokasiInput) lokasiInput.value = button.getAttribute('data-lokasi') || '';
        if (jumlahInput) jumlahInput.value = button.getAttribute('data-jumlah') || '0';
        if (colorInput) colorInput.value = button.getAttribute('data-color') || '#5B8DEF';
        if (deleteButton) deleteButton.disabled = !(idInput && idInput.value);
        if (title) title.textContent = 'Edit Data CCTV';
        if (lokasiInput) lokasiInput.focus();
    }

    document.querySelectorAll('.js-edit-cctv').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            setModeEdit(button);
        });
    });

    document.querySelectorAll('.js-reset-cctv').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            setModeAdd();
        });
    });

    if (deleteButton) {
        deleteButton.addEventListener('click', function (event) {
            if (!idInput || !idInput.value) {
                event.preventDefault();
                if (window.spmtPopup) window.spmtPopup('Pilih data CCTV yang ingin dihapus.', 'warning');
                return;
            }
            if (!window.spmtConfirm) return;
            event.preventDefault();
            window.spmtConfirm('Hapus data CCTV ini?', 'Konfirmasi Hapus').then(function (ok) {
                if (!ok) return;
                deleteButton.disabled = false;
                deleteButton.value = 'delete_cctv';
                form.setAttribute('data-confirmed-submit', '1');
                form.requestSubmit(deleteButton);
            });
        });
    }

    document.querySelectorAll('.dashboard-cctv-card').forEach(function (card) {
        card.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                card.click();
            }
        });
    });

    setModeAdd();
})();

(function () {
    var toggle = document.querySelector('.js-sidebar-toggle');
    var closeBtn = document.querySelector('.js-sidebar-close');
    var sidebar = document.getElementById('appSidebar');
    if (!sidebar || !toggle) return;

    function setOpen(open) {
        document.body.classList.toggle('sidebar-is-open', !!open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    toggle.addEventListener('click', function (event) {
        event.preventDefault();
        setOpen(!document.body.classList.contains('sidebar-is-open'));
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function (event) {
            event.preventDefault();
            setOpen(false);
        });
    }

    document.addEventListener('click', function (event) {
        if (!document.body.classList.contains('sidebar-is-open')) return;
        if (event.target.closest('#appSidebar') || event.target.closest('.js-sidebar-toggle')) return;
        setOpen(false);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') setOpen(false);
    });

    sidebar.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () { setOpen(false); });
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 1024) setOpen(false);
    });
})();


(function () {
    var form = document.querySelector('.js-user-management-filter');
    if (!form) return;

    var searchInput = form.querySelector('[data-user-live-search]');
    var statusSelect = form.querySelector('[data-user-status-filter]');
    var resetLink = form.querySelector('[data-user-filter-reset]');
    var rows = Array.prototype.slice.call(document.querySelectorAll('.js-user-row'));
    var emptyRow = document.querySelector('.js-user-empty-row');
    var countNode = document.querySelector('[data-user-visible-count]');

    function normalize(value) {
        return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function applyUserFilter() {
        var keyword = normalize(searchInput ? searchInput.value : '');
        var status = statusSelect ? String(statusSelect.value || 'all') : 'all';
        var visible = 0;

        rows.forEach(function (row) {
            var haystack = normalize(row.getAttribute('data-search') || row.textContent || '');
            var rowStatus = String(row.getAttribute('data-status') || '');
            var matchKeyword = !keyword || haystack.indexOf(keyword) !== -1;
            var matchStatus = status === 'all' || rowStatus === status;
            var show = matchKeyword && matchStatus;
            row.classList.toggle('is-hidden', !show);
            if (show) visible += 1;
        });

        if (emptyRow) emptyRow.style.display = visible === 0 ? '' : 'none';
        if (countNode) countNode.textContent = String(visible);
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyUserFilter);
    }
    if (statusSelect) {
        statusSelect.addEventListener('change', applyUserFilter);
    }
    if (resetLink) {
        resetLink.addEventListener('click', function (event) {
            event.preventDefault();
            if (searchInput) searchInput.value = '';
            if (statusSelect) statusSelect.value = 'all';
            applyUserFilter();
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', 'index.php?page=user-management');
            }
        });
    }

    applyUserFilter();
})();

(function () {
    var select = document.querySelector('.js-routine-period-select');
    if (!select) return;
    var dailyField = document.querySelector('[data-routine-daily-field]');
    var weeklyField = document.querySelector('[data-routine-weekly-field]');
    function syncPeriodFields() {
        var weekly = select.value === 'weekly';
        if (dailyField) dailyField.classList.toggle('is-hidden', weekly);
        if (weeklyField) weeklyField.classList.toggle('is-hidden', !weekly);
    }
    select.addEventListener('change', syncPeriodFields);
    syncPeriodFields();

    document.querySelectorAll('.routine-status-option input').forEach(function (input) {
        input.addEventListener('change', function () {
            var group = input.closest('.routine-status-options');
            if (!group) return;
            group.querySelectorAll('.routine-status-option').forEach(function (label) {
                label.classList.toggle('is-selected', label.contains(input) && input.checked);
            });
        });
    });
})();

(function () {
    var modal = document.getElementById('routineItemManagerModal');
    if (!modal) return;
    var openBtns = document.querySelectorAll('.js-open-routine-manager');
    var closeBtns = modal.querySelectorAll('.js-close-routine-manager');
    function openRoutineManager() {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        document.body.classList.add('has-modal-open');
        var firstField = modal.querySelector('input, select, button');
        if (firstField && firstField.focus) window.setTimeout(function () { firstField.focus(); }, 0);
    }
    function closeRoutineManager() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        document.body.classList.remove('has-modal-open');
    }
    openBtns.forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            openRoutineManager();
        });
    });
    closeBtns.forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            closeRoutineManager();
        });
    });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeRoutineManager();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) closeRoutineManager();
    });
})();

(function () {
    var cards = document.querySelectorAll('[data-routine-category-target]');
    if (!cards.length) return;
    var panels = document.querySelectorAll('[data-routine-category-panel]');
    function activate(category) {
        cards.forEach(function (card) {
            var active = card.getAttribute('data-routine-category-target') === category;
            card.classList.toggle('is-active', active);
            card.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach(function (panel) {
            var active = panel.getAttribute('data-routine-category-panel') === category;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
        });
    }
    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            activate(card.getAttribute('data-routine-category-target'));
        });
    });
})();

(function () {
    function applyImageRatioClass(img) {
        if (!img) return;
        var width = img.naturalWidth || 0;
        var height = img.naturalHeight || 0;
        if (!width || !height) return;
        var ratio = width / height;
        var targets = [img, img.closest('.thumb--image'), img.closest('.doc-upload__preview-thumb-wrap')].filter(Boolean);
        targets.forEach(function (target) {
            target.classList.remove('is-portrait', 'is-landscape', 'is-wide', 'is-square-ish');
            if (ratio < 0.78) target.classList.add('is-portrait');
            else if (ratio > 2.25) target.classList.add('is-wide');
            else if (ratio > 1.18) target.classList.add('is-landscape');
            else target.classList.add('is-square-ish');
            target.style.setProperty('--spmt-image-ratio', String(ratio));
        });
    }

    function watchImage(img) {
        if (!img) return;
        if (img.complete && img.naturalWidth) applyImageRatioClass(img);
        img.addEventListener('load', function () { applyImageRatioClass(img); });
        img.addEventListener('error', function () { applyImageRatioClass(img); });
    }

    document.querySelectorAll('.data-table--inventory .thumb--image img, .doc-upload__preview-thumb').forEach(watchImage);

    document.querySelectorAll('.js-image-input[data-preview-target]').forEach(function (input) {
        input.addEventListener('change', function () {
            var targetId = input.getAttribute('data-preview-target');
            var target = targetId ? document.getElementById(targetId) : null;
            if (!target || !input.files || !input.files[0]) return;
            var file = input.files[0];
            if (!/^image\//i.test(file.type || '')) return;
            var oldImg = target.querySelector('.inventory-inline-preview-img');
            if (oldImg && oldImg.dataset.objectUrl) {
                URL.revokeObjectURL(oldImg.dataset.objectUrl);
            }
            var url = URL.createObjectURL(file);
            target.classList.add('has-image-preview');
            target.innerHTML = '';
            var text = document.createElement('span');
            text.textContent = 'File dipilih: ' + file.name;
            var img = document.createElement('img');
            img.className = 'inventory-inline-preview-img';
            img.alt = 'Preview gambar inventaris';
            img.src = url;
            img.dataset.objectUrl = url;
            img.addEventListener('load', function () { applyImageRatioClass(img); });
            target.appendChild(text);
            target.appendChild(img);
        });
    });

    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (!node || node.nodeType !== 1) return;
                if (node.matches && node.matches('.data-table--inventory .thumb--image img, .doc-upload__preview-thumb, .inventory-inline-preview-img')) {
                    watchImage(node);
                }
                if (node.querySelectorAll) {
                    node.querySelectorAll('.data-table--inventory .thumb--image img, .doc-upload__preview-thumb, .inventory-inline-preview-img').forEach(watchImage);
                }
            });
        });
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
})();
