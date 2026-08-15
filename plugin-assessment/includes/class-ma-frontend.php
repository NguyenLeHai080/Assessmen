<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class MA_Frontend
{
    private const SCRIPT_HANDLE = 'mini-assessment-app';

    public function register(): void
    {
        add_shortcode('mini_assessment_app', array($this, 'render'));
        add_filter('script_loader_tag', array($this, 'module_script_tag'), 10, 3);
    }

    public function render(): string
    {
        $manifest = $this->manifest();
        if (is_wp_error($manifest)) {
            if (current_user_can('manage_options')) {
                return '<p>' . esc_html($manifest->get_error_message()) . '</p>';
            }
            return '<p>' . esc_html__('The assessment application is unavailable.', 'mini-assessment') . '</p>';
        }

        $entry = $manifest['index.html'] ?? null;
        if (! is_array($entry) || empty($entry['file'])) {
            return '<p>' . esc_html__('The assessment application build is invalid.', 'mini-assessment') . '</p>';
        }

        $base_url = plugin_dir_url(MINI_ASSESSMENT_FILE) . 'assets/app/';
        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            $base_url . ltrim((string) $entry['file'], '/'),
            array(),
            MINI_ASSESSMENT_VERSION,
            true
        );

        $settings = array(
            'apiBase' => esc_url_raw(rest_url('assessment/v1')),
            'nonce' => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
            'isAuthenticated' => is_user_logged_in(),
        );
        foreach (($entry['css'] ?? array()) as $index => $css_file) {
            wp_enqueue_style(
                self::SCRIPT_HANDLE . '-' . (int) $index,
                $base_url . ltrim((string) $css_file, '/'),
                array(),
                MINI_ASSESSMENT_VERSION
            );
        }

        $settings_json = wp_json_encode(
            $settings,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        return sprintf(
            '<script id="mini-assessment-settings">window.miniAssessmentSettings=%s;</script><div id="root" class="mini-assessment-root"></div>',
            $settings_json ?: '{}'
        );
    }

    public function module_script_tag(string $tag, string $handle, string $src): string
    {
        if (self::SCRIPT_HANDLE !== $handle) {
            return $tag;
        }

        return sprintf(
            '<script type="module" src="%s" id="%s-js"></script>',
            esc_url($src),
            esc_attr($handle)
        );
    }

    /** @return array<string, mixed>|WP_Error */
    private function manifest(): array|WP_Error
    {
        $path = MINI_ASSESSMENT_DIR . 'assets/app/.vite/manifest.json';
        if (! is_readable($path)) {
            return new WP_Error(
                'assessment_frontend_missing',
                __('Frontend build is missing. Run npm run build in the frontend directory.', 'mini-assessment')
            );
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            return new WP_Error('assessment_frontend_manifest_invalid', __('Frontend manifest is invalid.', 'mini-assessment'));
        }

        return $decoded;
    }
}
