<?php

class Web2go_Pos_Connector_Products
{

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Add tab
        add_action('admin_menu', [$this, 'add_products_tab']);

        // Enqueue JS and CSS
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        // Ajax handler
        add_action('wp_ajax_fetch_products_from_api', [$this, 'fetch_products_from_api']);
    }

    public function add_products_tab()
    {
        add_menu_page(
            'POS Products',
            'POS Products',
            'manage_options',
            'web2go-pos-products',
            [$this, 'render_products_page'],
            'dashicons-products',
            56
        );
    }

    public function render_products_page()
    {
        $license_key = get_option('web2go_license_key');
        $is_license_active = !empty($license_key);
        ?>
        <div class="wrap">
            <h1>POS Products</h1>

            <?php if (!$is_license_active): ?>
                <div
                    style="background: #ffecec; color: #d63638; padding: 10px; border-left: 4px solid #d63638; margin-bottom: 15px;">
                    <strong>Please activate your license first.</strong>
                </div>
            <?php else: ?>
                <div id="progress" style="display: none; margin-bottom: 10px;">
                    <strong>Fetching Products... </strong>
                    <div style="width:100%; background:#dfdfdf; height:20px; border-radius:5px;text-align:center;">
                        <div style="height:100%; width:0%; background:#007cba;color: white;" id="progress-bar"></div>
                    </div>
                </div>
                <button class="button button-primary" id="fetch-products">Fetch Products</button>

                <div id="fetch-response-box"
                    style="display:none; margin-top: 20px; background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h2 style="margin-top: 0;">API Response</h2>
                    <pre id="fetch-status" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function enqueue_admin_scripts()
    {
        wp_enqueue_script(
            $this->plugin_name . '-products',
            plugin_dir_url(__FILE__) . 'js/fetch-products.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script($this->plugin_name . '-products', 'fetch_products_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fetch_products_nonce'),
        ));
    }

    public function fetch_products_from_api()
    {
        try {
            check_ajax_referer('fetch_products_nonce', 'nonce');

            set_error_handler(function ($errno, $errstr, $errfile, $errline) {
                throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
            });

            if (!function_exists('wc_get_product')) {
                wp_send_json_error('WooCommerce not loaded. Please activate WooCommerce.');
            }

            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $domain = "https://sonpra-upholsterysuppliers.co.za/dev/";
            $api_base = "https://web2go.serverfortesting.com/api/products";

            $api = add_query_arg([
                'domain' => $domain,
                'page' => $page,
                'per_page' => 15,
            ], $api_base);

            $response = wp_remote_get($api, [
                'timeout' => 30,
                'sslverify' => false,
            ]);

            if (is_wp_error($response)) {
                wp_send_json_error('API request failed: ' . $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error('JSON decode error: ' . json_last_error_msg());
            }

            if (empty($data['productsmaster'])) {
                wp_send_json_error('No products found on page ' . $page);
            }

            $imported = 0;
            $parent_sku = 0;
            foreach ($data['productsmaster'] as $key => $products_data) {
                $parent_sku = $key;
                foreach ($products_data as $product_data) {
                    $api_product_id = $product_data['id'] ?? null;
                    if (!$api_product_id)
                        continue;

                    // check existing product
                    $args = [
                        'post_type' => 'product',
                        'meta_query' => [
                            [
                                'key' => '_api_product_id',
                                'value' => $api_product_id,
                                'compare' => '=',
                            ],
                        ],
                        'fields' => 'ids',
                    ];
                    $existing_products = get_posts($args);

                    if (!empty($existing_products)) {
                        $product_id = $existing_products[0];
                        $product = wc_get_product($product_id);
                    } else {
                        $product = new WC_Product_Simple();
                        $product_id = 0;
                    }

                    // set product data
                    $product->set_name($product_data['title'] ?? 'Untitled');
                    $product->set_status('publish');
                    $product->set_catalog_visibility('visible');

                    // SKU
                    $sku = isset($product_data['sku']) ? trim($product_data['sku']) : '';

                    if (!empty($sku)) {
                        $existing_id = wc_get_product_id_by_sku($sku);
                        if ($existing_id && (empty($product_id) || $existing_id != $product_id)) {
                            $sku = $sku . '-' . $api_product_id;
                        }
                    } else {
                        $sku = 'API-' . $api_product_id;
                    }

                    // $product->set_post_parent($parent_sku);
                    $product->set_sku($sku);

                    $product->set_description($product_data['web_description'] ?? '');
                    $product->set_regular_price($product_data['price_including'] ?? 0);

                    if (!empty($product_data['sale_price_including'])) {
                        $product->set_sale_price($product_data['sale_price_including']);
                    }

                    $product->set_manage_stock(true);
                    $product->set_stock_quantity($product_data['stock'] ?? 0);
                    $product->set_weight($product_data['weight'] ?? '');

                    $product_id = $product->save();

                    update_post_meta($product_id, '_api_product_id', $api_product_id);

                    // ✅ Handle Product Images (featured + gallery)
                    $image_fields = ['image', 'image_2', 'image_3', 'image_4'];
                    $image_base = "https://touch365api.co.za/uploads/mumz/";
                    $uploaded_images = [];

                    foreach ($image_fields as $index => $field) {
                        if (!empty($product_data[$field])) {
                            $image_url = $image_base . $product_data[$field];
                            $attach_id = $this->attach_product_image($product_id, $image_url);

                            if ($attach_id) {
                                if ($index === 0) {
                                    set_post_thumbnail($product_id, $attach_id);
                                } else {
                                    $uploaded_images[] = $attach_id;
                                }
                            }
                        }
                    }

                    if (!empty($uploaded_images)) {
                        update_post_meta($product_id, '_product_image_gallery', implode(',', $uploaded_images));
                    }

                    $imported++;
                }
            }

            $total_products = (int) ($data['meta']['total'] ?? 0);
            $per_page = (int) ($data['meta']['per_page'] ?? 15);
            $total_pages = $per_page > 0 ? ceil($total_products / $per_page) : 1;

            $progress = $total_pages > 0 ? round(($page / $total_pages) * 100) : 100;

            restore_error_handler();

            wp_send_json_success([
                'message' => "Page {$page} of {$total_pages} imported ({$imported} products).",
                'progress' => $progress,
                'current_page' => $page,
                'total_pages' => $total_pages,
                'imported' => $imported
            ]);

        } catch (\Throwable $e) {
            wp_send_json_error('Critical PHP Error: ' . $e->getMessage());
        }
    }

    private function attach_product_image($product_id, $image_url)
    {
        $upload_dir = wp_upload_dir();
        $image_name = basename($image_url);

        // ✅ Encode special characters in URL (spaces, parentheses, etc.)
        $encoded_image_name = rawurlencode($image_name);
        $encoded_url = str_replace($image_name, $encoded_image_name, $image_url);

        // ✅ Check if image already exists in Media Library
        $existing = get_page_by_title(sanitize_file_name($image_name), OBJECT, 'attachment');
        if ($existing) {
            return $existing->ID;
        }

        // ✅ Check if the image URL is valid
        $response = wp_remote_head($encoded_url);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        // ✅ Download the image
        $image_data = @file_get_contents($encoded_url);
        if (!$image_data) {
            return false;
        }

        // ✅ Save image to uploads folder
        $file = $upload_dir['path'] . '/' . $image_name;
        file_put_contents($file, $image_data);

        $wp_filetype = wp_check_filetype($image_name, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($image_name),
            'post_content' => '',
            'post_status' => 'inherit',
        );

        $attach_id = wp_insert_attachment($attachment, $file, $product_id);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }

}

