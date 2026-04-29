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

        const updateProgress = () => {
            const done = queue.filter((item) => item.status === 'done' || item.status === 'error').length;
            const percent = queue.length > 0 ? Math.round((done / queue.length) * 100) : 0;
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
                if (!running && item.status === 'waiting') {
                    const remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'upload-remove';
                    remove.textContent = 'Entfernen';
                    remove.addEventListener('click', () => {
                        const index = queue.indexOf(item);
                        if (index >= 0) {
                            queue.splice(index, 1);
                            queueMessage = '';
                            renderQueue();
                        }
                    });
                    row.append(remove);
                }

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
                queue.push({
                    file,
                    status: 'waiting',
                    statusLabel: 'Wartet',
                    detail: 'bereit zur Verarbeitung',
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
            const response = await fetch(form.getAttribute('action') || window.location.href, {
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

        const uploadItem = async (item) => {
            item.status = 'processing';
            item.statusLabel = 'Verarbeitet';
            item.detail = 'Upload läuft, anschließend verarbeitet die KI die Datei.';
            renderQueue();

            const formData = new FormData();
            formData.append('csrf_token', csrf.value);
            formData.append('action', 'upload_documents');
            formData.append('async_upload', '1');
            formData.append('documents[]', item.file, item.file.name);
            const payload = await postFormData(formData);
            const result = Array.isArray(payload.uploadResults) ? payload.uploadResults[0] : null;
            if (!payload.ok || !result || !result.ok) {
                throw new Error(result?.error || payload.message || 'Datei konnte nicht verarbeitet werden.');
            }

            const chunkCount = Array.isArray(result.saved_chunks) ? result.saved_chunks.length : 0;
            item.status = 'done';
            item.statusLabel = 'Fertig';
            item.detail = `${chunkCount} Textabschnitt(e) erzeugt`;
            renderQueue();
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
                queueMessage = 'Alle Dateien in dieser Warteschlange sind bereits verarbeitet. Fügen Sie neue Dateien hinzu.';
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
            let errorsThisRun = 0;
            for (const item of queue) {
                if (item.status !== 'waiting') {
                    continue;
                }
                try {
                    await uploadItem(item);
                    successThisRun += 1;
                } catch (error) {
                    item.status = 'error';
                    item.statusLabel = 'Fehler';
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
