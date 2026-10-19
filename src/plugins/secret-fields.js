import domReady from '@wordpress/dom-ready';

/**
 * Describe the next visibility action without including the credential itself.
 *
 * @param {HTMLElement} control Button or native disclosure summary.
 * @param {boolean}     visible Whether the credential is currently shown.
 * @return {void}
 */
function updateToggle( control, visible ) {
	const label = visible
		? control.dataset.hideLabel
		: control.dataset.showLabel;
	control.setAttribute( 'aria-label', label );
	control.title = label;
	const icon = control.querySelector( '.dashicons' );
	icon.classList.toggle( 'dashicons-visibility', ! visible );
	icon.classList.toggle( 'dashicons-hidden', visible );
}

/**
 * Reveal or mask a single-line credential without changing its value or focus.
 *
 * @param {HTMLInputElement}  input  Credential input.
 * @param {HTMLButtonElement} button Associated visibility button.
 * @param {boolean}           shown  Requested visibility.
 * @return {void}
 */
function setInputVisibility( input, button, shown ) {
	input.type = shown ? 'text' : 'password';
	updateToggle( button, shown );
}

/**
 * Synchronize a native multiline disclosure's label, icon and decorative mask.
 *
 * @param {HTMLDetailsElement} details Credential disclosure.
 * @return {void}
 */
function syncDisclosure( details ) {
	updateToggle( details.querySelector( 'summary' ), details.open );
	details.querySelector( '.rrze-settings-secret-preview' ).textContent =
		details.querySelector( 'textarea' ).value ? '••••••••' : '—';
}

/**
 * Enhance server-masked fields and reset visibility on navigation or submission.
 *
 * @return {void}
 */
function setupSecretFields() {
	const page = document.querySelector( '.rrze-settings-plugins-page' );
	if ( ! page ) {
		return;
	}
	const inputs = page.querySelectorAll( '[data-rrze-secret-input]' );
	const disclosures = page.querySelectorAll(
		'.rrze-settings-secret-details'
	);
	for ( const input of inputs ) {
		const button = input.parentElement.querySelector( 'button' );
		button.hidden = false;
		/** Toggle only this input, keeping the credential out of labels and storage. */
		button.addEventListener( 'click', function toggleSecretInput() {
			setInputVisibility( input, button, input.type === 'password' );
		} );
	}
	for ( const details of disclosures ) {
		/** Update the presentation after mouse or keyboard disclosure changes. */
		details.addEventListener( 'toggle', function handleDisclosureToggle() {
			syncDisclosure( details );
		} );
	}

	/**
	 * Restore concealed presentation, including after browser back/forward restore.
	 *
	 * @return {void}
	 */
	function maskAllSecrets() {
		for ( const input of inputs ) {
			setInputVisibility(
				input,
				input.parentElement.querySelector( 'button' ),
				false
			);
		}
		for ( const details of disclosures ) {
			details.open = false;
			syncDisclosure( details );
		}
	}

	maskAllSecrets();
	window.addEventListener( 'pagehide', maskAllSecrets );
	window.addEventListener( 'pageshow', maskAllSecrets );
	page.querySelector( 'form' ).addEventListener( 'submit', maskAllSecrets );
}

domReady( setupSecretFields );