// laestes code

class Web2go_Pos_Connector_Products_latest
{
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('admin_menu', [$this, 'add_products_tab']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_fetch_products_from_api', [$this, 'fetch_products_from_api']);
    }

    public function add_products_tab()
    {
        add_menu_page(
            'POS Products',
            'POS Products',
            'manage_options',
            'web2go-pos-products',
            [$this, 'render_products_page'],
            'dashicons-products',
            56
        );
    }

    public function render_products_page()
    {
        $license_key = get_option('web2go_license_key');
        $is_license_active = !empty($license_key);
        ?>
        <div class="wrap">
            <h1>POS Products</h1>

            <?php if (!$is_license_active): ?>
                <div style="background: #ffecec; color: #d63638; padding: 10px; border-left: 4px solid #d63638; margin-bottom: 15px;">
                    <strong>Please activate your license first.</strong>
                </div>
            <?php else: ?>
                <div id="progress" style="display: none; margin-bottom: 10px;">
                    <strong>Fetching Products...</strong>
                    <div style="width:100%; background:#dfdfdf; height:20px; border-radius:5px;text-align:center;">
                        <div style="height:100%; width:0%; background:#007cba;color: white;" id="progress-bar"></div>
                    </div>
                </div>
                <button class="button button-primary" id="fetch-products">Fetch Products</button>

                <div id="fetch-response-box"
                     style="display:none; margin-top: 20px; background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h2 style="margin-top: 0;">API Response</h2>
                    <pre id="fetch-status" style="white-space: pre-wrap; word-wrap: break-word;"></pre>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function enqueue_admin_scripts()
    {
        wp_enqueue_script(
            $this->plugin_name . '-products',
            plugin_dir_url(__FILE__) . 'js/fetch-products.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_localize_script($this->plugin_name . '-products', 'fetch_products_obj', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('fetch_products_nonce'),
        ]);
    }

