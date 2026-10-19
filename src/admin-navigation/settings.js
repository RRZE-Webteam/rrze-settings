import domReady from '@wordpress/dom-ready';

/**
 * Enhance numeric ordering with pointer and keyboard controls on the network form.
 *
 * @return {void}
 */
function setupNavigationOrder() {
	const $ = window.jQuery;
	const form = document.getElementById( 'rrze-admin-navigation-form' );
	const list = document.getElementById( 'rrze-navigation-order' );
	if ( ! form || ! list ) {
		return;
	}
	const messages = window.rrzeAdminNavigation;
	const announcement = document.getElementById(
		'rrze-navigation-announcement'
	);
	const dirty = document.getElementById( 'rrze-navigation-dirty' );

	/**
	 * Reflect current DOM order in the submitted positions and boundary controls.
	 *
	 * @return {void}
	 */
	function synchronize() {
		const rows = Array.from( list.children );
		for ( const [ index, row ] of rows.entries() ) {
			row.querySelector( '.rrze-navigation-position' ).value = index + 1;
			row.querySelector( '.rrze-navigation-up' ).setAttribute(
				'aria-disabled',
				String( index === 0 )
			);
			row.querySelector( '.rrze-navigation-down' ).setAttribute(
				'aria-disabled',
				String( index === rows.length - 1 )
			);
		}
	}

	/**
	 * Announce an unsaved move without transferring keyboard focus.
	 *
	 * @param {HTMLElement} row Repositioned entry.
	 * @return {void}
	 */
	function announceMove( row ) {
		dirty.textContent = messages.changed;
		announcement.textContent = messages.moved
			.replace( '%1$s', row.dataset.label )
			.replace(
				'%2$s',
				row.querySelector( '.rrze-navigation-position' ).value
			)
			.replace( '%3$s', list.children.length );
	}

	/**
	 * Move an entry to a zero-based index while retaining focus on its control.
	 *
	 * @param {HTMLElement} row   Entry to move.
	 * @param {number}      index Requested zero-based position.
	 * @return {void}
	 */
	function move( row, index ) {
		const rows = Array.from( list.children );
		const target = Math.max( 0, Math.min( rows.length - 1, index ) );
		const current = rows.indexOf( row );
		const focused = row.ownerDocument.activeElement;
		if ( current < target ) {
			rows[ target ].after( row );
		} else if ( current > target ) {
			rows[ target ].before( row );
		}
		synchronize();
		if ( row.contains( focused ) ) {
			focused.focus( { preventScroll: true } );
		}
		row.scrollIntoView( { block: 'nearest' } );
		if ( current !== target ) {
			announceMove( row );
		}
	}

	/**
	 * Delegate arrow-button moves so the same control stays usable repeatedly.
	 *
	 * @param {MouseEvent} event Button activation, including keyboard activation.
	 * @return {void}
	 */
	function handleClick( event ) {
		const button = event.target.closest(
			'.rrze-navigation-up, .rrze-navigation-down'
		);
		if ( ! button || button.getAttribute( 'aria-disabled' ) === 'true' ) {
			return;
		}
		const row = button.closest( '.rrze-navigation-row' );
		const index = Array.from( list.children ).indexOf( row );
		move(
			row,
			index +
				( button.classList.contains( 'rrze-navigation-up' ) ? -1 : 1 )
		);
	}

	/**
	 * Apply a manually entered position and mark checkbox changes as unsaved.
	 *
	 * @param {Event} event Form change event.
	 * @return {void}
	 */
	function handleChange( event ) {
		if ( event.target.matches( '.rrze-navigation-position' ) ) {
			const index = Number( event.target.value ) - 1;
			if (
				event.target.value.trim() !== '' &&
				Number.isInteger( index )
			) {
				move( event.target.closest( '.rrze-navigation-row' ), index );
			} else {
				synchronize();
			}
		} else {
			dirty.textContent = messages.changed;
		}
	}

	/**
	 * Synchronize a completed pointer drag with the accessible form fields.
	 *
	 * @param {Event}  event Sortable update event.
	 * @param {Object} ui    jQuery UI item containing the moved row.
	 * @return {void}
	 */
	function handleDrop( event, ui ) {
		synchronize();
		announceMove( ui.item[ 0 ] );
	}

	synchronize();
	list.addEventListener( 'click', handleClick );
	form.addEventListener( 'change', handleChange );
	$( list ).sortable( {
		axis: 'y',
		handle: '.rrze-navigation-handle',
		items: '> .rrze-navigation-row',
		placeholder: 'rrze-navigation-placeholder',
		forcePlaceholderSize: true,
		update: handleDrop,
	} );
}

domReady( setupNavigationOrder );
