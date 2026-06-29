<?php
// model/Friend.php
require_once __DIR__ . '/BaseModel.php';

class Friend extends BaseModel {

    private function ensureTable(){
        $this->db->query("CREATE TABLE IF NOT EXISTS friends (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            status ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /* ===== Basic actions ===== */
    public function sendRequest($sender, $receiver){
        $this->ensureTable();
        if ($sender === $receiver) return false;
        // Prevent duplicates: any direction pending or accepted
        $stmt = $this->db->prepare("SELECT id FROM friends WHERE 
            status IN ('pending','accepted') AND
            ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)) LIMIT 1");
        $stmt->bind_param("iiii", $sender, $receiver, $receiver, $sender);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) return false;
        $stmt->close();

        $stmt2 = $this->db->prepare("INSERT INTO friends(sender_id,receiver_id,status) VALUES(?,?,'pending')");
        $stmt2->bind_param("ii", $sender, $receiver);
        return $stmt2->execute();
    }

    public function cancelRequest($sender, $receiver){
        $this->ensureTable();
        $stmt = $this->db->prepare("DELETE FROM friends WHERE sender_id=? AND receiver_id=? AND status='pending'");
        $stmt->bind_param("ii", $sender, $receiver);
        return $stmt->execute();
    }

    public function acceptRequest($id, $receiver){
        $this->ensureTable();
        $stmt = $this->db->prepare("UPDATE friends SET status='accepted' WHERE id=? AND receiver_id=? AND status='pending'");
        $stmt->bind_param("ii", $id, $receiver);
        return $stmt->execute();
    }

    public function rejectRequest($id, $receiver){
        $this->ensureTable();
        $stmt = $this->db->prepare("UPDATE friends SET status='rejected' WHERE id=? AND receiver_id=? AND status='pending'");
        $stmt->bind_param("ii", $id, $receiver);
        return $stmt->execute();
    }

    public function deleteLinkForUser($id, $uid){
        $this->ensureTable();
        // Allow either participant to delete the relation (unfriend OR cancel after accept)
        $stmt = $this->db->prepare("DELETE FROM friends WHERE id=? AND (sender_id=? OR receiver_id=?)");
        $stmt->bind_param("iii", $id, $uid, $uid);
        return $stmt->execute();
    }

    public function hasPendingEitherDirection($a, $b){
        $this->ensureTable();
        $stmt = $this->db->prepare("SELECT id FROM friends WHERE status='pending' AND ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)) LIMIT 1");
        $stmt->bind_param("iiii", $a, $b, $b, $a);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function areFriends($a, $b){
        $this->ensureTable();
        $stmt = $this->db->prepare("SELECT id FROM friends WHERE status='accepted' AND ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)) LIMIT 1");
        $stmt->bind_param("iiii", $a, $b, $b, $a);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    /* ===== Lists used by FriendsController / view ===== */
    public function incomingRequestsList($uid){
        $this->ensureTable();
        $sql = "SELECT f.id AS request_id, u.id AS user_id, u.name
                FROM friends f
                JOIN users u ON u.id = f.sender_id
                WHERE f.receiver_id = ? AND f.status='pending'
                ORDER BY f.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()){
            $out[] = ['id'=>(int)$r['request_id'], 'user_id'=>(int)$r['user_id'], 'name'=>$r['name']];
        }
        return $out;
    }

    public function friendsList($uid){
        $this->ensureTable();
        $sql = "SELECT f.id AS link_id,
                       CASE WHEN f.sender_id=? THEN f.receiver_id ELSE f.sender_id END AS friend_id,
                       u.name
                FROM friends f
                JOIN users u ON u.id = (CASE WHEN f.sender_id=? THEN f.receiver_id ELSE f.sender_id END)
                WHERE (f.sender_id=? OR f.receiver_id=?) AND f.status='accepted'
                ORDER BY f.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iiii', $uid, $uid, $uid, $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()){
            $out[] = ['id'=>(int)$r['link_id'], 'friend_id'=>(int)$r['friend_id'], 'name'=>$r['name']];
        }
        return $out;
    }

    public function updateStatusForReceiver($id, $receiverId, $status){
        if ($status === 'accepted') return $this->acceptRequest($id, $receiverId);
        if ($status === 'rejected') return $this->rejectRequest($id, $receiverId);
        return false;
    }

    public function deletePendingForSender($id, $senderId){
        $this->ensureTable();
        $stmt = $this->db->prepare("DELETE FROM friends WHERE id=? AND sender_id=? AND status='pending'");
        $stmt->bind_param('ii', $id, $senderId);
        return $stmt->execute();
    }

    /* ===== Search with relation flags used by search_friends.php ===== */
    
    /* ===== Search with relation flags used by search_friends.php ===== */
    public function searchUsersWithRelation($uid, $q){
        $this->ensureTable();
        $uid = (int)$uid;
        $qLike = '%' . $this->db->real_escape_string($q) . '%';

        // Search users by name and compute relationship flags relative to $uid
        $sql = "
            SELECT 
                u.id,
                u.name,
                -- accepted friendship exists?
                (SELECT id FROM friends f_acc
                 WHERE ((f_acc.sender_id=u.id AND f_acc.receiver_id=?)
                     OR (f_acc.sender_id=? AND f_acc.receiver_id=u.id))
                   AND f_acc.status='accepted'
                 ORDER BY f_acc.id DESC LIMIT 1) AS accepted_id,
                -- my outgoing pending request to this user
                (SELECT id FROM friends f_req
                 WHERE f_req.sender_id=? AND f_req.receiver_id=u.id
                   AND f_req.status='pending'
                 ORDER BY f_req.id DESC LIMIT 1) AS req_id,
                -- incoming pending request from this user to me
                (SELECT id FROM friends f_accpt
                 WHERE f_accpt.sender_id=u.id AND f_accpt.receiver_id=?
                   AND f_accpt.status='pending'
                 ORDER BY f_accpt.id DESC LIMIT 1) AS accept_id
            FROM users u
            WHERE u.id<>? AND u.role='user' AND u.name LIKE ?
            ORDER BY u.name ASC
            LIMIT 50
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iiiiis', $uid, $uid, $uid, $uid, $uid, $qLike);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($r = $res->fetch_assoc()) {
            $is_friend = !empty($r['accepted_id']);
            $out[] = [
                'id'        => (int)$r['id'],
                'name'      => $r['name'],
                'is_friend' => $is_friend ? 1 : 0,
                'friend_id' => $is_friend ? (int)$r['id'] : null,
                'req_id'    => !empty($r['req_id']) ? (int)$r['req_id'] : null,
                'accept_id' => !empty($r['accept_id']) ? (int)$r['accept_id'] : null,
            ];
        }
        return $out;
    }
}
