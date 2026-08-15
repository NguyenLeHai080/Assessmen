<?php
/**
 * Plugin Name: Mini Assessment
 * Description: Headless assessment REST API for WordPress.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Assessment Candidate
 * Text Domain: mini-assessment
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('MINI_ASSESSMENT_VERSION', '1.0.0');
define('MINI_ASSESSMENT_DB_VERSION', '1.0.0');
define('MINI_ASSESSMENT_FILE', __FILE__);
define('MINI_ASSESSMENT_DIR', plugin_dir_path(__FILE__));

require_once MINI_ASSESSMENT_DIR . 'includes/class-ma-logger.php';
require_once MINI_ASSESSMENT_DIR . 'includes/class-ma-migrator.php';
require_once MINI_ASSESSMENT_DIR . 'includes/class-ma-repository.php';
require_once MINI_ASSESSMENT_DIR . 'includes/class-ma-rest-controller.php';
require_once MINI_ASSESSMENT_DIR . 'includes/class-ma-frontend.php';
require_once MINI_ASSESSMENT_DIR . 'includes/class-ma-plugin.php';

register_activation_hook(__FILE__, array('MA_Migrator', 'activate'));
register_deactivation_hook(__FILE__, array('MA_Migrator', 'deactivate'));

MA_Plugin::instance()->run();
