<?php
/**
 * KOBA-I Audio: EDD Bridge
 * Connects the Studio to Easy Digital Downloads for seamless checkout.
 */

if (!defined('ABSPATH')) exit;

class Koba_EDD_Bridge {

    // CHANGE THIS: The ID of your generic "Transcription Service" product in EDD
    private $service_product_id = 336081; 

    public function __construct() {
        // 1. Hook into EDD Payment Completion
        add_action('edd_complete_purchase', [$this, 'handle_purchase_completion']);
        
        // 2. Handle "Add to Cart" via AJAX (from your Studio)
        add_action('wp_ajax_koba_add_to_cart', [$this, 'ajax_add_to_cart']);
    }

    /**
     * AJAX: Adds the Transcription Credits to the Cart
     */
    public function ajax_add_to_cart() {
        // Security Check
        check_ajax_referer('k_studio_nonce', 'nonce');

        $book_id = intval($_POST['post_id']);
        $hours   = intval($_POST['hours']); // Calculated by JS

        if (!$this->service_product_id) {
            wp_send_json_error("Service Product ID not configured in Code.");
        }

        // Add to EDD Cart
        // We add 'options' to store the Book ID so we know what this is for later.
        $cart_item_data = [
            'koba_book_id' => $book_id
        ];

        // edd_add_to_cart( $download_id, $options )
        $added = edd_add_to_cart($this->service_product_id, [
            'quantity' => $hours,
            'item_price' => 0.50, // Optional: Force price if needed, or set in EDD
            'options' => $cart_item_data
        ]);

        if ($added) {
            wp_send_json_success(['redirect' => edd_get_checkout_uri()]);
        } else {
            wp_send_json_error("Could not add to cart.");
        }
    }

    /**
     * THE TRIGGER: Runs when money successfully hits your bank.
     */
    public function handle_purchase_completion($payment_id) {
        $cart_items = edd_get_payment_meta_cart_details($payment_id);

        foreach ($cart_items as $item) {
            // Check if this item has our Book ID attached
            if (isset($item['item_number']['options']['koba_book_id'])) {
                $book_id = intval($item['item_number']['options']['koba_book_id']);
                
                // 1. Mark Book as "Paid"
                update_post_meta($book_id, '_koba_payment_status', 'paid');
                update_post_meta($book_id, '_koba_payment_id', $payment_id);

                // 2. Trigger the Transcription Engine!
                // We instantiate the engine and start the job immediately
                $this->trigger_auto_transcription($book_id);
            }
        }
    }

    private function trigger_auto_transcription($book_id) {
        // Logic to get file URL and call AI Engine...
        // We can build this next. For now, it marks it as PAID.
        $processor = new Koba_AI_Processor();
        // You would need to add a method to processor to "Start All Chapters"
    }
}

// Initialize
new Koba_EDD_Bridge();