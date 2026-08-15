<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class MA_Repository
{
    private wpdb $db;
    private string $assessments;
    private string $questions;
    private string $answers;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->assessments = $wpdb->prefix . 'assessment';
        $this->questions = $wpdb->prefix . 'assessment_questions';
        $this->answers = $wpdb->prefix . 'assessment_answers';
    }

    /** @return array{items: array<int, object>, total: int} */
    public function list_assessments(int $page, int $per_page, ?string $status): array
    {
        $offset = ($page - 1) * $per_page;
        $where = '';
        $parameters = array();

        if (null !== $status) {
            $where = ' WHERE status = %s';
            $parameters[] = $status;
        }

        $count_sql = "SELECT COUNT(*) FROM {$this->assessments}{$where}";
        if ($parameters) {
            $count_sql = $this->db->prepare($count_sql, ...$parameters);
        }

        $list_sql = "SELECT id, title, description, status, created_at, updated_at
            FROM {$this->assessments}{$where}
            ORDER BY created_at DESC, id DESC
            LIMIT %d OFFSET %d";
        $list_parameters = array_merge($parameters, array($per_page, $offset));

        return array(
            'items' => $this->db->get_results($this->db->prepare($list_sql, ...$list_parameters)),
            'total' => (int) $this->db->get_var($count_sql),
        );
    }

    public function get_assessment(int $id): ?object
    {
        $sql = $this->db->prepare(
            "SELECT id, title, description, status, created_at, updated_at
             FROM {$this->assessments} WHERE id = %d",
            $id
        );
        $row = $this->db->get_row($sql);
        return $row ?: null;
    }

    /** @param array<string, string> $data */
    public function create_assessment(array $data): int|false
    {
        $now = current_time('mysql', true);
        $inserted = $this->db->insert(
            $this->assessments,
            array(
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => $data['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        return false === $inserted ? false : (int) $this->db->insert_id;
    }

    /** @param array<string, string> $data */
    public function update_assessment(int $id, array $data): bool
    {
        $data['updated_at'] = current_time('mysql', true);
        $formats = array_fill(0, count($data), '%s');
        $result = $this->db->update($this->assessments, $data, array('id' => $id), $formats, array('%d'));
        return false !== $result;
    }

    public function delete_assessment(int $id): bool
    {
        $this->db->query('START TRANSACTION');

        $question_ids = $this->db->get_col(
            $this->db->prepare("SELECT id FROM {$this->questions} WHERE assessment_id = %d", $id)
        );

        if ($question_ids) {
            $placeholders = implode(',', array_fill(0, count($question_ids), '%d'));
            $delete_answers = $this->db->query(
                $this->db->prepare("DELETE FROM {$this->answers} WHERE question_id IN ({$placeholders})", ...$question_ids)
            );
            if (false === $delete_answers) {
                $this->db->query('ROLLBACK');
                return false;
            }
        }

        $delete_questions = $this->db->delete($this->questions, array('assessment_id' => $id), array('%d'));
        $delete_assessment = $this->db->delete($this->assessments, array('id' => $id), array('%d'));

        if (false === $delete_questions || false === $delete_assessment || 0 === $delete_assessment) {
            $this->db->query('ROLLBACK');
            return false;
        }

        $this->db->query('COMMIT');
        return true;
    }

    /** @return array<int, object> */
    public function list_questions(int $assessment_id, bool $public_only): array
    {
        $sql = "SELECT id, assessment_id, content, sort_order, status, created_at, updated_at
            FROM {$this->questions} WHERE assessment_id = %d";
        $parameters = array($assessment_id);

        if ($public_only) {
            $sql .= ' AND status = %s';
            $parameters[] = 'active';
        }

        $sql .= ' ORDER BY sort_order ASC, id ASC';
        return $this->db->get_results($this->db->prepare($sql, ...$parameters));
    }

    public function get_question(int $id): ?object
    {
        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT id, assessment_id, content, sort_order, status, created_at, updated_at
                 FROM {$this->questions} WHERE id = %d",
                $id
            )
        );
        return $row ?: null;
    }

    /** @param array<string, int|string> $data */
    public function create_question(array $data): int|false
    {
        $now = current_time('mysql', true);
        $inserted = $this->db->insert(
            $this->questions,
            array(
                'assessment_id' => $data['assessment_id'],
                'content' => $data['content'],
                'sort_order' => $data['sort_order'],
                'status' => $data['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s')
        );
        return false === $inserted ? false : (int) $this->db->insert_id;
    }

    /** @return array<int, object> */
    public function list_answers(int $question_id, bool $include_score): array
    {
        $columns = $include_score
            ? 'id, question_id, content, score, sort_order, created_at, updated_at'
            : 'id, question_id, content, sort_order, created_at, updated_at';
        $sql = $this->db->prepare(
            "SELECT {$columns} FROM {$this->answers}
             WHERE question_id = %d ORDER BY sort_order ASC, id ASC",
            $question_id
        );
        return $this->db->get_results($sql);
    }

    /** @param array<string, float|int|string> $data */
    public function create_answer(array $data): int|false
    {
        $now = current_time('mysql', true);
        $inserted = $this->db->insert(
            $this->answers,
            array(
                'question_id' => $data['question_id'],
                'content' => $data['content'],
                'score' => $data['score'],
                'sort_order' => $data['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ),
            array('%d', '%s', '%f', '%d', '%s', '%s')
        );
        return false === $inserted ? false : (int) $this->db->insert_id;
    }
}

