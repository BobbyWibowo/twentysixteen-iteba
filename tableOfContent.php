<?php
/**
* Template Name: Table of Content
* Template Post Type: page
*
* The template for displaying Table of Content pages.
*
* @package WordPress
* @subpackage Twenty_Fourteen
* @since Twenty Fourteen 1.0
*/

// Do not cache this page (W3 Total Cache).
// We already use Transients to cache Chapters List.
if ( FALSE === defined( 'DONOTCACHEPAGE' ) )
	define( 'DONOTCACHEPAGE', 1 );

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<?php
		// Start the loop.
		while ( have_posts() ) :
			the_post();

			// Include the page content template.
			get_template_part( 'template-parts/content', 'page' );

			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}

			// End the loop.
		endwhile;
		?>

	</main><!-- .site-main -->

	<?php get_sidebar( 'content-bottom' ); ?>

</div><!-- .content-area -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