    /**
     * Generate a unique SKU, keeping the API SKU as base
     */
    private function generate_unique_sku($sku, $parent_id = 0)
    {
        if (empty($sku)) {
            $sku = 'SKU-' . time(); // fallback if SKU empty
        }

        $unique_sku = sanitize_text_field($sku);
        $counter = 1;

        while ($existing_id = wc_get_product_id_by_sku($unique_sku)) {
            // if parent_id provided, allow parent to keep same SKU
            if ($parent_id && $existing_id == $parent_id) {
                break;
            }

            // append counter until unique
            $unique_sku = sanitize_text_field($sku . '-' . $counter);
            $counter++;

            // safety: avoid infinite loop
            if ($counter > 1000) {
                $unique_sku = $sku . '-' . time();
                break;
            }
        }

        return $unique_sku;
    }

    /**
     * Ensure attribute taxonomy and terms exist.
     * $attr_slug is without pa_ prefix, e.g. 'size' or 'color'
     * $values is array of term names.
     */
    private function ensure_attribute_and_terms($attr_slug, $values = [])
    {
        if (empty($attr_slug)) return;

        $attribute_label = ucwords(str_replace('_', ' ', $attr_slug));
        $taxonomy = 'pa_' . $attr_slug;

        // Create attribute in WooCommerce if not exists
        if (!function_exists('wc_attribute_taxonomy_id_by_name')) {
            return;
        }

        $attr_tax_id = wc_attribute_taxonomy_id_by_name($taxonomy);
        if (!$attr_tax_id) {
            // create attribute
            $created = wc_create_attribute([
                'slug' => $attr_slug,
                'name' => $attribute_label,
                'type' => 'select',
                'order_by' => 'menu_order',
                'has_archives' => false,
            ]);
            // After creation, register_taxonomy should happen on wp_init by WC.
        }

        // If taxonomy is not registered yet, register it temporarily (so wp_insert_term works)
        if (!taxonomy_exists($taxonomy)) {
            register_taxonomy(
                $taxonomy,
                'product',
                [
                    'hierarchical' => false,
                    'label' => $attribute_label,
                    'query_var' => true,
                    'rewrite' => ['slug' => $taxonomy],
                ]
            );
        }

        // Ensure each term exists (create if not)
        if (!empty($values) && is_array($values)) {
            foreach ($values as $val) {
                $val = trim((string)$val);
                if ($val === '') continue;

                $term = term_exists($val, $taxonomy);
                if ($term === 0 || $term === null) {
                    // create term; wp_insert_term returns array or WP_Error
                    wp_insert_term($val, $taxonomy);
                }
            }
        }
    }


