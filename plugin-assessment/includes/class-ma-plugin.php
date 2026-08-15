<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class MA_Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    public function run(): void
    {
        add_action('plugins_loaded', array($this, 'maybe_migrate'));
        add_action('rest_api_init', array($this, 'register_routes'));
        (new MA_Frontend())->register();
    }

    public function maybe_migrate(): void
    {
        MA_Migrator::maybe_upgrade();
    }

    public function register_routes(): void
    {
        $repository = new MA_Repository();
        $controller = new MA_REST_Controller($repository, new MA_Logger());
        $controller->register_routes();
    }
}
