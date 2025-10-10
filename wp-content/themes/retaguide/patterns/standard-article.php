<?php
/**
 * Title: Standard Article
 * Slug: retaguide/standard-article
 * Categories: retaguide-news
 * Block Types: core/post-content
 * Description: Default news article scaffold with hero, byline, disclaimer, and related posts.
 */
?>
<!-- wp:cover {"overlayColor":"light-blue","minHeight":380,"contentPosition":"bottom left"} -->
<div class="wp-block-cover" style="min-height:380px"><span aria-hidden="true" class="wp-block-cover__background has-light-blue-background-color has-background"></span><div class="wp-block-cover__inner-container"><!-- wp:heading {"level":1,"fontSize":"xxl"} -->
<h1 class="wp-block-heading has-xxl-font-size">Article Title</h1>
<!-- /wp:heading --></div></div>
<!-- /wp:cover -->

<!-- wp:group {"layout":{"type":"constrained","justifyContent":"left"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"fontSize":"sm"} -->
<p class="has-sm-font-size">By <strong>Author Name</strong> · <time>October 10, 2025</time></p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[disclaimer]
<!-- /wp:shortcode -->

<!-- wp:paragraph -->
<p>Lead paragraph introducing the latest Retatrutide research development.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Key Findings</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Summarise the primary data points, results, or regulatory updates that matter for clinicians and researchers.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Expert Commentary</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Provide context from field experts, include quotes, and link to supporting materials.</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"opacity":"css"} -->
<hr class="wp-block-separator has-css-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Related News</h3>
<!-- /wp:heading -->

<!-- wp:query {"query":{"perPage":3,"offset":0,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"list"},"namespace":"retaguide/related-news"} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:post-title {"isLink":true,"fontSize":"lg"} /-->
<!-- wp:post-date {"textAlign":"left","fontSize":"sm"} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->
