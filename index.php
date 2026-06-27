<?php
require_once __DIR__ . '/config.php';
$topNewsItems = array_slice(load_news(), 0, 3);
$regularMembers = load_regular_members();
$regularMemberTotal = count($regularMembers);
$regularMemberGroups = group_regular_members_by_prefecture($regularMembers);
$regularMemberMapGroups = group_regular_members_by_map_position($regularMembers, get_map_group_distance());
$regularMemberMapGroupsMobile = group_regular_members_by_map_position($regularMembers, get_map_group_distance_mobile());
$mapDotSize = get_map_dot_size();
$mapDotSizeMobile = get_map_dot_size_mobile();
$mapDotMultiSize = get_map_dot_multi_size();
$mapDotMultiSizeMobile = get_map_dot_multi_size_mobile();
$mapDotSpread = get_map_dot_spread();
$mapDotSpreadMobile = get_map_dot_spread_mobile();
$mapDotMultiSpread = get_map_dot_multi_spread();
$mapDotMultiSpreadMobile = get_map_dot_multi_spread_mobile();
$supportMembers = load_support_members();
$requestScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$requestHost = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
$requestPath = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
$requestBasePath = $requestPath === '/' ? '' : rtrim($requestPath, '/');
$contactThanksUrl = $requestScheme . '://' . $requestHost . $requestBasePath . '/thanks.php';
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GKSチェーン協会</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/map.css?v=<?= filemtime(__DIR__ . '/css/map.css'); ?>">
  <link rel="stylesheet" href="css/member.css?v=<?= filemtime(__DIR__ . '/css/member.css'); ?>">
</head>

<body>

<?php include 'header.php'; ?>


