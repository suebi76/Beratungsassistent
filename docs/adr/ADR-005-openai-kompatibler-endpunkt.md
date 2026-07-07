# ADR-005: OpenAI-kompatibler Endpunkt

## Status

Akzeptiert.

## Entscheidung

Für v1 wird ein OpenAI-kompatibler Endpunkt als zweiter Provider-Typ geplant.

## Begründung

Viele lokale oder selbst gehostete Systeme bieten OpenAI-kompatible APIs, darunter Ollama, vLLM, LM Studio, LocalAI und interne Modellgateways.

## Konsequenzen

- Das System muss nicht selbst lokale Modelle hosten.
- Ein einfacher Webhost kann weiterhin genutzt werden, wenn er einen internen oder externen KI-Endpunkt per HTTPS erreicht.
- Lokale KI wird möglich, aber nicht als Shared-Hosting-Funktion versprochen.
