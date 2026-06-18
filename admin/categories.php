<?php
require_once __DIR__ . '/../core/functions.php';
requireAuth('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? 'briefcase');
        if ($name) {
            Database::query("INSERT INTO job_categories (name, icon) VALUES (?,?)", [$name, $icon]);
            setFlash('success', 'Category added.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['cat_id'] ?? 0);
        Database::query("DELETE FROM job_categories WHERE id = ?", [$id]);
        setFlash('success', 'Category deleted.');
    }
    redirect('/admin/categories.php');
}

$user = currentUser();
$pageTitle  = 'Categories';
$activePage = 'categories';

$categories = Database::fetchAll("
  SELECT cat.*, COUNT(j.id) AS job_count
  FROM job_categories cat
  LEFT JOIN jobs j ON j.category_id = cat.id
  GROUP BY cat.id ORDER BY cat.name
");

include __DIR__ . '/../views/partials/head.php';
?>
<div class="app-shell">
<?php include __DIR__ . '/../views/partials/sidebar.php'; ?>
<div class="main-content">
<?php include __DIR__ . '/../views/partials/navbar.php'; ?>
<div class="page-content">
<?= renderFlash() ?>

<div class="page-header">
  <h2>Job Categories</h2>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCatModal">
    <i class="bi bi-plus-circle me-1"></i> Add Category
  </button>
</div>

<div class="row g-3">
  <?php foreach ($categories as $cat): ?>
  <div class="col-sm-6 col-md-4 col-lg-3">
    <div class="card">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon primary" style="width:44px;height:44px;border-radius:10px;flex-shrink:0">
          <i class="bi bi-<?= e($cat['icon']) ?>"></i>
        </div>
        <div class="flex-1 min-w-0">
          <div class="fw-700 text-truncate"><?= e($cat['name']) ?></div>
          <div class="text-muted" style="font-size:12px"><?= $cat['job_count'] ?> jobs</div>
        </div>
        <form method="POST" class="d-inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
          <button class="btn btn-icon btn-outline-danger"
                  data-confirm="Delete '<?= e($cat['name']) ?>'?"
                  <?= $cat['job_count'] > 0 ? 'disabled title="Has active jobs"' : '' ?>>
            <i class="bi bi-trash"></i>
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCatModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:var(--radius-lg)">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-700">Add Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="form-label">Category Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Data Science" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Bootstrap Icon Name</label>
            <input type="text" name="icon" class="form-control" placeholder="e.g. graph-up" value="briefcase">
            <div class="form-text"><a href="https://icons.getbootstrap.com" target="_blank">Browse icons</a></div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Add Category</button>
        </form>
      </div>
    </div>
  </div>
</div>

</div></div></div>
<?php include __DIR__ . '/../views/partials/footer.php'; ?>
