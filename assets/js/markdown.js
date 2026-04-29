(function () {
    marked.setOptions({ breaks: true, gfm: true });

    const sanitizeHtml = (html) => {
        const template = document.createElement("template");
        template.innerHTML = String(html || "");

        const allowedTags = new Set([
            "a", "b", "blockquote", "br", "code", "del", "em", "h1", "h2", "h3", "h4", "h5", "h6",
            "hr", "i", "li", "ol", "p", "pre", "strong", "table", "tbody", "td", "th", "thead", "tr", "ul"
        ]);
        const dangerousTags = new Set(["script", "style", "iframe", "object", "embed", "link", "meta", "form", "input", "button", "textarea", "select"]);
        const safeUrl = /^(https?:|mailto:)/i;

        const sanitizeNode = (node) => {
            if (node.nodeType === Node.TEXT_NODE) return;
            if (node.nodeType !== Node.ELEMENT_NODE) {
                node.remove();
                return;
            }

            const tag = node.tagName.toLowerCase();
            if (!allowedTags.has(tag)) {
                if (dangerousTags.has(tag)) {
                    node.remove();
                    return;
                }
                Array.from(node.childNodes).forEach(sanitizeNode);
                node.replaceWith(...Array.from(node.childNodes));
                return;
            }

            Array.from(node.attributes).forEach((attr) => {
                const name = attr.name.toLowerCase();
                const value = attr.value || "";
                const allowed = (tag === "a" && (name === "href" || name === "title"))
                    || ((tag === "code" || tag === "pre") && name === "class");

                if (!allowed || name.startsWith("on") || name === "style") {
                    node.removeAttribute(attr.name);
                    return;
                }

                if (tag === "a" && name === "href" && !safeUrl.test(value)) {
                    node.removeAttribute(attr.name);
                }
            });

            if (tag === "a" && node.hasAttribute("href")) {
                node.setAttribute("target", "_blank");
                node.setAttribute("rel", "noopener noreferrer");
            }

            Array.from(node.childNodes).forEach(sanitizeNode);
        };

        Array.from(template.content.childNodes).forEach(sanitizeNode);
        return template.innerHTML;
    };

    const renderMarkdown = (text) => ({
        __html: sanitizeHtml(marked.parse(String(text || "")))
    });

    window.BeratungsassistentMarkdown = { sanitizeHtml, renderMarkdown };
})();

