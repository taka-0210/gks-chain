<?php
require_once __DIR__ . '/config.php';
$footerLogo = site_footer_logo();
?>
    <!-- フッター -->
    <footer class="site-footer">
      <div class="footer-inner">

        <p class="footer-logo">
          <a href="index.php">
            <img src="<?= h($footerLogo['src']); ?>" alt="GKSチェーン協会" style="--footer-logo-scale: <?= h((string)($footerLogo['scale'] / 100)); ?>;">
          </a>
        </p>

        <nav class="footer-nav">
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

        <p>&copy; GKS Chain Association. All Rights Reserved.</p>

      </div>
    </footer>
    <script src="js/script.js"></script>
