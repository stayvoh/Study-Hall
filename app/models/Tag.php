<?php
declare(strict_types=1);

class Tag {
    private PDO $db;
    public function __construct(PDO $db) { $this->db = $db; }

    public function bySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM tag WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    public function ensure(string $rawName): array {
        $name = trim($rawName);
        if ($name === '') { throw new InvalidArgumentException('Empty tag'); }
        $slug = $this->slugify($name);
        $this->db->beginTransaction();
        $stmt = $this->db->prepare("SELECT * FROM tag WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            $ins = $this->db->prepare("INSERT INTO tag (name, slug) VALUES (:name, :slug)");
            $ins->execute(['name' => $name, 'slug' => $slug]);
            $row = ['id' => (int)$this->db->lastInsertId(), 'name' => $name, 'slug' => $slug];
        }
        $this->db->commit();
        return $row;
    }

    public function attachToPost(int $postId, array $tagIds): void {
        if (!$tagIds) return;
        $this->db->beginTransaction();
        $del = $this->db->prepare("DELETE FROM post_tag WHERE post_id = :pid");
        $del->execute(['pid' => $postId]);
        $ins = $this->db->prepare("INSERT INTO post_tag (post_id, tag_id) VALUES (:pid, :tid)");
        foreach (array_unique(array_map('intval', $tagIds)) as $tid) {
            $ins->execute(['pid' => $postId, 'tid' => $tid]);
        }
        $this->db->commit();
    }

    public function tagsForPost(int $postId): array {
        $stmt = $this->db->prepare("
            SELECT t.id, t.name, t.slug
            FROM tag t
            JOIN post_tag pt ON pt.tag_id = t.id
            WHERE pt.post_id = :pid
            ORDER BY t.name ASC
        ");
        $stmt->execute(['pid' => $postId]);
        return $stmt->fetchAll();
    }

    public function suggest(string $q, int $limit = 8): array {
        $q = trim($q);
        if ($q === '') return [];
        // Prefer FULLTEXT; fallback to LIKE
        $sql = "SELECT id, name, slug FROM tag WHERE name LIKE :q ORDER BY name LIMIT :lim";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':q', '%'.$q.'%');
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function slugify(string $s): string {
        $s = preg_replace('~[^\pL\d]+~u', '-', $s);
        $s = trim($s, '-');
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        $s = strtolower($s);
        $s = preg_replace('~[^-a-z0-9]+~', '', $s);
        return $s ?: 'tag';
    }
}
