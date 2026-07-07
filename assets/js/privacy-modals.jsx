(function () {
    const Icon = window.BeratungsassistentIcon;
    const { useEffect } = React;

    const ModalShell = ({ children, maxWidth = "max-w-lg", onClose }) => {
        useEffect(() => {
            const handleKeyDown = (event) => {
                if (event.key === "Escape") {
                    onClose();
                }
            };

            document.addEventListener("keydown", handleKeyDown);
            return () => document.removeEventListener("keydown", handleKeyDown);
        }, [onClose]);

        return (
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60" onClick={onClose}>
                <div className={`bg-white rounded-3xl shadow-2xl ${maxWidth} w-full max-h-[85vh] overflow-y-auto`} onClick={e => e.stopPropagation()}>
                    {children}
                </div>
            </div>
        );
    };

    const ModalCloseButton = ({ onClose }) => (
        <button onClick={onClose} aria-label="Schließen"
            className="text-slate-400 hover:text-slate-700 text-xl font-bold w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 transition-all">{'\u2715'}</button>
    );

    const PrivacyModal = ({ config, onClose }) => (
        <ModalShell onClose={onClose}>
            <div className="sticky top-0 bg-white rounded-t-3xl border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="bg-[#e50046] p-2 rounded-xl text-white"><Icon name="shield" className="w-5 h-5" /></div>
                    <h2 className="font-bold text-[#0a192f] text-lg">Datenschutz</h2>
                </div>
                <ModalCloseButton onClose={onClose} />
            </div>
            <div className="px-6 py-5 space-y-5 text-sm text-slate-700">
                <section>
                    <h3 className="font-bold text-[#0a192f] mb-1">Wofür ist dieser Assistent gedacht?</h3>
                    <p>Der <strong>{config?.title || "Beratungsassistent"}</strong> beantwortet fachliche Fragen auf Basis der geladenen Wissensbasis.</p>
                </section>
                <section>
                    <h3 className="font-bold text-[#0a192f] mb-1">Was sollten Sie nicht eingeben?</h3>
                    <p className="bg-amber-50 border border-amber-200 rounded-xl p-3 text-amber-800">
                        Bitte geben Sie keine personenbezogenen Daten ein, insbesondere keine Namen, Einzelfälle oder vertraulichen Informationen.
                    </p>
                </section>
                <section>
                    <h3 className="font-bold text-[#0a192f] mb-1">Welche Daten werden verarbeitet?</h3>
                    <ul className="list-disc list-inside space-y-1">
                        <li>Ihre aktuelle Frage.</li>
                        <li>Der Chatverlauf der laufenden Sitzung, damit Anschlussfragen verständlich bleiben.</li>
                        <li>Passende Inhalte aus der geladenen Wissensbasis.</li>
                    </ul>
                </section>
                <section>
                    <h3 className="font-bold text-[#0a192f] mb-1">Wie entstehen Antworten?</h3>
                    <p>Die Serverkomponente ergänzt relevante Inhalte aus der Wissensbasis und ruft den konfigurierten Modellanbieter auf. Je nach Installation kann das ein externer Dienst oder ein lokales KI-System sein.</p>
                </section>
                <section>
                    <h3 className="font-bold text-[#0a192f] mb-1">Wird der Chat dauerhaft gespeichert?</h3>
                    <p>Diese Oberfläche speichert den Chatverlauf nicht dauerhaft. Technische Server- oder Hoster-Protokolle können abhängig von der jeweiligen Installation entstehen.</p>
                </section>
            </div>
            <div className="px-6 pb-5">
                <button onClick={onClose}
                    className="w-full py-3 bg-[#0a192f] text-white rounded-2xl font-bold hover:opacity-90 transition-all">
                    Verstanden
                </button>
            </div>
        </ModalShell>
    );

    const DSBModal = ({ onClose }) => (
        <ModalShell onClose={onClose} maxWidth="max-w-2xl">
            <div className="sticky top-0 bg-white rounded-t-3xl border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <div className="bg-[#0a192f] p-2 rounded-xl text-white"><Icon name="info" className="w-5 h-5" /></div>
                    <div>
                        <h2 className="font-bold text-[#0a192f] text-lg leading-none">Technische Hinweise</h2>
                        <p className="text-xs text-slate-400 mt-0.5">Verständliche Übersicht für Datenschutz und IT</p>
                    </div>
                </div>
                <ModalCloseButton onClose={onClose} />
            </div>
            <div className="px-6 py-5 space-y-6 text-sm text-slate-700">
                <section className="bg-slate-50 rounded-2xl p-4 border border-slate-200">
                    <h3 className="font-bold text-[#0a192f] text-base mb-2">1. Zweck</h3>
                    <p>Die Anwendung unterstützt fachliche Fragen zu einer geladenen Wissensbasis. Personenbezogene Eingaben sollen nicht verarbeitet werden.</p>
                </section>
                <section>
                    <h3 className="font-bold text-[#0a192f] text-base mb-2">2. Datenfluss</h3>
                    <div className="bg-[#0a192f] text-slate-300 rounded-xl p-4 text-xs space-y-2">
                        <p><strong>Browser:</strong> sendet die Frage an die Serverkomponente.</p>
                        <p><strong>Server:</strong> ergänzt relevante Inhalte aus der Wissensbasis.</p>
                        <p><strong>Modellanbieter:</strong> erzeugt aus Frage und Kontext eine Antwort.</p>
                        <p><strong>Browser:</strong> zeigt die Antwort in der laufenden Sitzung an.</p>
                    </div>
                </section>
                <section>
                    <h3 className="font-bold text-[#0a192f] text-base mb-2">3. Technische Schutzlinie</h3>
                    <div className="space-y-2">
                        {[
                            ["Transportverschlüsselung", "Die produktive Nutzung sollte ausschließlich über HTTPS erfolgen."],
                            ["API-Schlüssel", "Schlüssel werden serverseitig verwendet und nicht an den Browser ausgegeben."],
                            ["Wissensbasis", "Antworten werden mit passenden Inhalten aus den geladenen Dokumenten vorbereitet."],
                            ["Keine Tracking-Funktion", "Die Anwendung bringt keine eigene Analyse- oder Werbeeinbindung mit."],
                            ["Provider-neutral", "Der konkrete Modellanbieter hängt von der Installation und Admin-Konfiguration ab."],
                        ].map(([title, desc]) => (
                            <div key={title} className="flex gap-3 bg-slate-50 rounded-xl p-3 border border-slate-100">
                                <div className="text-[#e50046] mt-0.5 shrink-0"><Icon name="shield" className="w-4 h-4" /></div>
                                <div><span className="font-bold text-slate-800">{title}:</span> <span className="text-slate-600">{desc}</span></div>
                            </div>
                        ))}
                    </div>
                </section>
                <section className="bg-amber-50 border border-amber-200 rounded-2xl p-4">
                    <h3 className="font-bold text-amber-800 text-base mb-1">Hinweis für den dienstlichen Einsatz</h3>
                    <p className="text-amber-700 text-xs">Vor dem produktiven Einsatz sollten Betrieb, Hosting, Modellanbieter, Protokollierung und Datenschutzdokumentation installationsbezogen geprüft werden.</p>
                </section>
            </div>
            <div className="px-6 pb-5">
                <button onClick={onClose}
                    className="w-full py-3 bg-[#0a192f] text-white rounded-2xl font-bold hover:opacity-90 transition-all">
                    Schließen
                </button>
            </div>
        </ModalShell>
    );

    window.BeratungsassistentPrivacyModal = PrivacyModal;
    window.BeratungsassistentDsbModal = DSBModal;
})();