<main>

  <!-- ファーストビュー -->
  <section id="fv" class="fv">
    <div class="fv-slider">
      <?php foreach (site_fv_images() as $fvImage): ?>
        <img src="<?= h((string)$fvImage['src']); ?>" alt="<?= h((string)$fvImage['alt']); ?>">
      <?php endforeach; ?>
    </div>

    <div class="fv-text">
      <p>全国の厨房設備会社が<br class="sp-only">集うネットワーク</p>
      <h2>学び、<br class="sp-only">つながり、<br class="sp-only">成長する。</h2>
      <p>
        GKSチェーン協会は、<br class="sp-only">全国の厨房設備会社が<br class="sp-only">情報を共有し、<br>
        業界の発展と会員企業の<br class="sp-only">成長を目指す協会です。
      </p>
      <a href="#contact" class="btn">お問い合わせ</a>
    </div>
  </section>



  <!-- GKSとは -->
  <section id="about" class="about fadein">
    <div class="section-inner">

      <h2>GKSチェーン協会とは</h2>

      <div class="about-content">

        <div class="about-text">

          <p>
            GKSチェーン協会は、全国各地の厨房設備会社が集まり、
            地域や企業の枠を超えて連携するネットワークです。
          </p>

          <p>
            会員企業同士が情報やノウハウを共有し、
            共同仕入れ・研修会・視察会・委員会活動などを通じて、
            業界全体の発展と会員企業の成長を目指しています。
          </p>

          <p>
            個々では実現できない価値を仲間と共に創り、
            全国規模でお客様をサポートできる組織づくりに取り組んでいます。
          </p>

        </div>

        <div class="about-images">
          <img src="image/about/about01.jpg" alt="GKSチェーン協会の集合写真">
          <img src="image/about/about02.jpg" alt="GKS会員企業の交流会">
        </div>

      </div>

    </div>
  </section>



  <!-- 参加メリット -->
  <section id="merit" class="merit fadein">
    <div class="section-inner">
      <h2>GKSに参加するメリット</h2>
      <p>全国の仲間とつながり、学び、企業として成長できます。</p>

      <div class="merit-list">
        <article class="merit-item">
          <img src="image/merit/merit01.png" alt="経営を学べる">
          <h3>経営を学べる</h3>
          <p>成功事例や経営ノウハウを共有し、自社の成長に活かせます。</p>
        </article>

        <article class="merit-item">
          <img src="image/merit/merit02.png" alt="全国に仲間ができる">
          <h3>全国に仲間ができる</h3>
          <p>同じ業界で働く仲間と交流し、相談できる関係を築けます。</p>
        </article>

        <article class="merit-item">
          <img src="image/merit/merit03.png" alt="最新情報が得られる">
          <h3>最新情報が得られる</h3>
          <p>メーカー情報や市場動向など、業界の最新情報を学べます。</p>
        </article>

        <article class="merit-item">
          <img src="image/merit/merit04.png" alt="AI・DXを学べる">
          <h3>AI・DXを学べる</h3>
          <p>AIやDXなど、これからの時代に必要な取り組みを共有できます。</p>
        </article>
      </div>
    </div>
  </section>


  <!-- 最新情報 -->
  <section id="news" class="news fadein">
    <div class="section-inner">
      <h2>最新情報</h2>
      <p>GKSチェーン協会の活動やお知らせをご紹介します。</p>

      <div class="news-list">
        <?php foreach ($topNewsItems as $item): ?>
          <?php $newsMainImage = news_main_image($item); ?>
          <article class="news-item">
            <?php if ($newsMainImage): ?>
              <img src="<?= h((string)$newsMainImage['src']); ?>" alt="<?= h((string)($newsMainImage['alt'] ?? $item['title'] ?? '')); ?>">
            <?php endif; ?>
            <time datetime="<?= h((string)($item['date'] ?? '')); ?>"><?= h(news_display_date($item)); ?></time>
            <h3><?= h((string)($item['title'] ?? '')); ?></h3>
            <?php if (!empty($item['body'])): ?>
              <p><?= h(news_excerpt((string)$item['body'], 70)); ?></p>
            <?php endif; ?>
            <a href="news-detail.php?id=<?= h((string)($item['id'] ?? '')); ?>" class="news-more">さらに詳しく</a>
          </article>
        <?php endforeach; ?>
      </div>

      <div class="news-button">
        <a href="news.php" class="btn">
          NEWS一覧はこちら
        </a>
      </div>

    </div>
  </section>



  <!-- 活動紹介 -->
  <section id="activity" class="activity fadein">
    <div class="section-inner">
      <h2>活動紹介</h2>
      <p>GKSチェーン協会では、年間を通じてさまざまな活動を行っています。</p>

      <div class="activity-list">
        <article><img src="image/activity/activity01.png" alt="総会">
          <h3>総会</h3>
        </article>
        <article><img src="image/activity/activity02.jpg" alt="ミーティング">
          <h3>ミーティング</h3>
        </article>
        <article><img src="image/activity/activity03.jpg" alt="研修会">
          <h3>研修会</h3>
        </article>
        <article><img src="image/activity/activity04.jpg" alt="工場見学">
          <h3>工場見学</h3>
        </article>
        <article><img src="image/activity/activity05.png" alt="各種セミナー">
          <h3>各種セミナー</h3>
        </article>
        <article><img src="image/activity/activity06.jpg" alt="会員企業勉強会">
          <h3>会員企業間 勉強会</h3>
        </article>
        <article><img src="image/activity/activity07.jpg" alt="メーカー勉強会">
          <h3>メーカー勉強会</h3>
        </article>
        <article><img src="image/activity/activity08.jpg" alt="懇親会">
          <h3>懇親会</h3>
        </article>
      </div>
    </div>
  </section>

  <!-- 会員企業 -->
  <section id="member" class="member fadein">
    <div class="section-inner">

      <h2>全国をつなぐGKSネットワーク</h2>

      <a href="#member-list" class="member-block">
        <img src="image/member/member01.png" alt="全国の正会員企業">
        <h3>正会員企業</h3>
        <p>全国の厨房設備会社・厨房機器販売会社が参加しています。</p>
      </a>

      <a href="#support-member" class="member-block">
        <img src="image/member/supporter01.png" alt="賛助会員企業">
        <h3>賛助会員企業</h3>
        <p>厨房機器メーカーが協会活動を支えています。</p>
      </a>

      <p class="network-lead">
        全国の正会員企業と賛助会員企業が連携し、情報共有・研修会・共同活動を通じて業界の発展に取り組んでいます。
        会員企業同士が知識や経験を共有し、メーカー各社とも協力することで、個社では実現できない価値を創出。
        地域を越えたつながりを活かしながら、お客様へより良い厨房づくりとサービスを提供できるネットワークを築いています。
      </p>

    </div>
  </section>


  <!-- 正会員企業一覧 -->
  <section id="member-list" class="member-list-section fadein">

    <div class="section-inner">

      <h2>正会員企業 一覧</h2>

      <p class="section-lead">
        全国の正会員企業をご紹介いたします。
      </p>

      <p class="member-total">正会員企業 合計 <span><?= h((string)$regularMemberTotal); ?>社</span></p>

      <div class="prefecture-list">

        <?php foreach ($regularMemberGroups as $prefecture => $members): ?>
          <details class="prefecture-item">
            <summary><?= h($prefecture); ?> <span><?= count($members); ?>社</span></summary>

            <div class="member-list">
              <?php foreach ($members as $member): ?>
                <?php $memberNameParts = regular_member_parts($member); ?>
                <article class="member-company">
                  <h3>
                    <?= h($memberNameParts['company']); ?>
                    <?php if ($memberNameParts['store_name'] !== ''): ?>
                      <span class="member-store-name">（<?= h($memberNameParts['store_name']); ?>）</span>
                    <?php endif; ?>
                  </h3>

                  <?php if (!empty($member['president']) || !empty($member['president_image'])): ?>
                    <div class="member-representative">
                      <?php if (!empty($member['president_image'])): ?>
                        <img src="<?= h((string)$member['president_image']); ?>" alt="<?= h((string)($member['president'] ?? $memberNameParts['company'])); ?>">
                      <?php endif; ?>
                      <?php if (!empty($member['president'])): ?>
                        <p class="president"><?= h((string)$member['president']); ?></p>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($member['address'])): ?>
                    <p class="address"><?= h((string)$member['address']); ?></p>
                  <?php endif; ?>

                  <?php if (!empty($member['tel'])): ?>
                    <p class="tel">TEL <?= h((string)$member['tel']); ?></p>
                  <?php endif; ?>

                  <?php if (!empty($member['fax'])): ?>
                    <p class="fax">FAX <?= h((string)$member['fax']); ?></p>
                  <?php endif; ?>

                  <?php if (!empty($member['url'])): ?>
                    <a href="<?= h((string)$member['url']); ?>" target="_blank" rel="noopener">
                      ホームページを見る
                    </a>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endforeach; ?>

      </div>

    </div>

  </section>


  <!-- 賛助会員企業一覧 -->
  <section id="support-member" class="member-list-section support-member fadein">
    <div class="section-inner">

      <h2>賛助会員企業 一覧</h2>

      <p class="section-lead">
        GKSチェーン協会の活動を支えるメーカーをご紹介いたします。
      </p>

      <div class="prefecture-list">

        <details class="prefecture-item">
          <summary>
            賛助会員
            <span><?= count($supportMembers); ?>社</span>
          </summary>

          <div class="member-list">
            <?php foreach ($supportMembers as $member): ?>
              <article class="member-company">
                <h3><?= h((string)($member['company'] ?? '')); ?></h3>

                <?php if (!empty($member['president'])): ?>
                  <p class="president"><?= h((string)$member['president']); ?></p>
                <?php endif; ?>

                <?php if (!empty($member['address'])): ?>
                  <p class="address"><?= h((string)$member['address']); ?></p>
                <?php endif; ?>

                <?php if (!empty($member['url'])): ?>
                  <a href="<?= h((string)$member['url']); ?>" target="_blank" rel="noopener">WEBサイトを見る</a>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        </details>

      </div>

    </div>
  </section>


  <!-- 全国ネットワークマップ -->
  <section id="map" class="map fadein">
    <div class="section-inner">

      <h2>GKS正会員の全国ネットワーク</h2>

      <p>
        GKSチェーン協会は、全国の会員企業とともに活動しています。
      </p>

      <div class="japan-map" style="--map-dot-size: <?= h((string)$mapDotSize); ?>px; --map-dot-multi-size: <?= h((string)$mapDotMultiSize); ?>px; --map-dot-spread: <?= h((string)$mapDotSpread); ?>px; --map-dot-multi-spread: <?= h((string)$mapDotMultiSpread); ?>px; --map-dot-size-mobile: <?= h((string)$mapDotSizeMobile); ?>px; --map-dot-multi-size-mobile: <?= h((string)$mapDotMultiSizeMobile); ?>px; --map-dot-spread-mobile: <?= h((string)$mapDotSpreadMobile); ?>px; --map-dot-multi-spread-mobile: <?= h((string)$mapDotMultiSpreadMobile); ?>px;">

        <img src="image/map/map01.png" alt="全国ネットワークマップ">

        <svg class="map-network-lines" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"></svg>

        <?php foreach ($regularMemberMapGroups as $mapGroup): ?>
          <?php $mapMembers = $mapGroup['members']; ?>
          <div class="map-point map-point-desktop" style="top: <?= h((string)round((float)$mapGroup['top'], 1)); ?>%; left: <?= h((string)round((float)$mapGroup['left'], 1)); ?>%;">
            <span class="dot<?= count($mapMembers) > 1 ? ' multi' : ''; ?>"><?= count($mapMembers) > 1 ? h((string)count($mapMembers)) : ''; ?></span>

            <div class="tooltip">
              <?= h(implode(' / ', $mapGroup['prefectures'])); ?>
              <hr>

              <?php foreach ($mapMembers as $member): ?>
                <?php $mapMemberNameParts = regular_member_parts($member); ?>
                <p>
                  <strong class="<?= $mapMemberNameParts['store_name'] !== '' ? 'has-store-name' : ''; ?>">
                    <?= count($mapMembers) > 1 ? '■ ' : ''; ?><?= h($mapMemberNameParts['company']); ?>
                    <?php if ($mapMemberNameParts['store_name'] !== ''): ?>
                      <span class="member-store-name">（<?= h($mapMemberNameParts['store_name']); ?>）</span>
                    <?php endif; ?>
                  </strong>
                  <?php if (!empty($member['url'])): ?>
                    <a href="<?= h((string)$member['url']); ?>" target="_blank" rel="noopener">
                      WEBサイトへ
                    </a>
                  <?php endif; ?>
                </p>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <?php foreach ($regularMemberMapGroupsMobile as $mapGroup): ?>
          <?php $mapMembers = $mapGroup['members']; ?>
          <div class="map-point map-point-mobile" style="top: <?= h((string)round((float)$mapGroup['top'], 1)); ?>%; left: <?= h((string)round((float)$mapGroup['left'], 1)); ?>%;">
            <span class="dot<?= count($mapMembers) > 1 ? ' multi' : ''; ?>"><?= count($mapMembers) > 1 ? h((string)count($mapMembers)) : ''; ?></span>

            <div class="tooltip">
              <?= h(implode(' / ', $mapGroup['prefectures'])); ?>
              <hr>

              <?php foreach ($mapMembers as $member): ?>
                <?php $mapMemberNameParts = regular_member_parts($member); ?>
                <p>
                  <strong class="<?= $mapMemberNameParts['store_name'] !== '' ? 'has-store-name' : ''; ?>">
                    <?= count($mapMembers) > 1 ? '■ ' : ''; ?><?= h($mapMemberNameParts['company']); ?>
                    <?php if ($mapMemberNameParts['store_name'] !== ''): ?>
                      <span class="member-store-name">（<?= h($mapMemberNameParts['store_name']); ?>）</span>
                    <?php endif; ?>
                  </strong>
                  <?php if (!empty($member['url'])): ?>
                    <a href="<?= h((string)$member['url']); ?>" target="_blank" rel="noopener">
                      WEBサイトへ
                    </a>
                  <?php endif; ?>
                </p>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <?php /*
        <div class="map-point oohashi">

          <span class="dot"></span>

          <div class="tooltip">
            北海道
            <hr>
            <strong>株式会社 大橋冷機</strong><br>

            <a href="https://chubousenka.com/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>




        <div class="map-point toho">

          <span class="dot"></span>

          <div class="tooltip">
            茨城県
            <hr>
            <strong>東邦厨房 株式会社</strong><br>

          </div>

        </div>


        <div class="map-point hyodo">

          <span class="dot"></span>

          <div class="tooltip">
            栃木県
            <hr>
            <strong>株式会社 兵藤製作所</strong><br>
            <a href="https://k-hyodo.co.jp/" target="_blank">
              WEBサイトへ
            </a>

          </div>
        </div>


        <div class="map-point marubeni">

          <span class="dot"></span>

          <div class="tooltip">
            埼玉県
            <hr>
            <strong>株式会社 丸紅食器設備</strong><br>
            <a href="https://marubeni-ss.com/" target="_blank">
              WEBサイトへ
            </a>

          </div>
        </div>



        <div class="map-point tokyo01">

          <span class="dot multi">2</span>

          <div class="tooltip">

            東京都
            <hr>

            <p>
              <strong>■ NRTシステム株式会社</strong><br>
              <a href="ttps://nrt-system.co.jp/" target="_blank">
                WEBサイトへ
              </a>
            </p>

            <p>
              <strong>■ フレックスワークス株式会社</strong><br>
              <a href="https://flex-works.co.jp/" target="_blank">
                WEBサイトへ
              </a>
            </p>

          </div>

        </div>



        <div class="map-point sanei">

          <span class="dot"></span>

          <div class="tooltip">
            神奈川県
            <hr>
            <strong>株式会社 三栄コーポレーションリミテッド</strong><br>
            <a href="https://san-ei-ltd.co.jp/" target="_blank">
              WEBサイトへ
            </a>

          </div>
        </div>




        <div class="map-point techno">

          <span class="dot"></span>

          <div class="tooltip">
            長野県
            <hr>
            <strong>テクノ・フードシステム株式会社</strong><br>
            <a href="https://www.technofood.co.jp/" target="_blank">
              WEBサイトへ
            </a>

          </div>
        </div>



        <div class="map-point torei">

          <span class="dot"></span>

          <div class="tooltip">
            山梨県
            <hr>
            <strong>株式会社 トーレイ</strong><br>
            <a href="http://www.to-rei.co.jp/" target="_blank">
              WEBサイトへ
            </a>

          </div>
        </div>



        <div class="map-point maruzen">

          <span class="dot"></span>

          <div class="tooltip">
            静岡県
            <hr>
            <strong>マルゼン厨機株式会社</strong><br>
            <a href="http://www.maruzen-chuki.co.jp/" target="_blank">
              WEBサイトへ
            </a>

          </div>
        </div>



        <div class="map-point aichi">

          <span class="dot multi">2</span>

          <div class="tooltip">
            愛知県
            <hr>

            <p>
              <strong>■ 三岳工業株式会社</strong><br>
              <a href="https://www.mitakekogyo.co.jp/" target="_blank">
                WEBサイトへ
              </a>
            </p>

            <p>
              <strong>■ アルコ株式会社</strong><br>
              <a href="https://www.fbc-arco.co.jp/" target="_blank">
                WEBサイトへ
              </a>
            </p>

          </div>

        </div>


        <div class="map-point seiko">

          <span class="dot"></span>

          <div class="tooltip">
            岐阜県
            <hr>
            <strong>株式会社 セイコー</strong><br>
            <a href="http://www.seikoh.info/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>


        <div class="map-point suzukan">

          <span class="dot"></span>

          <div class="tooltip">
            三重県
            <hr>
            <strong>スズカン株式会社</strong><br>
            <a href="https://suzukan.co.jp/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>



        <div class="map-point ravo">

          <span class="dot"></span>

          <div class="tooltip">
            福井県
            <hr>
            <strong>株式会社 ラボー</strong><br>
            <a href="https://ravo.co.jp/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>


        <div class="map-point taiyo">

          <span class="dot"></span>

          <div class="tooltip">
            滋賀県
            <hr>
            <strong>大洋厨房 株式会社</strong><br>
            <a href="https://www.taiyocook.co.jp/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>


        <div class="map-point sanwa">

          <span class="dot"></span>

          <div class="tooltip">
            大阪府
            <hr>
            <strong>三和厨房 株式会社</strong><br>
            <a href="https://www.sanwa-chubo.com/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>



        <div class="map-point daiyacosmo">

          <span class="dot"></span>

          <div class="tooltip">
            奈良県
            <hr>
            <strong>ダイヤコスモ株式会社</strong><br>
            <a href="http://www.daiyacosmo.com/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>



        <div class="map-point riseup">

          <span class="dot"></span>

          <div class="tooltip">
            兵庫県
            <hr>
            <strong>株式会社 ライズアップ</strong><br>
            <a href="https://rise-up.net/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>




        <div class="map-point ainas">

          <span class="dot"></span>

          <div class="tooltip">
            兵庫県
            <hr>
            <strong>株式会社 アイナス</strong><br>
            <a href="https://ainas.co.jp/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>



        <div class="map-point fukui">

          <span class="dot"></span>

          <div class="tooltip">
            岡山県
            <hr>
            <strong>株式会社 福井廚房</strong><br>
            <a href="https://www.fukui-chubou.co.jp/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>


        <div class="map-point uematsu">

          <span class="dot"></span>

          <div class="tooltip">
            愛媛県
            <hr>
            <strong>有限会社 厨房のウエマツ</strong><br>
            <a href="https://chubo-uematsu.jp/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>


        <div class="map-point mk">

          <span class="dot"></span>

          <div class="tooltip">
            福岡県
            <hr>
            <strong>エムケー厨設株式会社</strong><br>
            <a href="https://mkc-gr.co.jp/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>



        <div class="map-point ito">

          <span class="dot"></span>

          <div class="tooltip">
            福岡県
            <hr>
            <strong>伊藤産業 株式会社</strong><br>
            <a href="https://www.ito-sk.co.jp/kanei/" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>



        <div class="map-point sendai">

          <span class="dot"></span>

          <div class="tooltip">
            鹿児島県
            <hr>
            <strong>株式会社 川内厨房食器</strong><br>
            <a href="https://senchubo.co.jp/index.htm" target="_blank">
              WEBサイトへ
            </a>

          </div>

        </div>
        */ ?>

      </div>
    </div>
  </section>



  <!-- 協会の歴史 -->
  <section id="history" class="history fadein">
    <div class="section-inner">

      <h2>協会の歴史と次世代に向けて</h2>

      <p class="history-lead">
        GKSチェーン協会は、全国の厨房設備会社がつながり、
        時代の変化とともに歩み続けています。
      </p>

      <div class="history-timeline">

        <div class="history-line"></div>

        <!-- 過去 -->
        <article class="history-card">

          <img src="image/history/history01.png" alt="GKSチェーン協会の設立">

          <div class="history-stage">
            過去
          </div>

          <div class="history-card-body">

            <h3>協会設立</h3>

            <p>
              全国の厨房設備会社が集い、
              情報共有と相互成長を目的として
              GKSチェーン協会が設立されました。
            </p>

            <div class="history-point">
              設立年：昭和54年（1979年）
            </div>

          </div>

        </article>


        <!-- 現在 -->
        <article class="history-card">

          <img src="image/history/history02.png" alt="全国に広がるGKSチェーン協会のネットワーク">

          <div class="history-stage">
            現在
          </div>

          <div class="history-card-body">

            <h3>全国ネットワーク</h3>

            <p>
              会員企業が全国へ広がり、
              情報交換や研修会、共同活動を通じて
              強いネットワークが築かれています。
            </p>

            <div class="history-point">
              全国の会員企業が連携・協力
            </div>

          </div>

        </article>


        <!-- 未来 -->
        <article class="history-card">

          <img src="image/history/history03.png" alt="AI・DX時代に向けたGKSチェーン協会の取り組み">

          <div class="history-stage">
            未来
          </div>

          <div class="history-card-body">

            <h3>AI・DX時代</h3>

            <p>
              生成AIやDX活用、
              デジタル技術の導入を進めながら、
              次世代の厨房業界を創造していきます。
            </p>

            <div class="history-point">
              未来を見据えた挑戦と進化
            </div>

          </div>

        </article>

      </div>

      <p class="history-message">
        設立から積み重ねてきた信頼とネットワークを礎に、
        会員企業・メーカー・関連企業が一つのチームとしてつながり、
        学び、支え合い、共に成長する。
        GKSチェーン協会は、より良い厨房づくりと業界の未来のために、
        これからも挑戦を続けてまいります。
      </p>

    </div>
  </section>



  <section id="chairman" class="chairman fadein">
    <div class="section-inner">

      <h2>会長メッセージ</h2>

      <div class="chairman-content">

        <img src="image/chairman/chairman.png" alt="GKSチェーン協会 会長 上崎明彦">

        <div class="chairman-text">

          <h3>
            GKSチェーン協会 会長<br>
            東邦厨房株式会社 代表取締役<br>
            上崎 明彦
          </h3>

          <p>
            GKSチェーン協会は、全国の厨房設備会社が学び、
            情報を共有し、共に成長していくためのネットワークです。
          </p>

          <p>
            厨房業界を取り巻く環境は日々変化しています。
            私たちは会員企業同士がつながり、
            新しい知識や情報を共有することで、
            業界全体の発展に貢献したいと考えています。
          </p>

          <p>
            今後も会員企業の皆様と共に学び、
            共に成長し、
            次世代へつながる協会づくりを進めてまいります。
          </p>

        </div>

      </div>

    </div>
  </section>

  <!-- お問い合わせ -->
  <section id="contact" class="contact fadein">
    <div class="section-inner">
      <h2>お問い合わせ</h2>
      <p>入会相談や協会活動についてのお問い合わせはこちらからお願いします。</p>

      <form action="https://api.web3forms.com/submit" method="POST">
        <input type="hidden" name="access_key" value="20a1395e-3265-478f-8946-1e16ef354457">
        <input type="hidden" name="redirect" value="<?= h($contactThanksUrl); ?>">
        <input type="hidden" name="subject" value="GKSチェーン協会 お問い合わせ">

        <div>
          <label for="name">お名前</label>
          <input type="text" id="name" name="name" required>
        </div>

        <div>
          <label for="company">会社名</label>
          <input type="text" id="company" name="company" required>
        </div>

        <div>
          <label for="email">メールアドレス</label>
          <input type="email" id="email" name="email" required>
        </div>

        <div>
          <label for="message">お問い合わせ内容</label>
          <textarea id="message" name="message" required></textarea>
        </div>

        <button type="submit">送信する</button>
      </form>
    </div>
  </section>


