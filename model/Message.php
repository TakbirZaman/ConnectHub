<?php
// model/Message.php
require_once __DIR__ . '/BaseModel.php';

class Message extends BaseModel {

  /* Cache the detected body column name */
  private ?string $bodyCol = null;

  /** Resolve which column stores the message text; add one if missing. */
  private function bodyColumn(): string {
    if ($this->bodyCol !== null) return $this->bodyCol;

    $candidates = ["body","message","text","content"];
    foreach ($candidates as $cand) {
      $res = $this->db->query("SHOW COLUMNS FROM messages LIKE '{$cand}'");
      if ($res && $res->num_rows > 0) {
        $this->bodyCol = $cand;
        return $this->bodyCol;
      }
    }
    // None found — create a standard 'body' column
    $this->db->query("ALTER TABLE messages ADD COLUMN body TEXT NOT NULL");
    $this->bodyCol = "body";
    return $this->bodyCol;
  }

  /** Ensure 'seen' column exists; add it if not */
  private function ensureSeenColumn(): void {
    $res = $this->db->query("SHOW COLUMNS FROM messages LIKE 'seen'");
    if (!$res || $res->num_rows === 0) {
      $this->db->query("ALTER TABLE messages ADD COLUMN seen TINYINT(1) DEFAULT 0");
    }
  }

  /** Create a new message (uses detected body column) */
  public function send(int $from, int $to, string $body): bool {
    $col = $this->bodyColumn();
    $sql = "INSERT INTO messages(sender_id, receiver_id, {$col}) VALUES(?, ?, ?)";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("iis", $from, $to, $body);
    return $stmt->execute();
  }

  /** Entire thread between two users, oldest first — aliases text as `body` */
  public function threadBetween(int $a, int $b): array {
    $col = $this->bodyColumn();
    $sql = "
      SELECT 
        m.id,
        m.sender_id,
        m.receiver_id,
        m.`{$col}` AS body,
        m.created_at,
        su.name AS sender_name,
        ru.name AS receiver_name
      FROM messages m
      JOIN users su ON su.id = m.sender_id
      JOIN users ru ON ru.id = m.receiver_id
      WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)
      ORDER BY m.id ASC
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("iiii", $a, $b, $b, $a);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  }

  /**
   * Inbox for a user: last message per conversation (latest per pair).
   * Returns raw rows; controller maps to stable keys.
   */
  public function inboxFor(int $uid): array {
    $col = $this->bodyColumn();
    // We keep * but ensure the body column exists in results as well.
    $res = $this->db->query("
      SELECT t.*
      FROM (
        SELECT m.*,
               CASE WHEN m.sender_id < m.receiver_id
                    THEN CONCAT(m.sender_id,'-',m.receiver_id)
                    ELSE CONCAT(m.receiver_id,'-',m.sender_id)
               END as conv_key
        FROM messages m
        WHERE m.sender_id={$uid} OR m.receiver_id={$uid}
        ORDER BY m.id DESC
      ) t
      GROUP BY t.conv_key
      ORDER BY MAX(t.id) DESC
    ");
    $rows = [];
    if ($res) {
      while ($r = $res->fetch_assoc()) {
        // Normalize to include 'body' key for controller/view convenience
        if (!isset($r['body']) && isset($r[$col])) {
          $r['body'] = $r[$col];
        }
        $rows[] = $r;
      }
    }
    return $rows;
  }

  /** Mark messages from partner -> me as seen */
  public function markSeen(int $me, int $partner): void {
    $this->ensureSeenColumn();
    $stmt = $this->db->prepare("UPDATE messages SET seen=1 WHERE receiver_id=? AND sender_id=? AND seen=0");
    if ($stmt) {
      $stmt->bind_param("ii", $me, $partner);
      $stmt->execute();
    }
  }
}
