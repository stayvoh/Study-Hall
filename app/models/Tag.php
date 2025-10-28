<?php
declare(strict_types=1);

class Tag {
    private PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }

    public function bySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM tag WHERE slug = :slug");
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function ensure(string $rawName): array {
        $name = trim($rawName);
        if ($name === '') throw new InvalidArgumentException('Empty tag');
        $name = mb_substr($name, 0, 80);
        $slug = $this->slugify($name);

        // Try find by slug first
        $stmt = $this->db->prepare("SELECT * FROM tag WHERE slug = :s OR name = :n LIMIT 1");
        $stmt->execute([':s' => $slug, ':n' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;

        // Insert new
        $ins = $this->db->prepare("INSERT INTO tag (name, slug) VALUES (:n, :s)");
        $ins->execute([':n' => $name, ':s' => $slug]);

        return [
            'id'   => (int)$this->db->lastInsertId(),
            'name' => $name,
            'slug' => $slug,
        ];
    }

    /** Given "php, help, arrays", return an array of tag rows (creating any that are missing). */
    public function ensureManyFromCsv(string $csv): array {
        $names = $this->parseCsv($csv);
        $out = [];
        foreach ($names as $n) $out[] = $this->ensure($n);
        return $out;
    }

    /** Attach tag ids to a post (ignores duplicates). */
    public function attachToPost(int $postId, array $tagIds): void {
        if (!$tagIds) return;
        $stmt = $this->db->prepare("INSERT IGNORE INTO post_tag (post_id, tag_id) VALUES (:p, :t)");
        foreach ($tagIds as $tid) {
            $stmt->execute([':p' => $postId, ':t' => (int)$tid]);
        }
    }

    /** Remove all tags for a post (not required for create flow, but handy). */
    public function detachAllForPost(int $postId): void {
        $stmt = $this->db->prepare("DELETE FROM post_tag WHERE post_id = :p");
        $stmt->execute([':p' => $postId]);
    }

    /** Tags for a post. */
    public function forPost(int $postId): array {
        $sql = "SELECT t.id, t.name, t.slug
                FROM post_tag pt
                JOIN tag t ON t.id = pt.tag_id
                WHERE pt.post_id = :p
                ORDER BY t.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':p' => $postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Tag list with usage counts (for /tags). */
    public function popular(int $limit = 200, string $q = ''): array {
        $where = '';
        $params = [];
        if ($q !== '') {
            $where = "WHERE t.name LIKE :q";
            $params[':q'] = "%{$q}%";
        }

        $sql = "SELECT t.id, t.name, t.slug, COUNT(pt.post_id) AS usage_count
                FROM tag t
                LEFT JOIN post_tag pt ON pt.tag_id = t.id
                $where
                GROUP BY t.id, t.name, t.slug
                ORDER BY usage_count DESC, t.name ASC
                LIMIT :lim";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Lightweight suggest for autocomplete. */
    public function suggest(string $q, int $limit = 10): array {
        $q = trim($q);
        if ($q === '') return [];
        $sql = "SELECT id, name, slug
                FROM tag
                WHERE name LIKE :q
                ORDER BY name ASC
                LIMIT :lim";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':q', "%{$q}%", PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Helpers */
    private function parseCsv(string $csv): array {
        $parts = preg_split('/[,;]/', $csv) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim(preg_replace('/\s+/', ' ', $p));
            if ($p !== '') $out[] = $p;
        }
        // de-dupe case-insensitive on the normalized string
        $out = array_values(array_unique(array_map('mb_strtolower', $out)));
        return $out;
    }

    private function slugify(string $s): string {
        $s = preg_replace('~[^\pL\d]+~u', '-', $s);
        $s = trim($s, '-');
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        $s = strtolower((string)$s);
        $s = preg_replace('~[^-a-z0-9]+~', '', $s);
        return $s ?: 'tag';
    }
}
