<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config.php';

$errors = [];
$message = '';
$requestMethod = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (empty($_SESSION['member_csrf'])) {
    $_SESSION['member_csrf'] = bin2hex(random_bytes(32));
}

if (!empty($_SESSION['member_message'])) {
    $message = (string)$_SESSION['member_message'];
    unset($_SESSION['member_message']);
}

function member_require_csrf(): void
{
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['member_csrf'] ?? '', (string)$_POST['csrf'])) {
        http_response_code(400);
        exit('Invalid request.');
    }
}

function current_member(): ?array
{
    $id = (string)($_SESSION['member_id'] ?? '');

    return $id !== '' ? find_member_account_by_id($id) : null;
}

function redirect_member(string $message = ''): void
{
    if ($message !== '') {
        $_SESSION['member_message'] = $message;
    }

    header('Location: member.php');
    exit;
}

function collect_member_content_form_data(array $member): array
{
    $visibility = (string)($_POST['visibility'] ?? 'all');
    $category = (string)($_POST['category'] ?? 'service');

    if (!in_array($visibility, ['all', 'regular', 'support'], true)) {
        $visibility = 'all';
    }

    if (!in_array($category, ['service', 'brochure', 'new_product', 'catalog', 'seminar'], true)) {
        $category = 'service';
    }

    return [
        'member_id' => (string)$member['id'],
        'member_type' => (string)$member['type'],
        'company' => (string)$member['company'],
        'title' => trim(strip_tags((string)($_POST['title'] ?? ''))),
        'category' => $category,
        'body' => trim((string)($_POST['body'] ?? '')),
        'contact_name' => trim(strip_tags((string)($_POST['contact_name'] ?? ''))),
        'contact_email' => trim(strip_tags((string)($_POST['contact_email'] ?? ''))),
        'link' => trim(strip_tags((string)($_POST['link'] ?? ''))),
        'visibility' => $visibility,
        'publish_start' => trim(strip_tags((string)($_POST['publish_start'] ?? ''))),
        'publish_end' => trim(strip_tags((string)($_POST['publish_end'] ?? ''))),
    ];
}

function validate_member_content_form(array $data): array
{
    $errors = [];

    if ($data['title'] === '') {
        $errors[] = 'タイトルを入力してください。';
    }

    if ($data['body'] === '') {
        $errors[] = '内容を入力してください。';
    }

    if ($data['publish_start'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['publish_start'])) {
        $errors[] = '公開開始日は正しい日付で入力してください。';
    }

    if ($data['publish_end'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['publish_end'])) {
        $errors[] = '公開終了日は正しい日付で入力してください。';
    }

    if ($data['publish_start'] !== '' && $data['publish_end'] !== '' && $data['publish_start'] > $data['publish_end']) {
        $errors[] = '公開終了日は公開開始日以降にしてください。';
    }

    return $errors;
}

function handle_member_upload(string $field, array $allowedTypes, string $directory, string $prefix): array
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['', []];
    }

    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['', ['ファイルのアップロードに失敗しました。']];
    }

    if (($_FILES[$field]['size'] ?? 0) > 8 * 1024 * 1024) {
        return ['', ['ファイルサイズは8MB以内にしてください。']];
    }

    $tmpName = (string)($_FILES[$field]['tmp_name'] ?? '');
    $mimeType = is_file($tmpName) ? (string)mime_content_type($tmpName) : '';

    if (!in_array($mimeType, $allowedTypes, true)) {
        return ['', ['アップロードできるファイル形式ではありません。']];
    }

    $extension = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        default => '',
    };

    if ($extension === '') {
        return ['', ['アップロードできるファイル形式ではありません。']];
    }

    $absoluteDirectory = __DIR__ . '/' . $directory;
    if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true)) {
        return ['', ['アップロード先ディレクトリを作成できません。']];
    }

    $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $absoluteDirectory . '/' . $filename;

    if (!move_uploaded_file($tmpName, $destination)) {
        return ['', ['ファイルを保存できませんでした。']];
    }

    return [$directory . '/' . $filename, []];
}

