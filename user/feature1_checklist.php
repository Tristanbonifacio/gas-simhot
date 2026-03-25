<?php
/**
 * FEATURE 1 — Safety Protocol Checklist
 * Include this in user/user_dashboard.php BEFORE </body>
 *
 * How to use:
 *   1. Copy the PHP section to top of user_dashboard.php
 *   2. Copy the HTML modal before </body>
 *   3. Copy the JS and merge with your existing <script>
 *   4. Replace your triggerLeak() call with triggerLeakWithChecklist()
 */

// ── PHP: Handle checklist task log (add to top of user_dashboard.php) ──
/*
if (isset($_POST['log_checklist_task']) && !empty($_SESSION['user_id'])) {
    $uid      = (int)$_SESSION['user_id'];
    $task     = $db->real_escape_string(trim($_POST['task_name']));
    $log_id   = (int)($_POST['log_id'] ?? 0);

    $db->query("INSERT INTO safety_checklists (user_id, log_id, task_name)
                VALUES ($uid, " . ($log_id ?: 'NULL') . ", '$task')");

    $action = "Checklist: $task";
    $db->query("INSERT INTO user_activity_logs (user_id, action) VALUES ($uid, '$action')");

    echo json_encode(['status' => 'ok']);
    exit();
}
*/
?>

<!-- ── SAFETY CHECKLIST MODAL ────────────────────────────────── -->
<div class="checklist-overlay" id="checklistOverlay">
  <div class="checklist-box">

    <div class="cl-header">
      <span class="cl-icon">🚨</span>
      <div>
        <h3 class="cl-title">EMERGENCY PROTOCOL</h3>
        <p class="cl-sub">Complete all safety tasks before resetting the system</p>
      </div>
    </div>

    <div class="cl-alert">
      ⚠️ Gas leak detected at <strong id="cl-station">your station</strong>.
      Admin has been notified. Follow these steps immediately.
    </div>

    <div class="cl-tasks" id="clTasks">

      <div class="cl-task" id="task-0">
        <div class="cl-checkbox" onclick="completeTask(0, 'Open All Windows and Ventilate Area')">
          <span class="cl-check-icon">☐</span>
        </div>
        <div class="cl-task-info">
          <div class="cl-task-title">Open All Windows and Ventilate Area</div>
          <div class="cl-task-desc">Ensure proper air circulation to disperse gas concentration</div>
        </div>
        <div class="cl-task-status" id="task-status-0"></div>
      </div>

      <div class="cl-task" id="task-1">
        <div class="cl-checkbox" onclick="completeTask(1, 'Shut Off Main Gas Valve')">
          <span class="cl-check-icon">☐</span>
        </div>
        <div class="cl-task-info">
          <div class="cl-task-title">Shut Off Main Gas Valve</div>
          <div class="cl-task-desc">Locate and close the main gas supply valve immediately</div>
        </div>
        <div class="cl-task-status" id="task-status-1"></div>
      </div>

      <div class="cl-task" id="task-2">
        <div class="cl-checkbox" onclick="completeTask(2, 'Evacuate Personnel from Area')">
          <span class="cl-check-icon">☐</span>
        </div>
        <div class="cl-task-info">
          <div class="cl-task-title">Evacuate Personnel from Area</div>
          <div class="cl-task-desc">Ensure all personnel have safely exited the affected zone</div>
        </div>
        <div class="cl-task-status" id="task-status-2"></div>
      </div>

    </div>

    <div class="cl-progress-wrap">
      <div class="cl-progress-label">
        <span>Safety Tasks Completed</span>
        <span id="cl-progress-text">0 / 3</span>
      </div>
      <div class="cl-progress-bar-bg">
        <div class="cl-progress-bar" id="clProgressBar" style="width:0%"></div>
      </div>
    </div>

    <div class="cl-footer">
      <button class="cl-btn-reset" id="clResetBtn" disabled onclick="executeReset()">
        🔄 Reset System
      </button>
      <p class="cl-hint" id="clHint">Complete all 3 tasks to enable reset</p>
    </div>

  </div>
</div>

