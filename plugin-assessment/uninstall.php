<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if ('1' !== get_option('mini_assessment_delete_data_on_uninstall', '0')) {
    return;
}

global $wpdb;

$tables = array(
    $wpdb->prefix . 'assessment_answers',
    $wpdb->prefix . 'assessment_questions',
    $wpdb->prefix . 'assessment',
);

foreach ($tables as $table) {
    // Table names are constructed from the trusted WordPress prefix and fixed suffixes.
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

delete_option('mini_assessment_db_version');
delete_option('mini_assessment_delete_data_on_uninstall');

$administrator = get_role('administrator');
if ($administrator) {
    foreach (array('read_assessments', 'edit_assessments', 'publish_assessments', 'delete_assessments') as $capability) {
        $administrator->remove_cap($capability);
    }
}

