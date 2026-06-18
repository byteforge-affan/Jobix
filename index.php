<?php
require_once __DIR__ . '/core/functions.php';

// Redirect logged-in users
if (isLoggedIn()) {
    redirect('/' . $_SESSION['user_role'] . '/dashboard.php');
}

// Stats for hero
$totalJobs      = Database::count("SELECT COUNT(*) FROM jobs WHERE is_active = 1");
$totalCompanies = Database::count("SELECT COUNT(*) FROM companies");
$totalCandidates= Database::count("SELECT COUNT(*) FROM candidates");

// Featured jobs
$featuredJobs = Database::fetchAll(
    "SELECT j.*, c.company_name, c.logo, cat.name AS cat_name,
            (SELECT COUNT(*) FROM applications WHERE job_id = j.id) AS app_count
     FROM jobs j
     JOIN companies c ON j.company_id = c.id
     JOIN job_categories cat ON j.category_id = cat.id
     WHERE j.is_active = 1
     ORDER BY j.views DESC, j.created_at DESC LIMIT 6"
);

$categories = Database::fetchAll(
    "SELECT cat.*, COUNT(j.id) AS job_count
     FROM job_categories cat
     LEFT JOIN jobs j ON j.category_id = cat.id AND j.is_active = 1
     GROUP BY cat.id ORDER BY job_count DESC LIMIT 8"
);

