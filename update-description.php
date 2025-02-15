<?php
/*
Plugin Name: Update Products Button
Description: Displays a table of products in the admin dashboard with an update button for each product.
Version: 1.1
Author: Payal Sharma
*/

require_once 'vendor/autoload.php'; // Adjust the path as needed

use Claude\Claude3Api\Client;
use Claude\Claude3Api\Config;

// Add menu for the product update page
add_action('admin_menu', 'upb_add_admin_menu');

function upb_add_admin_menu() {
    add_menu_page(
        'Update Products',  // Page title
        'Update Products',  // Menu title
        'manage_options',   // Capability
        'update-products',  // Menu slug
        'upb_render_products_table', // Callback function
        'dashicons-update', // Icon
        25                  // Position in menu
    );
}

// Render the table of products with update buttons
function upb_render_products_table() {
    ?>
    <div class="wrap">
        <h1>Update Product Descriptions</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Product Title</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch all products
                $args = array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'numberposts' => -1
                );

                $products = get_posts($args);

                if (!empty($products)) {
                    foreach ($products as $product) {
                        ?>
                        <tr>
                            <td><?php echo esc_html($product->post_title); ?></td>
                            <td><?php echo esc_html(wp_trim_words($product->post_content, 15, '...')); ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="product_id" value="<?php echo esc_attr($product->ID); ?>">
                                    <input type="submit" name="update_description" class="button button-primary" value="Update Description">
                                </form>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="3">No products found.</td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php

    // Handle form submission for a specific product
    if (isset($_POST['update_description']) && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        upb_update_product_description($product_id);
    }
}

// Function to update a single product description
function upb_update_product_description($product_id) {
    $product = get_post($product_id);

    if ($product) {
        $current_description = $product->post_content;
        $message = "Please write a detailed and unique product description including long paragraph and specifications for the following product:
        Product Name: " . $product->post_title . "
        Current Description: " . $current_description . "
        Specifications should be formatted as follows:
        
        1. Basic Configuration
            - Feature 1
            - Feature 2
        
        2. Additional Features
            - Feature detail
            - Feature detail

        Make sure the specifications are concise and structured and there should be atleast 5 specifications. Do not include the product title, product description, introductory sentences, 
        or any additional text and heading in description like product name or product description in the description.";

        // Get updated description from Claude API
        $updated_description = send_message_to_claude($message);

        // Update product with the new description
        $product_update = array(
            'ID'           => $product->ID,
            'post_content' => $updated_description
        );
        wp_update_post($product_update);

        echo '<div class="updated"><p>' . esc_html($product->post_title) . ' description updated successfully!</p></div>';
    }
}

// Function to communicate with Claude API
function send_message_to_claude($message) {
    $config = new Config('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');
    $client = new Client($config);

    try {
        $response = $client->chat([
            'model' => 'claude-3-opus-20240229',
            'maxTokens' => 4096,
            'messages' => [
                ['role' => 'user', 'content' => $message],
            ]
        ]);

        $content = $response->getContent();
        return $content[0]['text'] ?? 'Failed to retrieve updated description.';
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}
?>
