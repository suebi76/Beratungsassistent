(function () {
    const { useEffect, useRef, useState } = React;
    const {
        LoadingState,
        ConfigErrorState,
        NotConfiguredState,
        AppHeader,
        AppFooter,
    } = window.BeratungsassistentAppShell;
    const ChatView = window.BeratungsassistentChatView;
    const TemplatesView = window.BeratungsassistentTemplatesView;
    const PrivacyModal = window.BeratungsassistentPrivacyModal;
    const DSBModal = window.BeratungsassistentDsbModal;

    function App() {
        const [config, setConfig] = useState(null);
        const [configError, setConfigError] = useState(null);
        const [loadingConfig, setLoadingConfig] = useState(true);
        const [messages, setMessages] = useState([]);
        const [input, setInput] = useState("");
        const [isLoading, setIsLoading] = useState(false);
        const [isStreaming, setIsStreaming] = useState(false);
        const [currentView, setCurrentView] = useState("chat");
        const [copiedIndex, setCopiedIndex] = useState(null);
        const [error, setError] = useState(null);
        const [showPrivacy, setShowPrivacy] = useState(false);
        const [showDSB, setShowDSB] = useState(false);
        const scrollRef = useRef(null);
        const autoScroll = useRef(true);

        useEffect(() => {
            fetch("project.php")
                .then((response) => {
                    if (!response.ok) throw new Error("Konfiguration konnte nicht geladen werden.");
                    return response.json();
                })
                .then((data) => setConfig(data))
                .catch((err) => setConfigError(err.message || "Konfiguration konnte nicht geladen werden."))
                .finally(() => setLoadingConfig(false));
        }, []);

        useEffect(() => {
            if (autoScroll.current && scrollRef.current) {
                scrollRef.current.scrollTop = scrollRef.current.scrollHeight;
            }
        }, [messages, isLoading]);

        const quickQuestions = Array.isArray(config?.frontend?.quick_questions)
            ? config.frontend.quick_questions
            : [];
        const templates = Array.isArray(config?.frontend?.templates)
            ? config.frontend.templates
            : [];

        const handleScroll = () => {
            const element = scrollRef.current;
            if (!element) return;
            autoScroll.current = element.scrollHeight - element.scrollTop - element.clientHeight < 80;
        };

        const clearChat = () => {
            setMessages([]);
            setInput("");
            setError(null);
            autoScroll.current = true;
        };

        const downloadAsPdf = (text, filenameBase) => {
            window.BeratungsassistentPdf.downloadAsPdf(text, filenameBase, config);
        };

        const sendQuery = async (queryText) => {
            const trimmed = String(queryText || "").trim();
            if (!trimmed || isLoading || isStreaming || !config?.configured) return;

            autoScroll.current = true;
            setError(null);
            const history = messages.slice();
            setMessages([...history, { role: "user", text: trimmed }, { role: "assistant", text: "" }]);
            setInput("");
            setIsLoading(true);
            setIsStreaming(true);

            try {
                const response = await fetch("proxy.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        messages: history.map((entry) => ({ role: entry.role, text: entry.text })),
                        query: trimmed,
                    }),
                });
                if (!response.ok || !response.body) {
                    throw new Error("Antwort vom Server fehlgeschlagen.");
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = "";
                let fullText = "";

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split("\n");
                    buffer = lines.pop() || "";

                    for (const line of lines) {
                        if (!line.startsWith("data: ")) continue;
                        const payload = line.slice(6).trim();
                        if (!payload || payload === "[DONE]") continue;

                        let data;
                        try {
                            data = JSON.parse(payload);
                        } catch (parseError) {
                            continue;
                        }

                        if (data.error) {
                            throw new Error(data.error);
                        }

                        const chunk = data.candidates?.[0]?.content?.parts?.[0]?.text || "";
                        if (!chunk) continue;
                        fullText += chunk;
                        setMessages((current) => {
                            const updated = current.slice();
                            updated[updated.length - 1] = { role: "assistant", text: fullText };
                            return updated;
                        });
                    }
                }

                if (!fullText) {
                    setMessages((current) => current.slice(0, -1));
                    setError("Keine Antwort erhalten. Bitte erneut versuchen.");
                }
            } catch (err) {
                setMessages((current) => current.slice(0, -1));
                setError("Verbindungsfehler: " + (err.message || "Bitte versuchen Sie es später erneut."));
            } finally {
                setIsLoading(false);
                setIsStreaming(false);
            }
        };

        const handleSendMessage = (event) => {
            event?.preventDefault();
            if (!input.trim() || isLoading || isStreaming) return;
            sendQuery(input);
        };

        const applyTemplate = (prompt) => {
            setInput(prompt);
            setCurrentView("chat");
        };

        const copyMessage = (text, index) => {
            navigator.clipboard.writeText(text).then(() => {
                setCopiedIndex(index);
                setTimeout(() => setCopiedIndex(null), 2000);
            }).catch(() => {});
        };

        if (loadingConfig) {
            return <LoadingState />;
        }

        if (configError) {
            return <ConfigErrorState error={configError} />;
        }

        if (!config?.configured) {
            return <NotConfiguredState />;
        }

        return (
            <React.Fragment>
                <div className="flex flex-col h-screen overflow-hidden">
                    <AppHeader
                        config={config}
                        currentView={currentView}
                        onClearChat={clearChat}
                        onToggleView={() => setCurrentView(currentView === "chat" ? "templates" : "chat")}
                    />

                    <main className="flex-1 overflow-hidden relative flex flex-col bg-slate-50">
                        {currentView === "chat" ? (
                            <ChatView
                                config={config}
                                copiedIndex={copiedIndex}
                                error={error}
                                input={input}
                                isLoading={isLoading}
                                isStreaming={isStreaming}
                                messages={messages}
                                quickQuestions={quickQuestions}
                                scrollRef={scrollRef}
                                onCopyMessage={copyMessage}
                                onDownloadPdf={(text) => downloadAsPdf(text, (config?.title || "Beratungsassistent") + "-antwort")}
                                onInputChange={setInput}
                                onQuickQuestion={setInput}
                                onScroll={handleScroll}
                                onSubmit={handleSendMessage}
                            />
                        ) : (
                            <TemplatesView
                                quickQuestions={quickQuestions}
                                templates={templates}
                                onApplyTemplate={applyTemplate}
                            />
                        )}
                    </main>

                    <AppFooter
                        onPrivacyClick={() => setShowPrivacy(true)}
                        onDsbClick={() => setShowDSB(true)}
                    />
                </div>

                {showPrivacy && <PrivacyModal config={config} onClose={() => setShowPrivacy(false)} />}
                {showDSB && <DSBModal onClose={() => setShowDSB(false)} />}
            </React.Fragment>
        );
    }

    ReactDOM.createRoot(document.getElementById("root")).render(<App />);
})();
