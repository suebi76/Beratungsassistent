(function () {
    const Icon = window.BeratungsassistentIcon;

    const PrivacyModal = ({ config, onClose }) => (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60" onClick={onClose}>
            <div className="bg-white rounded-3xl shadow-2xl max-w-lg w-full max-h-[85vh] overflow-y-auto" onClick={e => e.stopPropagation()}>
                <div className="sticky top-0 bg-white rounded-t-3xl border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="bg-[#e50046] p-2 rounded-xl text-white"><Icon name="shield" className="w-5 h-5" /></div>
                        <h2 className="font-bold text-[#0a192f] text-lg">Datenschutzhinweis</h2>
                    </div>
                    <button onClick={onClose} aria-label="Schließen"
                        className="text-slate-400 hover:text-slate-700 text-xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 transition-all">{'\u2715'}</button>
                </div>
                <div className="px-6 py-5 space-y-5 text-sm text-slate-700">
                    <section>
                        <h3 className="font-bold text-[#0a192f] mb-1">Was macht diese App?</h3>
                        <p>Der <strong>{config?.title || "Beratungsassistent"}</strong> ist ein konfigurierbarer Assistent mit dateibasierter Wissensbasis (RAG). Die Antworten werden von <strong>Google Gemini</strong> auf Basis der hinterlegten Wissensbasis erstellt. Anfragen mit personenbezogenen Daten werden aktiv abgewiesen.</p>
                    </section>
                    <section>
                        <h3 className="font-bold text-[#0a192f] mb-1">Welche Daten werden verarbeitet?</h3>
                        <ul className="list-disc list-inside space-y-1">
                            <li>Die <strong>Texteingaben</strong>, die Sie in das Chat-Feld tippen</li>
                            <li>Der <strong>Chatverlauf der laufenden Sitzung</strong> (wird als Kontext an die KI übergeben)</li>
                        </ul>
                        <p className="mt-2 text-slate-500 text-xs bg-amber-50 border border-amber-200 rounded-xl p-3">
                            <strong>Wichtig:</strong> Bitte geben Sie <strong>keine personenbezogenen Daten</strong> ein. Die KI ist angewiesen, solche Eingaben zu erkennen und abzulehnen.
                        </p>
                    </section>
                    <section>
                        <h3 className="font-bold text-[#0a192f] mb-1">Wohin gehen meine Eingaben?</h3>
                        <p>Ihre Texteingabe wird verschlüsselt an den <strong>Webserver</strong> übertragen. Dort nimmt <code>proxy.php</code> die Anfrage entgegen und leitet sie an die <strong>Google Gemini API</strong> weiter. Der API-Schlüssel ist serverseitig gespeichert und per Zugriffsregel gesperrt.</p>
                        <p className="mt-1">Google LLC ist ein US-amerikanisches Unternehmen. Die Übertragung erfolgt auf Grundlage der EU-Standardvertragsklauseln (Art. 46 DSGVO).</p>
                    </section>
                    <section>
                        <h3 className="font-bold text-[#0a192f] mb-1">Werden Daten gespeichert?</h3>
                        <p><strong>Nein.</strong> Weder der Webserver noch diese Anwendung speichern Ihre Eingaben dauerhaft. Der Verlauf existiert nur im Arbeitsspeicher Ihres Browsers. Es werden <strong>keine Cookies</strong> gesetzt und <strong>kein Tracking</strong> durchgeführt.</p>
                    </section>
                    <section>
                        <h3 className="font-bold text-[#0a192f] mb-1">Externe Verbindungen</h3>
                        <p>Alle Skripte sind lokal auf dem Server gespeichert. Beim Laden der Seite wird <strong>keine Verbindung zu externen Diensten</strong> hergestellt.</p>
                    </section>
                </div>
                <div className="px-6 pb-5">
                    <button onClick={onClose}
                        className="w-full py-3 bg-[#0a192f] text-white rounded-2xl font-bold hover:opacity-90 transition-all">
                        Verstanden
                    </button>
                </div>
            </div>
        </div>
    );

    const DSBModal = ({ onClose }) => (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60" onClick={onClose}>
            <div className="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" onClick={e => e.stopPropagation()}>
                <div className="sticky top-0 bg-white rounded-t-3xl border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="bg-[#0a192f] p-2 rounded-xl text-white"><Icon name="book" className="w-5 h-5" /></div>
                        <div>
                            <h2 className="font-bold text-[#0a192f] text-lg leading-none">Technische Datenschutzdokumentation</h2>
                            <p className="text-xs text-slate-400 mt-0.5">Für Datenschutzbeauftragte</p>
                        </div>
                    </div>
                    <button onClick={onClose} aria-label="Schließen"
                        className="text-slate-400 hover:text-slate-700 text-xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 transition-all">{'\u2715'}</button>
                </div>
                <div className="px-6 py-5 space-y-6 text-sm text-slate-700">
                    <section className="bg-slate-50 rounded-2xl p-4 border border-slate-200">
                        <h3 className="font-bold text-[#0a192f] text-base mb-2">1. Zweck der Verarbeitung</h3>
                        <p>Die Anwendung dient als <strong>konfigurierbarer Beratungsassistent</strong> mit dateibasierter Wissensbasis. Eine Verarbeitung personenbezogener Daten ist durch die Systemanweisung explizit untersagt.</p>
                    </section>
                    <section>
                        <h3 className="font-bold text-[#0a192f] text-base mb-2">2. Datenfluss</h3>
                        <div className="bg-[#0a192f] text-slate-300 rounded-xl p-4 font-mono text-xs space-y-1">
                            <p>Browser (Nutzer)</p>
                            <p className="pl-4 text-[#e50046]">{'\u2192'} HTTPS POST {'\u2192'} proxy.php (Webserver)</p>
                            <p className="pl-8 text-slate-500">lädt den API-Schlüssel aus config/config.php (per .htaccess gesperrt)</p>
                            <p className="pl-8 text-slate-500">durchsucht rag/chunks/*.md nach relevanten Wissens-Chunks</p>
                            <p className="pl-4 text-[#e50046]">{'\u2192'} HTTPS POST {'\u2192'} generativelanguage.googleapis.com</p>
                            <p className="pl-4 text-[#e50046]">{'\u2190'} SSE-Stream zurück an Browser</p>
                            <p className="pl-4 text-slate-500">{'\u2717'} Keine Protokollierung, keine dauerhafte Speicherung</p>
                        </div>
                    </section>
                    <section>
                        <h3 className="font-bold text-[#0a192f] text-base mb-2">3. Technisch-organisatorische Maßnahmen</h3>
                        <div className="space-y-2">
                            {[
                                ["Transportverschlüsselung", "TLS/HTTPS für alle Datenübertragungen"],
                                ["Schutz des API-Schlüssels", "Schlüssel in config/config.php, per .htaccess gesperrt"],
                                ["RAG-Wissensdatenbank", "Antworten basieren auf geprüften Wissens-Chunks"],
                                ["Keine Datenpersistenz", "Keine Datenbank, keine Logdateien"],
                                ["Keine Tracking-Technologien", "Keine Cookies, kein Analytics"],
                                ["Lokale Bibliotheken", "Alle JS-Abhängigkeiten lokal gehostet"],
                                ["Ablehnung personenbezogener Daten", "Die Systemanweisung verbietet die Verarbeitung personenbezogener Daten"],
                            ].map(([title, desc]) => (
                                <div key={title} className="flex gap-3 bg-slate-50 rounded-xl p-3 border border-slate-100">
                                    <div className="text-[#e50046] mt-0.5 shrink-0"><Icon name="shield" className="w-4 h-4" /></div>
                                    <div><span className="font-bold text-slate-800">{title}:</span> <span className="text-slate-600">{desc}</span></div>
                                </div>
                            ))}
                        </div>
                    </section>
                    <section>
                        <h3 className="font-bold text-[#0a192f] text-base mb-2">4. Speicherdauer</h3>
                        <ul className="list-disc list-inside space-y-1">
                            <li><strong>Serverseitig:</strong> Keine Speicherung (Proxy-Betrieb).</li>
                            <li><strong>Clientseitig:</strong> Chatverlauf im React-State. Kein localStorage, keine Cookies.</li>
                            <li><strong>Google Gemini:</strong> API-Nutzung ohne Opt-in zur Modellverbesserung.</li>
                        </ul>
                    </section>
                    <section className="bg-amber-50 border border-amber-200 rounded-2xl p-4">
                        <h3 className="font-bold text-amber-800 text-base mb-1">Empfehlung für den dienstlichen Einsatz</h3>
                        <p className="text-amber-700 text-xs">Vor dem Einsatz sollte der zuständige <strong>Datenschutzbeauftragte</strong> informiert werden. Prüfung einer DSFA nach Art. 35 DSGVO empfohlen.</p>
                    </section>
                </div>
                <div className="px-6 pb-5">
                    <button onClick={onClose}
                        className="w-full py-3 bg-[#0a192f] text-white rounded-2xl font-bold hover:opacity-90 transition-all">
                        Schließen
                    </button>
                </div>
            </div>
        </div>
    );

    window.BeratungsassistentPrivacyModal = PrivacyModal;
    window.BeratungsassistentDsbModal = DSBModal;
})();
