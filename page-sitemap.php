<?php
/*
Plugin Name: Page Sitemap
Description: Create an html sitemap in a page with the [page-sitemap] shortcode. Automatically checks for nofollow links against Yoast and All-In-One-SEO.
Plugin URI: https://wordpress.org/extend/plugins/page-sitemap
Author: CustomScripts
Author URI: http://profiles.wordpress.org/customscripts
Version: 1.2
License: GPL2
*/

/* Enqueue Stylesheet and Attach to Shortcode */
/* https://developer.wordpress.org/reference/functions/wp_enqueue_style/ */
function cst_smp_enqueue_sitemap_style(){
    wp_enqueue_style( 'cst_sitemap_style', plugins_url( '/assets/css/sitemap.css', __FILE__ ), array(), '20180605', false );
}
add_action( 'wp_enqueue_scripts', 'cst_smp_enqueue_sitemap_style' );

/* Enqueue JS and Attach to Shortcode */
/* https://developer.wordpress.org/reference/functions/wp_enqueue_script/ */

function cst_smp_enqueue_sitemap_script(){
    wp_enqueue_script( 'cst_sitemap_script', plugins_url( '/assets/js/sitemap.js', __FILE__ ), array('jquery'), '20180605', true );
}
add_action( 'wp_enqueue_scripts', 'cst_smp_enqueue_sitemap_script' );

function cst_smp_check_sitemap_list( $sitemap_list, $url_val ){
    $include = false;
    if ( in_array( $url_val, $sitemap_list ) ){
        //do nothing
    } else {
        $include = true;
    }
    return $include;
}

/* Add Shortcode */
function cst_smp_sitemap_page($atts, $content = null){
    $child_list = array();
    $sitemap_list = array();
    
    $args = array(
        'sort_order' => 'asc',
        'sort_column' => 'post_title',
        'hierarchical' => 1,
        'exclude' => '',
        'include' => '',
        'meta_key' => '',
        'meta_value' => '',
        'authors' => '',
        'child_of' => 0,
        'parent' => 0,
        'exclude_tree' => '',
        'number' => '',
        'offset' => 0,
        'post_type' => 'page',
        'post_status' => 'publish'
    );
    
    //Open Wrapper
    $content .= '<div id="cst-sitemap-wrapper">';
    $content .= '<ul id="cst-sitemap" class="cst-sitemap-list">';
    
    $top_level_pages = get_pages( $args );
    $top_page;
    
    $lvl = 0;
    
    foreach ( $top_level_pages as $top_page ){
        //Get the ID of each top level page
        
        $pid = $top_page->ID;
        $ptitle = $top_page->post_title;
        $purl = get_page_link( $pid );
        //Check to see if there are children. If not, add to the array. Otherwise, level-down and run the function.
        
        $kids = cst_smp_get_desc_pages( $pid );
        
        if ( $kids == false ){
            $content .= '<li class="cst-top-level-list-item cst-sitemap-list-item"><a href="' . $purl . '"  alt="' . $ptitle. '">' . $ptitle . '</a></li>';
        } else {
            $my_wp_query = new WP_Query();
            $all_wp_pages = $my_wp_query->query(array('post_type' => 'page', 'posts_per_page' => '-1', 'orderby' => 'title', 'order' => 'ASC'));
            $page_children = get_page_children( $pid, $all_wp_pages );
            $content .= '<li class="cst-top-level-list-item cst-sitemap-list-item"><a href="' . $purl . '"  alt="' . $ptitle . '">' . $ptitle . '</a>';
            $content .= '<ul class="cst-child-list">';
            foreach ( $page_children as $chld ){
                $cid = $chld->ID;
                $ctitle = $chld->post_title;
                $clink = get_page_link ( $cid );
                $grandkids = cst_smp_get_desc_pages( $cid );
                if ( $grandkids == false ){
                    
                    if ( cst_smp_check_sitemap_list( $sitemap_list, $clink ) == true && cst_smp_check_aio_exclusion( $cid ) == false && cst_smp_check_yoast_exclusion( $cid ) == false ){
                        array_push( $sitemap_list, $clink );
                        $content .= '<li class="cst-sitemap-child-item"><a href="' . $clink . '"  alt="' . $ctitle . '">' . $ctitle . '</a></li>';
                    }
                } else {
                    $grand_children = get_page_children( $cid, $all_wp_pages );
                    
                    if ( cst_smp_check_sitemap_list( $sitemap_list, $clink ) == true && cst_smp_check_aio_exclusion( $cid ) == false && cst_smp_check_yoast_exclusion( $cid ) == false ){
                        array_push( $sitemap_list, $clink );
                        $content .= '<li class="cst-sitemap-child-item"><a href="' . $clink . '"  alt="' . $ctitle . '">' . $ctitle . '</a></li>';
                    }
                    
                    $content .= '<ul class="cst-grandchild-list">';
                    foreach ( $grand_children as $gchld ){
                        $gid = $gchld->ID;
                        $gtitle = $gchld->post_title;
                        $glink = get_page_link( $gid );
                        if ( cst_smp_check_sitemap_list( $sitemap_list, $glink ) == true && cst_smp_check_aio_exclusion( $gid ) == false && cst_smp_check_yoast_exclusion( $gid ) == false ){
                            array_push( $sitemap_list, $glink );
                            $content .= '<li class="cst-sitemap-grandchild-item"><a href="' . $glink . '"  alt="' . $gtitle . '">' . $gtitle . '</a></li>';
                        }
                    }
                    $content .= '</ul></li>';
                    
                }
            }
        }
        $content .= '</li>';
        
    }
    
    //Close List and Wrapper
    $content .= '</ul>';
    $content .= '</div>';
    
    return $content;
}

