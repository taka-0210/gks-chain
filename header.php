<?php
require_once __DIR__ . '/config.php';
$headerLogo = site_header_logo();
?>
  <!-- ヘッダー -->
  <header class="site-header">
    <div class="header-inner">

      <h1 class="logo">
        <a href="index.php">
          <img src="<?= h($headerLogo['src']); ?>" alt="GKSチェーン協会" style="--header-logo-scale: <?= h((string)($headerLogo['scale'] / 100)); ?>;">
        </a>
      </h1>

      <button class="hamburger">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <nav class="global-nav">
        <ul>
          <li><a href="index.php#about">GKSとは</a></li>
          <li><a href="index.php#merit">参加メリット</a></li>
          <li><a href="index.php#news">最新情報</a></li>
          <li><a href="index.php#activity">活動紹介</a></li>
          <li><a href="index.php#member-list">会員企業</a></li>
          <li><a href="index.php#support-member">賛助会員企業</a></li>
          <li><a href="organization.php">組織体制</a></li>
          <li><a href="index.php#contact">お問い合わせ</a></li>
        </ul>
      </nav>

    </div>
  </header>
  
