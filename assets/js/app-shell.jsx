(function () {
    const Icon = window.BeratungsassistentIcon;
    const REPOSITORY_URL = "https://github.com/suebi76/Beratungsassistent";
    const SOFTWARE_LICENSE_URL = "LICENSE";
    const CONTENT_LICENSE_URL = "CONTENT-LICENSE.md";
    const technicalTermPattern = new RegExp("\\b" + ["R", "A", "G"].join("") + "\\b", "gi");

    const headerSubtitle = (config) => {
        const value = config?.topic || config?.scope_summary || "Geladene Wissensbasis";
        return String(value).replace(technicalTermPattern, "Wissensbasis");
    };

    const LoadingState = () => (
        <div className="flex items-center justify-center h-screen bg-slate-100" aria-live="polite">
            <div className="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 max-w-md w-full text-center">
                <Icon name="refresh" className="w-8 h-8 text-[#e50046] animate-spin mx-auto mb-4" />
                <h2 className="font-bold text-[#0a192f] text-lg mb-2">Oberfläche wird vorbereitet</h2>
                <p className="text-sm text-slate-500">Profil und Wissensbasis werden geladen.</p>
            </div>
        </div>
    );

    const ConfigErrorState = ({ error }) => (
        <div className="flex items-center justify-center h-screen bg-slate-100" role="alert">
            <div className="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 max-w-md w-full text-center">
                <div className="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-red-500">
                    <Icon name="alert" className="w-6 h-6" />
                </div>
                <h2 className="font-bold text-[#0a192f] text-lg mb-2">Die Oberfläche ist momentan nicht verfügbar</h2>
                <p className="text-sm text-slate-500 mb-4">{error}</p>
                <a href="admin.php" className="inline-block px-6 py-3 bg-[#e50046] text-white rounded-2xl font-bold hover:opacity-90 transition-all text-sm">Admin öffnen</a>
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
                <p className="text-sm text-slate-500 mb-4">Der Assistent braucht zuerst ein Admin-Passwort, einen Modellanbieter, ein Projektprofil und eine Wissensbasis.</p>
                <a href="admin.php" className="inline-block px-6 py-3 bg-[#e50046] text-white rounded-2xl font-bold hover:opacity-90 transition-all text-sm">Einrichtung öffnen</a>
            </div>
        </div>
    );

    const AppHeader = ({
        config,
        currentView,
        onClearChat,
        onToggleView,
    }) => (
        <header className="bg-[#0a192f] text-white px-4 py-3 md:px-5 md:py-4 shadow-xl flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center z-20 shrink-0">
            <div className="flex items-center gap-3 min-w-0">
                <div className="bg-white/10 p-2 rounded-lg border border-white/10 text-[#e50046] shrink-0">
                    <Icon name="bot" className="w-6 h-6" />
                </div>
                <div className="min-w-0">
                    <h1 className="font-bold text-base leading-none truncate">{config?.title || "Beratungsassistent"}</h1>
                    <p className="text-[11px] text-slate-300 mt-1 truncate">
                        {headerSubtitle(config)}
                    </p>
                </div>
            </div>
            <nav className="flex items-center gap-2" aria-label="Hauptaktionen">
                <button
                    onClick={onClearChat}
                    title="Neues Gespräch"
                    className="flex items-center gap-1.5 px-3 py-2 rounded-md text-xs font-bold transition-all border bg-white/10 border-white/10 text-slate-200 hover:text-white"
                >
                    <Icon name="plus" className="w-4 h-4" />
                    <span>Neu</span>
                </button>
                <button
                    onClick={onToggleView}
                    className={`px-4 py-2 rounded-md flex items-center gap-2 transition-all text-sm font-bold shadow-lg ${
                        currentView === "chat"
                            ? "bg-[#e50046] text-white hover:opacity-90"
                            : "bg-white text-[#0a192f] hover:bg-slate-100"
                    }`}
                >
                    <Icon name={currentView === "chat" ? "layers" : "message"} className="w-4 h-4" />
                    {currentView === "chat" ? "Vorlagen" : "Chat"}
                </button>
            </nav>
        </header>
    );

    const AppFooter = ({ onPrivacyClick, onDsbClick }) => (
        <footer className="bg-[#0a192f] text-slate-300 py-3 px-4 border-t border-white/5 flex flex-wrap justify-center gap-x-4 gap-y-2 text-[11px] shrink-0">
            <span>© 2026 Steffen Schwabe</span>
            <a className="hover:text-white transition-colors" href={SOFTWARE_LICENSE_URL} target="_blank" rel="noreferrer">Software: MIT</a>
            <a className="hover:text-white transition-colors" href={CONTENT_LICENSE_URL} target="_blank" rel="noreferrer">Dokumentation und Inhalte: CC BY-SA 4.0</a>
            <a className="hover:text-white transition-colors" href={REPOSITORY_URL} target="_blank" rel="noreferrer">GitHub</a>
            <button
                onClick={onPrivacyClick}
                className="hover:text-white transition-colors"
            >
                Datenschutz
            </button>
            <button
                onClick={onDsbClick}
                className="hover:text-white transition-colors"
            >
                Technische Hinweise
            </button>
            <a href="admin.php" className="hover:text-white transition-colors no-underline">Admin</a>
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
