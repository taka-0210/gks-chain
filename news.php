<?php
require_once __DIR__ . '/config.php';
$newsItems = load_news();
$newsArchiveItems = $newsItems;
$newsArchiveOpenYear = $newsItems ? news_year($newsItems[0]) : '';
$newsArchiveCurrentId = '';
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>お知らせ一覧 | GKSチェーン協会</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/news.css?v=<?= filemtime(__DIR__ . '/css/news.css'); ?>">
</head>

<body>

<?php include 'header.php'; ?>

  <main>

    <section class="news-page">

      <div class="section-inner">

        <h2>お知らせ一覧</h2>

        <div class="news-layout">
          <div class="news-main">
            <?php foreach ($newsItems as $item): ?>
              <article class="news-item">
                <time datetime="<?= h((string)($item['date'] ?? '')); ?>"><?= h(news_display_date($item)); ?></time>
                <h3>
                  <a href="news-detail.php?id=<?= h((string)($item['id'] ?? '')); ?>"><?= h((string)($item['title'] ?? '')); ?></a>
                </h3>
              </article>
            <?php endforeach; ?>
          </div>

          <?php include 'news-archive.php'; ?>
        </div>

      </div>

    </section>

  </main>

<?php include 'footer.php'; ?>

</body>

</html>
