// Erzeugt das deterministische Referenz-PDF für Ingestion-Tests (E11, Fable-Planung).
// 300 Seiten, 12 Kapitel zu je 25 Seiten. Jede Seite trägt einen eindeutigen
// Marker (MARKER-Kxx-Syyy), damit Coverage- und Extraktionstests exakt prüfen
// können, welche Seiten in der Wissensbasis gelandet sind.
//
// Aufruf: node scripts/create-reference-pdf.js
// Ausgabe: tests/data/referenz-dokument-300-seiten.pdf

const fs = require('fs');
const path = require('path');
const { jsPDF } = require(path.join(__dirname, '..', 'vendor', 'jspdf.umd.min.js'));

const PAGE_COUNT = 300;
const PAGES_PER_CHAPTER = 25;

// Ein eindeutiges Fachthema pro Kapitel, damit Retrieval-Tests kapitelgenaue
// Treffer nachweisen können.
const CHAPTER_TOPICS = [
    'Medienkompetenz', 'Datenschutz', 'Unterrichtsentwicklung', 'Fortbildungsplanung',
    'Schulentwicklung', 'Digitale Werkzeuge', 'Beratungsprozesse', 'Qualitaetssicherung',
    'Barrierefreiheit', 'Informationssicherheit', 'Lernplattformen', 'Evaluation',
];

function chapterOfPage(page) {
    return Math.min(Math.ceil(page / PAGES_PER_CHAPTER), CHAPTER_TOPICS.length);
}

function pad(value, width) {
    return String(value).padStart(width, '0');
}

const doc = new jsPDF({ unit: 'pt', format: 'a4' });

for (let page = 1; page <= PAGE_COUNT; page++) {
    if (page > 1) {
        doc.addPage();
    }
    const chapter = chapterOfPage(page);
    const topic = CHAPTER_TOPICS[chapter - 1];
    const marker = `MARKER-K${pad(chapter, 2)}-S${pad(page, 3)}`;

    doc.setFontSize(16);
    doc.text(`Kapitel ${chapter}: ${topic}`, 60, 70);
    doc.setFontSize(10);
    doc.text(`Referenzdokument fuer Ingestion-Tests - Seite ${page} von ${PAGE_COUNT}`, 60, 92);
    doc.text(marker, 60, 108);

    doc.setFontSize(11);
    const body = [
        `Dieser Abschnitt behandelt das Thema ${topic} im Rahmen von Kapitel ${chapter}.`,
        `Die Seite ${page} enthaelt deterministischen Text ohne Zufallsanteile, damit`,
        'Textextraktion, Chunking und Abdeckungsberichte exakt gegen bekannte Inhalte',
        'geprueft werden koennen. Jede Seite ist ueber ihren Marker eindeutig',
        'identifizierbar und jedem Kapitel ist genau ein Fachbegriff zugeordnet.',
        '',
        `Leitfrage der Seite: Wie unterstuetzt ${topic} die Beratungsarbeit in`,
        `Kapitel ${chapter}? Die Antwort dieser Referenzseite lautet: ${topic}`,
        `wird auf Seite ${page} beschrieben und ist Teil des Abschnitts`,
        `${marker}. Damit lassen sich Golden-Questions kapitel- und seitengenau`,
        'verankern und Regressionen im Retrieval zuverlaessig erkennen.',
    ];
    let y = 140;
    for (const line of body) {
        doc.text(line, 60, y);
        y += 16;
    }
}

const outDir = path.join(__dirname, '..', 'tests', 'data');
fs.mkdirSync(outDir, { recursive: true });
const outFile = path.join(outDir, 'referenz-dokument-300-seiten.pdf');
fs.writeFileSync(outFile, Buffer.from(doc.output('arraybuffer')));
console.log(`Erzeugt: ${outFile} (${PAGE_COUNT} Seiten, ${fs.statSync(outFile).size} Bytes)`);
