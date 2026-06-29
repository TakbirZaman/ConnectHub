<?php
// model/Post.php
require_once __DIR__ . '/BaseModel.php';

class Post extends BaseModel {

  /* -------- Create a post -------- */
  public function create(int $user_id, string $content, ?string $image = null): bool {
    $stmt = $this->db->prepare("
      INSERT INTO posts (user_id, content, image, created_at)
      VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iss", $user_id, $content, $image);
    return $stmt->execute();
  }

  /* -------- Friends-only feed (self + accepted friends) -------- */
  public function friendsFeed(int $me): array {
    $sql = "
      SELECT p.id, p.user_id, p.content, p.image, p.created_at,
             u.name, u.profile_pic,
             (SELECT COUNT(*) FROM likes    l WHERE l.post_id = p.id) AS like_count,
             (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count
      FROM posts p
      JOIN users u ON u.id = p.user_id
      WHERE p.user_id = ?
         OR EXISTS (
            SELECT 1
            FROM friends f
            WHERE f.status = 'accepted'
              AND (
                (f.sender_id   = ? AND f.receiver_id = p.user_id) OR
                (f.receiver_id = ? AND f.sender_id   = p.user_id)
              )
         )
      ORDER BY p.created_at DESC
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("iii", $me, $me, $me);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }

  /* -------- Posts for a specific user (profile page) -------- */
  public function getByUser(int $user_id): array {
    $stmt = $this->db->prepare("
      SELECT id, user_id, content, image, created_at
      FROM posts
      WHERE user_id = ?
      ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }

  /* -------- Like a post (idempotent via IGNORE) -------- */
  public function like(int $user_id, int $post_id): bool {
    $stmt = $this->db->prepare("INSERT IGNORE INTO likes (user_id, post_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $post_id);
    return $stmt->execute();
  }

  /* -------- Add a comment -------- */
  public function addComment(int $user_id, int $post_id, string $comment): bool {
    $stmt = $this->db->prepare("INSERT INTO comments (user_id, post_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $post_id, $comment);
    return $stmt->execute();
  }

  /* -------- Like count (used by ProfileController) -------- */
  public function likeCount(int $post_id): int {
    $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM likes WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return (int)($res['c'] ?? 0);
  }

  /* -------- Comment count (often used alongside likeCount) -------- */
  public function commentCount(int $post_id): int {
    $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM comments WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return (int)($res['c'] ?? 0);
  }

  /* -------- Batch comments map for a set of post IDs (optional) -------- */
  public function commentsForPosts(array $postIds): array {
    if (empty($postIds)) return [];
    $in = implode(',', array_map('intval', $postIds));
    $sql = "
      SELECT c.id, c.post_id, c.comment, u.name
      FROM comments c
      JOIN users u ON u.id = c.user_id
      WHERE c.post_id IN ($in)
      ORDER BY c.id ASC
    ";
    $res = $this->db->query($sql);
    $map = [];
    if ($res) {
      while ($row = $res->fetch_assoc()) {
        $pid = (int)$row['post_id'];
        if (!isset($map[$pid])) $map[$pid] = [];
        $map[$pid][] = $row;
      }
    }
    return $map;
  }
}
