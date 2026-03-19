<?php
session_start();

if (!isset($_SESSION['isClientLoggedIn']) || $_SESSION['isClientLoggedIn'] !== true) {
    header("Location: login.php");
    exit();
}

$clientName  = htmlspecialchars($_SESSION['clientName']  ?? '');
$clientEmail = htmlspecialchars($_SESSION['clientEmail'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Messages – RIMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/homepage.css">
  <style>
    :root {
      --pink:      #f17dda;
      --pink-dark: #d45fbf;
      --teal:      #aafdfd;
      --bg:        #f9f9f9;
      --border:    #f5d0ef;
    }

    body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #ffe6f0, #fff5f9, #ffe0ec); }

    .msgs-section {
      max-width: 820px;
      margin: 120px auto 60px;
      padding: 0 1.5rem;
    }

    .page-heading { font-size: 2.5rem; font-weight: 700; color: #333; margin-bottom: 0.3rem; }
    .page-heading span { color: var(--pink); }
    .page-sub { color: #777; font-size: 0.95rem; margin-bottom: 2rem; }

    .top-bar {
      display: flex; justify-content: space-between;
      align-items: center; margin-bottom: 1.5rem;
      flex-wrap: wrap; gap: 1rem;
    }
    .summary-pills { display: flex; gap: 0.8rem; flex-wrap: wrap; }
    .pill {
      background: #fef0fb; border: 1.5px solid var(--border);
      border-radius: 20px; padding: 0.4rem 1rem;
      font-size: 0.85rem; font-weight: 600; color: var(--pink-dark);
      box-shadow: 0 2px 8px rgba(214,51,132,0.15);
    }
    .pill span { color: #333; }

    .msg-thread {
      background: #fff;
      border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(241,125,218,0.08);
      border-left: 5px solid #d63384;
      margin-bottom: 1.2rem;
      overflow: hidden;
      transition: box-shadow 0.2s;
    }
    .msg-thread:hover { box-shadow: 0 8px 28px rgba(241,125,218,0.18); }

    .customer-msg { padding: 1.2rem 1.5rem; }
    .customer-msg .msg-header {
      display: flex; justify-content: space-between;
      align-items: center; margin-bottom: 0.6rem;
      flex-wrap: wrap; gap: 0.5rem;
    }
    .customer-msg .msg-label {
      font-size: 0.8rem; font-weight: 700;
      background: #fef0fb; color: var(--pink-dark);
      border: 1px solid var(--border);
      padding: 0.2rem 0.8rem; border-radius: 20px;
    }
    .customer-msg .msg-time { font-size: 0.78rem; color: #aaa; }
    .customer-msg .msg-text { font-size: 0.95rem; color: #333; line-height: 1.6; }

    .badge-read {
      background: #e8f5e9; color: #2e7d32;
      font-size: 0.72rem; padding: 0.2rem 0.7rem;
      border-radius: 20px; font-weight: 600;
    }
    .badge-unread {
      background: #fff8e1; color: #e65100;
      font-size: 0.72rem; padding: 0.2rem 0.7rem;
      border-radius: 20px; font-weight: 600;
    }

    .replies-section {
      border-top: 1px solid #f5eef9;
    }
    .reply-bubble {
      padding: 1rem 1.5rem;
      border-bottom: 1px solid #eee;
    }
    .reply-bubble:last-child { border-bottom: none; }
    .reply-header {
      display: flex; justify-content: space-between;
      align-items: center; margin-bottom: 0.5rem;
      flex-wrap: wrap; gap: 0.4rem;
    }
    .reply-label {
      font-size: 0.78rem; font-weight: 700;
      padding: 0.2rem 0.8rem; border-radius: 20px;
    }
    .reply-time  { font-size: 0.78rem; color: #aaa; }
    .reply-text  { font-size: 0.93rem; color: #1a1a2e; line-height: 1.6; }

    .no-reply-label {
      padding: 0.9rem 1.5rem;
      font-size: 0.85rem; color: #aaa;
      font-style: italic;
      border-top: 1px solid #f5eef9;
    }

    .empty-state { text-align: center; padding: 4rem 2rem; color: #aaa; }
    .empty-state i { font-size: 4rem; color: #f5d0ef; display: block; margin-bottom: 1rem; }
    .empty-state h3 { font-size: 1.2rem; color: #bbb; margin-bottom: 0.5rem; }
    .empty-state p  { font-size: 0.9rem; margin-bottom: 1.5rem; }
    .btn-go {
      display: inline-block; padding: 0.8rem 2rem;
      background: var(--pink); color: #fff;
      border-radius: 8px; text-decoration: none;
      font-weight: 600; font-size: 0.95rem;
      transition: background 0.3s, transform 0.15s;
    }
    .btn-go:hover { background: var(--teal); color: #333; transform: translateY(-2px); }

    .loading { text-align: center; padding: 3rem; color: #bbb; font-size: 0.95rem; }
    .loading i {
      font-size: 2rem; color: var(--pink); display: block;
      margin-bottom: 0.8rem; animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .error-state {
      background: #fff0f5; border: 1.5px solid var(--border);
      border-radius: 10px; padding: 1.2rem 1.5rem;
      color: #c0392b; font-size: 0.9rem; text-align: center;
    }

    .followup-section {
      padding: 1rem 1.5rem;
      border-top: 1px solid #f5eef9;
      background: #fef9ff;
    }
    .followup-section textarea {
      width: 100%; min-height: 70px; padding: 10px 14px;
      border: 1.5px solid #f5d0ef; border-radius: 8px;
      font-family: 'Poppins', sans-serif; font-size: 14px;
      resize: vertical; outline: none; box-sizing: border-box;
    }
    .followup-section textarea:focus { border-color: var(--pink); }
    .followup-section button {
      margin-top: 8px; padding: 0.6rem 1.5rem;
      background: var(--pink); color: #fff; border: none;
      border-radius: 8px; font-family: 'Poppins', sans-serif;
      font-weight: 600; cursor: pointer; transition: background 0.2s;
    }
    .followup-section button:hover { background: var(--pink-dark); }
  </style>
</head>
<body>

<header>
  <a href="index.php" class="logo">RIMS<span>.</span></a>
  <nav class="navbar">
    <a href="index.php#home">Home</a>
    <a href="index.php#about">About Us</a>
    <a href="index.php#products">Products</a>
    <a href="index.php#contact">Contact</a>
    <a href="orders.php">My Orders</a>
    <a href="my_messages.php" style="color:#f17dda;">My Messages</a>
  </nav>
  <div class="icons">
    <i class="fas fa-user" id="userIcon" onclick="handleUser()" style="cursor:pointer" title="Account"></i>
    <i class="fas fa-shopping-cart" onclick="goToCart()" style="cursor:pointer" title="Shop"></i>
  </div>
</header>

<div class="msgs-section">

  <h1 class="page-heading">My <span>Messages</span></h1>
  <p class="page-sub">
    Welcome back, <strong><?= $clientName ?></strong>! Here are your conversations with RIMS.
  </p>

  <div class="top-bar">
    <div class="summary-pills">
      <div class="pill">Messages Sent: <span id="totalMsgs">–</span></div>
      <div class="pill">Replies Received: <span id="totalReplies">–</span></div>
    </div>
    <button onclick="loadMessages()" style="
      background:#fff; border:1.5px solid var(--border);
      color:var(--pink-dark); border-radius:8px;
      padding:0.45rem 1.1rem; font-family:'Poppins',sans-serif;
      font-weight:600; font-size:0.85rem; cursor:pointer;">
      <i class="fas fa-sync-alt" style="margin-right:0.4rem;"></i> Refresh
    </button>
  </div>

  <div id="msgsList">
    <div class="loading">
      <i class="fas fa-circle-notch"></i>
      Loading your messages...
    </div>
  </div>

</div>

<script>
  window.addEventListener('DOMContentLoaded', () => {
    document.getElementById('userIcon').style.color = '#aafdfd';
    loadMessages();
  });

  function loadMessages() {
    const list = document.getElementById('msgsList');
    list.innerHTML = `<div class="loading">
      <i class="fas fa-circle-notch"></i> Loading your messages...
    </div>`;

    fetch('get_my_replies.php')
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          list.innerHTML = `<div class="error-state">❌ ${data.error || 'Could not load messages.'}</div>`;
          return;
        }
        renderMessages(data.messages);
      })
      .catch(() => {
        list.innerHTML = `<div class="error-state">
          ❌ Network error. Please check your connection.
        </div>`;
      });
  }

  function renderMessages(messages) {
    const list = document.getElementById('msgsList');

    // Count only admin replies for "Replies Received"
    const totalReplies = messages.reduce((sum, m) =>
      sum + m.replies.filter(r => r.sender === 'admin').length, 0);
    document.getElementById('totalMsgs').textContent    = messages.length;
    document.getElementById('totalReplies').textContent = totalReplies;

    if (messages.length === 0) {
      list.innerHTML = `
        <div class="empty-state">
          <i class="fas fa-comment-slash"></i>
          <h3>No messages yet</h3>
          <p>You haven't contacted us yet. Use the contact form on the homepage!</p>
          <a href="index.php#contact" class="btn-go">
            <i class="fas fa-envelope" style="margin-right:0.4rem;"></i> Contact Us
          </a>
        </div>`;
      return;
    }

    list.innerHTML = messages.map(m => {
      const sentDate    = formatDate(m.sent_at);
      const statusBadge = m.is_read == 1
        ? `<span class="badge-read">✓ Seen by Admin</span>`
        : `<span class="badge-unread">⏳ Pending</span>`;

      let repliesHTML = '';
      if (m.replies.length > 0) {
        repliesHTML = `<div class="replies-section">` +
          m.replies.map(r => `
            <div class="reply-bubble" style="background:${r.sender === 'admin' ? '#f0f7ff' : '#fff0f7'};">
              <div class="reply-header">
                <span class="reply-label" style="background:${r.sender === 'admin' ? '#e3f2fd' : '#fce4ec'};color:${r.sender === 'admin' ? '#0d47a1' : '#c2185b'};">
                  ${r.sender === 'admin' ? '🏪 RIMS Support' : '👤 You'}
                </span>
                <span class="reply-time">${formatDate(r.time)}</span>
              </div>
              <div class="reply-text">${esc(r.text).replace(/\n/g, '<br>')}</div>
            </div>
          `).join('') + `</div>`;
      } else {
        repliesHTML = `<div class="no-reply-label">
          <i class="fas fa-clock" style="margin-right:0.4rem;"></i>
          No reply yet — we'll get back to you soon!
        </div>`;
      }

      return `
        <div class="msg-thread">
          <div class="customer-msg">
            <div class="msg-header">
              <span class="msg-label">
                <i class="fas fa-user" style="margin-right:0.3rem;"></i> You
              </span>
              <div style="display:flex;gap:0.6rem;align-items:center;flex-wrap:wrap;">
                ${statusBadge}
                <span class="msg-time">${sentDate}</span>
              </div>
            </div>
            <div class="msg-text">${esc(m.message).replace(/\n/g, '<br>')}</div>
          </div>
          ${repliesHTML}
          <div class="followup-section">
            <form onsubmit="sendReply(event, ${m.id})">
              <textarea name="reply_text" placeholder="Write a follow-up message..." required></textarea>
              <button type="submit">
                <i class="fas fa-paper-plane" style="margin-right:5px;"></i> Send Follow-up
              </button>
            </form>
          </div>
        </div>`;
    }).join('');
  }

  function sendReply(e, messageId) {
    e.preventDefault();
    const form = e.target;
    const text = form.reply_text.value.trim();
    const btn  = form.querySelector('button');
    if (!text) return;
    btn.disabled     = true;
    btn.textContent  = 'Sending...';
    fetch('send_reply.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ message_id: messageId, reply_text: text })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        form.reply_text.value = '';
        loadMessages();
      } else {
        alert('Error: ' + data.error);
        btn.disabled    = false;
        btn.textContent = 'Send Follow-up';
      }
    })
    .catch(() => {
      alert('Network error. Please try again.');
      btn.disabled    = false;
      btn.textContent = 'Send Follow-up';
    });
  }

  function formatDate(str) {
    return new Date(str).toLocaleDateString('en-GB', {
      day: '2-digit', month: 'short', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    });
  }

  function esc(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function handleUser() {
    if (confirm('You are logged in.\n\nClick OK to Logout, or Cancel to stay.')) {
      localStorage.removeItem('isLoggedIn');
      localStorage.removeItem('clientName');
      localStorage.removeItem('clientEmail');
      window.location.href = 'logout.php';
    }
  }

  function goToCart() {
    window.location.href = 'purchase.php';
  }
</script>

</body>
</html>