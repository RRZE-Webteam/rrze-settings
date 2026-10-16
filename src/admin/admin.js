import domReady from '@wordpress/dom-ready';

/**
 * Add non-interactive group headings to WordPress's server-ordered submenu.
 * Existing anchors retain their URLs, permissions and native keyboard behavior.
 *
 * @return {void}
 */
function groupSettingsNavigation() {
	const menu = document.querySelector(
		'#toplevel_page_rrze-settings > .wp-submenu'
	);
	const groups = window.rrzeSettingsNavigation?.groups;
	if ( ! menu || ! groups || menu.dataset.rrzeGrouped ) {
		return;
	}

	let previousGroup = null;
	let groupIndex = -1;
	const items = Array.from( menu.children );
	for ( const item of items ) {
		const group = Object.keys( groups ).find( ( key ) =>
			item.classList.contains( `rrze-settings-menu-group-${ key }` )
		);
		if ( ! group ) {
			continue;
		}

		if ( group !== previousGroup ) {
			groupIndex++;
			const headingItem = document.createElement( 'li' );
			headingItem.className = 'rrze-settings-menu-heading';
			headingItem.setAttribute( 'role', 'presentation' );
			const heading = document.createElement( 'h3' );
			heading.textContent = groups[ group ];
			headingItem.append( heading );
			headingItem.classList.toggle(
				'rrze-settings-menu-shaded',
				groupIndex % 2 === 1
			);
			menu.insertBefore( headingItem, item );
			previousGroup = group;
		}
		item.classList.toggle(
			'rrze-settings-menu-shaded',
			groupIndex % 2 === 1
		);
	}

	// Use the selected scheme's regular link color, including its contrast choice.
	const reference =
		menu.querySelector( 'li:not(.current) > a:not(:hover):not(:focus)' ) ||
		menu.querySelector( 'li > a' );
	if ( reference ) {
		menu.style.setProperty(
			'--rrze-menu-foreground',
			window.getComputedStyle( reference ).color
		);
	}
	menu.dataset.rrzeGrouped = 'true';
}

domReady( groupSettingsNavigation );
