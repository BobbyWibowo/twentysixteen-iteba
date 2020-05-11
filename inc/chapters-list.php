<?php
/**
 * Chapters list in Table of Content pages.
 * by Bobby Wibowo
 */

class Chapters_List
{
	private $chapters = array();

	private $get_dates = FALSE;
	private $query_fields = array(
		'id',
		'post_title'
	);

	private $transient_prefix = '';
	private $transient_expiration = 3600; // in seconds, 3600 = 1 hour.

	public function __construct( $version = NULL )
	{
		if ( NULL === $version )
		{
			$this->transient_prefix = 'ti_chapters_list';
		}
		else
		{
			$this->transient_prefix .= 'ti_' . $version . '_chapters_list_';
		}
	}

	private function load_chapters( $category_IDs )
	{
		$gets = get_posts( array(
			'numberposts' => -1,
			// 'category'    => implode( ',', $category_IDs ),
			'category__in' => $category_IDs,
			'order'       => 'ASC',
			'post_type'   => 'post',
			'post_status' => 'publish',
			'fields'      => $this->query_fields
		) );

		$size = count( $gets );
		if ( is_array( $gets ) && $size )
		{
			for ( $i = 0; $i < $size; ++$i )
			{
				$this->chapters[] = array(
					'id'    => $gets[$i]->ID,
					'title' => $gets[$i]->post_title
				);
			}
		}
	}

	private function get_value( &$var, $default = NULL )
	{
		return isset( $var ) ? $var : $default;
	}

	private function load_settings( $post, $options = array() )
	{
		$this->get_dates = $this->get_value( $options['get_dates'], 0 );

		$category_IDs = get_post_meta( $post->ID, 'category_id' );

		if ( count( $category_IDs ) )
		{
			$this->load_chapters( $category_IDs );
		}
	}

	private function start_block( $from = NULL, $to = NULL )
	{
		$w_from = $from ? '<span class="from">' . $from . '</span>' : '';
		$separator = ' - ';
		$w_to = $to ? '<span class="to">' . $to . '</span>' : '';

		$title = ( $w_from || $w_to )
			? $w_from . ( $w_to ? $separator . $w_to : '' )
			: '';

		$header = $title
			? ( '
				<div class="cl-header is-noselect">
					<i class="icon-cl-toggle"></i>
					' . $title . '
				</div>
				' )
			: '';

		$body_class = $title
			? 'cl-body'
			: 'cl-body is-single';

		return '
			<div class="cl-block">
				' . $header . '
				<ol class="' . $body_class . '">
		';
	}

	private function end_block()
	{
		return '
				</ol><!-- .cl-body -->
			</div><!-- .cl-block -->
		';
	}

	private function get_chapters_list( $options = array() )
	{
		$class       = $this->get_value( $options['class'], '' );
		$split_every = $this->get_value( $options['split_every'], 100 );

		$lists = '';

		$size = count( $this->chapters );
		if ( $size )
		{
			$j = 0;
			$x = $this->get_dates > 0 ? $size - $this->get_dates : -1;

			for ( $i = 0; $i < $size; ++$i )
			{
				if ( $j === 0 )
				{
					if ( $size > $split_every )
					{
						$remaining = $size - $i;
						$from = $i + 1;
						$upto = $i + ( $remaining <= $split_every ? $remaining : $split_every );
						$lists .= $this->start_block( $i + 1, $from !== $upto ? $upto : NULL );
					}
					else
					{
						$lists .= $this->start_block();
					}
				}

				$chapter = $this->chapters[$i];

				$lists .= '
					<li><a href="' . get_permalink( $chapter['id'] ) . '">'
						. $chapter['title'] .
						'</a>';

				if ( $i >= $x )
					$lists .= '<span datetime="' . get_the_date( 'c', $chapter['id'] ) . '">'
						. sprintf(
							__( '%1$s at %2$s' ),
							get_the_date( '', $chapter['id'] ),
							get_the_time( '', $chapter['id'] )
							) . '</span>';

				$lists .= '</li>';

				$j++;

				if ( $j === $split_every || $i === $size - 1 )
				{
					$lists .= $this->end_block();
					$j = 0;
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

		$disclaimer = '';
		if ( $this->get_dates && $this->get_dates < $size )
			$disclaimer = '
				<p class="cl-disclaimer">
					' . sprintf(
						__( '<i>n.b. only %1$s recent chapters will have their publish dates displayed.</i>' ),
						$this->get_dates
						) . '
				</p>
			';

		return '
			<div class="cl-container ' . ( $class ?: '' ) . '"
				data-collapse-title="' . __( 'Click to collapse this list' ) . '"
				data-expand-title="' . __( 'Click to expand this list' ) . '">

				<h4 class="cl-head">
					' . $size . ' ' . ( $size === 1 ? __( 'Chapter' ) : __( 'Chapters' ) ) . '
					<i class="icon-sort-toggle is-hidden"
						title="' . __( 'Toggle sorting direction' ) . '"></i>
				</h4>

				<div class="cl-lists">
					' . $lists . '
				</div>

				' . $disclaimer . '

			</div>
		';
	}

	public function render( $content )
	{
		if ( is_page_template( 'tableOfContent.php' ) && in_the_loop() && is_main_query() )
		{
			global $post;

			// Attempt to use cache.
			$transient_name = $this->transient_prefix . $post->ID;
			$transient = get_transient( $transient_name );

			if ( FALSE === $transient )
			{
				$options = array(
					'get_dates' => 10 // N amount of recent posts whose dates will be queried.
				);

				$this->load_settings( $post, $options );

				$chapters_list = $this->get_chapters_list( array(
					'split_every' => 100
				) );

				// Cache this, cause honestly this thing is very slow with large amount of chapters.
				$transient_set = set_transient( $transient_name, $chapters_list, $transient_expiration );

				return $content . $chapters_list;
			}
			else
			{
				return $content . $transient;
			}
		}

		return $content;
	}

}
