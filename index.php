<?php
// ============================================================
//  index.php  –  Dashboard (protected)
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – Tasko</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
/* ── Design Tokens ─────────────────────────────────────── */
:root {
  --ink:      #0d0d12;
  --paper:    #f5f3ee;
  --surface:  #ffffff;
  --accent:   #e8643a;
  --accent2:  #3a8be8;
  --mid:      #c9c4ba;
  --muted:    #7a7568;
  --done:     #16a34a;
  --cancel:   #9ca3af;
  --warn:     #d97706;
  --danger:   #dc2626;
}
*, *::before, *::after { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
  font-family: 'DM Sans', sans-serif;
  background: var(--paper);
  color: var(--ink);
  min-height: 100vh;
}
.syne { font-family: 'Syne', sans-serif; }

/* ── Noise texture ─────────────────────────────────────── */
body::before {
  content: '';
  position: fixed; inset: 0; pointer-events: none; z-index: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.035'/%3E%3C/svg%3E");
  background-size: 180px;
}

/* ── Layout ────────────────────────────────────────────── */
.container-inner { max-width: 900px; margin: 0 auto; padding: 0 1.25rem; }

/* ── Header ────────────────────────────────────────────── */
header {
  position: sticky; top: 0; z-index: 50;
  background: rgba(245,243,238,.88);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(201,196,186,.5);
}
header .inner {
  display: flex; align-items: center; justify-content: space-between;
  height: 60px;
}
.logo { font-family:'Syne',sans-serif; font-weight:800; font-size:1.5rem; }
.logo span { color: var(--accent); }
.btn-logout {
  font-size:.8rem; font-weight:600; letter-spacing:.04em;
  padding:.4rem .9rem; border-radius:8px;
  border: 1.5px solid var(--mid);
  transition: border-color .2s, background .2s;
}
.btn-logout:hover { border-color: var(--accent); background: #fff5f2; }

/* ── Hero greeting ─────────────────────────────────────── */
.greeting-section { padding: 2.5rem 0 1.5rem; }
.greeting-pill {
  display: inline-block; font-size:.75rem; font-weight:600;
  letter-spacing:.08em; text-transform:uppercase;
  background: var(--ink); color: var(--paper);
  padding:.25rem .75rem; border-radius:99px; margin-bottom:.9rem;
}
.greeting-name { font-size:2.25rem; line-height:1.15; }
.greeting-name em { font-style:normal; color:var(--accent); }

/* ── Quick-add bar ─────────────────────────────────────── */
.quick-add-bar {
  display: flex; gap:.75rem; align-items: center;
  background: var(--surface); border: 1.5px solid var(--mid);
  border-radius: 14px; padding: .75rem 1rem;
  transition: border-color .2s, box-shadow .2s;
}
.quick-add-bar:focus-within {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(232,100,58,.12);
}
.quick-add-bar input {
  flex:1; background:transparent; border:none; outline:none;
  font-family: inherit; font-size:.95rem; color: var(--ink);
}
.quick-add-bar input::placeholder { color: var(--muted); }
.btn-add {
  background: var(--ink); color: var(--paper);
  padding:.55rem 1.1rem; border-radius:9px; font-size:.85rem; font-weight:600;
  transition: background .2s, transform .15s;
  white-space: nowrap;
}
.btn-add:hover { background: var(--accent); transform: translateY(-1px); }

/* ── Notification strip ────────────────────────────────── */
#notif-strip { display:none; }
.notif-banner {
  border-radius: 12px; padding: 1rem 1.25rem;
  background: #fffbeb; border: 1.5px solid #fcd34d;
  margin-bottom: 1.25rem;
}
.notif-item { font-size:.85rem; display:flex; align-items:center; gap:.5rem; padding:.2rem 0; }
.notif-dot-overdue { width:8px; height:8px; border-radius:50%; background: var(--danger); flex-shrink:0; }
.notif-dot-soon    { width:8px; height:8px; border-radius:50%; background: var(--warn);   flex-shrink:0; }

/* ── Filter tabs ───────────────────────────────────────── */
.filter-tabs { display:flex; gap:.5rem; flex-wrap:wrap; }
.tab-btn {
  padding:.35rem .9rem; border-radius:99px; font-size:.82rem; font-weight:500;
  border: 1.5px solid var(--mid); background: var(--surface);
  transition: background .15s, border-color .15s, color .15s;
  cursor: pointer;
}
.tab-btn.active, .tab-btn:hover {
  background: var(--ink); border-color: var(--ink); color: var(--paper);
}

/* ── Counts ────────────────────────────────────────────── */
.stat-card {
  background: var(--surface); border-radius:12px;
  border: 1.5px solid var(--mid); padding:.8rem 1rem;
  text-align:center;
}
.stat-num { font-family:'Syne',sans-serif; font-size:1.75rem; font-weight:700; line-height:1; }
.stat-lbl { font-size:.72rem; font-weight:500; letter-spacing:.05em; color: var(--muted); text-transform:uppercase; margin-top:.2rem; }

/* ── Task list ─────────────────────────────────────────── */
#task-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:.65rem; }

.task-card {
  background: var(--surface); border: 1.5px solid var(--mid);
  border-radius: 14px; padding: 1rem 1.1rem;
  display: flex; align-items: flex-start; gap: 1rem;
  transition: border-color .2s, box-shadow .2s, opacity .3s, transform .3s;
  position: relative; overflow: hidden;
}
.task-card:hover { border-color: #a8a39b; box-shadow: 0 4px 16px rgba(0,0,0,.06); }
.task-card.status-done     { opacity: .65; }
.task-card.status-cancelled{ opacity: .5; }
.task-card .accent-bar {
  position:absolute; left:0; top:0; bottom:0; width:4px; border-radius:99px 0 0 99px;
}
.status-In.Progress .accent-bar, .accent-inprogress { background: var(--accent2); }
.status-done    .accent-bar { background: var(--done); }
.status-cancelled .accent-bar { background: var(--cancel); }

.task-content { flex:1; min-width:0; padding-left:.25rem; }
.task-title { font-weight:600; font-size:.95rem; word-break:break-word; }
.task-title.done { text-decoration: line-through; color: var(--muted); }
.task-desc { font-size:.82rem; color: var(--muted); margin-top:.2rem; word-break:break-word; }
.task-meta { display:flex; gap:.65rem; flex-wrap:wrap; align-items:center; margin-top:.55rem; }

.badge {
  display:inline-flex; align-items:center; gap:.3rem;
  font-size:.72rem; font-weight:600; padding:.18rem .6rem;
  border-radius:99px; border: 1px solid;
}
.badge-inprogress { background:#eff6ff; border-color:#93c5fd; color:#1d4ed8; }
.badge-done       { background:#f0fdf4; border-color:#86efac; color:#15803d; }
.badge-cancelled  { background:#f9fafb; border-color:#d1d5db; color:#6b7280; }

.due-chip {
  font-size:.72rem; display:inline-flex; align-items:center; gap:.25rem;
  color: var(--muted);
}
.due-chip.overdue { color: var(--danger); font-weight:600; }
.due-chip.soon    { color: var(--warn); font-weight:600; }

.task-actions { display:flex; gap:.4rem; flex-shrink:0; padding-top:.1rem; }
.icon-btn {
  width:32px; height:32px; border-radius:8px;
  display:grid; place-items:center;
  border: 1.5px solid var(--mid);
  transition: background .15s, border-color .15s;
}
.icon-btn:hover { background: var(--paper); border-color: var(--ink); }
.icon-btn.delete:hover { border-color: var(--danger); background:#fee2e2; }

/* ── Empty state ────────────────────────────────────────── */
#empty-state {
  text-align:center; padding: 4rem 1rem;
  color: var(--muted); display:none;
}
#empty-state .big-icon { font-size:3rem; margin-bottom:1rem; }

/* ── Modal ─────────────────────────────────────────────── */
.modal-overlay {
  position:fixed; inset:0; z-index:100;
  background: rgba(13,13,18,.45);
  backdrop-filter: blur(4px);
  display:none; place-items:center; padding:1rem;
}
.modal-overlay.open { display:grid; }
.modal-box {
  background: var(--surface); border-radius:20px;
  width:100%; max-width:500px;
  padding:2rem; position:relative;
  box-shadow: 0 24px 60px rgba(0,0,0,.18);
  animation: modalIn .25s cubic-bezier(.22,1,.36,1) both;
}
@keyframes modalIn { from{opacity:0;transform:translateY(20px) scale(.97)} to{opacity:1;transform:none} }
.modal-close {
  position:absolute; top:1rem; right:1rem;
  width:28px; height:28px; border-radius:50%;
  display:grid; place-items:center;
  border:1.5px solid var(--mid); font-size:.9rem; line-height:1;
}
.modal-close:hover { background: var(--paper); }

.form-label { display:block; font-size:.82rem; font-weight:500; margin-bottom:.35rem; }
.form-input {
  width:100%; background:#fff; border:1.5px solid var(--mid);
  border-radius:9px; padding:.65rem .85rem; font-family:inherit;
  font-size:.9rem; color:var(--ink);
  transition:border-color .2s, box-shadow .2s;
}
.form-input:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(232,100,58,.12); }
textarea.form-input { resize:vertical; min-height:90px; }
select.form-input { cursor:pointer; }

.btn-primary {
  background:var(--ink); color:var(--paper);
  padding:.65rem 1.4rem; border-radius:9px; font-size:.9rem; font-weight:600;
  transition:background .2s, transform .15s;
}
.btn-primary:hover { background:var(--accent); transform:translateY(-1px); }
.btn-ghost {
  padding:.65rem 1.1rem; border-radius:9px; font-size:.9rem;
  border:1.5px solid var(--mid);
  transition:background .15s, border-color .15s;
}
.btn-ghost:hover { background:var(--paper); }

/* ── Toast ─────────────────────────────────────────────── */
#toast-wrap { position:fixed; bottom:1.5rem; right:1.5rem; z-index:200; display:flex; flex-direction:column; gap:.5rem; }
.toast {
  background:var(--ink); color:var(--paper);
  padding:.7rem 1.2rem; border-radius:10px; font-size:.85rem; font-weight:500;
  box-shadow:0 8px 24px rgba(0,0,0,.18);
  animation: toastIn .3s cubic-bezier(.22,1,.36,1) both;
}
.toast.success { background:#15803d; }
.toast.error   { background: var(--danger); }
@keyframes toastIn { from{opacity:0;transform:translateX(24px)} to{opacity:1;transform:none} }

/* ── Spinner ────────────────────────────────────────────── */
.spinner {
  width:20px; height:20px; border:2px solid rgba(255,255,255,.3);
  border-top-color:#fff; border-radius:50%; animation:spin .6s linear infinite;
}
@keyframes spin { to{transform:rotate(360deg)} }

/* ── Skeleton loader ────────────────────────────────────── */
.skeleton { background: linear-gradient(90deg,#e8e5df 25%,#f0ede7 50%,#e8e5df 75%); background-size:200% 100%; animation:shimmer 1.2s infinite; border-radius:8px; }
@keyframes shimmer { to{background-position:-200% 0} }

/* ── Responsive ─────────────────────────────────────────── */
@media(max-width:600px){
  .greeting-name { font-size:1.6rem; }
  .stat-card     { padding:.6rem .75rem; }
  .stat-num      { font-size:1.4rem; }
}
</style>
</head>
<body>

<!-- ═══════ HEADER ═══════════════════════════════════════════ -->
<header>
  <div class="container-inner inner">
    <span class="logo">To Do List<span>.</span></span>
    <div class="flex items-center gap-3">
      <span class="text-sm hidden sm:block" style="color:var(--muted)"><?= $username ?></span>
      <a href="logout.php" class="btn-logout">Sign out</a>
    </div>
  </div>
</header>

<!-- ═══════ MAIN ═════════════════════════════════════════════ -->
<main class="container-inner relative z-10 pb-16">

  <!-- Greeting -->
  <section class="greeting-section">
    <div class="greeting-pill">Dashboard</div>
    <h1 class="syne greeting-name">Welcome back, <em><?= $username ?></em>!</h1>
    <p class="mt-1" style="color:var(--muted);font-size:.9rem">Here's what's on your plate today.</p>
  </section>

  <!-- Notifications -->
  <div id="notif-strip">
    <div class="notif-banner">
      <div class="flex items-center gap-2 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0" style="color:var(--warn)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <span class="syne font-bold text-sm" style="color:#92400e">Upcoming Deadlines</span>
      </div>
      <ul id="notif-list" class="space-y-1"></ul>
    </div>
  </div>

  <!-- Quick-add -->
  <div class="mb-6">
    <p class="text-sm font-medium mb-2" style="color:var(--muted)">What activity do you want to do today?</p>
    <div class="quick-add-bar">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" style="color:var(--muted)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      <input id="quick-input" type="text" placeholder="Type a task title and press Enter or click Add…" maxlength="150">
      <button id="quick-btn" class="btn-add">Add Task</button>
    </div>
    <p class="text-xs mt-1 ml-1" style="color:var(--muted)">Press <kbd class="bg-white border border-gray-200 rounded px-1 py-0.5 text-xs">Enter</kbd> to quick-add · or use the full form for more details</p>
  </div>

  <!-- Stats row -->
  <div class="grid grid-cols-3 gap-3 mb-6">
    <div class="stat-card">
      <div class="stat-num" id="count-inprogress" style="color:var(--accent2)">—</div>
      <div class="stat-lbl">In Progress</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" id="count-done" style="color:var(--done)">—</div>
      <div class="stat-lbl">Done</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" id="count-cancelled" style="color:var(--cancel)">—</div>
      <div class="stat-lbl">Cancelled</div>
    </div>
  </div>

  <!-- Filters + list header -->
  <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
    <div class="filter-tabs">
      <button class="tab-btn active" data-filter="all">All</button>
      <button class="tab-btn" data-filter="In Progress">In Progress</button>
      <button class="tab-btn" data-filter="Done">Done</button>
      <button class="tab-btn" data-filter="Cancelled">Cancelled</button>
    </div>
    <button id="open-full-form" class="btn-add" style="font-size:.82rem;padding:.45rem 1rem;">
      + Full Form
    </button>
  </div>

  <!-- Skeleton loaders -->
  <div id="skeleton-wrap" class="flex flex-col gap-3">
    <div class="skeleton h-20 w-full"></div>
    <div class="skeleton h-16 w-full"></div>
    <div class="skeleton h-20 w-4/5"></div>
  </div>

  <!-- Task list -->
  <ul id="task-list" class="hidden"></ul>

  <!-- Empty state -->
  <div id="empty-state">
    <div class="big-icon">📋</div>
    <p class="syne font-bold text-lg">No tasks here yet</p>
    <p class="text-sm mt-1" style="color:var(--muted)">Use the quick-add bar above to get started</p>
  </div>

</main>

<!-- ═══════ MODAL (Create / Edit) ════════════════════════════ -->
<div class="modal-overlay" id="task-modal">
  <div class="modal-box">
    <button class="modal-close" id="modal-close">✕</button>
    <h2 class="syne text-xl font-bold mb-5" id="modal-title">New Task</h2>

    <div class="space-y-4">
      <input type="hidden" id="edit-id">

      <div>
        <label class="form-label" for="f-title">Title <span style="color:var(--accent)">*</span></label>
        <input class="form-input" id="f-title" type="text" maxlength="150" placeholder="What needs to be done?">
      </div>

      <div>
        <label class="form-label" for="f-desc">Description</label>
        <textarea class="form-input" id="f-desc" placeholder="Optional details…"></textarea>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="form-label" for="f-due">Due Date</label>
          <input class="form-input" id="f-due" type="date">
        </div>
        <div>
          <label class="form-label" for="f-status">Status</label>
          <select class="form-input" id="f-status">
            <option value="In Progress">In Progress</option>
            <option value="Done">Done</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      <div id="form-error" class="text-sm py-2 px-3 rounded-lg hidden" style="background:#fee2e2;color:#991b1b"></div>

      <div class="flex gap-3 justify-end pt-1">
        <button class="btn-ghost" id="modal-cancel">Cancel</button>
        <button class="btn-primary" id="modal-save">Save Task</button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════ TOAST CONTAINER ══════════════════════════════════ -->
<div id="toast-wrap"></div>

<!-- ═══════ SCRIPT ════════════════════════════════════════════ -->
<script src="script.js"></script>
</body>
</html>
