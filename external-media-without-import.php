<?php
/*
Plugin Name: Secure External Media without Import
Description: Safely add external images to the media library without importing them to your WordPress site. 
Includes comprehensive security measures for URL validation, image handling, and protection against common vulnerabilities.
Version: 1.3.0
Original Author: Zhixiang Zhu
Modified by: Claude with security enhancements
License: GPLv3
*/

namespace secure_emwi;

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

class SecureExternalMedia {
    private $allowed_mime_types = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    private $max_image_size = 20971520; // 20MB in bytes
    private $nonce_action = 'secure_emwi_nonce';
    private $nonce_name = 'secure_emwi_security';

    public function __construct() {
        // Initialize hooks
        add_action('admin_enqueue_scripts', [$this, 'init_resources']);
        add_action('admin_menu', [$this, 'add_submenu']);
        add_action('post-plupload-upload-ui', [$this, 'post_upload_ui']);
        add_action('post-html-upload-ui', [$this, 'post_upload_ui']);
        add_action('wp_ajax_add_external_media_without_import', [$this, 'handle_ajax_request']);
        add_action('admin_post_add_external_media_without_import', [$this, 'handle_form_submission']);
        
        // Add security headers
        add_action('send_headers', [$this, 'add_security_headers']);
    }

    public function init_resources() {
        if (!current_user_can('upload_files')) {
            return;
        }

        wp_register_style(
            'secure-emwi-css',
            plugins_url('/css/external-media.css', __FILE__),
            [],
            '1.3.0'
        );
        wp_enqueue_style('secure-emwi-css');

        wp_register_script(
            'secure-emwi-js',
            plugins_url('/js/external-media.js', __FILE__),
            ['jquery'],
            '1.3.0',
            true
        );
        
        // Add nonce to JavaScript
        wp_localize_script('secure-emwi-js', 'secureEmwiSettings', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($this->nonce_action),
            'max_size' => $this->max_image_size
        ]);
        
        wp_enqueue_script('secure-emwi-js');
    }

    public function add_security_headers() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header("Content-Security-Policy: default-src 'self'; img-src * data: 'unsafe-inline'");
    }

    private function validate_url($url) {
        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Parse URL and check components
        $parsed_url = parse_url($url);
        if (!$parsed_url || !isset($parsed_url['scheme']) || 
            !in_array(strtolower($parsed_url['scheme']), ['http', 'https'])) {
            return false;
        }

        // Check for blocked domains (customize this list)
        $blocked_domains = [
            'localhost',
            '127.0.0.1',
            '[::1]',
            // Add more blocked domains as needed
        ];

        $domain = strtolower($parsed_url['host']);
        if (in_array($domain, $blocked_domains)) {
            return false;
        }

        return true;
    }

    private function validate_image($url) {
        // First check if URL is valid
        if (!$this->validate_url($url)) {
            return false;
        }

        // Configure context with timeout and user agent
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'WordPress/Secure-External-Media-Plugin'
            ]
        ]);

        // Safely check headers
        $headers = get_headers($url, 1, $context);
        if (!$headers) {
            return false;
        }

        // Check content type
        $content_type = is_array($headers['Content-Type']) 
            ? $headers['Content-Type'][0] 
            : $headers['Content-Type'];
            
        if (!in_array(strtolower($content_type), $this->allowed_mime_types)) {
            return false;
        }

        // Check file size
        $content_length = isset($headers['Content-Length']) ? (int)$headers['Content-Length'] : 0;
        if ($content_length > $this->max_image_size) {
            return false;
        }

        // Verify image dimensions and type
        $image_info = @getimagesize($url);
        if (!$image_info) {
            return false;
        }

        return [
            'width' => $image_info[0],
            'height' => $image_info[1],
            'mime' => $image_info['mime']
        ];
    }

    public function handle_ajax_request() {
        // Verify nonce and capabilities
        if (!check_ajax_referer($this->nonce_action, $this->nonce_name, false)) {
            wp_send_json_error('Invalid security token.');
            exit;
        }

        if (!current_user_can('upload_files')) {
            wp_send_json_error('Insufficient permissions.');
            exit;
        }

        $urls = $this->sanitize_urls($_POST['urls'] ?? '');
        if (empty($urls)) {
            wp_send_json_error('No valid URLs provided.');
            exit;
        }

        $results = $this->process_urls($urls);
        wp_send_json_success($results);
    }

    private function sanitize_urls($urls_input) {
        $urls = explode("\n", sanitize_textarea_field($urls_input));
        return array_filter(array_map('trim', $urls));
    }

    private function process_urls($urls) {
        $results = [
            'successful' => [],
            'failed' => []
        ];

        foreach ($urls as $url) {
            $image_info = $this->validate_image($url);
            
            if (!$image_info) {
                $results['failed'][] = $url;
                continue;
            }

            $attachment_id = $this->create_attachment($url, $image_info);
            if ($attachment_id) {
                $results['successful'][] = [
                    'url' => $url,
                    'attachment_id' => $attachment_id
                ];
            } else {
                $results['failed'][] = $url;
            }
        }

        return $results;
    }

    private function create_attachment($url, $image_info) {
        $filename = wp_basename($url);
        
        // Create attachment post
        $attachment = [
            'guid' => esc_url_raw($url),
            'post_mime_type' => sanitize_mime_type($image_info['mime']),
            'post_title' => sanitize_file_name(
                preg_replace('/\.[^.]+$/', '', $filename)
            ),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment);
        if (is_wp_error($attachment_id)) {
            return false;
        }

        // Create attachment metadata
        $attachment_metadata = [
            'width' => intval($image_info['width']),
            'height' => intval($image_info['height']),
            'file' => $filename,
            'sizes' => ['full' => [
                'width' => intval($image_info['width']),
                'height' => intval($image_info['height']),
                'file' => $filename,
                'mime-type' => sanitize_mime_type($image_info['mime'])
            ]]
        ];

        wp_update_attachment_metadata($attachment_id, $attachment_metadata);
        return $attachment_id;
    }
}

// Initialize plugin
function initialize_plugin() {
    new SecureExternalMedia();
}
add_action('init', 'secure_emwi\initialize_plugin');
