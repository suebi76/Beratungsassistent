(function () {
    const Icon = window.BeratungsassistentIcon;

    const LoadingState = () => (
        <div className="flex items-center justify-center h-screen bg-slate-100">
            <div className="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 max-w-md w-full text-center">
                <Icon name="refresh" className="w-8 h-8 text-[#e50046] animate-spin mx-auto mb-4" />
                <h2 className="font-bold text-[#0a192f] text-lg mb-2">Konfiguration wird geladen</h2>
                <p className="text-sm text-slate-500">Profil, Wissensbasis und Frontend-Konfiguration werden geladen.</p>
            </div>
        </div>
    );

    const ConfigErrorState = ({ error }) => (
        <div className="flex items-center justify-center h-screen bg-slate-100">
            <div className="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 max-w-md w-full text-center">
                <div className="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-red-500">
                    <Icon name="alert" className="w-6 h-6" />
                </div>
                <h2 className="font-bold text-[#0a192f] text-lg mb-2">Konfiguration nicht verfuegbar</h2>
                <p className="text-sm text-slate-500 mb-4">{error}</p>
                <a href="admin.php" className="inline-block px-6 py-3 bg-[#e50046] text-white rounded-2xl font-bold hover:opacity-90 transition-all text-sm">Zur Admin-Konfiguration</a>
            </div>
        </div>
    );

    const NotConfiguredState = () => (
        <div className="flex items-center justify-center h-screen bg-slate-100">
            <div className="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 max-w-md w-full text-center">
                <div className="w-12 h-12 bg-[#0a192f] rounded-2xl flex items-center justify-center mx-auto mb-4 text-[#e50046]">
                    <Icon name="settings" className="w-6 h-6" />
                </div>
                <h2 className="font-bold text-[#0a192f] text-lg mb-2">Noch nicht eingerichtet</h2>
                <p className="text-sm text-slate-500 mb-4">Passwort, API-Key, Projektprofil und Wissensbasis muessen im Admin-Bereich eingerichtet werden.</p>
                <a href="admin.php" className="inline-block px-6 py-3 bg-[#e50046] text-white rounded-2xl font-bold hover:opacity-90 transition-all text-sm">Admin-Wizard oeffnen</a>
            </div>
        </div>
    );

    const AppHeader = ({
        config,
        currentView,
        isSchifT,
        onClearChat,
        onToggleSchifT,
        onToggleView,
    }) => (
        <header className="bg-[#0a192f] text-white p-4 shadow-xl flex justify-between items-center z-20 shrink-0">
            <div className="flex items-center gap-3">
                <div className="bg-white/10 p-2 rounded-lg border border-white/10 text-[#e50046]">
                    <Icon name="bot" className="w-6 h-6" />
                </div>
                <div>
                    <h1 className="font-bold text-base leading-none">{config?.title || "Beratungsassistent"}</h1>
                    <p className="text-[9px] text-slate-400 mt-0.5 uppercase tracking-widest">
                        {config?.subtitle || config?.scope_summary || "Dokumentgestuetzte Beratung mit RAG"}
                    </p>
                </div>
            </div>
            <div className="flex items-center gap-2">
                {config?.safety?.pii_notice && (
                    <span className="hidden md:flex items-center gap-1.5 text-[9px] text-slate-500 uppercase tracking-widest">
                        <Icon name="shield" className="w-3 h-3 text-[#e50046]" /> Keine personenbezogenen Daten
                    </span>
                )}
                {isSchifT !== undefined && config?.frontend?.schift_toggle !== false && (
                    <button
                        onClick={onToggleSchifT}
                        title={isSchifT ? "Modus: SchifT - Klicken fuer Standard" : "Standard-Modus - Klicken fuer SchifT"}
                        className={`flex items-center gap-1.5 px-3 py-2 rounded-md text-xs font-bold transition-all border ${
                            isSchifT
                                ? "bg-amber-500 border-amber-400 text-white"
                                : "bg-white/10 border-white/10 text-slate-300 hover:text-white"
                        }`}
                    >
                        <Icon name={isSchifT ? "toggle" : "toggleOff"} className="w-4 h-4" />
                        <span className="hidden sm:inline">{isSchifT ? "SchifT" : "Standard"}</span>
                    </button>
                )}
                <button
                    onClick={onClearChat}
                    title="Neues Gespraech"
                    className="flex items-center gap-1.5 px-3 py-2 rounded-md text-xs font-bold transition-all border bg-white/10 border-white/10 text-slate-300 hover:text-white"
                >
                    <Icon name="plus" className="w-4 h-4" />
                    <span className="hidden sm:inline">Neu</span>
                </button>
                <button
                    onClick={onToggleView}
                    className="bg-[#e50046] hover:opacity-90 px-4 py-2 rounded-md flex items-center gap-2 transition-all text-sm font-bold shadow-lg"
                >
                    <Icon name={currentView === "chat" ? "settings" : "message"} className="w-4 h-4" />
                    {currentView === "chat" ? "Vorlagen" : "Chat"}
                </button>
                <a href="admin.php" title="Admin" className="flex items-center gap-1.5 px-3 py-2 rounded-md text-xs font-bold transition-all border bg-white/10 border-white/10 text-slate-300 hover:text-white no-underline">
                    <Icon name="settings" className="w-4 h-4" />
                    <span className="hidden md:inline">Admin</span>
                </a>
            </div>
        </header>
    );

    const AppFooter = ({ onPrivacyClick, onDsbClick }) => (
        <footer className="bg-[#0a192f] text-slate-400 py-3 px-4 border-t border-white/5 flex flex-wrap justify-center gap-4 text-[9px] font-bold uppercase tracking-widest shrink-0">
            <span className="flex items-center gap-2">
                <Icon name="shield" className="w-3 h-3 text-[#e50046]" />
                <span>RAG-gestuetzter Beratungsassistent</span>
            </span>
            <button
                onClick={onPrivacyClick}
                className="flex items-center gap-1.5 text-slate-400 hover:text-white transition-colors"
            >
                <Icon name="shield" className="w-3 h-3 text-[#e50046]" /> Datenschutz
            </button>
            <button
                onClick={onDsbClick}
                className="flex items-center gap-1.5 text-slate-400 hover:text-white transition-colors"
            >
                <Icon name="info" className="w-3 h-3 text-[#e50046]" /> DSB-Dokumentation
            </button>
        </footer>
    );

    window.BeratungsassistentAppShell = {
        LoadingState,
        ConfigErrorState,
        NotConfiguredState,
        AppHeader,
        AppFooter,
    };
})();
