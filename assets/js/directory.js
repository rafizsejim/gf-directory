/* GF Directory frontend JS: filter auto-submit, share/copy, no jQuery. */
( function () {
	'use strict';

	const ROOT_SELECTOR = '.gfd';

	function init( root ) {
		bindAutoSubmit( root );
		bindShare( root );
		bindCopy( root );
		bindThumbs( root );
		bindSave( root );
	}

	function bindSave( root ) {
		root.querySelectorAll( '[data-gfd-save]' ).forEach( ( btn ) => {
			btn.addEventListener( 'click', ( ev ) => {
				ev.preventDefault();
				ev.stopPropagation();
				toggleSave( btn );
			} );
		} );
	}

	function toggleSave( btn ) {
		const cfg = window.gfdSaves || {};
		if ( ! cfg.loggedIn ) {
			toast( cfg.loginPrompt || 'Please log in to save listings.', cfg.loginUrl, cfg.loginLabel || 'Log in' );
			return;
		}

		const entryId = parseInt( btn.getAttribute( 'data-gfd-save' ), 10 );
		const formId  = parseInt( btn.getAttribute( 'data-gfd-form' ), 10 );
		if ( ! entryId || ! formId ) {
			return;
		}

		btn.classList.add( 'is-saving' );

		fetch( cfg.restUrl + 'save', {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce || '',
			},
			body: JSON.stringify( { form_id: formId, entry_id: entryId } ),
		} )
			.then( ( r ) => r.json().then( ( body ) => ( { ok: r.ok, body } ) ) )
			.then( ( { ok, body } ) => {
				if ( ! ok ) {
					toast( body && body.message ? body.message : 'Could not save right now.' );
					return;
				}
				updateSaveButtons( entryId, !! body.saved );
				toast( body.saved ? ( cfg.savedLabel || 'Saved' ) : ( cfg.unsavedLabel || 'Removed' ) );
			} )
			.catch( () => {
				toast( 'Network error.' );
			} )
			.finally( () => {
				btn.classList.remove( 'is-saving' );
			} );
	}

	function updateSaveButtons( entryId, saved ) {
		document.querySelectorAll( '[data-gfd-save="' + entryId + '"]' ).forEach( ( el ) => {
			el.setAttribute( 'data-gfd-saved', saved ? '1' : '0' );
			const icon = el.querySelector( '[data-gfd-save-icon]' );
			if ( icon ) {
				icon.textContent = saved ? '♥' : '♡';
			} else if ( ! el.querySelector( 'span[aria-hidden]' ) ) {
				el.textContent = saved ? '♥' : '♡';
			}
			const label = el.querySelector( '[data-gfd-save-label]' );
			if ( label ) {
				label.textContent = saved ? ( window.gfdSaves?.savedWordLabel || 'Saved' ) : ( window.gfdSaves?.saveWordLabel || 'Save' );
			}
		} );
	}

	function toast( message, link, linkLabel ) {
		let el = document.querySelector( '.gfd-toast' );
		if ( ! el ) {
			el = document.createElement( 'div' );
			el.className = 'gfd-toast';
			document.body.appendChild( el );
		}
		el.textContent = message;
		if ( link ) {
			const a = document.createElement( 'a' );
			a.href = link;
			a.textContent = linkLabel || 'Log in';
			el.appendChild( a );
		}
		requestAnimationFrame( () => el.classList.add( 'is-visible' ) );
		clearTimeout( el._gfdTimer );
		el._gfdTimer = setTimeout( () => el.classList.remove( 'is-visible' ), 2400 );
	}

	function bindThumbs( root ) {
		const heroImg = root.querySelector( '.gfd-single__hero-img' );
		if ( ! heroImg ) {
			return;
		}
		root.querySelectorAll( '[data-gfd-image]' ).forEach( ( thumb ) => {
			thumb.addEventListener( 'click', () => {
				const url = thumb.getAttribute( 'data-gfd-image' );
				if ( ! url ) {
					return;
				}
				heroImg.setAttribute( 'src', url );
				root.querySelectorAll( '.gfd-single__thumb' ).forEach( ( t ) => t.classList.remove( 'is-active' ) );
				thumb.classList.add( 'is-active' );
			} );
		} );
	}

	function bindAutoSubmit( root ) {
		root.querySelectorAll( '[data-gfd-autosubmit]' ).forEach( ( el ) => {
			el.addEventListener( 'change', () => {
				const form = el.closest( 'form' );
				if ( form ) {
					form.submit();
				}
			} );
		} );
	}

	function bindShare( root ) {
		root.querySelectorAll( '[data-gfd-share]' ).forEach( ( btn ) => {
			btn.addEventListener( 'click', ( ev ) => {
				ev.preventDefault();
				const url = window.location.href;
				if ( navigator.share ) {
					navigator.share( { url } ).catch( () => {} );
				} else {
					navigator.clipboard?.writeText( url );
				}
			} );
		} );
	}

	function bindCopy( root ) {
		root.querySelectorAll( '[data-gfd-copy]' ).forEach( ( btn ) => {
			btn.addEventListener( 'click', ( ev ) => {
				ev.preventDefault();
				ev.stopPropagation();
				const url = btn.getAttribute( 'data-gfd-copy' );
				if ( url && navigator.clipboard ) {
					navigator.clipboard.writeText( url );
					btn.classList.add( 'is-copied' );
					setTimeout( () => btn.classList.remove( 'is-copied' ), 1200 );
				}
			} );
		} );
	}

	document.querySelectorAll( ROOT_SELECTOR ).forEach( init );
} )();
