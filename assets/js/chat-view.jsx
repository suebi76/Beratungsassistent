(function () {
    const Icon = window.BeratungsassistentIcon;
    const { renderMarkdown } = window.BeratungsassistentMarkdown;

    const EmptyChatState = ({ config, isSchifT, quickQuestions, onQuickQuestion }) => (
        <div className="flex flex-col items-center justify-center h-full text-center px-4 py-8">
            <div className="bg-white p-7 rounded-3xl shadow-sm border border-slate-200 max-w-lg w-full">
                <div className="w-16 h-16 bg-[#0a192f] rounded-2xl flex items-center justify-center mx-auto mb-5 text-[#e50046]">
                    <Icon name="bot" className="w-8 h-8" />
                </div>
                <h2 className="text-slate-800 font-bold text-xl mb-1">{config?.title || "Beratungsassistent"}</h2>
                <p className="text-xs font-bold text-[#e50046] uppercase tracking-widest mb-4">{config?.topic || "Dokumentgestuetzte Beratung"}</p>
                {isSchifT && (
                    <div className="bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 mb-4 text-xs text-amber-700 font-medium">
                        Modus: Schule in freier Traegerschaft (SchifT)
                    </div>
                )}
                <p className="text-sm text-slate-500 mb-6 leading-relaxed">
                    {config?.frontend?.welcome_text || config?.scope_summary || "Dieser Assistent beantwortet Fragen auf Basis der hinterlegten Wissensbasis."}
                    <br/><span className="text-amber-600 font-medium">{config?.safety?.pii_notice || "Bitte keine personenbezogenen Daten eingeben."}</span>
                </p>
                {quickQuestions.length > 0 && (
                    <React.Fragment>
                        <p className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 text-left">Schnelleinstieg:</p>
                        <div className="space-y-2">
                            {quickQuestions.slice(0, 6).map((question, index) => (
                                <button
                                    key={index}
                                    onClick={() => onQuickQuestion(question)}
                                    className="w-full text-left text-xs p-3 bg-slate-50 hover:bg-white border border-slate-100 hover:border-[#e50046] hover:text-[#e50046] rounded-xl transition-all font-medium text-slate-600 flex items-center gap-2.5"
                                >
                                    <Icon name="zap" className="w-3.5 h-3.5 shrink-0 text-[#e50046]" />
                                    {question}
                                </button>
                            ))}
                        </div>
                    </React.Fragment>
                )}
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

    const ChatInput = ({ config, input, isLoading, isStreaming, onInputChange, onSubmit }) => (
        <div className="p-4 bg-white border-t border-slate-200 shadow-sm shrink-0">
            <form onSubmit={onSubmit} className="max-w-4xl mx-auto flex gap-3">
                <input
                    type="text"
                    value={input}
                    onChange={(event) => onInputChange(event.target.value)}
                    placeholder={"Frage zu " + (config?.topic || "diesem Themenfeld") + " stellen..."}
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
        </div>
    );

    const ChatView = ({
        config,
        copiedIndex,
        error,
        input,
        isLoading,
        isSchifT,
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
                        isSchifT={isSchifT}
                        quickQuestions={quickQuestions}
                        onQuickQuestion={onQuickQuestion}
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
                    <div className="flex justify-start">
                        <div className="bg-white border border-slate-200 p-4 rounded-2xl shadow-sm flex items-center gap-3">
                            <Icon name="refresh" className="w-4 h-4 text-[#e50046] animate-spin" />
                            <span className="text-xs text-slate-400 font-bold uppercase tracking-widest">Analyse laeuft...</span>
                        </div>
                    </div>
                )}

                {error && (
                    <div className="flex justify-center p-4">
                        <div className="bg-red-50 text-red-700 p-4 rounded-xl flex items-center gap-3 max-w-md border border-red-200">
                            <Icon name="alert" className="w-5 h-5 shrink-0" />
                            <span className="text-sm font-medium">{error}</span>
                        </div>
                    </div>
                )}
            </div>

            <ChatInput
                config={config}
                input={input}
                isLoading={isLoading}
                isStreaming={isStreaming}
                onInputChange={onInputChange}
                onSubmit={onSubmit}
            />
        </React.Fragment>
    );

    window.BeratungsassistentChatView = ChatView;
})();
