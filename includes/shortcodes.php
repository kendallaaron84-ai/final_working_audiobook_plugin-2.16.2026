<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode('koba_bookshelf', function($atts) {
    if (!class_exists('Easy_Digital_Downloads')) return '<p style="color:white;">Store not active.</p>';

    // 1. Get Audiobooks
    $args = [ 'post_type' => 'download', 'posts_per_page' => -1, 'post_status' => 'publish' ];
    $books = get_posts($args);
    
    if (empty($books)) return '<p style="color:white;">No audiobooks found.</p>';

    $user_id = get_current_user_id();
    $output = '<div class="koba-bookshelf-grid">';

    foreach ($books as $book) {
        $id = $book->ID;
        $title = get_the_title($id);
        $slug = $book->post_name; 
        $price = edd_get_download_price($id);
        $image_url = get_the_post_thumbnail_url($id, 'large') ?: 'https://via.placeholder.com/300?text=Audiobook';
        
        $has_access = is_user_logged_in() && edd_has_user_purchased($user_id, $id);

        if ($has_access) {
            $css_class = 'koba-book-owned';
            $status_icon = '✅ Owned';
            $btn_html = '<a href="/listen/'. $slug .'" class="k-btn-listen">🎧 Listen Now</a>';
        } else {
            $css_class = 'koba-book-locked';
            $status_icon = '🔒 Locked';
            $btn_html = '<a href="/checkout?edd_action=add_to_cart&download_id='.$id.'" class="k-btn-buy">Get for $'.$price.'</a>';
        }

        $output .= '
        <div class="koba-book-card '. $css_class .'">
            <div class="k-book-cover" style="background-image:url('.$image_url.')"><span class="k-book-badge">'. $status_icon .'</span></div>
            <div class="k-book-details"><h4>'. $title .'</h4>'. $btn_html .'</div>
        </div>';
    }
    $output .= '</div><style>.koba-bookshelf-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; } .koba-book-card { background:#fff; border-radius:8px; overflow:hidden; } .k-book-cover { height:250px; background-size:cover; position:relative; } .koba-book-locked .k-book-cover { filter:grayscale(100%); opacity:0.8; } .k-book-badge { position:absolute; top:10px; right:10px; background:rgba(0,0,0,0.8); color:white; padding:4px 8px; border-radius:4px; font-size:11px; } .k-book-details { padding:15px; text-align:center; } .k-btn-listen { display:block; background:#f97316; color:white; padding:10px; text-decoration:none; border-radius:4px; font-weight:bold; } .k-btn-buy { display:block; background:#334155; color:white; padding:10px; text-decoration:none; border-radius:4px; } </style>';
    return $output;
});