<?php
declare(strict_types=1);

class Search {
    private PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }

    public function posts(array $opts): array {
        $q        = trim((string)($opts['q'] ?? ''));
        $tagSlug  = trim((string)($opts['tag'] ?? ''));
        $boardId  = (int)($opts['board_id'] ?? 0);
        $limit    = max(1, (int)($opts['limit'] ?? 20));
        $offset   = max(0, (int)($opts['offset'] ?? 0));

        $where = [];
        $params = [];

        if ($q !== '') {
            // FULLTEXT fallback to LIKE
            $where[] = "(p.title LIKE :q OR p.body LIKE :q)";
            $params[':q'] = '%'.$q.'%';
        }

        if ($boardId > 0) {
            $where[] = "p.board_id = :board_id";
            $params[':board_id'] = $boardId;
        }

        if ($tagSlug !== '') {
            $where[] = "EXISTS (
                SELECT 1 FROM post_tag x
                JOIN tag t ON t.id = x.tag_id
                WHERE x.post_id = p.id AND t.slug = :tag_slug
            )";
            $params[':tag_slug'] = $tagSlug;
        }

        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        $sql = "
            SELECT p.id, p.title, p.body, p.created_at, p.board_id,
                   COALESCE(up.username, ua.email) AS author
            FROM post p
            JOIN user_account ua ON ua.id = p.user_id
            LEFT JOIN user_profile up ON up.user_id = ua.id
            $whereSql
            ORDER BY p.created_at DESC
            LIMIT :lim OFFSET :off
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function users(array $opts): array {
        $q      = trim((string)($opts['q'] ?? ''));
        $limit  = max(1, (int)($opts['limit'] ?? 20));
        $offset = max(0, (int)($opts['offset'] ?? 0));

        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = "(up.username LIKE :q OR ua.email LIKE :q)";
            $params[':q'] = '%'.$q.'%';
        }

        $whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

        $sql = "
            SELECT ua.id, ua.email, COALESCE(up.username, '') AS username, ua.created_at
            FROM user_account ua
            LEFT JOIN user_profile up ON up.user_id = ua.id
            $whereSql
            ORDER BY COALESCE(up.username, ua.email) ASC
            LIMIT :lim OFFSET :off
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function tags(array $opts): array {
        $q      = trim((string)($opts['q'] ?? ''));
        $limit  = max(1, (int)($opts['limit'] ?? 20));
        $offset = max(0, (int)($opts['offset'] ?? 0));

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = "t.name LIKE :q";
            $params[':q'] = '%'.$q.'%';
        }
        $whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

        $sql = "
            SELECT t.id, t.name, t.slug,
                   (SELECT COUNT(*) FROM post_tag pt WHERE pt.tag_id = t.id) AS usage_count
            FROM tag t
            $whereSql
            ORDER BY usage_count DESC, t.name ASC
            LIMIT :lim OFFSET :off
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
