# Wissensbasis und Chunks

## Grundidee

Jede Markdown-Datei in `rag/chunks/` ist ein Wissens-Chunk.

Bei einer Nutzerfrage passiert serverseitig Folgendes:

1. Die Frage wird in Suchbegriffe zerlegt.
2. Titel, Tags, Quelle und Text der vorhandenen Chunks werden gewichtet durchsucht.
3. Die besten Treffer werden an den System-Prompt angehängt.
4. Das Modell beantwortet die Frage auf Basis dieser Treffer.

## Chunk-Format

```markdown
---
title: Prägnanter Titel
tags: tag1, tag2, tag3, tag4
quelle: Dokumenttitel oder Dateiname
source_file: originaldatei.pdf
doc_type: pdf
chunk_index: 1
---

## Kernaussage

Inhalt des Chunks.
```

## Gute Chunks

- Ein Chunk sollte genau ein klares Thema, einen Prozess oder eine wiederkehrende Frage abdecken.
- Tags sind wichtig, weil das Retrieval Titel, Tags und Text unterschiedlich gewichtet.
- Der Text sollte nahe an der Quelle bleiben.
- Wenn ein Dokument mehrere Themen enthält, sind mehrere kleine Chunks besser als ein riesiger Sammeltext.

## Ingest in dieser Version

Die Chunk-Erzeugung erfolgt im Admin-Bereich über Gemini:

- PDF wird als Datei an Gemini übergeben
- TXT und Markdown werden als Text übergeben
- Gemini liefert strukturierte Markdown-Chunks zurück

## RAG-Einordnung

Die Wissensbasis bildet ein echtes RAG-Setup, aber ohne Embeddings:

- Retrieval: gewichtete Keyword-Suche über lokale Chunk-Dateien
- Augmentation: die gefundenen Chunks werden in den System-Prompt eingebettet
- Generation: Gemini erzeugt die Antwort auf Basis der Nutzerfrage und der eingebetteten Chunks

Das bedeutet:

- Ja, der Assistent nutzt RAG
- Nein, die Suche ist aktuell nicht semantisch-vektorbasiert

## Leeres Repository

Im Git-Repository bleiben `rag/chunks/` und `rag/uploads/` absichtlich leer.
Die Inhalte entstehen erst pro Instanz während der Einrichtung und werden nicht versioniert.
