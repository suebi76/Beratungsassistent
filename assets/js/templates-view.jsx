(function () {
    const Icon = window.BeratungsassistentIcon;

    const QuickQuestionSection = ({ quickQuestions, onApplyTemplate }) => (
        <div className="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div className="bg-slate-50 p-5 border-b border-slate-100 flex items-start gap-4">
                <div className="bg-[#e50046] text-white p-3 rounded-xl shrink-0">
                    <Icon name="zap" />
                </div>
                <div>
                    <h3 className="font-bold text-slate-800 text-base leading-tight">Schnellfragen</h3>
                    <p className="text-xs text-slate-500 mt-1">Für einen schnellen Einstieg. Die Frage wird in den Chat übernommen.</p>
                </div>
            </div>
            <div className="p-5 grid grid-cols-1 md:grid-cols-2 gap-3">
                {quickQuestions.slice(0, 4).map((question, index) => (
                    <button
                        key={index}
                        onClick={() => onApplyTemplate(question)}
                        className="text-left text-xs p-4 bg-slate-50 hover:bg-white border border-slate-100 hover:border-[#e50046] rounded-2xl transition-all flex items-center justify-between"
                    >
                        <span className="font-bold text-slate-600">{question}</span>
                        <Icon name="chevronRight" className="w-4 h-4 text-slate-300 shrink-0" />
                    </button>
                ))}
            </div>
        </div>
    );

    const TemplateSection = ({ section, sectionIndex, onApplyTemplate }) => (
        <div key={sectionIndex} className="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div className="bg-slate-50 p-5 border-b border-slate-100 flex items-start gap-4">
                <div className="bg-[#0a192f] text-white p-3 rounded-xl shrink-0">
                    <Icon name={section.icon || "layers"} />
                </div>
                <div>
                    <h3 className="font-bold text-slate-800 text-base leading-tight">{section.title || "Vorlage"}</h3>
                    <p className="text-xs text-slate-500 mt-1">{section.description || "Hilfen für wiederkehrende fachliche Anliegen."}</p>
                </div>
            </div>
            <div className="p-5 grid grid-cols-1 md:grid-cols-2 gap-3">
                {(section.options || []).map((option, optionIndex) => (
                    <button
                        key={optionIndex}
                        onClick={() => onApplyTemplate(option.prompt || option.label)}
                        className="text-left text-xs p-4 bg-slate-50 hover:bg-white border border-slate-100 hover:border-[#e50046] rounded-2xl transition-all flex items-center justify-between"
                    >
                        <span className="font-bold text-slate-600">{option.label}</span>
                        <Icon name="chevronRight" className="w-4 h-4 text-slate-300 shrink-0" />
                    </button>
                ))}
            </div>
        </div>
    );

    const EmptyTemplatesState = () => (
        <div className="bg-white p-8 rounded-3xl shadow-sm border border-slate-200 text-center">
            <div className="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-400">
                <Icon name="layers" className="w-6 h-6" />
            </div>
            <h3 className="font-bold text-[#0a192f] mb-2">Keine Vorlagen vorhanden</h3>
            <p className="text-sm text-slate-500">Sie können trotzdem direkt eine Frage in den Chat eingeben.</p>
        </div>
    );

    const TemplatesView = ({ quickQuestions, templates, onApplyTemplate }) => (
        <div className="flex-1 overflow-y-auto p-4 md:p-8 bg-slate-100">
            <div className="max-w-3xl mx-auto space-y-5">
                <div className="bg-[#0a192f] text-white rounded-3xl p-5 shadow-sm">
                    <h2 className="font-bold text-lg mb-2 flex items-center gap-2">
                        <Icon name="layers" className="w-5 h-5 text-[#e50046]" /> Vorlagen
                    </h2>
                    <p className="text-sm text-slate-300 leading-relaxed">
                        Nutzen Sie Vorlagen, wenn Sie eine Frage strukturieren möchten. Die Formulierung wird in den Chat übernommen und kann vor dem Absenden angepasst werden.
                    </p>
                </div>

                {quickQuestions.length > 0 && (
                    <QuickQuestionSection quickQuestions={quickQuestions} onApplyTemplate={onApplyTemplate} />
                )}

                <div className="grid grid-cols-1 gap-5 pb-12">
                    {templates.map((section, sectionIndex) => (
                        <TemplateSection
                            key={sectionIndex}
                            section={section}
                            sectionIndex={sectionIndex}
                            onApplyTemplate={onApplyTemplate}
                        />
                    ))}
                </div>

                {templates.length === 0 && quickQuestions.length === 0 && (
                    <EmptyTemplatesState />
                )}
            </div>
        </div>
    );

    window.BeratungsassistentTemplatesView = TemplatesView;
})();
