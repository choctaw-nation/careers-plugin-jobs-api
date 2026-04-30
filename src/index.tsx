import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import App from './App';

domReady( () => {
	const el = document.getElementById( 'cno-jobs-api-settings' );
	if ( ! el ) {
		return;
	}
	const root = createRoot( el );
	root.render(
		<App nonce={ el.dataset.nonce! } restUrl={ el.dataset.restUrl! } />
	);
} );