    public function fetch_products_from_api()
    {
        try {
            check_ajax_referer('fetch_products_nonce', 'nonce');

            if (!function_exists('wc_get_product')) {
                wp_send_json_error('WooCommerce not loaded. Please activate WooCommerce.');
            }

            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $domain = "https://sonpra-upholsterysuppliers.co.za/dev/";
            $api_base = "https://web2go.serverfortesting.com/api/products";

            $api_url = add_query_arg([
                'domain'   => $domain,
                'page'     => $page,
                'per_page' => 15,
            ], $api_base);

            $response = wp_remote_get($api_url, [
                'timeout'   => 45,
                'sslverify' => false,
            ]);

            if (is_wp_error($response)) {
                wp_send_json_error('API request failed: ' . $response->get_error_message());
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error('JSON decode error: ' . json_last_error_msg());
            }

            if (empty($data['productsmaster'])) {
                wp_send_json_error('No products found on page ' . $page);
            }

            $imported = 0;

            foreach ($data['productsmaster'] as $parent_sku => $products_group) {

               if (empty($products_group)) continue;

                // Use the first product as parent
                $first_product_data = $products_group[0];

                $existing_parent_id = wc_get_product_id_by_sku($first_product_data['sku']);
                if ($existing_parent_id) {
                    $parent_product = wc_get_product($existing_parent_id);
                    $parent_id = $existing_parent_id;
                } else {
                    $parent_product = new WC_Product_Variable();
                    $parent_product->set_name($first_product_data['title'] ?? 'Untitled');
                    $parent_product->set_status('publish');
                    $parent_product->set_catalog_visibility('visible');
                    $parent_product->set_sku($this->generate_unique_sku($first_product_data['sku'] ?? 'SKU-' . time()));

                    $parent_id = $parent_product->save();
                }

                $all_sizes = [];
                $all_colors = [];

                foreach ($products_group as $index => $product_data) {
                    $api_product_id = $product_data['id'] ?? null;
                    if (!$api_product_id) continue;

                    $size = $product_data['size'] ?? null;
                    $color = $product_data['color'] ?? null;

                    if ($size) $all_sizes[] = $size;
                    if ($color) $all_colors[] = $color;

                    // Skip first product as it is parent
                    if ($index === 0) continue;

                    $existing_products = get_posts([
                        'post_type'   => 'product',
                        'meta_query'  => [[
                            'key'   => '_api_product_id',
                            'value' => $api_product_id,
                            'compare' => '=',
                        ]],
                        'fields'      => 'ids',
                        'numberposts' => 1,
                    ]);

                    if (!empty($existing_products)) {
                        $variation_id = $existing_products[0];
                        $variation = wc_get_product($variation_id);
                        // If product is not a variation object, create new variation instance
                        if (!($variation instanceof WC_Product_Variation)) {
                            $variation = new WC_Product_Variation();
                            $variation->set_parent_id($parent_id);
                        }
                    } else {
                        $variation = new WC_Product_Variation();
                        $variation->set_parent_id($parent_id);
                    }

                    $variation->set_sku($this->generate_unique_sku($product_data['sku'] ?? 'API-' . $api_product_id, $parent_id));
                    $variation->set_name($product_data['title'] ?? 'Untitled');
                    $variation->set_status('publish');
                    $variation->set_catalog_visibility('visible');
                    $variation->set_regular_price($product_data['price_including'] ?? 0);
                    if (!empty($product_data['sale_price_including'])) {
                        $variation->set_sale_price($product_data['sale_price_including']);
                    }

                    $variation->set_manage_stock(true);
                    $variation->set_stock_quantity($product_data['stock'] ?? 0);
                    $variation->set_weight($product_data['weight'] ?? '');
                    $variation->set_description($product_data['web_description'] ?? '');

                    // Ensure attribute taxonomies and terms exist BEFORE assigning to parent or variations
                    $this->ensure_attribute_and_terms('size', $all_sizes);
                    $this->ensure_attribute_and_terms('color', $all_colors);

                    // Correctly assign variation attributes using term slugs (taxonomy-based)
                    $attributes = [];

                    if (!empty($color)) {
                        $term = get_term_by('name', $color, 'pa_color');
                        if (!$term) {
                            // create term and fetch again
                            $inserted = wp_insert_term($color, 'pa_color');
                            if (!is_wp_error($inserted) && !empty($inserted['term_id'])) {
                                $term = get_term($inserted['term_id'], 'pa_color');
                            }
                        }
                        if ($term && !is_wp_error($term)) {
                            $attributes['pa_color'] = $term->slug;
                        }
                    }

                    if (!empty($size)) {
                        $term = get_term_by('name', $size, 'pa_size');
                        if (!$term) {
                            $inserted = wp_insert_term($size, 'pa_size');
                            if (!is_wp_error($inserted) && !empty($inserted['term_id'])) {
                                $term = get_term($inserted['term_id'], 'pa_size');
                            }
                        }
                        if ($term && !is_wp_error($term)) {
                            $attributes['pa_size'] = $term->slug;
                        }
                    }

                    $variation->set_attributes($attributes);

                    $variation_id = $variation->save();
                    update_post_meta($variation_id, '_api_product_id', $api_product_id);

                    // Handle images
                    $image_fields = ['image', 'image_2', 'image_3', 'image_4'];
                    $image_base = "https://touch365api.co.za/uploads/mumz/";
                    $gallery_ids = [];

                    foreach ($image_fields as $idx => $field) {
                        if (!empty($product_data[$field])) {
                            $image_url = $image_base . $product_data[$field];
                            $attach_id = $this->attach_product_image($variation_id, $image_url);
                            if ($attach_id) {
                                if ($idx === 0) set_post_thumbnail($variation_id, $attach_id);
                                else $gallery_ids[] = $attach_id;
                            }
                        }
                    }

                    if (!empty($gallery_ids)) {
                        update_post_meta($variation_id, '_product_image_gallery', implode(',', $gallery_ids));
                    }

                    $imported++;
                }

                // Assign attributes to parent
                $parent_attributes = [];

                if (!empty($all_sizes)) {
                    $size_attr = 'pa_size';
                    // ensure taxonomy & terms exist and terms are registered on product
                    $this->ensure_attribute_and_terms('size', $all_sizes);

                    // set terms (will create missing terms)
                    wp_set_object_terms($parent_id, array_values(array_unique(array_filter($all_sizes))), $size_attr, false);

                    $attr_obj = new WC_Product_Attribute();
                    $attr_obj->set_id(wc_attribute_taxonomy_id_by_name($size_attr));
                    $attr_obj->set_name($size_attr);
                    $attr_obj->set_options(array_values(array_unique(array_filter($all_sizes))));
                    $attr_obj->set_visible(true);
                    $attr_obj->set_variation(true);
                    $parent_attributes[$size_attr] = $attr_obj;
                }

                if (!empty($all_colors)) {
                    $color_attr = 'pa_color';
                    $this->ensure_attribute_and_terms('color', $all_colors);

                    wp_set_object_terms($parent_id, array_values(array_unique(array_filter($all_colors))), $color_attr, false);

                    $attr_obj = new WC_Product_Attribute();
                    $attr_obj->set_id(wc_attribute_taxonomy_id_by_name($color_attr));
                    $attr_obj->set_name($color_attr);
                    $attr_obj->set_options(array_values(array_unique(array_filter($all_colors))));
                    $attr_obj->set_visible(true);
                    $attr_obj->set_variation(true);
                    $parent_attributes[$color_attr] = $attr_obj;
                }

                $parent_product->set_attributes($parent_attributes);
                $parent_product->save();
            }

            // Pagination
            $total_products = (int)($data['meta']['total'] ?? 0);
            $per_page = (int)($data['meta']['per_page'] ?? 15);
            $total_pages = $per_page > 0 ? ceil($total_products / $per_page) : 1;
            $progress = $total_pages > 0 ? round(($page / $total_pages) * 100) : 100;

            wp_send_json_success([
                'message'      => "Page {$page} of {$total_pages} imported ({$imported} products).",
                'progress'     => $progress,
                'current_page' => $page,
                'total_pages'  => $total_pages,
                'imported'     => $imported
            ]);

        } catch (Throwable $e) {
            wp_send_json_error('Critical PHP Error: ' . $e->getMessage());
        }
    }

    private function attach_product_image($product_id, $image_url)
    {
        $upload_dir = wp_upload_dir();
        $image_name = basename($image_url);
        $encoded_url = str_replace($image_name, rawurlencode($image_name), $image_url);

        $existing = get_page_by_title(sanitize_file_name($image_name), OBJECT, 'attachment');
        if ($existing) return $existing->ID;

        $response = wp_remote_head($encoded_url);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $image_data = @file_get_contents($encoded_url);
        if (!$image_data) return false;

        $file_path = $upload_dir['path'] . '/' . $image_name;
        file_put_contents($file_path, $image_data);

        $wp_filetype = wp_check_filetype($image_name, null);
        $attachment = [
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name($image_name),
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $file_path, $product_id);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }
}

