<?php
/**
 * Show previous and next post links under chapters. Simplified.
 * Inspired by Anas Mir's WP Post Navigation.
 * by Bobby Wibowo
 */

class Chapter_Navigation
{
	private $links = array(
		'previous_chapter' => array(
			'url'    => NULL,
			'icon'   => 'icon-left-open',
			'title'  => '',
			'class'  => 'previous-chapter',
			'simple' => ''
		),
		'table_of_content' => array(
			'url'    => NULL,
			'icon'   => 'icon-list-alt',
			'title'  => '',
			'class'  => 'table-of-content',
			'simple' => ''
		),
		'next_chapter' => array(
			'url'    => NULL,
			'icon'   => 'icon-right-open',
			'title'  => '',
			'class'  => 'next-chapter',
			'simple' => ''
		)
	);

	private function get_table_of_contents ( $categories )
	{
		$table_of_contents = array();

		for ( $i = 0, $size = count( $categories ); $i < $size; ++$i )
		{
			if ( property_exists( $categories[$i], 'cat_ID' ) )
			{
				$cat_ID = $categories[$i]->cat_ID;
				$gets = get_pages( array(
					'meta_key'    => 'category_id',
					'meta_value'  => $cat_ID,
					'post_status' => 'publish'
				) );

				if ( is_array( $gets ) && count( $gets ) )
				{
					$table_of_contents += $gets;
				}
			}
		}

		return $table_of_contents;
	}

	private function load_settings( $post )
	{
		$is_reversed = TRUE;
		$within_category = TRUE;

		$next_post = get_next_post( $within_category );
		$pre_post = get_previous_post( $within_category );
		$simples = array(
			'previous_chapter' => __( 'Previous Chapter' ),
			'table_of_content' => __( 'Table of Content' ),
			'next_chapter'     => __( 'Next Chapter' ),
			'coming_soon'      => __( 'Coming soon!' )
		);

		$pre_nav = '';
		if ( $pre_post && ( $pre_post->ID !== '' ) )
		{
			$position = $is_reversed ? 'previous_chapter' : 'next_chapter';
			$this->links[$position]['url']    = get_permalink( $pre_post->ID );
			$this->links[$position]['title']  = $pre_post->post_title;
			$this->links[$position]['simple'] = $simples[$position];
		}

		$next_nav = '';
		$position = $is_reversed ? 'next_chapter' : 'previous_chapter';
		if ( $next_post && ( $next_post->ID !== '' ) )
		{
			$this->links[$position]['url']   = get_permalink( $next_post->ID );
			$this->links[$position]['title'] = $next_post->post_title;
			$this->links[$position]['simple'] = $simples[$position];
		}
		else
		{
			$this->links[$position]['icon'] = 'icon-clock';
			$this->links[$position]['simple'] = $simples['coming_soon'];
		}

		// Get post's category IDs.
		$categories = get_the_category( $post->ID );

		if ( count( $categories ) )
		{
			// Get table of contents of all category IDs.
			$table_of_contents = $this->get_table_of_contents( $categories );

			// Try to filter out mismatched templates.
			if ( count( $table_of_contents ) > 1 )
			{
				$table_of_contents = array_filter(
					$table_of_contents,
					function ( $value )
					{
						$metadata = get_post_meta( $value->ID );

						if ( $metadata && is_array( $metadata['_wp_page_template' ] ) )
						{
							return in_array( 'tableOfContent.php', $metadata['_wp_page_template' ], TRUE );
						}

						return FALSE;
					}
				);
			}

			// Use the first match if exists.
			if ( isset( $table_of_contents[0] ) )
			{
				$this->links['table_of_content']['url']   = get_permalink( $table_of_contents[0]->ID );
				$this->links['table_of_content']['title'] = $table_of_contents[0]->post_title;
				$this->links['table_of_content']['simple'] = $simples['table_of_content'];
			}
		}
	}

	private function get_value( &$var, $default = NULL )
	{
		return isset( $var ) ? $var : $default;
	}

	private function get_navigation_block ( $options = array() )
	{
		$class			  = $this->get_value( $options['class'], '' );
		$hide_icons       = $this->get_value( $options['hide_icons'], FALSE );
		$hide_titles      = $this->get_value( $options['hide_titles'], FALSE );
		$simple_titles    = $this->get_value( $options['simple_titles'], FALSE );
		$simple_toc_title = $this->get_value( $options['simple_toc_title'], TRUE );

		$result = '';

		$keys = array( 'previous_chapter', 'table_of_content', 'next_chapter' );

		for ( $i = 0, $size = count( $keys ); $i < $size; ++$i )
		{
			$key = $keys[$i];

			if ( $this->links[$key]['url'] || ( 'next_chapter' === $key ) )
			{
				$config = $this->links[$key];

				$is_simple = ( 'table_of_content' === $key )
					? $simple_toc_title
					: $simple_titles;

				$icon = ( $hide_icons || !$config['icon'] )
					? ''
					: '<i class="' . $config['icon'] . '"></i>';

				$title = ( $is_simple || !$config['title'] )
					? $config['simple']
					: $config['title'];

				$text = $hide_titles
					? ''
					: $title;

				$a_href_attr = $config['url']
					? ( 'href="' . $config['url'] . '"' )
					: 'disabled';

				$a_title = ( $hide_titles || $is_simple )
					? ( $config['title'] ?: $config['simple'] )
					: $config['simple'];

				$a_text = ( 'next_chapter' === $key )
					? ( $text . $icon )
					: ( $icon . $text );

				$result .= '
					<a class="cn-col is-noselect ' . ( $config['class'] ?: '' ) . '"
						' . $a_href_attr . '
						title="' . $a_title . '">' . $a_text . '</a>
				';
			}
		}

		return '
			<div class="chapter-navigation ' . ( $class ?: '' ) . '">
				' . $result . '
			</div>
		';
	}

	public function render( $content )
	{
		if ( is_single() && in_the_loop() && is_main_query() )
		{
			global $post;
			$this->load_settings( $post );

			$position = 'both';

			$top_options = array(
				'class'       => 'is-top',
				'hide_titles' => TRUE
			);

			$bottom_options = array(
				'simple_titles' => TRUE
			);

			switch( $position )
			{
				case 'top':
					return
						$this->get_navigation_block( $top_options )
						. $content;
				break;
				case 'bottom':
					return
						$content .
						$this->get_navigation_block( $bottom_options );
				break;
				case 'both':
				default:
					return
						$this->get_navigation_block( $top_options )
						. $content .
						$this->get_navigation_block( $bottom_options );
				break;
			}
		}

		return $content;
	}

}