$companies = Database::fetchAll("SELECT * FROM companies WHERE is_verified = 1 ORDER BY RAND() LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HireHub – Find Your Dream Job</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/app.css">
  <style>
    body { margin: 0; }

    /* ── Public nav ── */
    .pub-nav {
      background: rgba(15,23,42,.95);
      backdrop-filter: blur(12px);
      position: sticky; top: 0; z-index: 100;
      padding: 0 24px;
      height: 64px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .pub-nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
    .pub-nav-logo-icon {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
      border-radius: 10px; display: flex; align-items: center; justify-content: center;
      font-size: 16px; color: #fff;
    }
    .pub-nav-logo-text { font-family: var(--font-display); font-size: 18px; font-weight: 700; color: #fff; }
    .pub-nav-logo-text span { color: var(--accent); }

    /* ── Hero ── */
    .hero {
      background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #0c1a2e 100%);
      padding: 80px 24px 100px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .hero::before {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(79,70,229,.35), transparent);
    }
    .hero-eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(79,70,229,.2); border: 1px solid rgba(79,70,229,.35);
      border-radius: 30px; padding: 6px 16px;
      color: #a5b4fc; font-size: 13px; font-weight: 600;
      margin-bottom: 24px;
    }
    .hero h1 {
      font-family: var(--font-display);
      font-size: clamp(32px, 5vw, 58px);
      font-weight: 800;
      color: #fff;
      line-height: 1.1;
      margin-bottom: 16px;
    }
    .hero h1 span {
      background: linear-gradient(135deg, #818cf8, #06b6d4);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .hero p { color: rgba(255,255,255,.65); font-size: 17px; max-width: 520px; margin: 0 auto 36px; }

    /* ── Hero search ── */
    .hero-search {
      max-width: 680px; margin: 0 auto 40px;
      background: #fff; border-radius: 14px; padding: 8px;
      display: flex; gap: 8px; box-shadow: 0 20px 60px rgba(0,0,0,.3);
    }
    .hero-search input {
      flex: 1; border: none; outline: none; font-size: 15px;
      padding: 8px 14px; color: var(--gray-800);
    }

    /* ── Stats bar ── */
    .stats-bar {
      background: rgba(255,255,255,.06);
      border-radius: 12px; padding: 16px 32px;
      display: inline-flex; gap: 40px;
      margin: 0 auto;
    }
    .stats-bar .stat { color: #fff; text-align: center; }
    .stats-bar .stat-val { font-family: var(--font-display); font-size: 24px; font-weight: 800; }
    .stats-bar .stat-lbl { font-size: 12.5px; color: rgba(255,255,255,.55); }

    /* ── Section ── */
    .section { padding: 64px 0; }
    .section-header { text-align: center; margin-bottom: 40px; }
    .section-header .eyebrow {
      font-size: 12px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 2px; color: var(--primary); margin-bottom: 10px;
    }
    .section-header h2 {
      font-family: var(--font-display); font-size: clamp(22px, 3vw, 32px);
      font-weight: 800; color: var(--gray-900); margin-bottom: 10px;
    }
    .section-header p { color: var(--gray-500); max-width: 480px; margin: 0 auto; }

    /* ── Category cards ── */
    .cat-card {
      background: #fff; border: 1px solid var(--gray-200);
      border-radius: var(--radius-lg); padding: 24px;
      text-align: center; cursor: pointer; transition: var(--transition);
      text-decoration: none; color: inherit; display: block;
    }
    .cat-card:hover {
      border-color: var(--primary); transform: translateY(-3px);
      box-shadow: 0 8px 24px rgba(79,70,229,.12); color: inherit;
    }
    .cat-card-icon {
      width: 56px; height: 56px; border-radius: 14px;
      background: var(--primary-light); color: var(--primary);
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; margin: 0 auto 12px;
    }
    .cat-card:hover .cat-card-icon { background: var(--primary); color: #fff; }
    .cat-card-name { font-weight: 700; font-size: 14px; color: var(--gray-800); margin-bottom: 4px; }
    .cat-card-count { font-size: 12.5px; color: var(--gray-400); }

    /* ── Company logos ── */
    .company-logo-pill {
      display: flex; align-items: center; gap: 12px;
      background: #fff; border: 1px solid var(--gray-200);
      border-radius: 50px; padding: 10px 20px 10px 10px;
      text-decoration: none; color: inherit; transition: var(--transition);
    }
    .company-logo-pill:hover { border-color: var(--primary); box-shadow: var(--shadow); color: inherit; }
    .company-logo-pill img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }

    /* ── CTA section ── */
    .cta-section {
      background: linear-gradient(135deg, var(--primary) 0%, #7c3aed 100%);
      border-radius: var(--radius-xl); padding: 60px 40px;
      text-align: center; color: #fff; margin: 0 24px;
    }
    .cta-section h2 { font-family: var(--font-display); font-size: 30px; font-weight: 800; margin-bottom: 12px; }
    .cta-section p { opacity: .8; margin-bottom: 28px; }

    /* ── Footer ── */
    .pub-footer {
      background: var(--gray-900); color: rgba(255,255,255,.55);
      padding: 32px 24px; text-align: center; font-size: 13.5px;
    }

    @media (max-width: 576px) {
      .hero-search { flex-direction: column; }
      .stats-bar { gap: 24px; padding: 16px 20px; }
    }
  </style>
</head>
<body>

<!-- ── Nav ───────────────────────────────────────────────── -->
<nav class="pub-nav">
  <a href="<?= BASE_URL ?>/" class="pub-nav-logo">
    <div class="pub-nav-logo-icon"><i class="bi bi-briefcase-fill"></i></div>
    <span class="pub-nav-logo-text">Hire<span>Hub</span></span>
  </a>
  <div class="d-flex align-items-center gap-2">
    <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-sm btn-outline-light px-3">Sign In</a>
    <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-sm btn-primary px-3">Get Started</a>
  </div>
</nav>

<!-- ── Hero ──────────────────────────────────────────────── -->
<section class="hero">
  <div class="position-relative">
    <div class="hero-eyebrow">
      <i class="bi bi-stars"></i>
      <?= $totalJobs ?> jobs available right now
    </div>
    <h1>Find the Job That<br><span>Moves Your Career</span></h1>
    <p>Connect with top companies actively hiring talented professionals across Pakistan and beyond.</p>

    <form class="hero-search" method="GET" action="<?= BASE_URL ?>/auth/register.php">
      <i class="bi bi-search text-muted ms-2" style="font-size:15px;align-self:center"></i>
      <input type="text" name="q" placeholder="Search job title, skill, or company…">
      <button type="submit" class="btn btn-primary px-4 py-2 fw-600">Find Jobs</button>
    </form>

    <!-- Stats -->
    <div class="stats-bar">
      <div class="stat"><div class="stat-val"><?= $totalJobs ?>+</div><div class="stat-lbl">Open Jobs</div></div>
      <div class="stat"><div class="stat-val"><?= $totalCompanies ?>+</div><div class="stat-lbl">Companies</div></div>
      <div class="stat"><div class="stat-val"><?= $totalCandidates ?>+</div><div class="stat-lbl">Candidates</div></div>
    </div>
  </div>
</section>

<!-- ── Categories ────────────────────────────────────────── -->
<section class="section" style="background:#fff">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow">Browse by Category</div>
      <h2>Explore Popular Job Categories</h2>
      <p>Find opportunities across every domain of technology and business.</p>
    </div>
    <div class="row g-3">
      <?php foreach ($categories as $cat): ?>
      <div class="col-6 col-sm-4 col-lg-3">
        <a href="<?= BASE_URL ?>/auth/register.php" class="cat-card">
          <div class="cat-card-icon"><i class="bi bi-<?= e($cat['icon']) ?>"></i></div>
          <div class="cat-card-name"><?= e($cat['name']) ?></div>
          <div class="cat-card-count"><?= $cat['job_count'] ?> open position<?= $cat['job_count'] !== '1' ? 's' : '' ?></div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Featured Jobs ─────────────────────────────────────── -->
<section class="section" style="background:var(--gray-50)">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow">Featured Listings</div>
      <h2>Hot Jobs Right Now</h2>
      <p>Top opportunities from verified companies – apply before they're gone.</p>
    </div>
    <div class="row g-3">
      <?php foreach ($featuredJobs as $job): ?>
      <div class="col-md-6 col-lg-4">
        <a href="<?= BASE_URL ?>/auth/register.php" class="job-card text-decoration-none">
          <div class="d-flex align-items-start gap-3">
            <img src="<?= logoUrl($job['logo'], $job['company_name']) ?>" alt="" class="job-card-logo">
            <div class="flex-1 min-w-0">
              <div class="job-card-title"><?= e($job['title']) ?></div>
              <div class="job-card-company"><?= e($job['company_name']) ?></div>
            </div>
            <?= jobTypeBadge($job['type']) ?>
          </div>
          <div class="job-card-meta">
            <?php if ($job['location']): ?><span><i class="bi bi-geo-alt"></i> <?= e($job['location']) ?></span><?php endif; ?>
            <span><i class="bi bi-cash"></i> <?= formatSalary($job['salary_min'], $job['salary_max']) ?></span>
          </div>
          <div class="job-card-footer">
            <div>
              <div class="text-muted small"><?= $job['app_count'] ?> applicants</div>
              <div class="text-muted" style="font-size:11.5px"><?= timeAgo($job['created_at']) ?></div>
            </div>
            <span class="btn btn-sm btn-outline-primary">View Job</span>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-primary px-5 py-2">
        View All <?= $totalJobs ?>+ Jobs <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
  </div>
</section>

<!-- ── Companies ─────────────────────────────────────────── -->
<section class="section" style="background:#fff">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow">Top Employers</div>
      <h2>Companies Hiring Now</h2>
      <p>Join teams at industry-leading companies driving innovation in Pakistan.</p>
    </div>
    <div class="row g-3 justify-content-center">
      <?php foreach ($companies as $c): ?>
      <div class="col-auto">
        <a href="<?= BASE_URL ?>/auth/register.php" class="company-logo-pill">
          <img src="<?= logoUrl($c['logo'], $c['company_name']) ?>" alt="">
          <div>
            <div class="fw-700" style="font-size:13.5px"><?= e($c['company_name']) ?></div>
            <div class="text-muted" style="font-size:12px"><?= e($c['industry'] ?? 'Technology') ?></div>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── How it works ───────────────────────────────────────── -->
<section class="section" style="background:var(--gray-50)">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow">How It Works</div>
      <h2>Get Hired in 3 Simple Steps</h2>
    </div>
    <div class="row g-4 justify-content-center">
      <?php
      $steps = [
        ['icon' => 'person-plus', 'title' => 'Create Your Profile', 'desc' => 'Sign up in seconds and build a professional profile that showcases your skills and experience.'],
        ['icon' => 'search-heart', 'title' => 'Discover Opportunities', 'desc' => 'Browse curated job listings from verified companies. Filter by type, category, and location.'],
        ['icon' => 'send-check', 'title' => 'Apply with One Click', 'desc' => 'Submit your resume and cover letter directly to employers and track your application status live.'],
      ];
      foreach ($steps as $i => $step): ?>
      <div class="col-md-4">
        <div class="text-center p-4">
          <div style="width:64px;height:64px;background:var(--primary-light);border-radius:18px;display:flex;align-items:center;justify-content:center;font-size:26px;color:var(--primary);margin:0 auto 16px">
            <i class="bi bi-<?= $step['icon'] ?>"></i>
          </div>
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--primary);margin-bottom:8px">Step <?= $i+1 ?></div>
          <h5 class="fw-700 mb-2"><?= $step['title'] ?></h5>
          <p class="text-muted small"><?= $step['desc'] ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── CTA ───────────────────────────────────────────────── -->
<section class="section">
  <div class="container">
    <div class="cta-section">
      <h2>Ready to Land Your Dream Job?</h2>
      <p>Join thousands of professionals who found their next opportunity on HireHub.</p>
      <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-light fw-700 px-5 py-2 text-primary">
          Get Started – It's Free
        </a>
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline-light px-5 py-2">
          Sign In
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ── Footer ─────────────────────────────────────────────── -->
<footer class="pub-footer">
  <div class="container">
    <div class="mb-2">
      <span class="fw-700" style="color:#fff">Hire<span style="color:var(--accent)">Hub</span></span>
      <span class="mx-2">·</span>
      Modern Recruitment Platform
    </div>
    <div>© <?= date('Y') ?> HireHub. Built with PHP 8 + MySQL + Bootstrap 5.</div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
