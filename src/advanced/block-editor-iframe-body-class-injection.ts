import domReady from '@wordpress/dom-ready';
import { subscribe, select } from '@wordpress/data';

declare global {
  interface Window {
    /**
     * Data localized from PHP containing CSS classes for the iframe.
     */
    iframeBodyData?: {
      /**
       * Space-separated list of CSS classes.
       */
      classes: string;
    };
  }
}

/**
 * State to track the active iframe and its observer.
 * This ensures we can properly disconnect observers when the iframe is removed.
 */
let activeIframe: HTMLIFrameElement | null = null;
let observer: MutationObserver | null = null;

/**
 * Securely applies a list of CSS classes to an element.
 *
 * Checks if each class is already present before adding to avoid redundant DOM updates.
 *
 * @param {HTMLElement} element        The target element to receive the classes.
 * @param {string[]}    classesToApply Array of sanitized class names.
 */
const ensureClasses = (element: HTMLElement, classesToApply: string[]): void => {
  classesToApply.forEach((cls) => {
    if (!element.classList.contains(cls)) {
      element.classList.add(cls);
    }
  });
};

/**
 * Monitors the editor iframe for DOM changes and reapplies required theme classes.
 *
 * This is necessary because React re-renders within the iframe can remove classes
 * set manually on the body element.
 *
 * @param {HTMLIFrameElement} iframe         The editor iframe element ("editor-canvas").
 * @param {string[]}          classesToApply Array of sanitized class names to maintain.
 */
const observeIframe = (iframe: HTMLIFrameElement, classesToApply: string[]): void => {
  if (observer) {
    observer.disconnect();
  }

  try {
    const contentDoc = iframe.contentDocument;
    if (!contentDoc) {
      return;
    }

    /**
     * Callback for the MutationObserver.
     * Targets the iframe's body and ensures it has the required classes.
     */
    observer = new MutationObserver(() => {
      const { body } = contentDoc;
      if (body) {
        ensureClasses(body, classesToApply);
      }
    });

    // Observe the entire document to catch re-renders that might replace the body attributes.
    observer.observe(contentDoc.documentElement, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class'],
    });

    // Initial check in case the body is already present when observation starts.
    const { body } = contentDoc;
    if (body) {
      ensureClasses(body, classesToApply);
    }
  } catch (error) {
    console.warn('RRZE Settings: Could not observe editor iframe.', error);
  }
};

/**
 * Main initialization logic.
 *
 * Hooks into the WordPress Block Editor lifecycle to ensure theme-specific classes
 * (like post-type or theme name) are preserved within the iframe-based canvas.
 */
domReady(() => {
  const data = window.iframeBodyData;
  if (!data || typeof data.classes !== 'string') {
    return;
  }

  // Sanitize and filter input to ensure only valid class names are processed.
  const classesToApply = data.classes
    .split(/\s+/)
    .filter((cls) => /^[a-z0-9_-]+$/i.test(cls));

  if (classesToApply.length === 0) {
    return;
  }

  /**
   * Subscribe to the WordPress data store.
   * Efficiently detects when the editor is ready and when the iframe canvas is mounted or replaced.
   */
  subscribe(() => {
    // @ts-ignore - The 'core/editor' store is standard but might not be in every environment's types.
    const isEditorReady = select('core/editor')?.__unstableIsEditorReady?.();

    if (!isEditorReady) {
      // Cleanup when navigating away from the editor.
      if (activeIframe) {
        activeIframe = null;
        if (observer) {
          observer.disconnect();
          observer = null;
        }
      }
      return;
    }

    const iframe = document.querySelector(
      'iframe[name="editor-canvas"]'
    ) as HTMLIFrameElement | null;

    // Initialize observation only if a new iframe instance is detected.
    if (iframe && iframe !== activeIframe) {
      activeIframe = iframe;
      // It can take a moment for the iframe's contentDocument to be accessible
      setTimeout(() => observeIframe(iframe, classesToApply), 50);
    }
  });
});