</main>

<?php include 'footer.php'; ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const map = document.querySelector('.japan-map');

    if (!map || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      return;
    }

    const svg = map.querySelector('.map-network-lines');
    let isVisible = true;
    let timerId = null;

    function visiblePoints() {
      return Array.from(map.querySelectorAll('.map-point')).filter(function (point) {
        return window.getComputedStyle(point).display !== 'none';
      }).map(function (point) {
        return {
          x: parseFloat(point.style.left),
          y: parseFloat(point.style.top)
        };
      }).filter(function (point) {
        return Number.isFinite(point.x) && Number.isFinite(point.y);
      });
    }

    function drawRandomLine() {
      if (!isVisible || !svg) {
        return;
      }

      const points = visiblePoints();

      if (points.length < 2) {
        return;
      }

      const startIndex = Math.floor(Math.random() * points.length);
      let endIndex = Math.floor(Math.random() * points.length);

      if (endIndex === startIndex) {
        endIndex = (endIndex + 1) % points.length;
      }

      const start = points[startIndex];
      const end = points[endIndex];
      const deltaX = end.x - start.x;
      const deltaY = end.y - start.y;
      const distance = Math.hypot(deltaX, deltaY) || 1;
      const curveDirection = Math.random() > 0.5 ? 1 : -1;
      const curveAmount = Math.min(11, Math.max(4, distance * 0.18)) * curveDirection;
      const controlX = (start.x + end.x) / 2 + (-deltaY / distance) * curveAmount;
      const controlY = (start.y + end.y) / 2 + (deltaX / distance) * curveAmount;
      const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

      path.setAttribute('d', 'M ' + start.x + ' ' + start.y + ' Q ' + controlX + ' ' + controlY + ' ' + end.x + ' ' + end.y);
      path.setAttribute('pathLength', '1');
      path.classList.add('map-network-line');
      svg.appendChild(path);

      window.setTimeout(function () {
        path.remove();
      }, 1200);
    }

    function startLines() {
      if (timerId !== null) {
        return;
      }

      timerId = window.setInterval(drawRandomLine, 850);
      drawRandomLine();
    }

    function stopLines() {
      if (timerId === null) {
        return;
      }

      window.clearInterval(timerId);
      timerId = null;
    }

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(function (entries) {
        isVisible = entries.some(function (entry) {
          return entry.isIntersecting;
        });

        if (isVisible) {
          startLines();
        } else {
          stopLines();
        }
      }, { threshold: 0.2 });

      observer.observe(map);
    } else {
      startLines();
    }
  });
</script>

</body>

</html>
