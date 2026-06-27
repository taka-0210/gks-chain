<?php
require_once __DIR__ . '/config.php';

$id = (string)($_GET['id'] ?? '');
$newsItem = $id !== '' ? find_news_by_id($id) : null;
$newsArchiveItems = load_news();
$newsArchiveCurrentId = $id;
$newsArchiveOpenYear = $newsItem ? news_year($newsItem) : ($newsArchiveItems ? news_year($newsArchiveItems[0]) : '');

if ($newsItem === null) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $newsItem ? h((string)$newsItem['title']) . ' | ' : ''; ?>GKSチェーン協会</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/news.css?v=<?= filemtime(__DIR__ . '/css/news.css'); ?>">
</head>

<body>

<?php include 'header.php'; ?>

  <main>
    <section class="news-detail-page">
      <div class="section-inner">
        <div class="news-layout">
          <article class="news-detail-main">
            <?php if ($newsItem === null): ?>
              <h2>お知らせが見つかりません</h2>
              <p>指定されたお知らせは存在しないか、公開されていません。</p>
              <div class="news-detail-actions">
                <a href="news.php" class="btn">お知らせ一覧へ戻る</a>
              </div>
            <?php else: ?>
              <?php $newsImages = news_images($newsItem); ?>
              <time datetime="<?= h((string)($newsItem['date'] ?? '')); ?>"><?= h(news_display_date($newsItem)); ?></time>
              <h1><?= h((string)($newsItem['title'] ?? '')); ?></h1>

              <?php if ($newsImages): ?>
                <div class="news-gallery" data-news-gallery>
                  <img class="news-gallery-main" src="<?= h((string)$newsImages[0]['src']); ?>" alt="<?= h((string)($newsImages[0]['alt'] ?? $newsItem['title'] ?? '')); ?>" data-news-gallery-main>
                  <?php if (count($newsImages) > 1): ?>
                    <div class="news-gallery-thumbs">
                      <?php foreach ($newsImages as $index => $image): ?>
                        <button type="button" class="<?= $index === 0 ? 'active' : ''; ?>" data-gallery-src="<?= h((string)$image['src']); ?>" data-gallery-alt="<?= h((string)($image['alt'] ?? $newsItem['title'] ?? '')); ?>">
                          <img src="<?= h((string)$image['src']); ?>" alt="<?= h((string)($image['alt'] ?? $newsItem['title'] ?? '')); ?>">
                        </button>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <div class="news-detail-body">
                <?php foreach (preg_split('/\R{2,}/u', (string)($newsItem['body'] ?? '')) as $paragraph): ?>
                  <?php if (trim($paragraph) !== ''): ?>
                    <p><?= nl2br(h(trim($paragraph))); ?></p>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>

              <div class="news-detail-actions">
                <a href="news.php" class="btn">お知らせ一覧へ戻る</a>
              </div>
            <?php endif; ?>
          </article>

          <?php include 'news-archive.php'; ?>
        </div>
      </div>
    </section>
  </main>

<?php include 'footer.php'; ?>

<script>
  document.addEventListener('click', function (event) {
    const thumb = event.target.closest('[data-gallery-src]');

    if (!thumb) {
      return;
    }

    const gallery = thumb.closest('[data-news-gallery]');
    const mainImage = gallery ? gallery.querySelector('[data-news-gallery-main]') : null;

    if (!mainImage) {
      return;
    }

    mainImage.src = thumb.getAttribute('data-gallery-src') || '';
    mainImage.alt = thumb.getAttribute('data-gallery-alt') || '';

    gallery.querySelectorAll('[data-gallery-src]').forEach(function (button) {
      button.classList.toggle('active', button === thumb);
    });
  });
</script>

</body>

</html>
