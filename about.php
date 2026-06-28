<?php
require_once __DIR__ . '/config.php';
$chairmanMessages = load_chairman_messages();
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GKSチェーン協会とは | GKSチェーン協会</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/about.css?v=<?= filemtime(__DIR__ . '/css/about.css'); ?>">
</head>

<body>

<?php include 'header.php'; ?>

<main>
  <section class="about-page-hero">
    <div class="section-inner">
      <h1>GKSチェーン協会とは</h1>
      <p>
        全国各地の厨房設備会社が、地域や企業の枠を超えて連携し、
        情報・技術・経験を共有しながら、業界と会員企業の未来をつくるネットワークです。
      </p>
    </div>
  </section>

  <section class="about-detail-section">
    <div class="section-inner">
      <h2>GKSという名前に込めた意味</h2>
      <p class="about-detail-lead">
        GKSは、厨房設備業界に関わる企業が互いに支え合い、全国規模で価値を生み出すための考え方を表しています。
      </p>

      <div class="about-name-box">
        <p class="about-name-en">
          <span>G</span>ENERAL <span>K</span>ITCHEN <span>S</span>YSTEM<br>
          &amp; <span>S</span>UPPLIERS CHAIN ASSOCIATION
        </p>
        <div class="about-name-list">
          <div>
            <strong>General</strong>
            <span>総合的な視点</span>
          </div>
          <div>
            <strong>Kitchen System</strong>
            <span>厨房設備・厨房づくり</span>
          </div>
          <div>
            <strong>Suppliers Chain</strong>
            <span>供給・協力のネットワーク</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="about-detail-section alt">
    <div class="section-inner">
      <h2>設立の背景</h2>

      <div class="about-story-grid">
        <div class="about-story-text">
          <p>
            GKSチェーン協会は、昭和54年（1979年）に設立されました。
            当時の厨房設備業界では、大手企業の営業網拡大や価格競争が進み、
            地域に根ざした中小の厨房設備会社にとって、単独では対応しきれない課題が増えていました。
          </p>
          <p>
            そのなかで、各社の個性を消すのではなく、それぞれの強みを活かしながら協力することを重視し、
            情報交換・共同活動・研修を通じて力を合わせる組織としてGKSの構想が生まれました。
          </p>
          <p>
            設立総会では、協定書や規約の確認、役員選出、共同カタログ制作、勉強会開催、
            会員相互の協力体制づくりなどが話し合われ、現在につながる活動の土台が築かれました。
          </p>
        </div>
        <div class="about-story-image">
          <img src="image/about/about02.jpg" alt="GKSチェーン協会の交流風景">
        </div>
      </div>
    </div>
  </section>

  <section class="about-detail-section">
    <div class="section-inner">
      <h2>歴代会長</h2>
      <p class="about-detail-lead">
        GKSが大切にしてきたのは、時代が変わっても地域に根ざし、仲間と学び続ける姿勢です。
      </p>

      <div class="about-message-list">
        <?php foreach ($chairmanMessages as $chairmanMessage): ?>
          <?php $chairmanName = chairman_message_name($chairmanMessage); ?>
          <article class="about-message">
            <div class="about-message-head">
              <?php if (!empty($chairmanMessage['image'])): ?>
                <img src="<?= h((string)$chairmanMessage['image']); ?>" alt="<?= h($chairmanName !== '' ? $chairmanName : '歴代会長'); ?>">
              <?php endif; ?>
              <div>
                <p class="about-message-label">会長任期 <?= h(chairman_message_term_display($chairmanMessage)); ?></p>
                <h3><?= h($chairmanName); ?></h3>
                <?php if (!empty($chairmanMessage['company'])): ?>
                  <p class="about-message-role"><?= h((string)$chairmanMessage['company']); ?></p>
                <?php endif; ?>
              </div>
            </div>
            <div class="about-message-body">
              <?php foreach (preg_split('/\R{2,}/u', (string)($chairmanMessage['message'] ?? '')) as $paragraph): ?>
                <?php if (trim($paragraph) !== ''): ?>
                  <p><?= nl2br(h(trim($paragraph))); ?></p>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="about-detail-section alt">
    <div class="section-inner">
      <h2>協会の歩み</h2>

      <div class="about-timeline">
        <article class="about-timeline-item">
          <time>1979年</time>
          <div>
            <h3>GKSチェーン協会 設立</h3>
            <p>全国の厨房設備会社が集まり、相互扶助・情報交換・親睦を目的として発足しました。</p>
          </div>
        </article>

        <article class="about-timeline-item">
          <time>1980年</time>
          <div>
            <h3>共同カタログ制作</h3>
            <p>設計資料や関係法規も含めた総合的なカタログ制作に取り組みました。</p>
          </div>
        </article>

        <article class="about-timeline-item">
          <time>以降</time>
          <div>
            <h3>研修・情報交換・共同活動</h3>
            <p>総会、研修会、視察会、委員会活動を通じて、会員企業の学びと交流を重ねています。</p>
          </div>
        </article>
      </div>
    </div>
  </section>

  <section class="about-detail-section">
    <div class="section-inner">
      <h2>現在の活動</h2>
      <p class="about-detail-lead">
        GKSチェーン協会は、会員企業と賛助会員企業が連携し、厨房業界の発展と会員企業の成長に取り組んでいます。
      </p>

      <div class="about-activity-grid">
        <article>
          <h3>情報共有</h3>
          <p>市場動向、メーカー情報、経営課題を会員同士で共有します。</p>
        </article>
        <article>
          <h3>研修会</h3>
          <p>技術、経営、営業、AI・DXなど、時代に合わせた学びを深めます。</p>
        </article>
        <article>
          <h3>視察会</h3>
          <p>現場や施設を見学し、実践的な知識と気づきを持ち帰ります。</p>
        </article>
        <article>
          <h3>委員会活動</h3>
          <p>協会運営や活動企画を通じて、会員が主体的に協会を育てています。</p>
        </article>
      </div>

      <div class="about-page-actions">
        <a href="index.php#member-list" class="btn">会員企業を見る</a>
        <a href="index.php#contact" class="btn">お問い合わせ</a>
      </div>
    </div>
  </section>
</main>

<?php include 'footer.php'; ?>

</body>

</html>
