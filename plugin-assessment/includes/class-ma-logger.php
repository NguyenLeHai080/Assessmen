<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class MA_Logger
{
    /** @param array<string, scalar|null> $context */
    public function info(string $action, array $context = array()): void
    {
        $this->write('info', $action, $context);
    }

    /** @param array<string, scalar|null> $context */
    public function error(string $action, array $context = array()): void
    {
        $this->write('error', $action, $context);
    }

    /** @param array<string, scalar|null> $context */
    private function write(string $level, string $action, array $context): void
    {
        if (! defined('WP_DEBUG_LOG') || ! WP_DEBUG_LOG) {
            return;
        }

        $safe_context = array_intersect_key(
            $context,
            array_flip(array('user_id', 'resource_id', 'result', 'error_code', 'request_id'))
        );
        $parts = array('level=' . $level, 'action=' . sanitize_key($action));

        foreach ($safe_context as $key => $value) {
            $parts[] = sanitize_key((string) $key) . '=' . sanitize_text_field((string) $value);
        }

        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log('[mini-assessment] ' . implode(' ', $parts));
    }
}