if (($_GET['action'] ?? '') === 'logout') {
    unset($_SESSION['member_id'], $_SESSION['member_message']);
    redirect_member('ログアウトしました。');
}

if ($requestMethod === 'POST' && ($_POST['action'] ?? '') === 'login') {
    member_require_csrf();

    $loginId = trim((string)($_POST['login_id'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $account = find_member_account_by_login_id($loginId);

    if ($account && password_verify($password, (string)($account['password_hash'] ?? ''))) {
        $_SESSION['member_id'] = (string)$account['id'];
        redirect_member('ログインしました。');
    }

    $errors[] = 'ログインIDまたはパスワードが違います。';
}

$member = current_member();

if ($member && $requestMethod === 'POST' && ($_POST['action'] ?? '') === 'content_create') {
    member_require_csrf();

    $data = collect_member_content_form_data($member);
    [$imagePath, $imageErrors] = handle_member_upload('image', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 'image/member-content', 'member_image');
    [$filePath, $fileErrors] = handle_member_upload('attachment', ['application/pdf'], 'file/member-content', 'member_file');
    $errors = array_merge($errors, $imageErrors, $fileErrors, validate_member_content_form($data));

    if (!$errors) {
        $items = load_member_content(false);
        array_unshift($items, array_merge($data, [
            'id' => 'member_content_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)),
            'image' => $imagePath,
            'attachment' => $filePath,
            'status' => 'pending',
            'admin_note' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]));

        if (save_member_content($items)) {
            redirect_member('投稿を受け付けました。管理者の承認後に公開されます。');
        }

        $errors[] = '投稿の保存に失敗しました。';
    }
}

$visibleItems = $member ? load_member_content(true, $member) : [];
$myItems = $member ? array_values(array_filter(load_member_content(false), function ($item) use ($member) {
    return (string)($item['member_id'] ?? '') === (string)$member['id'];
})) : [];
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>会員専用ページ | GKSチェーン協会</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/member-area.css">
</head>

<body>
  <?php include __DIR__ . '/header.php'; ?>

  <main class="member-page">
    <section class="member-hero">
      <div class="member-hero-inner">
        <p class="member-kicker">MEMBER AREA</p>
        <h1>会員専用ページ</h1>
        <p>会員企業のサービス、パンフレット、新機種案内を会員限定で共有できます。</p>
      </div>
    </section>

    <div class="member-wrap">
      <?php if ($message !== ''): ?>
        <p class="member-alert success"><?= h($message); ?></p>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="member-alert error">
          <?php foreach ($errors as $error): ?>
            <p><?= h($error); ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!$member): ?>
        <section class="member-panel member-login">
          <h2>ログイン</h2>
          <form class="member-form" method="POST">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['member_csrf']); ?>">
            <input type="hidden" name="action" value="login">
            <label>
              ログインID
              <input type="text" name="login_id" autocomplete="username" required>
            </label>
            <label>
              パスワード
              <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <button type="submit">ログイン</button>
          </form>
          <p class="member-help">テスト用: regular-demo / member123、support-demo / member123</p>
        </section>
      <?php else: ?>
        <div class="member-toolbar">
          <div>
            <p class="member-kicker">LOGIN</p>
            <p class="member-company"><?= h((string)$member['company']); ?> <span><?= h(member_type_label((string)$member['type'])); ?></span></p>
          </div>
          <a class="member-link-button" href="member.php?action=logout">ログアウト</a>
        </div>

        <section class="member-panel">
          <div class="member-section-head">
            <div>
              <p class="member-kicker">BROWSE</p>
              <h2>会員向け情報</h2>
            </div>
            <p><?= count($visibleItems); ?>件</p>
          </div>

          <?php if (!$visibleItems): ?>
            <p class="member-empty">現在閲覧できる情報はありません。</p>
          <?php else: ?>
            <div class="member-card-grid">
              <?php foreach ($visibleItems as $item): ?>
                <article class="member-card">
                  <?php if (!empty($item['image'])): ?>
                    <img src="<?= h((string)$item['image']); ?>" alt="<?= h((string)($item['title'] ?? '')); ?>">
                  <?php endif; ?>
                  <div class="member-card-body">
                    <div class="member-tags">
                      <span><?= h(member_content_category_label((string)($item['category'] ?? 'service'))); ?></span>
                      <span><?= h(member_content_visibility_label((string)($item['visibility'] ?? 'all'))); ?></span>
                    </div>
                    <h3><?= h((string)($item['title'] ?? '')); ?></h3>
                    <p class="member-card-company"><?= h((string)($item['company'] ?? '')); ?></p>
                    <p><?= h(member_content_excerpt((string)($item['body'] ?? ''))); ?></p>
                    <div class="member-card-actions">
                      <?php if (!empty($item['attachment'])): ?>
                        <a href="<?= h((string)$item['attachment']); ?>" target="_blank" rel="noopener">PDFを見る</a>
                      <?php endif; ?>
                      <?php if (!empty($item['link'])): ?>
                        <a href="<?= h((string)$item['link']); ?>" target="_blank" rel="noopener">詳細リンク</a>
                      <?php endif; ?>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <section class="member-panel">
          <div class="member-section-head">
            <div>
              <p class="member-kicker">SUBMIT</p>
              <h2>自社情報を登録</h2>
            </div>
          </div>
          <form class="member-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['member_csrf']); ?>">
            <input type="hidden" name="action" value="content_create">
            <div class="member-form-grid">
              <label>
                タイトル
                <input type="text" name="title" required>
              </label>
              <label>
                カテゴリ
                <select name="category">
                  <option value="service">サービス紹介</option>
                  <option value="brochure">パンフレット</option>
                  <option value="new_product">新機種案内</option>
                  <option value="catalog">製品カタログ</option>
                  <option value="seminar">展示会・セミナー</option>
                </select>
              </label>
            </div>
            <label>
              内容
              <textarea name="body" required></textarea>
            </label>
            <div class="member-form-grid">
              <label>
                公開範囲
                <select name="visibility">
                  <option value="all">全会員</option>
                  <option value="regular">正会員のみ</option>
                  <option value="support">賛助会員のみ</option>
                </select>
              </label>
              <label>
                詳細リンク
                <input type="url" name="link" placeholder="https://example.com">
              </label>
            </div>
            <div class="member-form-grid">
              <label>
                公開開始日
                <input type="date" name="publish_start">
              </label>
              <label>
                公開終了日
                <input type="date" name="publish_end">
              </label>
            </div>
            <div class="member-form-grid">
              <label>
                画像
                <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
              </label>
              <label>
                PDF
                <input type="file" name="attachment" accept="application/pdf">
              </label>
            </div>
            <div class="member-form-grid">
              <label>
                担当者名
                <input type="text" name="contact_name">
              </label>
              <label>
                連絡先メール
                <input type="email" name="contact_email">
              </label>
            </div>
            <button type="submit">承認待ちで登録する</button>
          </form>
        </section>

        <section class="member-panel">
          <div class="member-section-head">
            <div>
              <p class="member-kicker">MY POSTS</p>
              <h2>自社投稿</h2>
            </div>
            <p><?= count($myItems); ?>件</p>
          </div>
          <?php if (!$myItems): ?>
            <p class="member-empty">まだ投稿はありません。</p>
          <?php else: ?>
            <div class="member-table-wrap">
              <table class="member-table">
                <thead>
                  <tr>
                    <th>タイトル</th>
                    <th>カテゴリ</th>
                    <th>公開範囲</th>
                    <th>状態</th>
                    <th>登録日</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($myItems as $item): ?>
                    <tr>
                      <td><?= h((string)($item['title'] ?? '')); ?></td>
                      <td><?= h(member_content_category_label((string)($item['category'] ?? 'service'))); ?></td>
                      <td><?= h(member_content_visibility_label((string)($item['visibility'] ?? 'all'))); ?></td>
                      <td><span class="member-status <?= h((string)($item['status'] ?? 'pending')); ?>"><?= h(member_content_status_label((string)($item['status'] ?? 'pending'))); ?></span></td>
                      <td><?= h((string)($item['created_at'] ?? '')); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>
