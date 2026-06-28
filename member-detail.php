<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config.php';

$memberId = (string)($_SESSION['member_id'] ?? '');
$member = $memberId !== '' ? find_member_account_by_id($memberId) : null;

if (!$member) {
    $_SESSION['member_message'] = '会員専用情報を見るにはログインしてください。';
    header('Location: member.php');
    exit;
}

$id = (string)($_GET['id'] ?? '');
$item = $id !== '' ? find_visible_member_content_by_id($id, $member) : null;

if ($item === null) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $item ? h((string)$item['title']) . ' | ' : ''; ?>会員専用ページ | GKSチェーン協会</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/member-area.css">
</head>

<body>
  <?php include __DIR__ . '/header.php'; ?>

  <main class="member-page">
    <section class="member-hero member-detail-hero">
      <div class="member-hero-inner">
        <p class="member-kicker">MEMBER DETAIL</p>
        <h1>会員向け情報</h1>
        <p><?= h((string)$member['company']); ?> としてログイン中</p>
      </div>
    </section>

    <div class="member-wrap">
      <div class="member-toolbar">
        <a class="member-link-button secondary" href="member.php">一覧へ戻る</a>
        <a class="member-link-button" href="member.php?action=logout">ログアウト</a>
      </div>

      <article class="member-panel member-detail-panel">
        <?php if ($item === null): ?>
          <p class="member-kicker">NOT FOUND</p>
          <h2>情報が見つかりません</h2>
          <p class="member-empty">指定された情報は存在しないか、現在の会員種別では閲覧できません。</p>
        <?php else: ?>
          <div class="member-tags">
            <span><?= h(member_content_category_label((string)($item['category'] ?? 'service'))); ?></span>
            <span><?= h(member_content_visibility_label((string)($item['visibility'] ?? 'all'))); ?></span>
          </div>

          <h2><?= h((string)($item['title'] ?? '')); ?></h2>
          <p class="member-detail-company"><?= h((string)($item['company'] ?? '')); ?></p>

          <?php if (!empty($item['image'])): ?>
            <img class="member-detail-image" src="<?= h((string)$item['image']); ?>" alt="<?= h((string)($item['title'] ?? '')); ?>">
          <?php endif; ?>

          <div class="member-detail-body">
            <?php foreach (preg_split('/\R{2,}/u', (string)($item['body'] ?? '')) as $paragraph): ?>
              <?php if (trim($paragraph) !== ''): ?>
                <p><?= nl2br(h(trim($paragraph))); ?></p>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>

          <div class="member-detail-actions">
            <?php if (!empty($item['attachment'])): ?>
              <a class="member-link-button" href="<?= h((string)$item['attachment']); ?>" target="_blank" rel="noopener">PDFを見る</a>
            <?php endif; ?>
            <?php if (!empty($item['link'])): ?>
              <a class="member-link-button secondary" href="<?= h((string)$item['link']); ?>" target="_blank" rel="noopener">詳細リンク</a>
            <?php endif; ?>
          </div>

          <?php if (!empty($item['contact_name']) || !empty($item['contact_email'])): ?>
            <section class="member-detail-contact">
              <h3>問い合わせ先</h3>
              <?php if (!empty($item['contact_name'])): ?>
                <p>担当者: <?= h((string)$item['contact_name']); ?></p>
              <?php endif; ?>
              <?php if (!empty($item['contact_email'])): ?>
                <p>メール: <a href="mailto:<?= h((string)$item['contact_email']); ?>"><?= h((string)$item['contact_email']); ?></a></p>
              <?php endif; ?>
            </section>
          <?php endif; ?>
        <?php endif; ?>
      </article>
    </div>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>
