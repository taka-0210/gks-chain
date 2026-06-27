<?php
$newsArchiveItems = $newsArchiveItems ?? load_news();
$newsArchiveGroups = group_news_by_year($newsArchiveItems);
$newsArchiveCurrentId = (string)($newsArchiveCurrentId ?? '');
$newsArchiveOpenYear = (string)($newsArchiveOpenYear ?? '');

if ($newsArchiveOpenYear === '' && $newsArchiveGroups) {
    $newsArchiveOpenYear = (string)array_key_first($newsArchiveGroups);
}
?>

<aside class="news-archive" aria-label="年度別アーカイブ">
  <h2>アーカイブ</h2>

  <?php foreach ($newsArchiveGroups as $year => $items): ?>
    <details class="news-archive-year" <?= (string)$year === $newsArchiveOpenYear ? 'open' : ''; ?>>
      <summary><?= h((string)$year); ?>年度</summary>
      <ul>
        <?php foreach ($items as $item): ?>
          <?php $itemId = (string)($item['id'] ?? ''); ?>
          <li>
            <a class="<?= $itemId !== '' && $itemId === $newsArchiveCurrentId ? 'active' : ''; ?>" href="news-detail.php?id=<?= h($itemId); ?>">
              <span><?= h(news_display_date($item)); ?></span>
              <?= h((string)($item['title'] ?? '')); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </details>
  <?php endforeach; ?>
</aside>
