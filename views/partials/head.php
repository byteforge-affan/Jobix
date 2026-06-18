<?php
// views/partials/head.php
// Expects: $pageTitle (string)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="HireHub – Modern Recruitment & Job Portal">
  <title><?= e($pageTitle ?? 'Dashboard') ?> – HireHub</title>

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- App CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/app.css">
</head>
<body>
