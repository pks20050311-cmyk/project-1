// ============================================================
//  script.js  –  Tasko frontend logic (jQuery)
// ============================================================

$(function () {

  /* ── State ─────────────────────────────────────────────── */
  let allTasks  = [];
  let activeFilter = 'all';
  let isSaving  = false;

  /* ── Toast helper ──────────────────────────────────────── */
  function toast(msg, type = '') {
    const $t = $('<div class="toast"></div>').text(msg);
    if (type) $t.addClass(type);
    $('#toast-wrap').append($t);
    setTimeout(() => $t.fadeOut(300, () => $t.remove()), 3000);
  }

  /* ── Date helpers ──────────────────────────────────────── */
  function today() {
    return new Date().toISOString().split('T')[0];
  }
  function daysLeft(dateStr) {
    if (!dateStr) return null;
    const diff = Math.ceil(
      (new Date(dateStr + 'T00:00:00') - new Date(today() + 'T00:00:00')) /
      (1000 * 60 * 60 * 24)
    );
    return diff;
  }
  function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-MY', { day:'numeric', month:'short', year:'numeric' });
  }

  /* ── Status badge ──────────────────────────────────────── */
  function statusBadge(status) {
    const map = {
      'In Progress': ['badge-inprogress', '● In Progress'],
      'Done':        ['badge-done',        '✔ Done'],
      'Cancelled':   ['badge-cancelled',   '✕ Cancelled'],
    };
    const [cls, label] = map[status] || ['badge-inprogress', status];
    return `<span class="badge ${cls}">${label}</span>`;
  }

  /* ── Due chip ───────────────────────────────────────────── */
  function dueChip(dateStr, status) {
    if (!dateStr || status === 'Done' || status === 'Cancelled') {
      return dateStr ? `<span class="due-chip">📅 ${formatDate(dateStr)}</span>` : '';
    }
    const days = daysLeft(dateStr);
    let cls = 'due-chip';
    let prefix = '📅';
    let label = formatDate(dateStr);
    if (days < 0) {
      cls += ' overdue'; prefix = '🔴'; label = `Overdue by ${Math.abs(days)}d`;
    } else if (days === 0) {
      cls += ' soon'; prefix = '🟡'; label = 'Due today';
    } else if (days <= 3) {
      cls += ' soon'; prefix = '🟡'; label = `${days}d left`;
    }
    return `<span class="${cls}">${prefix} ${label}</span>`;
  }

  /* ── Render a single task card ─────────────────────────── */
  function renderCard(task) {
    const statusSlug = task.status.toLowerCase().replace(' ', '');
    const titleCls   = task.status === 'Done' ? 'task-title done' : 'task-title';
    const accentColor = {
      'In Progress': 'var(--accent2)',
      'Done':        'var(--done)',
      'Cancelled':   'var(--cancel)',
    }[task.status] || 'var(--mid)';

    return `
    <li class="task-card status-${statusSlug}" data-id="${task.id}" data-status="${task.status}">
      <div class="accent-bar" style="background:${accentColor}"></div>
      <div class="task-content">
        <div class="${titleCls}">${escHtml(task.title)}</div>
        ${task.description ? `<div class="task-desc">${escHtml(task.description)}</div>` : ''}
        <div class="task-meta">
          ${statusBadge(task.status)}
          ${dueChip(task.due_date, task.status)}
        </div>
      </div>
      <div class="task-actions">
        <button class="icon-btn edit-btn" title="Edit task" data-id="${task.id}">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
        </button>
        <button class="icon-btn delete delete-btn" title="Delete task" data-id="${task.id}">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/>
          </svg>
        </button>
      </div>
    </li>`;
  }

  /* ── Escape HTML ───────────────────────────────────────── */
  function escHtml(str) {
    return $('<div>').text(str || '').html();
  }

  /* ── Apply filter & render list ────────────────────────── */
  function applyFilter() {
    const filtered = activeFilter === 'all'
      ? allTasks
      : allTasks.filter(t => t.status === activeFilter);

    const $list = $('#task-list');
    $list.empty();

    if (filtered.length === 0) {
      $list.addClass('hidden');
      $('#empty-state').show();
    } else {
      $('#empty-state').hide();
      $list.removeClass('hidden');
      filtered.forEach(t => $list.append(renderCard(t)));
    }
  }

  /* ── Update stat counters ──────────────────────────────── */
  function updateStats() {
    const counts = { 'In Progress': 0, 'Done': 0, 'Cancelled': 0 };
    allTasks.forEach(t => counts[t.status] = (counts[t.status] || 0) + 1);
    $('#count-inprogress').text(counts['In Progress']);
    $('#count-done').text(counts['Done']);
    $('#count-cancelled').text(counts['Cancelled']);
  }

  /* ── Load notifications ────────────────────────────────── */
  function loadNotifications() {
    $.get('tasks.php?action=notifications', function (res) {
      const notifs = res.notifications || [];
      if (notifs.length === 0) { $('#notif-strip').hide(); return; }
      const $list = $('#notif-list').empty();
      notifs.forEach(n => {
        const days = parseInt(n.days_left, 10);
        const isOverdue = days < 0;
        const isSoon    = days === 0;
        let label;
        if      (isOverdue) label = `Overdue by ${Math.abs(days)} day(s)`;
        else if (isSoon)    label = 'Due today!';
        else                label = `Due in ${days} day(s) – ${formatDate(n.due_date)}`;
        $list.append(`
          <li class="notif-item">
            <span class="${isOverdue ? 'notif-dot-overdue' : 'notif-dot-soon'}"></span>
            <strong>${escHtml(n.title)}</strong>: ${label}
          </li>`);
      });
      $('#notif-strip').show();
    });
  }

  /* ── Load all tasks ────────────────────────────────────── */
  function loadTasks(showSkeleton = false) {
    if (showSkeleton) {
      $('#skeleton-wrap').show();
      $('#task-list').addClass('hidden');
      $('#empty-state').hide();
    }
    $.get('tasks.php?action=list', function (res) {
      allTasks = res.tasks || [];
      $('#skeleton-wrap').hide();
      updateStats();
      applyFilter();
      loadNotifications();
    }).fail(function () {
      $('#skeleton-wrap').hide();
      toast('Failed to load tasks.', 'error');
    });
  }

  /* ── Modal helpers ─────────────────────────────────────── */
  function openModal(mode, task = null) {
    $('#form-error').addClass('hidden').text('');
    $('#edit-id').val('');
    $('#f-title').val('');
    $('#f-desc').val('');
    $('#f-due').val('');
    $('#f-status').val('In Progress');

    if (mode === 'edit' && task) {
      $('#modal-title').text('Edit Task');
      $('#edit-id').val(task.id);
      $('#f-title').val(task.title);
      $('#f-desc').val(task.description || '');
      $('#f-due').val(task.due_date || '');
      $('#f-status').val(task.status);
    } else {
      $('#modal-title').text('New Task');
    }
    $('#task-modal').addClass('open');
    setTimeout(() => $('#f-title').focus(), 50);
  }

  function closeModal() {
    $('#task-modal').removeClass('open');
  }

  /* ── Save (create / update) ────────────────────────────── */
  function saveTask() {
    if (isSaving) return;
    const id     = $('#edit-id').val();
    const title  = $('#f-title').val().trim();
    const desc   = $('#f-desc').val().trim();
    const due    = $('#f-due').val();
    const status = $('#f-status').val();

    if (!title) {
      $('#form-error').text('Title is required.').removeClass('hidden');
      $('#f-title').focus();
      return;
    }

    const action  = id ? 'update' : 'create';
    const payload = { title, description: desc, due_date: due, status };
    if (id) payload.id = parseInt(id, 10);

    isSaving = true;
    $('#modal-save').html('<span class="spinner mx-auto"></span>').prop('disabled', true);

    $.ajax({
      url:         `tasks.php?action=${action}`,
      method:      'POST',
      contentType: 'application/json',
      data:        JSON.stringify(payload),
      success(res) {
        closeModal();
        if (id) {
          const idx = allTasks.findIndex(t => t.id == id);
          if (idx > -1) allTasks[idx] = res.task;
        } else {
          allTasks.unshift(res.task);
        }
        updateStats();
        applyFilter();
        loadNotifications();
        toast(id ? 'Task updated!' : 'Task created!', 'success');
      },
      error(xhr) {
        const err = (xhr.responseJSON || {}).error || 'Something went wrong.';
        $('#form-error').text(err).removeClass('hidden');
      },
      complete() {
        isSaving = false;
        $('#modal-save').text('Save Task').prop('disabled', false);
      }
    });
  }

  /* ── Delete ────────────────────────────────────────────── */
  function deleteTask(id) {
    if (!confirm('Delete this task? This cannot be undone.')) return;
    $.ajax({
      url:         'tasks.php?action=delete',
      method:      'POST',
      contentType: 'application/json',
      data:        JSON.stringify({ id: parseInt(id, 10) }),
      success() {
        allTasks = allTasks.filter(t => t.id != id);
        updateStats();
        applyFilter();
        loadNotifications();
        toast('Task deleted.', '');
      },
      error() { toast('Could not delete task.', 'error'); }
    });
  }

  /* ── Quick-add ─────────────────────────────────────────── */
  function quickAdd() {
    const title = $('#quick-input').val().trim();
    if (!title) { $('#quick-input').focus(); return; }

    $.ajax({
      url:         'tasks.php?action=create',
      method:      'POST',
      contentType: 'application/json',
      data:        JSON.stringify({ title, description: '', due_date: '', status: 'In Progress' }),
      success(res) {
        allTasks.unshift(res.task);
        updateStats();
        applyFilter();
        $('#quick-input').val('');
        toast('Task added!', 'success');
      },
      error() { toast('Could not add task.', 'error'); }
    });
  }

  /* ── Event bindings ────────────────────────────────────── */

  // Quick-add
  $('#quick-btn').on('click', quickAdd);
  $('#quick-input').on('keydown', function (e) {
    if (e.key === 'Enter') quickAdd();
  });

  // Filter tabs
  $('.tab-btn').on('click', function () {
    $('.tab-btn').removeClass('active');
    $(this).addClass('active');
    activeFilter = $(this).data('filter');
    applyFilter();
  });

  // Open full form button
  $('#open-full-form').on('click', () => openModal('create'));

  // Modal close
  $('#modal-close, #modal-cancel').on('click', closeModal);
  $('#task-modal').on('click', function (e) {
    if ($(e.target).is('#task-modal')) closeModal();
  });

  // Save modal
  $('#modal-save').on('click', saveTask);
  $(document).on('keydown', function (e) {
    if (e.key === 'Enter' && $('#task-modal').hasClass('open') &&
        !$(e.target).is('textarea')) {
      e.preventDefault();
      saveTask();
    }
    if (e.key === 'Escape' && $('#task-modal').hasClass('open')) closeModal();
  });

  // Delegated: Edit & Delete buttons on task cards
  $('#task-list').on('click', '.edit-btn', function () {
    const id   = $(this).data('id');
    const task = allTasks.find(t => t.id == id);
    if (task) openModal('edit', task);
  });

  $('#task-list').on('click', '.delete-btn', function () {
    deleteTask($(this).data('id'));
  });

  /* ── Init ──────────────────────────────────────────────── */
  loadTasks(true);
});
