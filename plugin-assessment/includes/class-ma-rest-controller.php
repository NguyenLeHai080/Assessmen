<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class MA_REST_Controller
{
    private const NAMESPACE = 'assessment/v1';
    private const ASSESSMENT_STATUSES = array('draft', 'published', 'archived');
    private const QUESTION_STATUSES = array('active', 'inactive');

    public function __construct(private MA_Repository $repository, private MA_Logger $logger)
    {
    }

    public function register_routes(): void
    {
        register_rest_route(self::NAMESPACE, '/assessments', array(
            array('methods' => WP_REST_Server::READABLE, 'callback' => array($this, 'list_assessments'), 'permission_callback' => '__return_true', 'args' => $this->list_args()),
            array('methods' => WP_REST_Server::CREATABLE, 'callback' => array($this, 'create_assessment'), 'permission_callback' => array($this, 'can_edit'), 'args' => $this->assessment_args(true)),
        ));

        register_rest_route(self::NAMESPACE, '/assessments/(?P<id>\d+)', array(
            array('methods' => WP_REST_Server::READABLE, 'callback' => array($this, 'get_assessment'), 'permission_callback' => '__return_true', 'args' => array('id' => $this->id_arg())),
            array('methods' => array('PUT', 'PATCH'), 'callback' => array($this, 'update_assessment'), 'permission_callback' => array($this, 'can_edit'), 'args' => array_merge(array('id' => $this->id_arg()), $this->assessment_args(false))),
            array('methods' => WP_REST_Server::DELETABLE, 'callback' => array($this, 'delete_assessment'), 'permission_callback' => array($this, 'can_delete'), 'args' => array('id' => $this->id_arg())),
        ));

        register_rest_route(self::NAMESPACE, '/assessments/(?P<id>\d+)/questions', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'list_questions'),
            'permission_callback' => '__return_true',
            'args' => array('id' => $this->id_arg()),
        ));

        register_rest_route(self::NAMESPACE, '/questions', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_question'),
            'permission_callback' => array($this, 'can_edit'),
            'args' => $this->question_args(),
        ));

        register_rest_route(self::NAMESPACE, '/questions/(?P<id>\d+)/answers', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'list_answers'),
            'permission_callback' => '__return_true',
            'args' => array('id' => $this->id_arg()),
        ));

        register_rest_route(self::NAMESPACE, '/answers', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_answer'),
            'permission_callback' => array($this, 'can_edit'),
            'args' => $this->answer_args(),
        ));
    }

    public function can_edit(): bool|WP_Error
    {
        return $this->permission('edit_assessments');
    }

    public function can_delete(): bool|WP_Error
    {
        return $this->permission('delete_assessments');
    }

    private function permission(string $capability): bool|WP_Error
    {
        if (! is_user_logged_in()) {
            return new WP_Error('assessment_unauthorized', __('Authentication is required.', 'mini-assessment'), array('status' => 401));
        }
        if (! current_user_can($capability)) {
            return new WP_Error('assessment_forbidden', __('You do not have permission to perform this action.', 'mini-assessment'), array('status' => 403));
        }
        return true;
    }

    public function list_assessments(WP_REST_Request $request): WP_REST_Response
    {
        $page = (int) $request['page'];
        $per_page = (int) $request['per_page'];
        $status = current_user_can('read_assessments') && $request['status'] ? (string) $request['status'] : 'published';
        $result = $this->repository->list_assessments($page, $per_page, $status);
        $total_pages = (int) ceil($result['total'] / $per_page);
        $response = new WP_REST_Response(array('data' => $result['items'], 'meta' => array('page' => $page, 'per_page' => $per_page, 'total' => $result['total'], 'total_pages' => $total_pages)), 200);
        $response->header('X-WP-Total', (string) $result['total']);
        $response->header('X-WP-TotalPages', (string) $total_pages);
        return $response;
    }

    public function get_assessment(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $assessment = $this->repository->get_assessment((int) $request['id']);
        if (! $assessment || ('published' !== $assessment->status && ! current_user_can('read_assessments'))) {
            return $this->not_found('assessment');
        }
        return new WP_REST_Response(array('data' => $assessment), 200);
    }

    public function create_assessment(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $data = array('title' => trim((string) $request['title']), 'description' => (string) ($request['description'] ?? ''), 'status' => (string) ($request['status'] ?? 'draft'));
        if ('' === $data['title']) {
            return $this->validation_error('title', 'Title is required.');
        }
        if ('published' === $data['status'] && ! current_user_can('publish_assessments')) {
            return new WP_Error('assessment_forbidden', __('You cannot publish assessments.', 'mini-assessment'), array('status' => 403));
        }
        $id = $this->repository->create_assessment($data);
        if (false === $id) {
            return $this->database_error('create_assessment');
        }
        $this->logger->info('create_assessment', array('user_id' => get_current_user_id(), 'resource_id' => $id, 'result' => 'success'));
        return new WP_REST_Response(array('data' => $this->repository->get_assessment($id)), 201);
    }

    public function update_assessment(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request['id'];
        if (! $this->repository->get_assessment($id)) {
            return $this->not_found('assessment');
        }
        $data = array();
        foreach (array('title', 'description', 'status') as $field) {
            if ($request->has_param($field)) {
                $data[$field] = (string) $request[$field];
            }
        }
        if (isset($data['title']) && '' === trim($data['title'])) {
            return $this->validation_error('title', 'Title is required.');
        }
        if (! $data) {
            return $this->validation_error('_request', 'At least one writable field is required.');
        }
        if (isset($data['status']) && 'published' === $data['status'] && ! current_user_can('publish_assessments')) {
            return new WP_Error('assessment_forbidden', __('You cannot publish assessments.', 'mini-assessment'), array('status' => 403));
        }
        if (! $this->repository->update_assessment($id, $data)) {
            return $this->database_error('update_assessment');
        }
        $this->logger->info('update_assessment', array('user_id' => get_current_user_id(), 'resource_id' => $id, 'result' => 'success'));
        return new WP_REST_Response(array('data' => $this->repository->get_assessment($id)), 200);
    }

    public function delete_assessment(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $id = (int) $request['id'];
        if (! $this->repository->get_assessment($id)) {
            return $this->not_found('assessment');
        }
        if (! $this->repository->delete_assessment($id)) {
            return $this->database_error('delete_assessment');
        }
        $this->logger->info('delete_assessment', array('user_id' => get_current_user_id(), 'resource_id' => $id, 'result' => 'success'));
        return new WP_REST_Response(array('data' => array('deleted' => true, 'id' => $id)), 200);
    }

    public function list_questions(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $assessment = $this->repository->get_assessment((int) $request['id']);
        if (! $assessment || ('published' !== $assessment->status && ! current_user_can('read_assessments'))) {
            return $this->not_found('assessment');
        }
        return new WP_REST_Response(array('data' => $this->repository->list_questions((int) $assessment->id, ! current_user_can('read_assessments'))), 200);
    }

    public function create_question(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $assessment_id = (int) $request['assessment_id'];
        if (! $this->repository->get_assessment($assessment_id)) {
            return $this->not_found('assessment');
        }
        $content = trim((string) $request['content']);
        if ('' === $content) {
            return $this->validation_error('content', 'Content is required.');
        }
        $id = $this->repository->create_question(array('assessment_id' => $assessment_id, 'content' => $content, 'sort_order' => (int) ($request['sort_order'] ?? 0), 'status' => (string) ($request['status'] ?? 'active')));
        if (false === $id) {
            return $this->database_error('create_question');
        }
        $this->logger->info('create_question', array('user_id' => get_current_user_id(), 'resource_id' => $id, 'result' => 'success'));
        return new WP_REST_Response(array('data' => $this->repository->get_question($id)), 201);
    }

    public function list_answers(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $question = $this->repository->get_question((int) $request['id']);
        if (! $question) {
            return $this->not_found('question');
        }
        if ('active' !== $question->status && ! current_user_can('read_assessments')) {
            return $this->not_found('question');
        }
        $assessment = $this->repository->get_assessment((int) $question->assessment_id);
        if (! $assessment || ('published' !== $assessment->status && ! current_user_can('read_assessments'))) {
            return $this->not_found('question');
        }
        $privileged = current_user_can('read_assessments');
        return new WP_REST_Response(array('data' => $this->repository->list_answers((int) $question->id, $privileged)), 200);
    }

    public function create_answer(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $question_id = (int) $request['question_id'];
        if (! $this->repository->get_question($question_id)) {
            return $this->not_found('question');
        }
        $content = trim((string) $request['content']);
        if ('' === $content) {
            return $this->validation_error('content', 'Content is required.');
        }
        $id = $this->repository->create_answer(array('question_id' => $question_id, 'content' => $content, 'score' => (float) ($request['score'] ?? 0), 'sort_order' => (int) ($request['sort_order'] ?? 0)));
        if (false === $id) {
            return $this->database_error('create_answer');
        }
        $this->logger->info('create_answer', array('user_id' => get_current_user_id(), 'resource_id' => $id, 'result' => 'success'));
        $rows = $this->repository->list_answers($question_id, true);
        $created = array_values(array_filter($rows, static fn(object $row): bool => (int) $row->id === $id));
        return new WP_REST_Response(array('data' => $created[0] ?? null), 201);
    }

    /** @return array<string, array<string, mixed>> */
    private function list_args(): array
    {
        return array(
            'page' => array('type' => 'integer', 'default' => 1, 'minimum' => 1, 'sanitize_callback' => 'absint'),
            'per_page' => array('type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 100, 'sanitize_callback' => 'absint'),
            'status' => array('type' => 'string', 'enum' => self::ASSESSMENT_STATUSES, 'sanitize_callback' => 'sanitize_key'),
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function assessment_args(bool $required): array
    {
        $args = array(
            'title' => array('type' => 'string', 'required' => $required, 'maxLength' => 255, 'sanitize_callback' => 'sanitize_text_field'),
            'description' => array('type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'),
            'status' => array('type' => 'string', 'enum' => self::ASSESSMENT_STATUSES, 'sanitize_callback' => 'sanitize_key'),
        );
        if ($required) {
            $args['description']['default'] = '';
            $args['status']['default'] = 'draft';
        }
        return $args;
    }

    /** @return array<string, array<string, mixed>> */
    private function question_args(): array
    {
        return array(
            'assessment_id' => array_merge($this->id_arg(), array('required' => true)),
            'content' => array('type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field'),
            'sort_order' => array('type' => 'integer', 'default' => 0, 'minimum' => 0, 'sanitize_callback' => 'absint'),
            'status' => array('type' => 'string', 'default' => 'active', 'enum' => self::QUESTION_STATUSES, 'sanitize_callback' => 'sanitize_key'),
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function answer_args(): array
    {
        return array(
            'question_id' => array_merge($this->id_arg(), array('required' => true)),
            'content' => array('type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field'),
            'score' => array('type' => 'number', 'default' => 0, 'minimum' => 0, 'maximum' => 100),
            'sort_order' => array('type' => 'integer', 'default' => 0, 'minimum' => 0, 'sanitize_callback' => 'absint'),
        );
    }

    /** @return array<string, mixed> */
    private function id_arg(): array
    {
        return array('type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint');
    }

    private function not_found(string $resource): WP_Error
    {
        return new WP_Error('assessment_' . $resource . '_not_found', ucfirst($resource) . ' not found.', array('status' => 404));
    }

    private function validation_error(string $field, string $message): WP_Error
    {
        return new WP_Error('assessment_validation_failed', 'The request data is invalid.', array('status' => 422, 'fields' => array($field => $message)));
    }

    private function database_error(string $action): WP_Error
    {
        $this->logger->error($action, array('user_id' => get_current_user_id(), 'result' => 'failure', 'error_code' => 'database_error'));
        return new WP_Error('assessment_database_error', 'The operation could not be completed.', array('status' => 500));
    }
}
