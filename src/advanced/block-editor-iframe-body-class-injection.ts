import domReady from '@wordpress/dom-ready';

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

const EDITOR_IFRAME_SELECTOR = 'iframe[name="editor-canvas"]';

interface IframeState {
	document: Document | null;
	observer: MutationObserver | null;
	onLoad: () => void;
}

/**
 * An editor may replace or reload its canvas iframe while navigating between
 * editing modes. Track every canvas independently so each one can be cleaned
 * up without affecting another editor canvas on the page.
 */
const iframeStates = new Map< HTMLIFrameElement, IframeState >();

/**
 * Securely applies a list of CSS classes to an element.
 *
 * Checks if each class is already present before adding to avoid redundant DOM updates.
 *
 * @param {HTMLElement} element        The target element to receive the classes.
 * @param {string[]}    classesToApply Array of sanitized class names.
 */
const ensureClasses = (
	element: HTMLElement,
	classesToApply: string[]
): void => {
	classesToApply.forEach( ( cls ) => {
		if ( ! element.classList.contains( cls ) ) {
			element.classList.add( cls );
		}
	} );
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
const observeIframe = (
	iframe: HTMLIFrameElement,
	classesToApply: string[]
): void => {
	const state = iframeStates.get( iframe );
	if ( ! state ) {
		return;
	}

	try {
		const contentDoc = iframe.contentDocument;
		if ( ! contentDoc?.documentElement ) {
			return;
		}

		// A load can be reported more than once without replacing the document.
		if ( contentDoc === state.document && state.observer ) {
			if ( contentDoc.body ) {
				ensureClasses( contentDoc.body, classesToApply );
			}
			return;
		}

		state.observer?.disconnect();
		state.document = contentDoc;

		/**
		 * Callback for the MutationObserver.
		 * Targets the iframe's body and ensures it has the required classes.
		 */
		state.observer = new MutationObserver( () => {
			const { body } = contentDoc;
			if ( body ) {
				ensureClasses( body, classesToApply );
			}
		} );

		// Observe the entire document to catch the React portal mounting the body,
		// as well as later re-renders that replace its class attribute.
		state.observer.observe( contentDoc.documentElement, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: [ 'class' ],
		} );

		// Initial check in case the body is already present when observation starts.
		const { body } = contentDoc;
		if ( body ) {
			ensureClasses( body, classesToApply );
		}
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.warn(
			'RRZE Settings: Could not observe editor iframe.',
			error
		);
	}
};

/**
 * Start tracking an editor canvas. WordPress 7.1 loads the iframe document
 * asynchronously, so observing only the document available when the iframe is
 * first discovered can leave us attached to the initial document.
 *
 * @param {HTMLIFrameElement} iframe         The editor canvas to track.
 * @param {string[]}          classesToApply Array of sanitized class names to maintain.
 */
const attachIframe = (
	iframe: HTMLIFrameElement,
	classesToApply: string[]
): void => {
	if ( iframeStates.has( iframe ) ) {
		return;
	}

	const state: IframeState = {
		document: null,
		observer: null,
		onLoad: () => observeIframe( iframe, classesToApply ),
	};

	iframeStates.set( iframe, state );
	iframe.addEventListener( 'load', state.onLoad );

	// Handle an iframe whose load event fired before the outer observer found it.
	observeIframe( iframe, classesToApply );
};

/**
 * Stop tracking a canvas which has been removed from the editor.
 *
 * @param {HTMLIFrameElement} iframe The editor canvas to stop tracking.
 */
const detachIframe = ( iframe: HTMLIFrameElement ): void => {
	const state = iframeStates.get( iframe );
	if ( ! state ) {
		return;
	}

	iframe.removeEventListener( 'load', state.onLoad );
	state.observer?.disconnect();
	iframeStates.delete( iframe );
};

/**
 * Find editor canvases in a newly added part of the admin DOM.
 *
 * @param {Document|Element} root           Node in which to find editor canvases.
 * @param {string[]}         classesToApply Array of sanitized class names to maintain.
 */
const attachIframesWithin = (
	root: Document | Element,
	classesToApply: string[]
): void => {
	if (
		root instanceof HTMLIFrameElement &&
		root.matches( EDITOR_IFRAME_SELECTOR )
	) {
		attachIframe( root, classesToApply );
	}

	root.querySelectorAll< HTMLIFrameElement >(
		EDITOR_IFRAME_SELECTOR
	).forEach( ( iframe ) => attachIframe( iframe, classesToApply ) );
};

/**
 * Main initialization logic.
 *
 * Hooks into the WordPress Block Editor lifecycle to ensure theme-specific classes
 * (like post-type or theme name) are preserved within the iframe-based canvas.
 */
domReady( () => {
	const data = window.iframeBodyData;
	if ( ! data || typeof data.classes !== 'string' ) {
		return;
	}

	// Sanitize and filter input to ensure only valid class names are processed.
	const classesToApply = data.classes
		.split( /\s+/ )
		.filter( ( cls ) => /^[a-z0-9_-]+$/i.test( cls ) );

	if ( classesToApply.length === 0 ) {
		return;
	}

	// The editor-ready data-store update can occur before React mounts the iframe
	// and no later store update is guaranteed. Observe the DOM lifecycle directly.
	attachIframesWithin( document, classesToApply );

	const editorObserver = new MutationObserver( ( mutations ) => {
		mutations.forEach( ( mutation ) => {
			mutation.addedNodes.forEach( ( node ) => {
				if ( node instanceof Element ) {
					attachIframesWithin( node, classesToApply );
				}
			} );
		} );

		// Canvas nodes can be moved as the editor changes mode. Only detach nodes
		// that are still disconnected after the complete mutation batch.
		iframeStates.forEach( ( _state, iframe ) => {
			if ( ! iframe.isConnected ) {
				detachIframe( iframe );
			}
		} );
	} );

	editorObserver.observe( document.documentElement, {
		childList: true,
		subtree: true,
	} );
} );
