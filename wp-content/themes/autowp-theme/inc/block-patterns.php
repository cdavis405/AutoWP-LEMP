<?php
/**
 * Block Patterns
 *
 * @package AutoWP
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register pattern categories
 */
function autowp_register_pattern_categories() {
    register_block_pattern_category('autowp-news', array(
        'label' => __('News', 'autowp'),
    ));
    
    register_block_pattern_category('autowp-guides', array(
        'label' => __('Guides', 'autowp'),
    ));
    
    register_block_pattern_category('autowp-callouts', array(
        'label' => __('Callouts', 'autowp'),
    ));
}
add_action('init', 'autowp_register_pattern_categories');

/**
 * Register block patterns
 */
function autowp_register_patterns() {
    
    // News: Standard Article Pattern
    register_block_pattern('autowp/news-standard-article', array(
        'title' => __('Standard Article', 'autowp'),
        'description' => __('A complete article layout with hero image, metadata, disclaimer, and related posts', 'autowp'),
        'categories' => array('autowp-news'),
        'content' => '<!-- wp:cover {"url":"https://placehold.co/1200x600","dimRatio":30,"minHeight":400,"minHeightUnit":"px","align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:400px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-30 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="https://placehold.co/1200x600" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
<!-- wp:post-title {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"3rem"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"textColor":"white"} /-->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:post-date {"format":"F j, Y","isLink":false} /-->
<!-- wp:post-author {"showAvatar":false} /-->
<!-- wp:post-terms {"term":"category"} /-->

<!-- wp:separator {"className":"is-style-wide"} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:group {"backgroundColor":"warning-amber","className":"disclaimer-banner","layout":{"type":"constrained"}} -->
<div class="wp-block-group disclaimer-banner has-warning-amber-background-color has-background">
<!-- wp:heading {"level":3} -->
<h3>Medical Disclaimer</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This content is for informational and educational purposes only. This is a generic disclaimer placeholder.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"lead","fontSize":"large"} -->
<p class="lead has-large-font-size"><strong>Article lead paragraph goes here.</strong> This should summarize the main points of the article and hook the reader.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Main Content</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Start writing your article content here. Use headings to structure your content and make it easy to scan.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Subsection</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Add more detailed information in subsections.</p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Related Articles</h3>
<!-- /wp:heading -->

<!-- wp:latest-posts {"displayPostContent":true,"excerptLength":20,"displayPostDate":true,"postLayout":"grid","columns":3,"displayFeaturedImage":true,"featuredImageSizeSlug":"medium"} /-->
</div>
<!-- /wp:group -->',
    ));
    
    // Guides: Step-by-step Protocol
    register_block_pattern('autowp/guide-protocol', array(
        'title' => __('Step-by-step Protocol', 'autowp'),
        'description' => __('A structured guide with numbered steps and safety information', 'autowp'),
        'categories' => array('autowp-guides'),
        'content' => '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:post-title {"level":1} /-->

<!-- wp:group {"backgroundColor":"light-blue","className":"last-reviewed","layout":{"type":"constrained"}} -->
<div class="wp-block-group last-reviewed has-light-blue-background-color has-background">
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Last Reviewed: January 2024 | Version 1.0</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"backgroundColor":"warning-amber","className":"disclaimer-banner","layout":{"type":"constrained"}} -->
<div class="wp-block-group disclaimer-banner has-warning-amber-background-color has-background">
<!-- wp:heading {"level":3} -->
<h3>Important Notice</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This protocol is for research purposes only. Always follow applicable regulations and safety guidelines.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:heading -->
<h2>Overview</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Provide a brief overview of what this protocol covers and who it\'s for.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Prerequisites</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>Prerequisite 1</li>
<li>Prerequisite 2</li>
<li>Prerequisite 3</li>
</ul>
<!-- /wp:list -->

<!-- wp:heading -->
<h2>Step-by-Step Instructions</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3>Step 1: Preparation</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Detailed instructions for step 1.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Step 2: Execution</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Detailed instructions for step 2.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>Step 3: Follow-up</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Detailed instructions for step 3.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Safety Considerations</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Important safety information and precautions.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
    ));
    
    // Guides: FAQ Guide
    register_block_pattern('autowp/guide-faq', array(
        'title' => __('FAQ Guide', 'autowp'),
        'description' => __('Question and answer format for common inquiries', 'autowp'),
        'categories' => array('autowp-guides'),
        'content' => '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:post-title {"level":1} /-->

<!-- wp:paragraph {"className":"lead","fontSize":"large"} -->
<p class="lead has-large-font-size">Find answers to frequently asked questions.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"backgroundColor":"warning-amber","className":"disclaimer-banner","layout":{"type":"constrained"}} -->
<div class="wp-block-group disclaimer-banner has-warning-amber-background-color has-background">
<!-- wp:heading {"level":3} -->
<h3>Disclaimer</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This information is for educational purposes only and does not constitute medical advice.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:heading -->
<h2>General Questions</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3>What is AutoWP?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Answer to the question goes here with detailed information.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>How does it work?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Explanation of the mechanism of action.</p>
<!-- /wp:paragraph -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:heading -->
<h2>Research Questions</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3>What studies have been conducted?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Information about research studies and clinical trials.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3>What are the results?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Summary of research findings.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
    ));
    
    // Guides: Overview Guide
    register_block_pattern('autowp/guide-overview', array(
        'title' => __('Overview Guide', 'autowp'),
        'description' => __('Comprehensive overview with key sections', 'autowp'),
        'categories' => array('autowp-guides'),
        'content' => '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
<!-- wp:post-title {"level":1} /-->

<!-- wp:post-featured-image {"aspectRatio":"16/9","height":"400px"} /-->

<!-- wp:group {"backgroundColor":"light-blue","className":"last-reviewed","layout":{"type":"constrained"}} -->
<div class="wp-block-group last-reviewed has-light-blue-background-color has-background">
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Last Reviewed: January 2024 | Reading Time: 10 minutes</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"backgroundColor":"warning-amber","className":"disclaimer-banner","layout":{"type":"constrained"}} -->
<div class="wp-block-group disclaimer-banner has-warning-amber-background-color has-background">
<!-- wp:heading {"level":3} -->
<h3>Disclaimer</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>This guide is for informational purposes only. Consult with qualified professionals before making any decisions.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"lead","fontSize":"large"} -->
<p class="lead has-large-font-size"><strong>Introduction paragraph</strong> that provides context and sets expectations for what the reader will learn.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Key Concepts</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Explain the fundamental concepts that readers need to understand.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Background</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Provide historical context and development information.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Current Status</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Describe the current state of research and development.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2>Future Directions</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Discuss potential future developments and areas of research.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
    ));
    
    // Callout: Safety Notice
    register_block_pattern('autowp/callout-safety', array(
        'title' => __('Safety Notice', 'autowp'),
        'description' => __('Important safety warning callout box', 'autowp'),
        'categories' => array('autowp-callouts'),
        'content' => '<!-- wp:group {"backgroundColor":"alert-red","className":"callout callout-safety","style":{"border":{"left":{"color":"var:preset|color|alert-red","width":"4px"},"top":{},"right":{},"bottom":{}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group callout callout-safety has-alert-red-background-color has-background" style="border-left-color:var(--wp--preset--color--alert-red);border-left-width:4px">
<!-- wp:heading {"level":3,"textColor":"alert-red"} -->
<h3 class="has-alert-red-color has-text-color">⚠️ Safety Warning</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Important safety information goes here. Use this callout for critical warnings and precautions.</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
    ));
    
    // Callout: Key Takeaways
    register_block_pattern('autowp/callout-takeaways', array(
        'title' => __('Key Takeaways', 'autowp'),
        'description' => __('Highlighted summary of main points', 'autowp'),
        'categories' => array('autowp-callouts'),
        'content' => '<!-- wp:group {"backgroundColor":"light-blue","className":"callout callout-takeaways","style":{"border":{"left":{"color":"var:preset|color|primary-blue","width":"4px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group callout callout-takeaways has-light-blue-background-color has-background" style="border-left-color:var(--wp--preset--color--primary-blue);border-left-width:4px">
<!-- wp:heading {"level":3,"textColor":"primary-blue"} -->
<h3 class="has-primary-blue-color has-text-color">💡 Key Takeaways</h3>
<!-- /wp:heading -->
<!-- wp:list -->
<ul>
<li>Important point 1</li>
<li>Important point 2</li>
<li>Important point 3</li>
<li>Important point 4</li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->',
    ));
    
    // Callout: Further Reading
    register_block_pattern('autowp/callout-reading', array(
        'title' => __('Further Reading', 'autowp'),
        'description' => __('Links to additional resources', 'autowp'),
        'categories' => array('autowp-callouts'),
        'content' => '<!-- wp:group {"backgroundColor":"light-green","className":"callout callout-reading","style":{"border":{"left":{"color":"var:preset|color|dark-green","width":"4px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group callout callout-reading has-light-green-background-color has-background" style="border-left-color:var(--wp--preset--color--dark-green);border-left-width:4px">
<!-- wp:heading {"level":3,"textColor":"dark-green"} -->
<h3 class="has-dark-green-color has-text-color">📚 Further Reading</h3>
<!-- /wp:heading -->
<!-- wp:list -->
<ul>
<li><a href="#">Related Article 1</a></li>
<li><a href="#">Related Article 2</a></li>
<li><a href="#">External Resource</a></li>
<li><a href="#">Research Paper</a></li>
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:group -->',
    ));
}
add_action('init', 'autowp_register_patterns');
