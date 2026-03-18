/**
 * Entry point for the Market Readiness Shield Studio widget.
 *
 * When loaded as a script (via getJsPaths() in Pimcore admin), this module
 * auto-registers itself by mounting the ReadinessPanel into a dedicated
 * container element injected into the Pimcore admin sidebar.
 *
 * Pimcore Studio injects a data attribute `data-readiness-object-id` onto the
 * container element to tell the widget which DataObject to display. The widget
 * reads this attribute on mount and when the Pimcore admin fires a global
 * `pimcore:object:changed` CustomEvent (dispatched by the admin whenever the
 * active object changes).
 */

import React from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { ReadinessPanel } from './ReadinessPanel';

export { ReadinessPanel } from './ReadinessPanel';
export { ScoreRing } from './ScoreRing';
export { MissingFieldList } from './MissingFieldList';
export { ProfileSelector } from './ProfileSelector';
export type { MissingField, ProfileScore, ScoreResponse } from './api';

const CONTAINER_ID = 'market-readiness-shield-root';
const OBJECT_ID_ATTR = 'data-readiness-object-id';
const WIDGET_CSS_CLASS = 'pimcore-market-readiness-shield';

/** Singleton React root — created once, reused on every re-render. */
let reactRoot: Root | null = null;

/**
 * Reads the active Pimcore DataObject ID from the container attribute
 * or falls back to the URL hash (#?object/{id}/...).
 */
function resolveObjectId(container: HTMLElement): number | null {
    const attr = container.getAttribute(OBJECT_ID_ATTR);
    if (attr !== null) {
        const parsed = parseInt(attr, 10);
        if (!isNaN(parsed)) return parsed;
    }

    const hashMatch = /[?&]object\/(\d+)/.exec(window.location.hash);
    if (hashMatch) return parseInt(hashMatch[1], 10);

    return null;
}

/**
 * Returns the widget container element, creating it on first call.
 * The container is styled via a CSS class so host applications can
 * override the appearance without touching JavaScript.
 */
function getOrCreateContainer(): HTMLElement {
    let container = document.getElementById(CONTAINER_ID);
    if (!container) {
        container = document.createElement('div');
        container.id = CONTAINER_ID;
        container.className = WIDGET_CSS_CLASS;
        document.body.appendChild(container);
    }
    return container;
}

/**
 * Mounts (or re-mounts) the ReadinessPanel into the designated container.
 * Called once on DOMContentLoaded and again on every `pimcore:object:changed`
 * CustomEvent so the panel always reflects the currently open object.
 */
function mountWidget(): void {
    const container = getOrCreateContainer();
    const objectId = resolveObjectId(container);

    if (objectId === null) {
        container.style.display = 'none';
        return;
    }

    container.style.display = '';

    // Reuse the existing root to avoid memory leaks.
    if (!reactRoot) {
        reactRoot = createRoot(container);
    }
    reactRoot.render(React.createElement(ReadinessPanel, { objectId }));
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountWidget);
    } else {
        mountWidget();
    }

    // Re-mount whenever Pimcore fires a global object-changed event.
    document.addEventListener('pimcore:object:changed', mountWidget);

    // Also listen for hash changes (legacy Pimcore navigation).
    window.addEventListener('hashchange', mountWidget);
}
