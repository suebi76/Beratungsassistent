(() => {
    const formatBytes = (bytes) => {
        if (!Number.isFinite(bytes) || bytes <= 0) {
            return '0 KB';
        }
        const units = ['B', 'KB', 'MB', 'GB'];
        let value = bytes;
        let unitIndex = 0;
        while (value >= 1024 && unitIndex < units.length - 1) {
            value /= 1024;
            unitIndex += 1;
        }
        return `${value.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
    };

    const createJobId = () => {
        const bytes = new Uint8Array(12);
        if (window.crypto?.getRandomValues) {
            window.crypto.getRandomValues(bytes);
        } else {
            bytes.forEach((_, index) => {
                bytes[index] = Math.floor(Math.random() * 256);
            });
        }
        return Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
    };

    const safePdfBase = (name) => {
        const raw = (name || 'dokument.pdf').replace(/\.[^.]+$/, '').trim().toLowerCase();
        return raw
            .replaceAll('ä', 'ae')
            .replaceAll('ö', 'oe')
            .replaceAll('ü', 'ue')
            .replaceAll('ß', 'ss')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'dokument';
    };

    const splitPartName = (name, part, start, end, pageCount) => {
        const width = Math.max(3, String(Math.max(1, pageCount || end)).length);
        const pad = (value, size) => String(value).padStart(size, '0');
        return `${safePdfBase(name)}_teil-${pad(part, 3)}_seiten-${pad(start, width)}-${pad(end, width)}.pdf`;
    };

    document.querySelectorAll('[data-pdf-split-planner]').forEach((planner) => {
        const nameInput = planner.querySelector('[data-pdf-split-name]');
        const pagesInput = planner.querySelector('[data-pdf-split-pages]');
        const sizeInput = planner.querySelector('[data-pdf-split-size]');
        const output = planner.querySelector('[data-pdf-split-output]');
        if (!nameInput || !pagesInput || !sizeInput || !output) {
            return;
        }

        const renderSplitPlan = () => {
            const pageCount = Math.max(1, Number.parseInt(pagesInput.value, 10) || 1);
            const pagesPerPart = Math.max(1, Math.min(500, Number.parseInt(sizeInput.value, 10) || 25));
            const partCount = Math.ceil(pageCount / pagesPerPart);
            const names = [];
            for (let part = 1; part <= partCount; part += 1) {
                const start = ((part - 1) * pagesPerPart) + 1;
                const end = Math.min(pageCount, start + pagesPerPart - 1);
                names.push(splitPartName(nameInput.value, part, start, end, pageCount));
            }
            const visibleNames = partCount > 8
                ? [...names.slice(0, 5), '...', names[names.length - 1]]
                : names;
            output.replaceChildren();
            const summary = document.createElement('strong');
            summary.textContent = `${partCount} Teil-Datei(en) mit je ${pagesPerPart} Seiten geplant.`;
            const hint = document.createElement('p');
            hint.className = 'muted';
            hint.textContent = 'Die Namen werden später für Server-, Browser- oder Portable-Splitting identisch verwendet.';
            const list = document.createElement('ul');
            visibleNames.forEach((fileName) => {
                const item = document.createElement('li');
                item.textContent = fileName;
                list.append(item);
            });
            output.append(summary, hint, list);
        };

        [nameInput, pagesInput, sizeInput].forEach((input) => {
            input.addEventListener('input', renderSplitPlan);
        });
        renderSplitPlan();
    });

    document.querySelectorAll('[data-auto-submit]').forEach((control) => {
        control.addEventListener('change', () => control.form?.submit());
    });

    document.querySelectorAll('[data-select-all]').forEach((control) => {
        control.addEventListener('change', () => {
            const formId = control.getAttribute('data-select-all');
            const form = formId ? document.getElementById(formId) : control.form;
            if (!form) {
                return;
            }
            document.querySelectorAll('input[type="checkbox"][name="files[]"]').forEach((checkbox) => {
                if (checkbox.form !== form) {
                    return;
                }
                checkbox.checked = control.checked;
            });
        });
    });

    document.querySelectorAll('form[data-upload-queue]').forEach((form) => {
        const input = form.querySelector('[data-upload-input]');
        const dropzone = form.querySelector('[data-upload-dropzone]');
        const panel = form.querySelector('[data-upload-panel]');
        const list = form.querySelector('[data-upload-list]');
        const summary = form.querySelector('[data-upload-summary]');
        const progress = form.querySelector('[data-upload-progress]');
        const submitButton = form.querySelector('[data-upload-submit]');
        const csrf = form.querySelector('input[name="csrf_token"]');
        const queue = [];
        let running = false;
        let queueMessage = '';

        if (!input || !dropzone || !panel || !list || !summary || !progress || !csrf) {
            return;
        }

        const actionUrl = form.getAttribute('action') || window.location.href;
        const statusUrl = new URL(actionUrl, window.location.href);
        statusUrl.search = '';

        const resetFailedItem = (item) => {
            item.jobId = createJobId();
            item.percent = 0;
            item.status = 'waiting';
            item.statusLabel = 'Wartet';
            item.detail = 'bereit zur Wiederholung';
            queueMessage = 'Fehlgeschlagene Datei wurde wieder in die Warteschlange gelegt.';
            renderQueue();
        };

        const removeQueueItem = (item) => {
            const index = queue.indexOf(item);
            if (index >= 0) {
                queue.splice(index, 1);
                queueMessage = '';
                renderQueue();
            }
        };

        const updateProgress = () => {
            const percent = queue.length > 0
                ? Math.round(queue.reduce((sum, item) => sum + (item.percent || 0), 0) / queue.length)
                : 0;
            progress.style.width = `${percent}%`;
        };

        const renderQueue = () => {
            panel.hidden = queue.length === 0;
            list.replaceChildren();
            queue.forEach((item) => {
                const row = document.createElement('div');
                row.className = `upload-item ${item.status}`;

                const body = document.createElement('div');
                const title = document.createElement('strong');
                title.textContent = item.file.name;
                const meta = document.createElement('span');
                meta.className = 'muted';
                meta.textContent = `${formatBytes(item.file.size)} · ${item.detail}`;
                body.append(title, meta);

                const badge = document.createElement('span');
                badge.className = 'upload-status';
                badge.textContent = item.statusLabel;

                row.append(body, badge);
                if (!running && (item.status === 'waiting' || item.status === 'error')) {
                    const itemActions = document.createElement('div');
                    itemActions.className = 'upload-item-actions';

                    if (item.status === 'error') {
                        const retry = document.createElement('button');
                        retry.type = 'button';
                        retry.className = 'upload-retry';
                        retry.textContent = 'Wiederholen';
                        retry.addEventListener('click', () => resetFailedItem(item));
                        itemActions.append(retry);
                    }

                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'upload-remove';
                    remove.textContent = 'Entfernen';
                    remove.addEventListener('click', () => removeQueueItem(item));
                    itemActions.append(remove);
                    row.append(itemActions);
                }
                const itemProgress = document.createElement('div');
                itemProgress.className = 'upload-item-progress';
                itemProgress.setAttribute('aria-hidden', 'true');
                const itemProgressBar = document.createElement('span');
                itemProgressBar.style.width = `${item.percent || 0}%`;
                itemProgress.append(itemProgressBar);
                row.append(itemProgress);

                list.append(row);
            });

            const waiting = queue.filter((item) => item.status === 'waiting').length;
            const done = queue.filter((item) => item.status === 'done').length;
            const errors = queue.filter((item) => item.status === 'error').length;
            if (queueMessage !== '') {
                summary.textContent = queueMessage;
            } else if (queue.length === 0) {
                summary.textContent = 'Keine Dateien ausgewählt.';
            } else if (running) {
                summary.textContent = `${done} erledigt, ${errors} mit Fehler, ${waiting} warten.`;
            } else if (errors > 0) {
                summary.textContent = `${done} erledigt, ${errors} mit Fehler. Fehlgeschlagene Dateien können wiederholt werden.`;
            } else {
                summary.textContent = `${queue.length} Datei(en) in der Warteschlange.`;
            }
            updateProgress();
        };

        const addFiles = (fileList) => {
            Array.from(fileList || []).forEach((file) => {
                const duplicate = queue.some((item) => item.file.name === file.name && item.file.size === file.size && item.file.lastModified === file.lastModified);
                if (duplicate) {
                    return;
                }
                const isLargePdf = file.name.toLowerCase().endsWith('.pdf') && file.size >= 8 * 1024 * 1024;
                queue.push({
                    file,
                    jobId: createJobId(),
                    percent: 0,
                    status: 'waiting',
                    statusLabel: 'Wartet',
                    detail: isLargePdf
                        ? 'große PDF erkannt; falls die Verarbeitung abbricht, vorher in 25-Seiten-Teile splitten'
                        : 'bereit zur Verarbeitung',
                });
            });
            input.value = '';
            queueMessage = '';
            renderQueue();
        };

        input.addEventListener('change', () => addFiles(input.files));

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            addFiles(event.dataTransfer?.files || []);
        });

        const postFormData = async (formData) => {
            const response = await fetch(actionUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'fetch' },
            });
            let payload = null;
            try {
                payload = await response.json();
            } catch (error) {
                throw new Error('Der Server hat keine gültige JSON-Antwort geliefert.');
            }
            if (!response.ok) {
                throw new Error(payload.message || 'Die Anfrage wurde vom Server abgelehnt.');
            }
            return payload;
        };

        const pollJobStatus = async (item) => {
            const url = new URL(statusUrl.toString());
            url.searchParams.set('async_status', 'upload_job');
            url.searchParams.set('job_id', item.jobId);
            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'fetch' },
            });
            if (!response.ok) {
                return;
            }
            const payload = await response.json();
            if (!payload.ok) {
                return;
            }
            if (item.status !== 'processing') {
                return;
            }

            item.percent = typeof payload.percent === 'number' ? payload.percent : item.percent;
            item.detail = payload.message || item.detail;
            if (payload.status === 'running') {
                item.status = 'processing';
                item.statusLabel = 'Verarbeitet';
            }
            renderQueue();
        };

        const uploadItem = async (item) => {
            item.status = 'processing';
            item.statusLabel = 'Verarbeitet';
            item.percent = Math.max(item.percent || 0, 2);
            item.detail = 'Upload läuft, anschließend verarbeitet die KI die Datei.';
            renderQueue();

            let pollTimer = window.setInterval(() => {
                pollJobStatus(item).catch(() => {});
            }, 1500);

            const formData = new FormData();
            formData.append('csrf_token', csrf.value);
            formData.append('action', 'upload_documents');
            formData.append('async_upload', '1');
            formData.append('job_id', item.jobId);
            formData.append('documents[]', item.file, item.file.name);
            try {
                const payload = await postFormData(formData);
                const result = Array.isArray(payload.uploadResults) ? payload.uploadResults[0] : null;
                if (!result || !result.ok) {
                    throw new Error(result?.error || payload.message || 'Datei konnte nicht verarbeitet werden.');
                }

                const chunkCount = Array.isArray(result.saved_chunks) ? result.saved_chunks.length : 0;
                item.status = 'done';
                item.statusLabel = result.skipped_duplicate ? 'Übersprungen' : 'Fertig';
                item.percent = 100;
                if (result.skipped_duplicate && result.message) {
                    item.detail = result.message;
                } else if (!payload.ok && payload.message) {
                    item.detail = `Verarbeitet; Hinweis: ${payload.message}`;
                } else {
                    item.detail = `${chunkCount} Textabschnitt(e) erzeugt`;
                }
                renderQueue();
                return result;
            } finally {
                window.clearInterval(pollTimer);
            }
        };

        const finalizeQueue = async () => {
            const formData = new FormData();
            formData.append('csrf_token', csrf.value);
            formData.append('action', 'finalize_upload_queue');
            formData.append('async_action', '1');
            return postFormData(formData);
        };

        form.addEventListener('submit', async (event) => {
            const submitter = event.submitter || document.activeElement;
            if (submitter?.name === 'action' && submitter.value !== 'upload_documents') {
                return;
            }
            if (!window.fetch) {
                return;
            }

            event.preventDefault();
            if (running) {
                return;
            }
            if (queue.length === 0 && input.files?.length) {
                addFiles(input.files);
            }
            if (queue.length === 0) {
                panel.hidden = false;
                summary.textContent = 'Bitte mindestens eine Datei auswählen oder in den Uploadbereich ziehen.';
                return;
            }
            if (!queue.some((item) => item.status === 'waiting')) {
                const errors = queue.filter((item) => item.status === 'error').length;
                queueMessage = errors > 0
                    ? 'Keine wartenden Dateien. Fehlgeschlagene Dateien können direkt mit „Wiederholen“ erneut eingeplant werden.'
                    : 'Alle Dateien in dieser Warteschlange sind bereits verarbeitet. Fügen Sie neue Dateien hinzu.';
                renderQueue();
                return;
            }

            running = true;
            queueMessage = '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Warteschlange läuft ...';
            }
            renderQueue();

            let successThisRun = 0;
            let skippedThisRun = 0;
            let errorsThisRun = 0;
            for (const item of queue) {
                if (item.status !== 'waiting') {
                    continue;
                }
                try {
                    const result = await uploadItem(item);
                    if (result?.skipped_duplicate) {
                        skippedThisRun += 1;
                    } else {
                        successThisRun += 1;
                    }
                } catch (error) {
                    item.status = 'error';
                    item.statusLabel = 'Fehler';
                    item.percent = 100;
                    item.detail = error instanceof Error ? error.message : 'Unbekannter Fehler.';
                    errorsThisRun += 1;
                    renderQueue();
                }
            }

            if (successThisRun > 0) {
                queueMessage = 'Dateien verarbeitet. Beispielinhalte werden aktualisiert ...';
                renderQueue();
                try {
                    const finalPayload = await finalizeQueue();
                    queueMessage = finalPayload.message || 'Warteschlange abgeschlossen. Öffnen Sie Textabschnitte, um die neuen Inhalte zu prüfen.';
                } catch (error) {
                    const message = error instanceof Error ? error.message : 'Unbekannter Fehler.';
                    queueMessage = `Dateien verarbeitet, aber die automatische Aktualisierung ist fehlgeschlagen: ${message}`;
                }
            } else if (skippedThisRun > 0) {
                queueMessage = `${skippedThisRun} Datei(en) übersprungen, weil sie bereits vorhanden sind.`;
            } else {
                queueMessage = `Keine Datei verarbeitet. ${errorsThisRun} Datei(en) mit Fehler.`;
            }

            running = false;
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = 'Weitere Warteschlange starten';
            }
            renderQueue();
        });

        renderQueue();
    });

    document.querySelectorAll('form[data-confirm], form[data-working-label]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) {
                return;
            }

            const confirmation = form.getAttribute('data-confirm');
            if (confirmation && !window.confirm(confirmation)) {
                event.preventDefault();
                return;
            }

            const submitter = event.submitter;
            const label = submitter?.getAttribute('data-working-label')
                || form.getAttribute('data-working-label');
            if (!label) {
                return;
            }

            form.setAttribute('aria-busy', 'true');
            form.classList.add('is-working');
            if (submitter && 'textContent' in submitter) {
                submitter.dataset.originalLabel = submitter.textContent || '';
                submitter.textContent = label;
            }
            if (submitter && !submitter.getAttribute('name')) {
                submitter.disabled = true;
            }

            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                if (button === submitter && button.getAttribute('name')) {
                    return;
                }
                button.disabled = true;
            });

            let status = form.querySelector('[data-working-status]');
            if (!status) {
                status = document.createElement('p');
                status.className = 'working-status';
                status.setAttribute('role', 'status');
                status.setAttribute('data-working-status', '');
                form.appendChild(status);
            }
            status.textContent = label;
        });
    });
})();
