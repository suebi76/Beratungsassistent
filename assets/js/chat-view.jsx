(function () {
    const { useState } = React;
    const Icon = window.BeratungsassistentIcon;
    const { renderMarkdown } = window.BeratungsassistentMarkdown;
    const PRIVACY_NOTICE = "Bitte keine personenbezogenen Daten eingeben.";

    const safetyNotice = () => PRIVACY_NOTICE;

    const ChatInput = ({
        config,
        input,
        inputId = "chat-input",
        isEmbedded = false,
        isLoading,
        isStreaming,
        onInputChange,
        onSubmit,
    }) => (
        <div className={isEmbedded ? "w-full" : "p-4 bg-white border-t border-slate-200 shadow-sm shrink-0"}>
            <form onSubmit={onSubmit} className="max-w-4xl mx-auto flex gap-3">
                <label htmlFor={inputId} className="sr-only">Frage eingeben</label>
                <input
                    id={inputId}
                    type="text"
                    value={input}
                    onChange={(event) => onInputChange(event.target.value)}
                    placeholder="Ihre Frage eingeben ..."
                    aria-label="Frage eingeben"
                    className="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-5 py-3 text-sm focus:ring-2 focus:ring-[#e50046] outline-none"
                    disabled={isLoading || isStreaming}
                    autoComplete="off"
                />
                <button
                    type="submit"
                    disabled={isLoading || isStreaming || !input.trim()}
                    aria-label="Nachricht senden"
                    className="bg-[#e50046] text-white p-3 rounded-xl hover:opacity-90 disabled:opacity-30 transition-all flex items-center justify-center min-w-[52px]"
                >
                    <Icon name="send" className="w-5 h-5" />
                </button>
            </form>
            <p className="max-w-4xl mx-auto mt-2 flex items-center gap-1.5 text-[11px] text-amber-700">
                <Icon name="shield" className="w-3.5 h-3.5 shrink-0" />
                <span>{safetyNotice(config)}</span>
            </p>
        </div>
    );

    const OrientationCard = () => (
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 text-left">
            {[
                ["Antworten", "aus der geladenen Wissensbasis"],
                ["Schnellfragen", "für den Einstieg"],
                ["Vorlagen", "für längere Anliegen"],
            ].map(([title, description]) => (
                <div key={title} className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p className="text-xs font-bold text-[#0a192f]">{title}</p>
                    <p className="text-xs text-slate-500 mt-1">{description}</p>
                </div>
            ))}
        </div>
    );

    const QuickQuestions = ({ quickQuestions, onQuickQuestion }) => {
        const [expanded, setExpanded] = useState(false);
        const questions = Array.isArray(quickQuestions) ? quickQuestions : [];
        const visibleQuestions = expanded ? questions : questions.slice(0, 4);

        if (questions.length === 0) {
            return null;
        }

        return (
            <section className="text-left">
                <div className="flex items-center justify-between gap-3 mb-3">
                    <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">Schnellfragen</p>
                    {questions.length > 4 && (
                        <button
                            type="button"
                            onClick={() => setExpanded(!expanded)}
                            className="text-xs font-bold text-[#e50046] hover:text-[#0a192f] transition-colors"
                        >
                            {expanded ? "Weniger Fragen" : "Mehr Fragen"}
                        </button>
                    )}
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    {visibleQuestions.map((question, index) => (
                        <button
                            key={index}
                            type="button"
                            onClick={() => onQuickQuestion(question)}
                            className="text-left text-xs p-3 bg-white hover:bg-slate-50 border border-slate-200 hover:border-[#e50046] hover:text-[#e50046] rounded-xl transition-all font-medium text-slate-600 flex items-center gap-2.5"
                        >
                            <Icon name="zap" className="w-3.5 h-3.5 shrink-0 text-[#e50046]" />
                            <span>{question}</span>
                        </button>
                    ))}
                </div>
            </section>
        );
    };

    const EmptyChatState = ({
        config,
        input,
        isLoading,
        isStreaming,
        quickQuestions,
        onInputChange,
        onQuickQuestion,
        onSubmit,
    }) => (
        <div className="min-h-full flex items-center justify-center px-4 py-8">
            <div className="bg-white p-5 md:p-7 rounded-3xl shadow-sm border border-slate-200 max-w-3xl w-full text-center space-y-6">
                <div className="mx-auto w-14 h-14 bg-[#0a192f] rounded-2xl flex items-center justify-center text-[#e50046]">
                    <Icon name="bot" className="w-7 h-7" />
                </div>
                <div>
                    <h2 className="text-slate-900 font-bold text-2xl md:text-3xl">Wobei kann ich helfen?</h2>
                </div>
                <OrientationCard />
                <ChatInput
                    config={config}
                    input={input}
                    inputId="chat-input-start"
                    isEmbedded={true}
                    isLoading={isLoading}
                    isStreaming={isStreaming}
                    onInputChange={onInputChange}
                    onSubmit={onSubmit}
                />
                <QuickQuestions quickQuestions={quickQuestions} onQuickQuestion={onQuickQuestion} />
            </div>
        </div>
    );

    const MessageBubble = ({
        config,
        copiedIndex,
        index,
        isLastStreamingMessage,
        message,
        onCopyMessage,
        onDownloadPdf,
    }) => (
        <div className={`flex ${message.role === "user" ? "justify-end" : "justify-start"}`}>
            <div className={`max-w-[85%] md:max-w-[75%] p-4 rounded-2xl shadow-sm border ${
                message.role === "user"
                    ? "bg-[#e50046] text-white border-[#e50046] rounded-tr-none"
                    : "bg-white text-slate-800 border-slate-200 rounded-tl-none"
            }`}>
                <div className={`flex items-center gap-2 mb-2 ${message.role === "user" ? "opacity-90 text-xs" : "text-[#0a192f] font-bold text-xs"}`}>
                    <Icon name={message.role === "user" ? "user" : "bot"} className="w-3 h-3" />
                    <span className="uppercase tracking-wider">
                        {message.role === "user" ? "Sie" : (config?.title || "Assistent")}
                    </span>
                </div>
                {message.role === "assistant" ? (
                    <div>
                        <div className="text-sm md" dangerouslySetInnerHTML={renderMarkdown(message.text || "")} />
                        {isLastStreamingMessage && (
                            <span className="streaming-cursor" />
                        )}
                        {!isLastStreamingMessage && message.text && (
                            <div className="flex items-center gap-1 mt-3 pt-2 border-t border-slate-100">
                                <button
                                    onClick={() => onCopyMessage(message.text, index)}
                                    title="Antwort kopieren"
                                    className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-all text-xs"
                                >
                                    {copiedIndex === index
                                        ? <React.Fragment><Icon name="checkmark" className="w-3.5 h-3.5 text-green-500" /><span className="text-green-600 font-medium">Kopiert</span></React.Fragment>
                                        : <React.Fragment><Icon name="clipboard" className="w-3.5 h-3.5" /><span>Kopieren</span></React.Fragment>
                                    }
                                </button>
                                <button
                                    onClick={() => onDownloadPdf(message.text)}
                                    title="Als PDF speichern"
                                    className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-all text-xs"
                                >
                                    <Icon name="fileDown" className="w-3.5 h-3.5" />
                                    <span>PDF</span>
                                </button>
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="text-sm whitespace-pre-wrap leading-relaxed">{message.text}</div>
                )}
            </div>
        </div>
    );

    const ChatView = ({
        config,
        copiedIndex,
        error,
        input,
        isLoading,
        isStreaming,
        messages,
        quickQuestions,
        scrollRef,
        onCopyMessage,
        onDownloadPdf,
        onInputChange,
        onQuickQuestion,
        onScroll,
        onSubmit,
    }) => (
        <React.Fragment>
            <div ref={scrollRef} onScroll={onScroll} className="flex-1 overflow-y-auto p-4 md:p-6 space-y-6">
                {messages.length === 0 && (
                    <EmptyChatState
                        config={config}
                        input={input}
                        isLoading={isLoading}
                        isStreaming={isStreaming}
                        quickQuestions={quickQuestions}
                        onInputChange={onInputChange}
                        onQuickQuestion={onQuickQuestion}
                        onSubmit={onSubmit}
                    />
                )}

                {messages.map((message, index) => (
                    <MessageBubble
                        key={index}
                        config={config}
                        copiedIndex={copiedIndex}
                        index={index}
                        isLastStreamingMessage={isStreaming && index === messages.length - 1}
                        message={message}
                        onCopyMessage={onCopyMessage}
                        onDownloadPdf={onDownloadPdf}
                    />
                ))}

                {isLoading && (
                    <div className="flex justify-start" aria-live="polite">
                        <div className="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-center gap-3">
                            <Icon name="refresh" className="w-4 h-4 text-[#e50046] animate-spin" />
                            <span className="text-xs text-slate-500 font-bold uppercase tracking-widest">Antwort wird vorbereitet ...</span>
                        </div>
                    </div>
                )}

                {error && (
                    <div className="flex justify-center p-4" role="alert">
                        <div className="bg-red-50 text-red-700 p-4 rounded-xl flex items-center gap-3 max-w-md border border-red-200">
                            <Icon name="alert" className="w-5 h-5 shrink-0" />
                            <span className="text-sm font-medium">{error}</span>
                        </div>
                    </div>
                )}
            </div>

            {messages.length > 0 && (
                <ChatInput
                    config={config}
                    input={input}
                    inputId="chat-input-followup"
                    isLoading={isLoading}
                    isStreaming={isStreaming}
                    onInputChange={onInputChange}
                    onSubmit={onSubmit}
                />
            )}
        </React.Fragment>
    );

    window.BeratungsassistentChatView = ChatView;
})();
