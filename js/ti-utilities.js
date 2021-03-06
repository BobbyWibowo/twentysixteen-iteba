/**
 * Polyfill :scope query selectors.
 * https://stackoverflow.com/a/17989803/10044786
 */

( function( doc, proto ) {
	try { // check if browser supports :scope natively
		doc.querySelector( ':scope body' );
	} catch ( err ) { // polyfill native methods if it doesn't
		[ 'querySelector', 'querySelectorAll' ].forEach( function( method ) {
			var nativ = proto[ method ];
			proto[ method ] = function(selectors) {
				if ( /(^|,)\s*:scope/.test( selectors ) ) { // only if selectors contains :scope
					var id = this.id; // remember current element id
					this.id = 'ID_' + Date.now(); // assign new unique id
					selectors = selectors.replace( /((^|,)\s*):scope/g, '$1#' + this.id ); // replace :scope with #ID
					var result = doc[ method ]( selectors );
					this.id = id; // restore previous id
					return result;
				} else {
					return nativ.call( this, selectors ); // use native code for other selectors
				}
			}
		} );
	}
} )( window.document, Element.prototype );

/**
 * Utilities for customized Twenty Sixteen theme for Translateindo.
 * by Bobby Wibowo
 */

( function() {

	function createCookie( name, value, days ) {
		const domain = document.location.hostname
		let path = '/'
		const secure = document.location.protocol.startsWith( 'https' )

		// Deduce correct path of the WP installation
		const titleUrl = document.body.querySelector( '.site-title a[rel="home"]' )
		if ( titleUrl && titleUrl.href ) {
			path = titleUrl.href
				.replace( /^https?:\/\//, '')
				.replace( domain, '' )
			if ( path.length > 1 )
				path = path.replace( /\/$/, '' )
		}

		let date = new Date(0)
		if ( typeof days === 'number' )
			date.setTime( Date.now() + ( days * 24 * 60 * 60 * 1000 ) )

		document.cookie = name + '=' + value +
			'; path=' + path +
			'; domain=' + domain +
			'; expires=' + date.toGMTString() +
			( secure ? ';secure' : '' ) +
			'; samesite=strict'
	}

	function readCookie( name ) {
		const nameEQ = name + '='
		const ca = document.cookie.split( ';' )

		for ( let i = 0; i < ca.length; i++ ) {
			let c = ca[i]
			while ( c.charAt( 0 ) === ' ' )
				c = c.substring( 1, c.length )
			if ( c.indexOf(nameEQ) === 0 )
				return c.substring( nameEQ.length, c.length )
		}

		return null
	}

	function eraseCookie( name ) {
		createCookie( name, '' )
	}

	function relativeDate ( datetime ) {
		// Source: https://stackoverflow.com/a/7641812/10044786

		const date = new Date( datetime )
		const delta = Math.round( ( +new Date - date ) / 1000 )

		const minute = 60
		const hour = minute * 60
		const day = hour * 24

		let result
		if ( delta < 30 )
			result = 'just now'
		else if ( delta < minute )
			result = delta + ' seconds ago'
		else if ( delta < 2 * minute )
			result = 'a minute ago'
		else if ( delta < hour )
			result = Math.floor( delta / minute ) + ' minutes ago'
		else if ( Math.floor( delta / hour ) === 1 )
			result = '1 hour ago'
		else if ( delta < day )
			result = Math.floor( delta / hour ) + ' hours ago'
		else if ( delta < day * 2 )
			result = 'yesterday'
		else
			result = Math.floor( delta / day ) + ' days ago'

		return result
	}

	function bulkSetRelativeDates ( dates ) {
		for ( let i = 0; i < dates.length; i++ ) {
			const datetime = dates[ i ].getAttribute( 'datetime' )
			if ( datetime ) {
				const container = dates[ i ].parentNode
				if ( container ) {
					container.setAttribute( 'title', dates[ i ].innerHTML.trim() )
					container.setAttribute( 'aria-label', dates[ i ].innerHTML.trim() )
				} else {
					dates[ i ].setAttribute( 'title', dates[ i ].innerHTML.trim() )
					dates[ i ].setAttribute( 'aria-label', dates[ i ].innerHTML.trim() )
				}
				dates[ i ].innerHTML = relativeDate( datetime ) || dates[ i ].innerHTML
			}
		}
	}

	/**
	 * Emulating Reddit's nested comments.
	 */

	( function() {

		const commentList = document.body.querySelector( '#comments .comment-list' )
		if ( !commentList )
			return

		const selectors = {
			expandButton: ':scope > .threadline-column > .expand-button',
			commentAuthor: ':scope > .threadline-column > .comment-author',
			commentWeight: ':scope > .threadline-column > .comment-weight-container',
			threadlineDiv: ':scope > .threadline-column > .threadline-div',
			commentColumn: ':scope > .comment-column',
			children: ':scope > .comment-column > .children',
			commentBody: ':scope > .comment-column > .comment-body',
			collapseMessage: ':scope > .comment-column > .collapse-message'
		}

		function collapse ( comment ) {
			// If expand button is not hidden (meaning comments are already collapsed), do nothing.
			const expandButton = comment.querySelector( selectors.expandButton )
			if ( !expandButton.classList.contains( 'is-hidden' ) )
				return

			// Close reply form if exist within thread.
			const cancelReply = comment.querySelector( '#cancel-comment-reply-link' )
			if ( cancelReply ) cancelReply.click()

			const message = document.createElement( 'div' )
			message.className = 'collapse-message'

			// Find amount of children.
			const children = comment.querySelector( selectors.children )
			if ( children ) {
				const amount = children.querySelectorAll( '.comment' ).length
				message.innerHTML = amount + ' child' + ( amount === 1 ? '' : 'ren' )
				// Hide children.
				children.classList.add( 'is-hidden' )
			} else {
				message.innerHTML = ''
			}

			// Attempt to copy author name and comment date.
			const commentBody = comment.querySelector( selectors.commentBody )
			const author = commentBody.querySelector( '.comment-author .fn' )
			const metadata = commentBody.querySelector( '.comment-metadata > a' )
			if (author || metadata) {
				message.innerHTML = ( author ? author.outerHTML : '' ) +
					( metadata ? metadata.outerHTML : '' ) +
					message.innerHTML
			}

			// Attach collapse message before comment body then hide comment body.
			commentBody.insertAdjacentElement( 'beforebegin', message )
			commentBody.classList.add( 'is-hidden' )

			// Hide other extra elements.
			const extras = [
				selectors.commentAuthor,
				selectors.commentWeight,
				selectors.threadlineDiv
			]
			for ( let i = 0; i < extras.length; i++ ) {
				const element = comment.querySelector( extras[ i ] )
				if ( element ) element.classList.add( 'is-hidden' )
			}

			// Show expand button.
			expandButton.classList.remove( 'is-hidden' )
			comment.classList.add( 'is-collapsed' )
		}

		function expand ( comment ) {
			// If expand button is hidden (meaning comments are already expanded), do nothing
			const expandButton = comment.querySelector( selectors.expandButton )
			if ( expandButton.classList.contains( 'is-hidden' ) )
				return

			// Remove collapse message
			const collapseMessage = comment.querySelector( selectors.collapseMessage )
			if ( collapseMessage )
				collapseMessage.parentNode.removeChild( collapseMessage )

			// Unhide these elements.
			const unhide = [
				selectors.children,
				selectors.commentBody,
				selectors.commentAuthor,
				selectors.commentWeight,
				selectors.threadlineDiv
			]
			for ( let i = 0; i < unhide.length; i++ ) {
				const element = comment.querySelector( unhide[ i ] )
				if ( element ) element.classList.remove( 'is-hidden' )
			}

			// Hide expand button.
			expandButton.classList.add( 'is-hidden' )
			comment.classList.remove( 'is-collapsed' )
		}

		// Use single click event listener on comments list.
		commentList.addEventListener( 'click', function ( event ) {
			if ( !event.target ) return

			let expandButton
			let threadline

			// Logic for belongings.
			if ( event.target.classList.contains( 'threadline-div' ) )
				threadline = event.target
			else if ( event.target.classList.contains( 'threadline' ) )
				threadline = event.target.parentNode
			else if ( event.target.classList.contains( 'expand-button' ) )
				expandButton = event.target
			else if ( event.target.classList.contains( 'icon-expand-button' ) )
				expandButton = event.target.parentNode

			const target = threadline || expandButton
			if ( target ) {
				const comment = target.parentNode && target.parentNode.parentNode
				// Proceed only if the expected parent/grandparent node is a comment.
				if ( comment && comment.classList.contains( 'comment' ) ) {
					if ( threadline )
						collapse( comment )
					else if ( expandButton )
						expand( comment )
				}
			}
		}, true )

		// TODO: Disabled till I copy Reddit's design for pre-collapsed threads.
		// On first page load, collapse all level 4 comments.
		/*
		const targetLevel = 6
		const targets = commentList.querySelectorAll( '.comment.depth-' + targetLevel )
		if ( targets.length ) {
			for ( let i = 0; i < targets.length; i++ )
				collapseCommentsThread( targets[ i ] )
		}
		*/

		// Replace comment dates with relative dates
		const dates = commentList.getElementsByTagName( 'time' )
		if ( dates.length )
			bulkSetRelativeDates( dates )
	} )();

	/**
	 * Chapters list in Table of Content pages.
	 */

	( function() {

		const clContainer = document.body.querySelector( '.cl-container' )
		if ( !clContainer )
			return

		const collapseTitle = clContainer.dataset.collapseTitle
		const expandTitle = clContainer.dataset.expandTitle

		function toggle ( header, forceCollapse ) {
			// Proceed only if the expected parent/grandparent node is a .cl-block.
			const block = header.parentNode
			if ( !block || !block.classList.contains( 'cl-block' ) )
				return false

			// Proceed only if .cl-block contains a direct .cl-body child.
			const body = block.querySelector( ':scope > .cl-body' )
			if ( !body )
				return false

			const isCollapsed = forceCollapse !== undefined
				? !forceCollapse
				: header.classList.contains( 'is-collapsed' )

			if ( isCollapsed ) {
				body.classList.remove( 'is-hidden' )
				header.classList.remove( 'is-collapsed' )
				header.setAttribute( 'title', collapseTitle )
			} else {
				body.classList.add( 'is-hidden' )
				header.classList.add( 'is-collapsed' )
				header.setAttribute( 'title', expandTitle )
			}
		}

		clContainer.addEventListener( 'click', function ( event ) {
			if ( !event.target ) return

			// Logic for belongings.
			if ( event.target.classList.contains( 'cl-header' ) )
				toggle( event.target )
			else if ( event.target.parentNode && event.target.parentNode.classList.contains( 'cl-header' ) )
				toggle( event.target.parentNode )
		}, true )

		const headers = clContainer.querySelectorAll( '.cl-header' )
		if ( headers.length ) {
			for ( let i = 0; i < headers.length; i++ ) {
				// Pre-collapse all but the last block.
				toggle( headers[i], i < headers.length - 1 )
			}
		}

		const lis = clContainer.querySelectorAll( '.cl-body li' )
		if ( lis.length > 1 ) {
			const head = clContainer.querySelector( '.cl-head' )
			const sortButton = head.querySelector( '.icon-sort-toggle' )
			if ( !sortButton ) return

			const lists = clContainer.querySelector( '.cl-lists' )

			function toggleSort ( event, skipCookie = false ) {
				// Thanks to: https://stackoverflow.com/a/12539391/10044786
				const blocks = lists.querySelectorAll( '.cl-block' )
				for ( let i = 0; i < blocks.length; i++ ) {
					const block = blocks[i]

					const body = block.querySelector( '.cl-body' )
					if ( body && body.childNodes.length > 1 ) {
						let j = body.childNodes.length
						while ( j-- )
							body.appendChild( body.childNodes[j] )

						// Reverse chapter numbers in block header
						const header = block.querySelector( '.cl-header' )
						if ( header ) {
							const from = header.querySelector( '.from' )
							const to = header.querySelector( '.to' )
							if ( from && to ) {
								const tmp = from.innerHTML
								from.innerHTML = to.innerHTML
								to.innerHTML = tmp
							}
						}
					}
				}

				let k = lists.childNodes.length
				while ( k-- )
					lists.appendChild( lists.childNodes[k] )

				const isDescending = sortButton.classList.contains( 'is-descending' )
				if ( isDescending ) {
					sortButton.classList.remove( 'is-descending' )
					if ( !skipCookie )
						eraseCookie( 'cl-is-descending' )
				} else {
					sortButton.classList.add( 'is-descending' )
					if ( !skipCookie )
						createCookie( 'cl-is-descending', '1', 365)
				}
			}

			sortButton.addEventListener( 'click', toggleSort, true )
			sortButton.classList.remove( 'is-hidden' )

			if ( readCookie( 'cl-is-descending' ) === '1' )
				toggleSort( null, true )
		}

		// Replace chapter dates with relative dates
		const dates = clContainer.querySelectorAll( '.cl-body li span' )
		if ( dates.length )
			bulkSetRelativeDates( dates )

	} )();

} )();
