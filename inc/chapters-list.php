<?php
/**
 * Chapters list in Table of Content pages.
 * by Bobby Wibowo
 */

class Chapters_List
{
	// Using Transient is RECOMMENDED,
	// since building chapter lists are very slow with ToCs that have thousands of chapters.
	private $transient = array(
		'enabled'     => NULL,
		'version'     => NULL,
		'prefix'      => NULL,
		// The longer the better. Do not set 0 (no expiration), as those will be autoloaded on every pages.
		'expiration'  => 1 * MONTH_IN_SECONDS,
		'_name'       => NULL,
		'_validation' => NULL // Validation array.
	);

	// Fields from last post that will be additionally stored into validation array.
	private $validation_fields = array(
		'ID',
		'post_date_gmt',
		'post_title'
	);

	private $settings = NULL;
	private $categories = '';
	private $query_args = array(
		'order'       => 'ASC',
		'post_type'   => 'post',
		'post_status' => 'publish'
	);

	private $chapters = array();

	public function __construct( bool $enabled = NULL, string $version = NULL, string $prefix = NULL )
	{
		$this->transient['enabled'] = ( NULL !== $enabled ? $enabled : TRUE );
		$this->transient['version'] = $version ?: '0';
		$this->transient['prefix']  = $prefix ?: 'TI_CL_';
	}

	private function load_chapters()
	{
		if ( $this->transient['enabled'] && !isset( $this->transient['_validation'] ) )
		{
			// If we use Transients, we already queries posts in $this->load_validation_data().
			// So if $this->transient['_validation'] is NOT set, simply assume zero posts.
			$this->chapters = array();
			return TRUE;
		}
		else
		{
			$this->chapters = get_posts(
				wp_parse_args( array(
					'numberposts' => -1
				), $this->query_args )
			);
			return is_array( $this->chapters );
		}
	}

	private function load_validation_data()
	{
		$get = get_posts(
			wp_parse_args( array(
				'numberposts' => 1,
				'order'       => 'DESC'
			), $this->query_args )
		);

		if ( isset( $get[0] ) )
		{
			$this->transient['_validation'] = array(
				'version'  => $this->transient['version'],
				'category' => $this->query_args['category']
			);

			foreach ( $this->settings as $setting => $value )
			{
				$this->transient['_validation'][$setting] = ( string ) $value;
			}

			foreach ( $this->validation_fields as $field )
			{
				$this->transient['_validation'][$field] = ( string ) $get[0]->$field;
			}
		}
	}

	private function load_settings( WP_Post $post, array $settings = array() )
	{
		$category_IDs = get_post_meta( $post->ID, 'category_id' );
		if ( is_array( $category_IDs) && count( $category_IDs ) > 0 )
		{
			// Parse options.
			$defaults = array(
				'get_dates'        => 0,
				'use_prefix'       => FALSE,
				'class'            => '',
				'split_every'      => 100,
				'legacy_numbering' => FALSE // Can be overriden per post.
			);
			$this->settings = wp_parse_args( $settings, $defaults );

			$this->query_args['category'] = implode( ',', $category_IDs );

			// Legacy numbering: Increment chapters count without parsing
			// their actual chapter numbers from their titles.
			$legacy_numbering = get_post_meta( $post->ID, 'legacy_numbering' );
			if ( isset( $legacy_numbering[0] ) && ( 'true' === $legacy_numbering[0] ) )
			{
				$this->settings['legacy_numbering'] = TRUE; // Override.
			}

			// Transients.
			if ( FALSE !== $this->transient['enabled'] )
			{
				$this->transient['_name'] = $this->transient['prefix'] . 'P' . $post->ID;
				$this->load_validation_data();
			}

			$this->settings['_loaded'] = TRUE;
		}
	}

