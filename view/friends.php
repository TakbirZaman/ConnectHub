<?php
// view/friends.php expects $pending and $friends arrays
?>
<div>
  <h3>Find friends</h3>
  <div class="searchbar">
    <input id="q" placeholder="Search users by name..." oninput="doSearch()">
    <button onclick="doSearch()">Search</button>
  </div>
  <div id="search_results" style="margin-top:10px"></div>
</div>

<div style="margin-top:12px">
  <h3>Requests</h3>
  <?php if(empty($pending)): ?>
    <div class="muted" style="padding:8px 12px;">No pending requests</div>
  <?php else: foreach($pending as $r): ?>
    <div class="friend-row">
      <span class="friend-name"><?= htmlspecialchars($r['name']) ?></span>
      <span class="actions">
        <a class="btn" href="index.php?page=friends&action=accept&id=<?= (int)$r['id'] ?>">Accept</a>
        <a class="btn danger" href="index.php?page=friends&action=reject&id=<?= (int)$r['id'] ?>">Reject</a>
      </span>
    </div>
  <?php endforeach; endif; ?>
</div>

<div style="margin-top:12px">
  <h3>Friends</h3>
  <?php if(empty($friends)): ?>
    <div class="muted" style="padding:8px 12px;">No friends yet</div>
  <?php else: foreach($friends as $f): ?>
    <div class="friend-row">
      <span class="friend-name">
        <a href="index.php?page=profile&user=<?= (int)$f['user_id'] ?>"><?= htmlspecialchars($f['name']) ?></a>
      </span>
      <span class="actions">
        <a class="btn" href="index.php?page=messages&to=<?= (int)$f['user_id'] ?>">Message</a>
        <a class="btn danger" href="index.php?page=friends&action=unfriend&id=<?= (int)$f['friend_link_id'] ?>">Unfriend</a>
      </span>
    </div>
  <?php endforeach; endif; ?>
</div>

<script>
function doSearch(){
  const q=document.getElementById('q').value;
  const box=document.getElementById('search_results');
  if(!q){ box.innerHTML=''; return; }
  const xhr=new XMLHttpRequest();
  xhr.open('GET','controller/search_friends.php?q='+encodeURIComponent(q),true);
  xhr.onload=function(){
    if(this.status!==200){ box.innerHTML='<div class="bad">Search error</div>'; return; }
    const data=JSON.parse(this.responseText||'[]');
    box.innerHTML='';
    data.forEach(item=>{
      let btn='';
      if(item.req_id){
        btn = `<a class="btn" href="controller/friend_action.php?a=cancel&id=${item.req_id}">Cancel</a>`;
      } else if(item.accept_id){
        btn = `<a class="btn" href="controller/friend_action.php?a=accept&id=${item.accept_id}">Accept</a>
               <a class="btn danger" href="controller/friend_action.php?a=reject&id=${item.accept_id}">Reject</a>`;
      } else if(item.is_friend){
        const fid = item.friend_id || item.req_id || item.accept_id;
        btn = `<a class="btn" href="index.php?page=messages&to=${item.id}">Message</a> ` +
               `<a class="btn danger" href="controller/friend_action.php?a=unfriend&id=${fid}">Unfriend</a>`;
      } else {
        btn = `<a class="btn" href="controller/friend_action.php?a=send&to=${item.id}">Add Friend</a>`;
      }
      const row = document.createElement('div');
      row.className='friend-row';
      row.innerHTML = `<span class="friend-name">${item.name}</span><span class="actions">${btn}</span>`;
      box.appendChild(row);
    });
  };
  xhr.send();
}
</script>
