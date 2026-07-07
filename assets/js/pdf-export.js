(function () {
    const downloadAsPdf = (text, filenameBase, config) => {
        try {
            const { jsPDF } = window.jspdf;
            if (!jsPDF || !text) return;
            const doc = new jsPDF({ orientation: "p", unit: "mm", format: "a4" });
            const ML = 20, MR = 20, MT = 28, MB = 22;
            const PW = 210, PH = 297;
            const CW = PW - ML - MR;
            let y = MT;
            const newPage = () => { doc.addPage(); y = MT; };
            const checkY = (h) => { if (y + h > PH - MB) newPage(); };
            const strip = (s) => s.replace(/\*\*(.*?)\*\*/g, '$1').replace(/\*(.*?)\*/g, '$1').replace(/`(.*?)`/g, '$1').replace(/~~(.*?)~~/g, '$1');
            const writeLine = (content, size, style, rgb, indent = 0) => {
                doc.setFontSize(size); doc.setFont('helvetica', style); doc.setTextColor(...rgb);
                const wrapped = doc.splitTextToSize(content, CW - indent);
                wrapped.forEach((l, i) => { checkY(size * 0.4); doc.text(l, ML + indent + (i > 0 ? 4 : 0), y); y += size * 0.38; });
            };
            doc.setFillColor(229, 0, 70);
            doc.rect(0, 0, PW, 14, 'F');
            doc.setFontSize(10); doc.setFont('helvetica', 'bold'); doc.setTextColor(255, 255, 255);
            doc.text((config?.title || 'Beratungsassistent') + '  \u00b7  Antwort', ML, 9.5);
            const lines = text.split('\n');
            let inCode = false;
            lines.forEach((raw) => {
                if (raw.startsWith('```')) { inCode = !inCode; y += 2; return; }
                if (inCode) { writeLine(raw, 9, 'normal', [80, 80, 80], 4); return; }
                const trimmed = raw.trim();
                if (!trimmed) { y += 3; return; }
                if (trimmed.startsWith('### ')) { y += 2; checkY(14); writeLine(strip(trimmed.slice(4)), 12, 'bold', [10, 25, 47]); y += 1; return; }
                if (trimmed.startsWith('## ')) { y += 3; checkY(16); writeLine(strip(trimmed.slice(3)), 14, 'bold', [10, 25, 47]); y += 2; return; }
                if (trimmed.startsWith('# ')) { y += 4; checkY(18); writeLine(strip(trimmed.slice(2)), 16, 'bold', [10, 25, 47]); y += 3; return; }
                if (trimmed.startsWith('> ')) { doc.setDrawColor(229, 0, 70); doc.setLineWidth(0.8); checkY(8); doc.line(ML, y - 3, ML, y + 3); writeLine(strip(trimmed.slice(2)), 10, 'italic', [100, 116, 139], 4); return; }
                if (/^[-*] /.test(trimmed)) { checkY(7); doc.setFontSize(11); doc.setFont('helvetica', 'normal'); doc.setTextColor(229, 0, 70); doc.text('\u2022', ML + 1, y); writeLine(strip(trimmed.slice(2)), 11, 'normal', [30, 30, 30], 6); return; }
                const numMatch = trimmed.match(/^(\d+)\.\s+(.*)/);
                if (numMatch) { checkY(7); doc.setFontSize(11); doc.setFont('helvetica', 'bold'); doc.setTextColor(229, 0, 70); doc.text(numMatch[1] + '.', ML + 1, y); writeLine(strip(numMatch[2]), 11, 'normal', [30, 30, 30], 8); return; }
                if (/^---+$/.test(trimmed)) { checkY(6); y += 2; doc.setDrawColor(226, 232, 240); doc.setLineWidth(0.3); doc.line(ML, y, PW - MR, y); y += 4; return; }
                const hasBold = /\*\*/.test(trimmed);
                writeLine(strip(trimmed), 11, hasBold ? 'bold' : 'normal', [30, 30, 30]); y += 1;
            });
            const total = doc.getNumberOfPages();
            for (let p = 1; p <= total; p++) {
                doc.setPage(p); doc.setFontSize(8); doc.setFont('helvetica', 'normal'); doc.setTextColor(148, 163, 184);
                doc.text((config?.title || 'Beratungsassistent') + '  \u00b7  Antwort aus der Wissensbasis', ML, PH - 8);
                doc.text(p + ' / ' + total, PW - MR, PH - 8, { align: 'right' });
            }
            const safeName = String(filenameBase || "antwort").toLowerCase().replace(/[^a-z0-9äöüß]+/gi, "-").replace(/^-+|-+$/g, "") || "antwort";
            doc.save(safeName + ".pdf");
        } catch (e) {}
    };

    window.BeratungsassistentPdf = { downloadAsPdf };
})();
