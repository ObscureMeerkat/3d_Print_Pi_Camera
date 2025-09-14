<?php
// Require login + single-session validation
require __DIR__ . '/auth.php';

// Only show Invite to your account
$isAdmin = (strcasecmp($_SESSION['email'] ?? '', 'Joshua.barrett00@gmail.com') === 0);

// CSRF token for invite POST
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Live Stream</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  :root { --bg:#000; --panel:rgba(0,0,0,.35); --btn:#3AAA35; --btnh:#2e7d28; --text:#fff; }
  html,body{height:100%;margin:0;background:var(--bg);color:var(--text);font-family:system-ui,Arial,sans-serif}
  .wrap{height:100%;display:flex;align-items:center;justify-content:center}
  .player{max-width:100%;max-height:100%;display:block}

  .topbar{
    position:fixed; top:0; left:0; right:0; height:56px;
    display:flex; align-items:center; justify-content:flex-end; gap:12px;
    padding:0 12px; background:var(--panel); backdrop-filter:blur(6px); z-index:10;
  }
  .btn, .menu-btn, .menu .item {
    appearance:none; border:none; border-radius:8px; padding:8px 12px;
    background:var(--btn); color:#fff; font-weight:600; cursor:pointer; text-decoration:none;
  }
  .btn:hover, .menu-btn:hover, .menu .item:hover { background:var(--btnh); }
  .menu-btn{ background:#444; }
  .menu{
    position:absolute; top:56px; right:12px; background:#111; border:1px solid #333;
    border-radius:10px; padding:8px; display:flex; flex-direction:column; gap:8px; min-width:160px;
  }

  .modal{
    position:fixed; inset:0; display:flex; align-items:center; justify-content:center;
    background:rgba(0,0,0,.5); z-index:20;
  }
  .hidden{ display:none !important; }
  .card{
    background:#1a1a1a; border:1px solid #333; border-radius:12px; padding:16px; width:min(520px, 92vw);
  }
  .row{ display:flex; gap:8px; margin-top:12px; }
  input.linkbox{ width:100%; padding:10px; border-radius:8px; border:1px solid #333; background:#111; color:#fff; }
  .msg{ margin-top:8px; opacity:.85; font-size:.92rem; }
</style>
</head>
<body>
  <div class="topbar">
    <button class="menu-btn" id="menuBtn" aria-haspopup="true" aria-expanded="false">Menu ☰</button>
    <div class="menu hidden" id="menu" role="menu" aria-hidden="true">
      <?php if ($isAdmin): ?>
        <button class="item" id="inviteBtn" role="menuitem">Invite</button>
      <?php endif; ?>
      <form method="POST" action="/logout.php" style="margin:0">
        <button class="item" type="submit" role="menuitem">Logout</button>
      </form>
    </div>
  </div>

  <div class="wrap">
    <img class="player" id="player" src="/video_feed" alt="Camera stream">
  </div>

  <!-- Invite Modal -->
  <div class="modal hidden" id="inviteModal" role="dialog" aria-modal="true" aria-labelledby="inviteTitle">
    <div class="card">
      <h3 id="inviteTitle" style="margin:0 0 8px 0;">Invite link</h3>
      <input class="linkbox" id="inviteLink" type="text" readonly value="">
      <div class="row">
        <button class="btn" id="copyBtn" type="button">Copy</button>
        <button class="menu-btn" id="closeBtn" type="button">Close</button>
      </div>
      <div class="msg" id="inviteMsg"></div>
    </div>
  </div>

<script>
(function(){
  const menuBtn   = document.getElementById('menuBtn');
  const menu      = document.getElementById('menu');
  const inviteBtn = document.getElementById('inviteBtn');
  const modal     = document.getElementById('inviteModal');
  const linkBox   = document.getElementById('inviteLink');
  const msg       = document.getElementById('inviteMsg');
  const copyBtn   = document.getElementById('copyBtn');
  const closeBtn  = document.getElementById('closeBtn');
  const CSRF      = <?= json_encode($csrf) ?>;

  // ======= menu toggle =======
  function toggleMenu(show){
    const willShow = (show === undefined) ? menu.classList.contains('hidden') : show;
    menu.classList.toggle('hidden', !willShow);
    menuBtn.setAttribute('aria-expanded', String(willShow));
    menu.setAttribute('aria-hidden', String(!willShow));
  }
  menuBtn.addEventListener('click', e => { e.stopPropagation(); toggleMenu(); });
  document.addEventListener('click', () => toggleMenu(false));

  // ======= invite logic =======
  if (inviteBtn) {
    inviteBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      toggleMenu(false);
      msg.textContent = 'Generating link...';
      try {
        const res = await fetch('/generate_invite.php', { method:'POST', headers:{ 'X-CSRF': CSRF }});
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Failed to create invite');
        linkBox.value = data.link;
        msg.textContent = `Expires: ${data.expires_at}`;
        modal.classList.remove('hidden'); linkBox.focus(); linkBox.select();
      } catch (err) {
        msg.textContent = 'Error: ' + err.message;
        modal.classList.remove('hidden');
      }
    });
  }
  copyBtn.addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(linkBox.value); msg.textContent = 'Copied to clipboard ✅'; }
    catch { linkBox.select(); msg.textContent = 'Press Ctrl+C / ⌘C to copy.'; }
  });
  function closeModal(){ modal.classList.add('hidden'); msg.textContent=''; linkBox.value=''; }
  closeBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', (e)=>{ if(e.target === modal) closeModal(); });
  document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeModal(); });
})();
</script>
</body>
</html>