	private function header_div( string $from_number = '', string $to_number = '' )
	{
		$from = ( '' !== $from_number )
			? ( '<span class="from">' . $from_number . '</span>' )
			: FALSE;
		$to = $from_number !== $to_number
			? ( '<span class="to">' . $to_number . '</span>' )
			: FALSE;

		$separator = ' – ';
		$title = ( FALSE !== $from || FALSE !== $to )
			? ( $from . ( $to ? $separator . $to : '' ) )
			: FALSE;

		$prefix = $this->settings['use_prefix']
			? ( ( $from && !$to ) ? __( 'Chapter' ) : __( 'Chapters' ) ) . ' '
			: '';

		return '
			<div class="cl-header is-noselect">
				<i class="icon-cl-toggle"></i>'
				. ( $prefix ?: '' ) . ( $title ?: '' ) . '
			</div>
		';
	}

	private function get_chapters_list()
	{
		$lists = '';

		// Special chapters count (those whose numbers cannot be parsed with smart numbering).
		$specials_count = 0;
		// Parts count (those whose numbers match the previous iteration).
		$parts_count    = 0;

		$first_number = NULL;
		$last_number  = NULL;

		$size = count( $this->chapters );
		if ( 0 !== $size )
		{
			// Chapter number.
			$chapter_number = 0;

			// Last chapter number for smart numbering.
			$last_chapter_number = '';

			// Tag to be excluded out from title before parsing their titles.
			$tag_exclude = NULL;
			if ( FALSE === $this->settings['legacy_numbering'] )
			{
				// Use string with smart numbering.
				$chapter_number = '';

				// Get name of first/primary category ID as replacement tag.
				$category_ID = explode( ',', $this->query_args['category'], 1 )[0];
				$category = get_category( $category_ID );
				// Only use if the category's name contains digits.
				if ( isset( $category ) && '' !== $category->name && ( 1 === preg_match( '/\d/', $category->name ) ) )
				{
					$tag_exclude = $category->name;
				}
			}

			// Split once reached, then increment with $this->settings['split_every'].
			$split_on = NULL;

			// Index starting from which their dates should be fetched.
			$get_dates_on = $this->settings['get_dates'] > 0
				? $size - $this->settings['get_dates']
				: -1;

			// Set to TRUE if new <ol> tag needs to initiated in the next iteration.
			$new_ol_tag = TRUE;

			$body = '';
			$from_number = NULL;
			$to_number   = NULL;

			// We add an extra iteration for last closing.
			for ( $i = 0; $i < $size + 1; ++$i )
			{
				if ( isset( $this->chapters[$i] ) )
				{
					// WP_Post Object
					$chapter = &$this->chapters[$i];
					$li = '';

					$li .= '
						<li><a href="' . get_permalink( $chapter->ID ) . '">'
							. $chapter->post_title .
							'</a>';

					if ( $i >= $get_dates_on )
						$li .= '<span datetime="' . get_the_date( 'c', $chapter->ID ) . '">'
							. sprintf(
								__( '%1$s at %2$s' ),
								get_the_date( '', $chapter->ID ),
								get_the_time( '', $chapter->ID )
								) .
							'</span>';

					$li .= '</li>';

					if ( $this->settings['legacy_numbering'] )
					{
						$chapter_number++;
					}
					else
					{
						$post_title = $chapter->post_title;
						if ( NULL !== $tag_exclude )
						{
							$post_title = str_replace( $tag_exclude, '', $post_title );
						}

						$matches = array();
						$match = preg_match(
							'/0?(\d+[a-zA-Z]|\d+\.\d{1}|\d+)([^a-zA-Z\d]|$)/',
							$post_title,
							$matches
						);

						if ( 1 === $match )
						{
							$parsed_number = $matches[1];

							// Increase chapter count if it is the same, 1 number higher, or first valid chapter.
							$diff = ( int ) $parsed_number - ( int ) $chapter_number;
							if ( ( 1 === $diff ) || ( '' === $chapter_number ) )
							{
								$last_chapter_number = $chapter_number;
								$chapter_number = $parsed_number;
							}
							else if ( 0 === $diff )
							{
								$chapter_number = $parsed_number;
								$parts_count++;
							}
							else
							{
								$specials_count++;
							}
						}
						else
						{
							$specials_count++;
						}
					}
				}

				// Close <ol> tag.
				$is_next_block = ( NULL !== $split_on ) && ( ( int ) $chapter_number === ( $split_on + 1 ) );
				if ( $is_next_block || ( $i === $size ) ) {
					$body .= '</ol>';

					if ( '' !== $chapter_number )
					{
						$to_number = $is_next_block
							? ( $last_chapter_number ?: $chapter_number - 1 )
							: $chapter_number;
						// If "from" chapter number for current block had not been initiated yet (with 1 chapter).
						if ( NULL === $from_number )
						{
							$from_number = $to_number;
						}
					}

					$lists .= '
						<div class="cl-block">
							' . $this->header_div( ( string ) $from_number, ( string ) $to_number ) . '
							' . $body . '
						</div>
					';

					if ( $is_next_block )
					{
						$split_on += $this->settings['split_every'];
						$from_number = NULL;
						$to_number   = NULL;

						$body = '';
						$new_ol_tag = TRUE;
					}

					if ( $i === $size )
					{
						$last_number = $to_number;
						// If "first" chapter number for current series has not been initiated yet (with 1 chapter).
						if ( NULL === $first_number )
						{
							$first_number = $last_number;
						}
					}
				}

				// Initiate new <ol> tag if needed.
				if ( TRUE === $new_ol_tag )
				{
					$body .= '<ol class="cl-body">';
					$new_ol_tag = FALSE;
				}

				// Finally add <li> to body block.
				$body .= $li;

				// Initiate "from" chapter number for current block.
				if ( NULL === $from_number )
				{
					$valid_count = $this->settings['legacy_numbering'] || ( '' !== $chapter_number );
					if ( $valid_count )
					{
						$from_number = $chapter_number;
						// Initiate "first" chapter number for current series.
						if ( NULL === $first_number )
						{
							$first_number = $from_number;
							// Initiate index on which to split chapters.
							if ( NULL === $split_on )
							{
								$split_on = max( 1, ( int ) $chapter_number ) + $this->settings['split_every'] - 1;
							}
						}
					}
				}
			}
		}
		else
		{
			$lists .= '
				<p class="cl-no-chapters">
					' . __( 'There are no chapters for this series yet. Check back soon! 😉' ) . '
				</p>
			';
		}

		$total = ( int ) $last_number - ( int ) $first_number + 1;

		$head = '';
		if ( TRUE === $this->settings['legacy_numbering'] )
		{
			$head = $size . ' ' . ( $size === 1 ? __( 'Chapter' ) : __( 'Chapters' ) );
		}
		else
		{
			if ( NULL === $first_number )
			{
				$head = __( 'Chapters' );
			}
			else if ( ( int ) $first_number === 1 )
			{
				$head = $total . ' ' . ( $total === 1 ? __( 'Chapter' ) : __( 'Chapters' ) );
			}
			else
			{
				$head = ( $total === 1 ? __( 'Chapter' ) : __( 'Chapters' ) ) . ' '
					. $first_number . ( $first_number !== $last_number ? ' - ' . $last_number : '' );
			}
		}

		$info = '';
		if ( ( $size > 0 ) && ( ( NULL === $first_number ) || ( $size > $total ) ) )
		{
			$parts_info = '';
			if ( $parts_count > 0 )
			{
				$parts_info = sprintf(
					( 1 === $parts_count
						? __( '<b>%1$s</b> post is an additional part of a chapter.</b> ')
						: __( '<b>%1$s</b> posts are additional parts of some chapters.</b> ') ),
					$parts_count
				);
			}

			$specials_info = '';
			if ( $specials_count > 0 )
			{
				$specials_info = sprintf(
					( 1 === $specials_count
						? __( '<b>%1$s</b> post is special or announcement.</b> ')
						: __( '<b>%1$s</b> posts are specials or announcements.</b> ') ),
					$specials_count
				);
			}

			$size_info = '';
			if ( $size > $total )
			{
				$size_info = sprintf(
					( 1 === $total
						? __( 'There is <b>%1$s</b> chapter, but <b>%2$s</b> posts in total.' )
						: __( 'There are <b>%1$s</b> chapters, but <b>%2$s</b> posts in total.' ) ),
					$total,
					$size
				);
			}
			else
			{
				$size_info = sprintf(
					__( 'There are <b>%1$s</b> posts in total.' ),
					$size
				);
			}

			$info = '
				<div class="cl-info">
					<p>' . $size_info . '</p>
					' . ( $parts_info ? '<p>' . $parts_info .'</p>' : '' ) . '
					' . ( $specials_info ? '<p>' . $specials_info .'</p>' : '' ) . '
				</div>
			';
		}

		if ( $this->settings['get_dates'] && $this->settings['get_dates'] < $size )
		{
			// Also use .cl-block for end notes,
			// to make sure they're put on top with descending sorting direction.
			$lists .= '
				<div class="cl-block cl-dates-info">
					<p>' .
						sprintf(
							__( 'n.b. Only %1$s recent posts will have their publish dates displayed.' ),
							$this->settings['get_dates']
						) . '</p>
				</div>
			';
		}

		/**
		 * <!--sse-->...<!--/sse--> is Cloudflare's Server Side Excludes (SSE)
		 * https://support.cloudflare.com/hc/en-us/articles/200170036-What-does-Server-Side-Excludes-SSE-do-
		 * This feature needs to be manually enabled from Cloudflare to activate.
		 */
		return '
			<!--sse-->
			<div class="cl-container ' . ( $this->settings['class'] ?: '' ) . '"
				data-collapse-title="' . __( 'Click to collapse this list' ) . '"
				data-expand-title="' . __( 'Click to expand this list' ) . '">

				<div class="cl-head">
					<h4 class="cl-title">
						' . $head . '
						<i class="icon-sort-toggle is-hidden"
							title="' . __( 'Toggle sorting direction' ) . '"></i>
					</h4>
					' . $info . '
				</div>

				<div class="cl-lists">
					' . $lists . '
				</div>

			</div>
			<!--/sse-->
		';
	}

