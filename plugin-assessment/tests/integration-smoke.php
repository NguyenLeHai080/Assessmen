<?php
/**
 * Run inside a WordPress environment:
 * php wp-content/plugins/plugin-assessment/tests/integration-smoke.php
 */

declare(strict_types=1);

require dirname(__DIR__, 4) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';

/** @param array<string, mixed> $body */
function ma_test_request(string $method, string $path, array $body = array()): WP_REST_Response
{
    $request = new WP_REST_Request($method, $path);
    if ($body) {
        $request->set_header('Content-Type', 'application/json');
        $request->set_body_params($body);
    }

    return rest_do_request($request);
}

function ma_assert_status(int $expected, WP_REST_Response $response, string $case): void
{
    $actual = $response->get_status();
    if ($expected !== $actual) {
        throw new RuntimeException(
            sprintf('%s: expected HTTP %d, received %d: %s', $case, $expected, $actual, wp_json_encode($response->get_data()))
        );
    }
    echo sprintf("PASS %-32s HTTP %d\n", $case, $actual);
}

$assessment_id = 0;
$subscriber_id = 0;

try {
    foreach (get_users(array('search' => 'assessment_smoke_*', 'search_columns' => array('user_login'))) as $stale_user) {
        wp_delete_user((int) $stale_user->ID);
    }

    wp_set_current_user(0);
    ma_assert_status(
        401,
        ma_test_request('POST', '/assessment/v1/assessments', array('title' => 'Unauthorized test')),
        'anonymous create'
    );

    $subscriber_id = wp_insert_user(
        array(
            'user_login' => 'assessment_smoke_' . wp_generate_password(8, false),
            'user_pass' => wp_generate_password(24, true, true),
            'user_email' => 'assessment-smoke-' . time() . '@example.test',
            'role' => 'subscriber',
        )
    );
    if (is_wp_error($subscriber_id)) {
        throw new RuntimeException($subscriber_id->get_error_message());
    }
    wp_set_current_user((int) $subscriber_id);
    ma_assert_status(
        403,
        ma_test_request('POST', '/assessment/v1/assessments', array('title' => 'Forbidden test')),
        'subscriber create'
    );

    $admin = get_user_by('login', 'assessment_admin');
    if (! $admin) {
        throw new RuntimeException('Local administrator assessment_admin was not found.');
    }
    wp_set_current_user((int) $admin->ID);

    ma_assert_status(
        422,
        ma_test_request('POST', '/assessment/v1/assessments', array('title' => '   ')),
        'invalid assessment'
    );
    ma_assert_status(404, ma_test_request('GET', '/assessment/v1/assessments/999999'), 'missing assessment');

    $create = ma_test_request(
        'POST',
        '/assessment/v1/assessments',
        array('title' => 'Disposable integration test', 'description' => 'Removed automatically.', 'status' => 'draft')
    );
    ma_assert_status(201, $create, 'create assessment');
    $assessment_id = (int) $create->get_data()['data']->id;

    ma_assert_status(
        200,
        ma_test_request('PATCH', '/assessment/v1/assessments/' . $assessment_id, array('title' => 'Updated integration test')),
        'patch assessment'
    );

    $question = ma_test_request(
        'POST',
        '/assessment/v1/questions',
        array('assessment_id' => $assessment_id, 'content' => 'Disposable question', 'sort_order' => 1)
    );
    ma_assert_status(201, $question, 'create question');
    $question_id = (int) $question->get_data()['data']->id;

    $answer = ma_test_request(
        'POST',
        '/assessment/v1/answers',
        array('question_id' => $question_id, 'content' => 'Disposable answer', 'score' => 1, 'sort_order' => 1)
    );
    ma_assert_status(201, $answer, 'create answer');

    ma_assert_status(
        200,
        ma_test_request('DELETE', '/assessment/v1/assessments/' . $assessment_id),
        'delete assessment'
    );

    global $wpdb;
    $question_count = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}assessment_questions WHERE assessment_id = %d", $assessment_id)
    );
    $answer_count = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}assessment_answers WHERE question_id = %d", $question_id)
    );
    if (0 !== $question_count || 0 !== $answer_count) {
        throw new RuntimeException('Cascade delete left orphan records.');
    }
    echo "PASS cascade delete integrity\n";
    $assessment_id = 0;
    echo "ALL INTEGRATION SMOKE TESTS PASSED\n";
} finally {
    if ($assessment_id > 0) {
        $cleanup_admin = get_user_by('login', 'assessment_admin');
        wp_set_current_user($cleanup_admin ? (int) $cleanup_admin->ID : 0);
        ma_test_request('DELETE', '/assessment/v1/assessments/' . $assessment_id);
    }
    if ($subscriber_id > 0) {
        wp_delete_user((int) $subscriber_id);
    }
}