add_shortcode('page-sitemap', 'cst_smp_sitemap_page');


function cst_smp_get_desc_pages( $pid ){
    
    //Loop through each top-level page to find direct descendents.
    $desc_args = array(
        'sort_order' => 'asc',
        'sort_column' => 'post_title',
        'hierarchical' => 0,
        'exclude' => '',
        'include' => '',
        'meta_key' => '',
        'meta_value' => '',
        'authors' => '',
        'child_of' => $pid,
        'parent' => -1,
        'exclude_tree' => '',
        'number' => '',
        'offset' => 0,
        'post_type' => 'page',
        'post_status' => 'publish'
    );
    
    $desc_pages = get_pages( $desc_args );
    
    return $desc_pages;
}


function cst_smp_check_aio_exclusion( $post_id ){
    // Relevant Values for All-in-One Sitemap
    //      NOINDEX This page/post:     _aioseop_noindex (on/off)
    //      NOFOLLOW This page/post:    _aioseop_nofollow (on/off)
    //      Exclude from Sitemap:       _aioseop_sitemap_exclude (on/off)
    
    $excluded = false;
    
    //Exclude is only pro version.
    if ( get_post_meta( $post_id, '_aioseop_sitemap_exclude', true ) == 'on' ){
        $excluded = true;
    }
    
    //Comment out if using the pro version. If not, nofollow is the best test for excluding from sitemap.
    if ( get_post_meta( $post_id, '_aioseop_nofollow', true ) == 'on' ){
        $excluded = true;
    }
    
    return $excluded;
}


function cst_smp_check_yoast_exclusion( $post_id ){
    // Relevant Values for Yoast SEO
    // Allow search engines to index this post (NOINDEX):     _yoast_wpseo_meta-robots-noindex (1/0)
    // NOFOLLOW links from this page/post:    _yoast_wpseo_meta-robots-nofollow (1/0)
    
    $excluded = false;
    
    //NoIndex
    if ( get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true ) == 1 ){
        $excluded = true;
    }
    
    //Comment out if only want to rely on NoIndex
    if ( get_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', true ) == 1 ){
        $excluded = true;
    }
    
    return $excluded;
}