	private function get_transient()
	{
		if ( FALSE !== $this->transient['enabled'] && isset( $this->transient['_validation'] ) )
		{
			$transient = get_transient( $this->transient['_name'] );
			if ( isset( $transient['_validation'] ) )
			{
				$diff = array_diff_assoc( $this->transient['_validation'], $transient['_validation'] );
				if ( !count( $diff ) )
				{
					return $transient['chapters_list'];
				}
			}
		}

		return FALSE;
	}

	private function set_transient( string $chapters_list )
	{
		if ( FALSE !== $this->transient['enabled'] && isset( $this->transient['_validation'] ) )
		{
			$set = set_transient(
				$this->transient['_name'],
				array(
					'_validation'   => $this->transient['_validation'],
					'chapters_list' => $chapters_list
				),
				$this->transient['expiration']
			);
			return $set;
		}

		return FALSE;
	}

	public function render( $content )
	{
		if ( is_page_template( 'tableOfContent.php' ) && in_the_loop() && is_main_query() )
		{
			global $post;
			$this->load_settings( $post, array(
				'get_dates'  => 10, // N amount of recent posts whose dates will be queried.
				'use_prefix' => TRUE // Consult $this->start_block().
			) );

			// Proceed only IF all settings have been properly loaded.
			if ( TRUE === $this->settings['_loaded'] )
			{
				// Use Transient (cache) if exists.
				$transient = $this->get_transient();
				if ( FALSE !== $transient )
				{
					return $content . $transient;
				}
				else
				{
					if ( $this->load_chapters() )
					{
						$chapters_list = $this->get_chapters_list();
						if ( NULL !== $chapters_list )
						{
							// Store Transient (cache).
							$this->set_transient( $chapters_list );
							return $content . $chapters_list;
						}
					}
				}
			}
		}

		return $content;
	}

}
