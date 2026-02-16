<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. LEGACY VAULT UI
 * (Kept for backward compatibility, though Hub now handles keys)
 */
function koba_render_security_vault() {
    if (isset($_POST['koba_save_security'])) {
        update_option('koba_google_json_path', stripslashes(sanitize_text_field($_POST['google_path'])));
        update_option('koba_google_bucket', sanitize_text_field($_POST['google_bucket']));
        echo '<div class="updated"><p>Vault Locked.</p></div>';
    }
    ?>
    <div class="wrap" style="background:#020617; color:white; padding:40px; border-radius:12px;">
        <h1 style="color:#f97316;">Security Vault</h1>
        <p style="opacity:0.7;">Note: Version 3.7.1+ uses the Secure Hub. These fields are legacy.</p>
        <form method="post">
            <p>JSON Path: <input type="text" name="google_path" value="<?php echo esc_attr(get_option('koba_google_json_path')); ?>" style="width:100%;"></p>
            <p>Bucket Name: <input type="text" name="google_bucket" value="<?php echo esc_attr(get_option('koba_google_bucket')); ?>" style="width:100%;"></p>
            <input type="submit" name="koba_save_security" class="button button-primary" value="SAVE VAULT">
        </form>
    </div>
    <?php
}

/**
 * 2. THE KOBA LOCK SHORTCODE
 * Protects pages so only book owners can see them.
 * USAGE: [koba_lock download_id="123"] ... [/koba_lock]
 */
add_shortcode('koba_lock', function($atts, $content = null) {
    // 1. Get the required Product ID (Download ID)
    $args = shortcode_atts(['download_id' => 0], $atts);
    $download_id = intval($args['download_id']);

    // 2. If user is not logged in -> Show Login Form
    if (!is_user_logged_in()) {
        return '<div class="koba-lock-msg" style="text-align:center; padding:40px; background:#f8f9fa; border-radius:8px;">
            <h3>🔒 Authenticated Access Only</h3>
            <p>Please log in to access your audiobook.</p>
            ' . wp_login_form(['echo' => false]) . '
        </div>';
    }

    // 3. Check if they bought the book
    $user_id = get_current_user_id();
    
    // Check if EDD is active to avoid errors
    if (function_exists('edd_has_user_purchased')) {
        $has_access = edd_has_user_purchased($user_id, $download_id);
    } else {
        $has_access = false; // EDD not active, lock by default
    }

    // 4. Return Content OR "Buy Now" Message
    if ($has_access) {
        return do_shortcode($content); // Show the Player!
    } else {
        return '<div class="koba-lock-msg" style="text-align:center; padding:40px; background:#f8f9fa; border-radius:8px; border:1px solid #ddd;">
            <h3>⛔ No Access</h3>
            <p>You do not own this audiobook yet.</p>
            <a href="/checkout?edd_action=add_to_cart&download_id='.$download_id.'" class="button" style="background:#f97316; color:white; padding:10px 20px; text-decoration:none; border-radius:4px;">Buy Now for $14.99</a>
        </div>';
    }
});