<style>
/* ── Checklist Overlay ── */
.checklist-overlay{
  position:fixed;inset:0;
  background:rgba(0,0,0,.85);
  z-index:10000;
  display:none;
  align-items:center;
  justify-content:center;
  backdrop-filter:blur(8px);
}
.checklist-overlay.show{display:flex;}

.checklist-box{
  background:#100818;
  border:2px solid #ff4c7a;
  border-radius:16px;
  padding:2rem;
  max-width:500px;
  width:90%;
  box-shadow:0 0 80px rgba(255,76,122,.3);
  animation:clIn .4s cubic-bezier(.175,.885,.32,1.275) both;
}
@keyframes clIn{from{opacity:0;transform:scale(.85) translateY(20px)}to{opacity:1;transform:scale(1) translateY(0)}}

.cl-header{display:flex;align-items:center;gap:1rem;margin-bottom:1.2rem;}
.cl-icon{font-size:2.5rem;animation:clPulse .8s ease-in-out infinite alternate;}
@keyframes clPulse{from{transform:scale(1)}to{transform:scale(1.15)}}
.cl-title{font-family:'Share Tech Mono',monospace;font-size:1.1rem;color:#ff4c7a;letter-spacing:.08em;margin-bottom:.2rem;}
.cl-sub{font-size:.78rem;color:#4a5280;}

.cl-alert{background:rgba(255,76,122,.1);border:1px solid rgba(255,76,122,.3);border-radius:8px;padding:.8rem 1rem;font-size:.83rem;color:#ffb3c6;margin-bottom:1.4rem;line-height:1.5;}

/* Tasks */
.cl-tasks{display:flex;flex-direction:column;gap:.8rem;margin-bottom:1.3rem;}

.cl-task{
  display:flex;align-items:center;gap:.9rem;
  background:rgba(255,255,255,.03);
  border:1px solid rgba(30,38,64,.8);
  border-radius:10px;
  padding:.9rem 1rem;
  transition:border-color .3s,background .3s;
  cursor:pointer;
}
.cl-task:hover{border-color:rgba(255,76,122,.3);background:rgba(255,76,122,.05);}
.cl-task.completed{border-color:rgba(0,229,160,.4);background:rgba(0,229,160,.05);}
.cl-task.completed .cl-task-title{text-decoration:line-through;color:#4a5280;}

.cl-checkbox{
  width:36px;height:36px;border-radius:8px;
  border:2px solid rgba(255,76,122,.4);
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;transition:all .3s;cursor:pointer;
}
.cl-task.completed .cl-checkbox{border-color:#00e5a0;background:rgba(0,229,160,.15);}
.cl-check-icon{font-size:1.2rem;transition:all .3s;}
.cl-task.completed .cl-check-icon::before{content:'✓';color:#00e5a0;font-weight:700;}

.cl-task-info{flex:1;}
.cl-task-title{font-size:.88rem;font-weight:600;color:#d4d9f0;margin-bottom:.2rem;}
.cl-task-desc{font-size:.73rem;color:#4a5280;line-height:1.4;}
.cl-task-status{font-size:.72rem;color:#00e5a0;font-family:'Share Tech Mono',monospace;white-space:nowrap;}

/* Progress */
.cl-progress-wrap{margin-bottom:1.3rem;}
.cl-progress-label{display:flex;justify-content:space-between;font-size:.72rem;color:#4a5280;margin-bottom:.4rem;}
.cl-progress-bar-bg{background:rgba(22,51,80,.5);border-radius:10px;height:8px;overflow:hidden;}
.cl-progress-bar{height:100%;border-radius:10px;background:linear-gradient(90deg,#ff4c7a,#ff4c7a);transition:width .4s ease,background .4s;}
.cl-progress-bar.done{background:linear-gradient(90deg,#00d4ff,#00e5a0);}

/* Footer */
.cl-footer{text-align:center;}
.cl-btn-reset{
  width:100%;background:#00e5a0;color:#050d1a;border:none;
  border-radius:8px;padding:.85rem;font-family:'Share Tech Mono',monospace;
  font-size:.95rem;font-weight:700;letter-spacing:.06em;cursor:pointer;
  transition:background .2s,box-shadow .2s,opacity .2s;
}
.cl-btn-reset:disabled{background:#1e2640;color:#4a5280;cursor:not-allowed;opacity:.6;}
.cl-btn-reset:not(:disabled):hover{background:#33ffb8;box-shadow:0 0 20px rgba(0,229,160,.4);}
.cl-hint{font-size:.75rem;color:#4a5280;margin-top:.6rem;}
</style>

<script>
/* ── FEATURE 1: Safety Checklist JS ──────────────────────────── */
const CHECKLIST_LOG_URL = '<?= base_url() ?>core/log_checklist.php';

let completedTasks = new Set();
let currentLeakLogId = 0;
const TOTAL_TASKS = 3;

// Call this instead of triggerLeak() — replaces existing function
function triggerLeakWithChecklist() {
  // 1. Set gauge to danger
  setGauge(450, 'danger');
  showBanner('leak');
  startBuzzer();

  // 2. Log the leak to server
  const fd = new FormData();
  fd.append('action', 'Leak Detected');
  fetch(LOG_ACT_URL, {method:'POST', body:fd})
    .then(r => r.json())
    .then(d => { currentLeakLogId = d.log_id || 0; })
    .catch(()=>{});

  // 3. Show checklist modal
  completedTasks.clear();
  resetChecklistUI();
  document.getElementById('cl-station').textContent =
    document.querySelector('.station')?.textContent?.replace('📍','').trim() || 'your station';
  document.getElementById('checklistOverlay').classList.add('show');
}

function resetChecklistUI() {
  for (let i = 0; i < TOTAL_TASKS; i++) {
    const task = document.getElementById('task-' + i);
    if (task) {
      task.classList.remove('completed');
      const icon = task.querySelector('.cl-check-icon');
      if (icon) icon.textContent = '☐';
    }
    const status = document.getElementById('task-status-' + i);
    if (status) status.textContent = '';
  }
  updateProgress();
}

function completeTask(index, taskName) {
  if (completedTasks.has(index)) return; // already done

  completedTasks.add(index);

  // Update UI
  const task = document.getElementById('task-' + index);
  task.classList.add('completed');
  const now = new Date().toLocaleTimeString('en-PH', {hour12:false});
  document.getElementById('task-status-' + index).textContent = '✓ ' + now;

  // Log to server
  const fd = new FormData();
  fd.append('log_checklist_task', '1');
  fd.append('task_name', taskName);
  fd.append('log_id', currentLeakLogId);
  fetch(LOG_ACT_URL, {method:'POST', body:fd}).catch(()=>{});

  updateProgress();
}

function updateProgress() {
  const done  = completedTasks.size;
  const pct   = Math.round((done / TOTAL_TASKS) * 100);
  const bar   = document.getElementById('clProgressBar');
  const btn   = document.getElementById('clResetBtn');
  const hint  = document.getElementById('clHint');
  const label = document.getElementById('cl-progress-text');

  label.textContent = done + ' / ' + TOTAL_TASKS;
  bar.style.width   = pct + '%';

  if (done >= TOTAL_TASKS) {
    bar.classList.add('done');
    btn.disabled = false;
    hint.textContent = '✅ All tasks complete — you can now reset the system';
    hint.style.color = '#00e5a0';
  } else {
    bar.classList.remove('done');
    btn.disabled = true;
    hint.textContent = 'Complete all ' + TOTAL_TASKS + ' tasks to enable reset (' + (TOTAL_TASKS - done) + ' remaining)';
    hint.style.color = '#4a5280';
  }
}

function executeReset() {
  // Close modal
  document.getElementById('checklistOverlay').classList.remove('show');
  // Reset gauge and system
  setGauge(0, 'safe');
  showBanner(null);
  stopBuzzer();
  // Log reset
  const fd = new FormData();
  fd.append('action', 'System Reset');
  fetch(LOG_ACT_URL, {method:'POST', body:fd}).catch(()=>{});
}

// Override original buttons — replace onclick in HTML:
// btn-leak  → onclick="triggerLeakWithChecklist()"
// btn-reset → onclick="executeReset()" (keep as is, checklist handles enable/disable)
</script>