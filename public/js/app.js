/* HireHub – Main JS */

document.addEventListener('DOMContentLoaded', () => {

  // ── Sidebar toggle (mobile) ──────────────────────────────────
  const toggleBtn = document.getElementById('sidebarToggle');
  const sidebar   = document.getElementById('sidebar');
  const overlay   = document.getElementById('sidebarOverlay');

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay?.classList.toggle('show');
    });
    overlay?.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('show');
    });
  }

  // ── Auto-dismiss alerts ──────────────────────────────────────
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 300);
    }, 4000);
  });

  // ── Confirm delete ───────────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  // ── Mark notification read ────────────────────────────────────
  document.querySelectorAll('.notif-item[data-id]').forEach(el => {
    el.addEventListener('click', () => {
      fetch(BASE_URL + '/api/mark_notification.php?id=' + el.dataset.id);
      el.classList.remove('unread');
      const dot = document.querySelector('.notif-dot');
      const badge = document.querySelector('.notif-count');
      if (badge) {
        const count = parseInt(badge.textContent) - 1;
        if (count <= 0) { badge.remove(); dot?.remove(); }
        else badge.textContent = count;
      }
    });
  });

  // ── Job search live filter ────────────────────────────────────
  const searchInput = document.getElementById('jobSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', debounce(() => {
      document.getElementById('searchForm')?.submit();
    }, 600));
  }

  // ── Save job toggle ───────────────────────────────────────────
  document.querySelectorAll('.save-job-btn').forEach(btn => {
    btn.addEventListener('click', async function () {
      const jobId = this.dataset.jobId;
      const res = await fetch(`${BASE_URL}/api/save_job.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ job_id: jobId, csrf: CSRF_TOKEN })
      });
      const data = await res.json();
      if (data.saved) {
        this.innerHTML = '<i class="bi bi-bookmark-fill"></i>';
        this.classList.add('text-primary');
      } else {
        this.innerHTML = '<i class="bi bi-bookmark"></i>';
        this.classList.remove('text-primary');
      }
    });
  });

  // ── File input label ──────────────────────────────────────────
  document.querySelectorAll('.file-input-wrapper input[type=file]').forEach(input => {
    input.addEventListener('change', function () {
      const label = this.closest('.file-input-wrapper')?.querySelector('.file-label');
      if (label && this.files[0]) label.textContent = this.files[0].name;
    });
  });

  // ── Salary range display ──────────────────────────────────────
  const minSalary = document.getElementById('salary_min');
  const maxSalary = document.getElementById('salary_max');
  const salaryDisplay = document.getElementById('salaryDisplay');
  if (minSalary && maxSalary && salaryDisplay) {
    const update = () => {
      const min = parseInt(minSalary.value) || 0;
      const max = parseInt(maxSalary.value) || 0;
      if (min || max) {
        salaryDisplay.textContent = `PKR ${(min/1000).toFixed(0)}k – ${(max/1000).toFixed(0)}k`;
      }
    };
    minSalary.addEventListener('input', update);
    maxSalary.addEventListener('input', update);
  }

  // ── Tooltip init ──────────────────────────────────────────────
  const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
  tooltipEls.forEach(el => new bootstrap.Tooltip(el));

});

// Debounce helper
function debounce(fn, delay) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
}
