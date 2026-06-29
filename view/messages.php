<?php
if(!isset($_SESSION['user'])){ header("Location: index.php?page=login"); exit; }
$me = $_SESSION['user'];
$myid = $me['id'];
$role = $me['role'];
$to = (int)($_GET['to'] ?? 0);
?>
<?php

$activeId = isset($partner['id']) ? (int)$partner['id'] : 0;
?>
<div class="card messages">
  <h3>Messages</h3>

  <div class="messages-layout">
    <!-- Sidebar: inbox -->
    <aside class="messages-sidebar">
      <div class="muted sidebar-title">Conversations</div>
      <div class="inbox">
        <?php if (empty($inbox)): ?>
          <div class="inbox-empty muted">No conversations yet</div>
        <?php else: ?>
          <?php foreach ($inbox as $conv): ?>
            <?php
              $pid    = (int)($conv['partner_id'] ?? 0);
              $pname  = htmlspecialchars($conv['partner_name'] ?? 'User');
              $last   = htmlspecialchars($conv['last_body'] ?? '');
              $lastAt = htmlspecialchars($conv['last_at'] ?? '');
              $isActive = ($pid === $activeId);
            ?>
            <a class="inbox-row<?= $isActive ? ' active' : '' ?>"
               href="index.php?page=messages&to=<?= $pid ?>">
              <div class="inbox-name"><?= $pname ?></div>
              <?php if ($last !== ''): ?>
                <div class="inbox-last muted"><?= $last ?></div>
              <?php endif; ?>
              <?php if ($lastAt !== ''): ?>
                <div class="inbox-time muted"><?= $lastAt ?></div>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </aside>

    <!-- Thread -->
    <section class="messages-thread">
      <?php if (!empty($partner)): ?>
        <div class="thread-title">
          Chat with <strong><?= htmlspecialchars($partner['name'] ?? 'User'); ?></strong>
        </div>

        <div id="thread" class="thread-scroller">
          <?php if (empty($thread)): ?>
            <div class="muted">No messages yet. Say hello!</div>
          <?php else: ?>
            <?php foreach ($thread as $m): ?>
              <?php
                $senderId   = (int)($m['sender_id'] ?? 0);
                $senderName = htmlspecialchars($m['sender_name'] ?? 'User');
                $createdAt  = htmlspecialchars($m['created_at'] ?? '');
                $bodyHtml   = nl2br(htmlspecialchars((string)($m['body'] ?? '')));
                $mine       = ($senderId === (int)$me['id']);
              ?>
              <div class="bubble-row <?= $mine ? 'me' : 'them' ?>">
                <div class="bubble">
                  <div class="bubble-meta muted">
                    <?= $senderName ?><?= $createdAt ? ' • '.$createdAt : '' ?>
                  </div>
                  <div class="bubble-body"><?= $bodyHtml ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <form method="post" class="composer">
          <?= csrf_field() ?>
          <input type="hidden" name="to" value="<?= (int)($partner['id'] ?? 0); ?>">
          <input name="body" class="composer-input" placeholder="Write a message...">
          <button class="composer-send">Send</button>
        </form>
      <?php else: ?>
        <div class="muted">Pick a conversation on the left to start chatting.</div>
      <?php endif; ?>
    </section>
  </div>
</div>
