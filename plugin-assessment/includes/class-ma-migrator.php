<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class MA_Migrator
{
    private const VERSION_OPTION = 'mini_assessment_db_version';
    private const DELETE_OPTION = 'mini_assessment_delete_data_on_uninstall';

    public static function activate(): void
    {
        self::migrate();
        self::add_capabilities();

        if (false === get_option(self::DELETE_OPTION, false)) {
            add_option(self::DELETE_OPTION, '0', '', false);
        }
    }

    public static function deactivate(): void
    {
        // Business data and capabilities intentionally remain on deactivation.
    }

    public static function maybe_upgrade(): void
    {
        $installed = (string) get_option(self::VERSION_OPTION, '0.0.0');
        if (version_compare($installed, MINI_ASSESSMENT_DB_VERSION, '<')) {
            self::migrate();
        }
    }

    private static function migrate(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $assessment = $wpdb->prefix . 'assessment';
        $questions = $wpdb->prefix . 'assessment_questions';
        $answers = $wpdb->prefix . 'assessment_answers';

        $queries = array(
            "CREATE TABLE {$assessment} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                title varchar(255) NOT NULL,
                description text NULL,
                status varchar(20) NOT NULL DEFAULT 'draft',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_status (status),
                KEY idx_created_at (created_at)
            ) {$charset};",
            "CREATE TABLE {$questions} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                assessment_id bigint(20) unsigned NOT NULL,
                content text NOT NULL,
                sort_order int(10) unsigned NOT NULL DEFAULT 0,
                status varchar(20) NOT NULL DEFAULT 'active',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_assessment_id (assessment_id),
                KEY idx_assessment_status (assessment_id, status),
                KEY idx_assessment_sort (assessment_id, sort_order)
            ) {$charset};",
            "CREATE TABLE {$answers} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                question_id bigint(20) unsigned NOT NULL,
                content text NOT NULL,
                score decimal(10,2) NOT NULL DEFAULT 0,
                sort_order int(10) unsigned NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY idx_question_id (question_id),
                KEY idx_question_sort (question_id, sort_order)
            ) {$charset};",
        );

        foreach ($queries as $query) {
            dbDelta($query);
        }

        update_option(self::VERSION_OPTION, MINI_ASSESSMENT_DB_VERSION, false);
    }

    private static function add_capabilities(): void
    {
        $administrator = get_role('administrator');
        if (! $administrator) {
            return;
        }

        foreach (array('read_assessments', 'edit_assessments', 'publish_assessments', 'delete_assessments') as $capability) {
            $administrator->add_cap($capability);
        }
    }
}

