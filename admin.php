<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config.php';

$errors = [];
$message = '';
$allowedSections = ['news', 'regular_members', 'support_members', 'chairman_messages', 'settings'];
$section = (string)($_GET['section'] ?? 'news');

if (!empty($_SESSION['admin_message'])) {
    $message = (string)$_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}

if (!in_array($section, $allowedSections, true)) {
    $section = 'news';
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function require_csrf(): void
{
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf'])) {
        http_response_code(400);
        exit('Invalid request.');
    }
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']);
}

function redirect_admin(string $section = 'news', string $message = ''): void
{
    if ($message !== '') {
        $_SESSION['admin_message'] = $message;
    }

    header('Location: admin.php?section=' . rawurlencode($section));
    exit;
}

function starts_with(string $value, string $prefix): bool
{
    return substr($value, 0, strlen($prefix)) === $prefix;
}

function collect_news_form_data(): array
{
    $date = trim((string)($_POST['date'] ?? ''));
    $title = trim(strip_tags((string)($_POST['title'] ?? '')));
    $body = trim((string)($_POST['body'] ?? ''));
    $additionalImage = trim(strip_tags((string)($_POST['image'] ?? '')));
    $imageAlt = trim(strip_tags((string)($_POST['image_alt'] ?? '')));
    $link = trim(strip_tags((string)($_POST['link'] ?? '')));
    $images = [];
    $postedImages = $_POST['news_image_order'] ?? ($_POST['news_images'] ?? []);

    if (!is_array($postedImages)) {
        $postedImages = [$postedImages];
    }

    foreach ($postedImages as $postedImage) {
        $src = trim(strip_tags((string)$postedImage));

        if (starts_with($src, 'existing:')) {
            $src = substr($src, strlen('existing:'));
        }

        if (starts_with($src, 'upload:')) {
            continue;
        }

        if ($src !== '') {
            $images[] = [
                'src' => $src,
                'alt' => $imageAlt !== '' ? $imageAlt : $title,
            ];
        }
    }

    if ($additionalImage !== '') {
        $images[] = [
            'src' => $additionalImage,
            'alt' => $imageAlt !== '' ? $imageAlt : $title,
        ];
    }

    $mainImage = $images[0]['src'] ?? '';

    return [
        'date' => $date,
        'display_date' => $date !== '' ? str_replace('-', '/', $date) : '',
        'title' => $title,
        'body' => $body,
        'image' => $mainImage,
        'images' => $images,
        'image_alt' => $imageAlt !== '' ? $imageAlt : $title,
        'link' => $link,
        'published' => !empty($_POST['published']),
    ];
}

function apply_news_uploaded_images(array $data, array $uploadedImages): array
{
    $alt = (string)($data['image_alt'] ?? $data['title'] ?? '');
    $existingBySrc = [];

    foreach (($data['images'] ?? []) as $image) {
        $src = (string)($image['src'] ?? '');

        if ($src !== '') {
            $existingBySrc[$src] = [
                'src' => $src,
                'alt' => (string)($image['alt'] ?? $alt),
            ];
        }
    }

    $orderedImages = [];
    $usedSources = [];
    $imageOrder = $_POST['news_image_order'] ?? [];

    if (!is_array($imageOrder)) {
        $imageOrder = [$imageOrder];
    }

    foreach ($imageOrder as $token) {
        $token = trim((string)$token);
        $src = '';

        if (starts_with($token, 'existing:')) {
            $src = substr($token, strlen('existing:'));
        } elseif (starts_with($token, 'upload:')) {
            $uploadIndex = substr($token, strlen('upload:'));
            $src = (string)($uploadedImages[$uploadIndex] ?? '');
        }

        if ($src !== '' && empty($usedSources[$src])) {
            $orderedImages[] = [
                'src' => $src,
                'alt' => (string)($existingBySrc[$src]['alt'] ?? $alt),
            ];
            $usedSources[$src] = true;
        }
    }

    foreach (($data['images'] ?? []) as $image) {
        $src = (string)($image['src'] ?? '');

        if ($src !== '' && empty($usedSources[$src])) {
            $orderedImages[] = [
                'src' => $src,
                'alt' => (string)($image['alt'] ?? $alt),
            ];
            $usedSources[$src] = true;
        }
    }

    foreach ($uploadedImages as $uploadedImage) {
        $src = (string)$uploadedImage;

        if ($src !== '' && empty($usedSources[$src])) {
            $orderedImages[] = [
                'src' => $src,
                'alt' => $alt,
            ];
            $usedSources[$src] = true;
        }
    }

    $data['images'] = $orderedImages;
    $data['image'] = (string)($orderedImages[0]['src'] ?? '');

    return $data;
}

function validate_news_form(array $data): array
{
    $errors = [];

    if ($data['date'] === '') {
        $errors[] = '日付を入力してください。';
    }

    if ($data['title'] === '') {
        $errors[] = 'タイトルを入力してください。';
    }

    if (!empty($data['published']) && $data['body'] === '') {
        $errors[] = '本文を入力してください。';
    }

    return $errors;
}

function collect_regular_member_form_data(): array
{
    $mapTop = trim(strip_tags((string)($_POST['map_top'] ?? '')));
    $mapLeft = trim(strip_tags((string)($_POST['map_left'] ?? '')));
    $presidentRole = trim(strip_tags((string)($_POST['president_role'] ?? '')));
    $presidentLastName = trim(strip_tags((string)($_POST['president_last_name'] ?? '')));
    $presidentFirstName = trim(strip_tags((string)($_POST['president_first_name'] ?? '')));
    $presidentAlphabet = trim(strip_tags((string)($_POST['president_alphabet'] ?? '')));
    $president = trim($presidentRole . ' ' . $presidentLastName . ' ' . $presidentFirstName);
    $presidentImage = !empty($_POST['remove_president_image'])
        ? ''
        : trim(strip_tags((string)($_POST['existing_president_image'] ?? '')));

    return [
        'prefecture' => trim(strip_tags((string)($_POST['prefecture'] ?? ''))),
        'company' => trim(strip_tags((string)($_POST['company'] ?? ''))),
        'store_name' => trim(strip_tags((string)($_POST['store_name'] ?? ''))),
        'president' => $president,
        'president_role' => $presidentRole,
        'president_last_name' => $presidentLastName,
        'president_first_name' => $presidentFirstName,
        'president_alphabet' => $presidentAlphabet,
        'president_image' => $presidentImage,
        'address' => trim(strip_tags((string)($_POST['address'] ?? ''))),
        'tel' => trim(strip_tags((string)($_POST['tel'] ?? ''))),
        'fax' => trim(strip_tags((string)($_POST['fax'] ?? ''))),
        'url' => trim(strip_tags((string)($_POST['url'] ?? ''))),
        'map_top' => $mapTop !== '' ? (float)$mapTop : null,
        'map_left' => $mapLeft !== '' ? (float)$mapLeft : null,
        'sort_order' => (int)($_POST['sort_order'] ?? 9999),
        'published' => !empty($_POST['published']),
    ];
}

function validate_regular_member_form(array $data): array
{
    $errors = [];

    if ($data['prefecture'] === '') {
        $errors[] = '都道府県を入力してください。';
    }

    if ($data['company'] === '') {
        $errors[] = '会社名を入力してください。';
    }

    foreach (['map_top' => '地図の上位置', 'map_left' => '地図の左位置'] as $key => $label) {
        if ($data[$key] !== null && ($data[$key] < 0 || $data[$key] > 100)) {
            $errors[] = $label . 'は0から100の範囲で入力してください。';
        }
    }

    return $errors;
}

function collect_support_member_form_data(): array
{
    return [
        'company' => trim(strip_tags((string)($_POST['company'] ?? ''))),
        'president' => trim(strip_tags((string)($_POST['president'] ?? ''))),
        'address' => trim(strip_tags((string)($_POST['address'] ?? ''))),
        'url' => trim(strip_tags((string)($_POST['url'] ?? ''))),
        'sort_order' => (int)($_POST['sort_order'] ?? 9999),
        'published' => !empty($_POST['published']),
    ];
}

function validate_support_member_form(array $data): array
{
    $errors = [];

    if ($data['company'] === '') {
        $errors[] = '会社名を入力してください。';
    }

    return $errors;
}

function collect_chairman_message_form_data(): array
{
    $image = !empty($_POST['remove_image'])
        ? ''
        : trim(strip_tags((string)($_POST['existing_image'] ?? '')));

    return [
        'term' => trim(strip_tags((string)($_POST['term'] ?? ''))),
        'company' => trim(strip_tags((string)($_POST['company'] ?? ''))),
        'last_name' => trim(strip_tags((string)($_POST['last_name'] ?? ''))),
        'first_name' => trim(strip_tags((string)($_POST['first_name'] ?? ''))),
        'image' => $image,
        'message' => trim((string)($_POST['message'] ?? '')),
        'sort_order' => (int)($_POST['sort_order'] ?? 9999),
        'published' => !empty($_POST['published']),
    ];
}

function validate_chairman_message_form(array $data): array
{
    $errors = [];

    if ($data['term'] === '') {
        $errors[] = '会長任期を入力してください。';
    }

    if ($data['last_name'] === '' || $data['first_name'] === '') {
        $errors[] = '姓と名を入力してください。';
    }

    if ($data['message'] === '') {
        $errors[] = 'メッセージを入力してください。';
    }

    return $errors;
}

function collect_settings_form_data(): array
{
    $fvImages = [];
    $postedFvImages = $_POST['fv_image_order'] ?? [];

    if (!is_array($postedFvImages)) {
        $postedFvImages = [$postedFvImages];
    }

    foreach ($postedFvImages as $postedFvImage) {
        $src = trim(strip_tags((string)$postedFvImage));

        if (starts_with($src, 'existing:')) {
            $src = substr($src, strlen('existing:'));
        }

        if (starts_with($src, 'upload:')) {
            continue;
        }

        if ($src !== '') {
            $fvImages[] = [
                'src' => $src,
                'alt' => 'GKSチェーン協会のファーストビュー画像',
            ];
        }

        if (count($fvImages) >= FV_IMAGE_LIMIT) {
            break;
        }
    }

    return [
        'fv_images' => $fvImages,
        'header_logo' => trim(strip_tags((string)($_POST['existing_header_logo'] ?? 'image/logo/logo.png'))),
        'header_logo_scale' => (float)($_POST['header_logo_scale'] ?? 100),
        'footer_logo' => trim(strip_tags((string)($_POST['existing_footer_logo'] ?? 'image/logo/logo.png'))),
        'footer_logo_scale' => (float)($_POST['footer_logo_scale'] ?? 100),
        'map_group_distance' => (float)($_POST['map_group_distance'] ?? MAP_GROUP_DISTANCE),
        'map_group_distance_mobile' => (float)($_POST['map_group_distance_mobile'] ?? MAP_GROUP_DISTANCE_MOBILE),
        'map_dot_size' => (float)($_POST['map_dot_size'] ?? MAP_DOT_SIZE),
        'map_dot_size_mobile' => (float)($_POST['map_dot_size_mobile'] ?? MAP_DOT_SIZE_MOBILE),
        'map_dot_multi_size' => (float)($_POST['map_dot_multi_size'] ?? MAP_DOT_MULTI_SIZE),
        'map_dot_multi_size_mobile' => (float)($_POST['map_dot_multi_size_mobile'] ?? MAP_DOT_MULTI_SIZE_MOBILE),
        'map_dot_spread' => (float)($_POST['map_dot_spread'] ?? MAP_DOT_SPREAD),
        'map_dot_spread_mobile' => (float)($_POST['map_dot_spread_mobile'] ?? MAP_DOT_SPREAD_MOBILE),
        'map_dot_multi_spread' => (float)($_POST['map_dot_multi_spread'] ?? MAP_DOT_MULTI_SPREAD),
        'map_dot_multi_spread_mobile' => (float)($_POST['map_dot_multi_spread_mobile'] ?? MAP_DOT_MULTI_SPREAD_MOBILE),
    ];
}

function validate_settings_form(array $data): array
{
    $errors = [];

    if (count($data['fv_images'] ?? []) < 1) {
        $errors[] = 'FV画像は1枚以上登録してください。';
    }

    if (count($data['fv_images'] ?? []) > FV_IMAGE_LIMIT) {
        $errors[] = 'FV画像は最大' . FV_IMAGE_LIMIT . '枚まで登録できます。';
    }

    foreach ([
        'header_logo_scale' => 'ヘッダーロゴ表示倍率',
        'footer_logo_scale' => 'フッターロゴ表示倍率',
    ] as $key => $label) {
        if ($data[$key] < 20 || $data[$key] > 200) {
            $errors[] = $label . 'は20から200の範囲で入力してください。';
        }
    }

    if ($data['map_group_distance'] < 0 || $data['map_group_distance'] > 20) {
        $errors[] = '地図ピンまとめ距離（PC）は0から20の範囲で入力してください。';
    }

    if ($data['map_group_distance_mobile'] < 0 || $data['map_group_distance_mobile'] > 20) {
        $errors[] = '地図ピンまとめ距離（スマホ）は0から20の範囲で入力してください。';
    }

    foreach ([
        'map_dot_size' => '座標の丸の大きさ（PC）',
        'map_dot_size_mobile' => '座標の丸の大きさ（スマホ）',
        'map_dot_multi_size' => 'まとめ丸の大きさ（PC）',
        'map_dot_multi_size_mobile' => 'まとめ丸の大きさ（スマホ）',
    ] as $key => $label) {
        if ($data[$key] < 1 || $data[$key] > 40) {
            $errors[] = $label . 'は1から40の範囲で入力してください。';
        }
    }

    foreach ([
        'map_dot_spread' => '座標の広がり（PC）',
        'map_dot_spread_mobile' => '座標の広がり（スマホ）',
        'map_dot_multi_spread' => 'まとめ丸の広がり（PC）',
        'map_dot_multi_spread_mobile' => 'まとめ丸の広がり（スマホ）',
    ] as $key => $label) {
        if ($data[$key] < 0 || $data[$key] > 40) {
            $errors[] = $label . 'は0から40の範囲で入力してください。';
        }
    }

    return $errors;
}

function apply_fv_uploaded_images(array $data, array $uploadedImages): array
{
    $existingBySrc = [];

    foreach (($data['fv_images'] ?? []) as $image) {
        $src = (string)($image['src'] ?? '');

        if ($src !== '') {
            $existingBySrc[$src] = [
                'src' => $src,
                'alt' => (string)($image['alt'] ?? 'GKSチェーン協会のファーストビュー画像'),
            ];
        }
    }

    $orderedImages = [];
    $usedSources = [];
    $imageOrder = $_POST['fv_image_order'] ?? [];

    if (!is_array($imageOrder)) {
        $imageOrder = [$imageOrder];
    }

    foreach ($imageOrder as $token) {
        $token = trim((string)$token);
        $src = '';

        if (starts_with($token, 'existing:')) {
            $src = substr($token, strlen('existing:'));
        } elseif (starts_with($token, 'upload:')) {
            $uploadIndex = substr($token, strlen('upload:'));
            $src = (string)($uploadedImages[$uploadIndex] ?? '');
        }

        if ($src !== '' && empty($usedSources[$src])) {
            $orderedImages[] = [
                'src' => $src,
                'alt' => (string)($existingBySrc[$src]['alt'] ?? 'GKSチェーン協会のファーストビュー画像'),
            ];
            $usedSources[$src] = true;
        }

        if (count($orderedImages) >= FV_IMAGE_LIMIT) {
            break;
        }
    }

    $data['fv_images'] = $orderedImages;

    return $data;
}

function handle_news_image_upload(): array
{
    if (empty($_FILES['news_image'])) {
        return [[], []];
    }

    $files = $_FILES['news_image'];
    $fileCount = is_array($files['name'] ?? null) ? count($files['name']) : 1;
    $uploadedImages = [];
    $errors = [];
    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    for ($index = 0; $index < $fileCount; $index++) {
        $fileError = is_array($files['error'] ?? null) ? ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) : ($files['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($fileError === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = '画像のアップロードに失敗しました。';
            continue;
        }

        $fileSize = is_array($files['size'] ?? null) ? (int)($files['size'][$index] ?? 0) : (int)($files['size'] ?? 0);

        if ($fileSize > 5 * 1024 * 1024) {
            $errors[] = '画像サイズは1枚あたり5MB以内にしてください。';
            continue;
        }

        $tmpName = is_array($files['tmp_name'] ?? null) ? (string)($files['tmp_name'][$index] ?? '') : (string)($files['tmp_name'] ?? '');
        $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;

        if ($imageInfo === false || !isset($allowedTypes[$imageInfo[2]])) {
            $errors[] = 'アップロードできる画像は JPG / PNG / GIF / WebP です。';
            continue;
        }

        $uploadDir = __DIR__ . '/image/news';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
            return [[], ['画像保存フォルダを作成できませんでした。']];
        }

        $extension = $allowedTypes[$imageInfo[2]];
        $fileName = 'news_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            $errors[] = '画像を保存できませんでした。';
            continue;
        }

        $uploadedImages[(string)$index] = 'image/news/' . $fileName;
    }

    return [$uploadedImages, $errors];
}

function handle_fv_image_upload(): array
{
    if (empty($_FILES['fv_image'])) {
        return [[], []];
    }

    $files = $_FILES['fv_image'];
    $fileCount = is_array($files['name'] ?? null) ? count($files['name']) : 1;
    $uploadedImages = [];
    $errors = [];
    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    for ($index = 0; $index < $fileCount; $index++) {
        $fileError = is_array($files['error'] ?? null) ? ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) : ($files['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($fileError === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = 'FV画像のアップロードに失敗しました。';
            continue;
        }

        $fileSize = is_array($files['size'] ?? null) ? (int)($files['size'][$index] ?? 0) : (int)($files['size'] ?? 0);

        if ($fileSize > 5 * 1024 * 1024) {
            $errors[] = 'FV画像サイズは1枚あたり5MB以内にしてください。';
            continue;
        }

        $tmpName = is_array($files['tmp_name'] ?? null) ? (string)($files['tmp_name'][$index] ?? '') : (string)($files['tmp_name'] ?? '');
        $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;

        if ($imageInfo === false || !isset($allowedTypes[$imageInfo[2]])) {
            $errors[] = 'FVにアップロードできる画像は JPG / PNG / GIF / WebP です。';
            continue;
        }

        $uploadDir = __DIR__ . '/image/fv';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
            return [[], ['FV画像保存フォルダを作成できませんでした。']];
        }

        $extension = $allowedTypes[$imageInfo[2]];
        $fileName = 'fv_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = $uploadDir . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            $errors[] = 'FV画像を保存できませんでした。';
            continue;
        }

        $uploadedImages[(string)$index] = 'image/fv/' . $fileName;
    }

    return [$uploadedImages, $errors];
}

function handle_site_logo_upload(string $field, string $label): array
{
    if (empty($_FILES[$field])) {
        return ['', []];
    }

    $file = $_FILES[$field];
    $fileError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($fileError === UPLOAD_ERR_NO_FILE) {
        return ['', []];
    }

    if ($fileError !== UPLOAD_ERR_OK) {
        return ['', [$label . 'のアップロードに失敗しました。']];
    }

    $fileSize = (int)($file['size'] ?? 0);

    if ($fileSize > 5 * 1024 * 1024) {
        return ['', [$label . 'は5MB以内にしてください。']];
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    if ($imageInfo === false || !isset($allowedTypes[$imageInfo[2]])) {
        return ['', [$label . 'にアップロードできる画像は JPG / PNG / GIF / WebP です。']];
    }

    $uploadDir = __DIR__ . '/image/logo';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        return ['', [$label . '保存フォルダを作成できませんでした。']];
    }

    $extension = $allowedTypes[$imageInfo[2]];
    $fileName = $field . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        return ['', [$label . 'を保存できませんでした。']];
    }

    return ['image/logo/' . $fileName, []];
}

function apply_site_logo_uploads(array $data, string $uploadedHeaderLogo, string $uploadedFooterLogo): array
{
    if ($uploadedHeaderLogo !== '') {
        $data['header_logo'] = $uploadedHeaderLogo;
    }

    if ($uploadedFooterLogo !== '') {
        $data['footer_logo'] = $uploadedFooterLogo;
    }

    return $data;
}

function handle_regular_president_image_upload(): array
{
    if (empty($_FILES['president_image'])) {
        return ['', []];
    }

    $file = $_FILES['president_image'];
    $fileError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($fileError === UPLOAD_ERR_NO_FILE) {
        return ['', []];
    }

    if ($fileError !== UPLOAD_ERR_OK) {
        return ['', ['代表者写真のアップロードに失敗しました。']];
    }

    $fileSize = (int)($file['size'] ?? 0);

    if ($fileSize > 5 * 1024 * 1024) {
        return ['', ['代表者写真は5MB以内にしてください。']];
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    if ($imageInfo === false || !isset($allowedTypes[$imageInfo[2]])) {
        return ['', ['代表者写真にアップロードできる画像は JPG / PNG / GIF / WebP です。']];
    }

    $uploadDir = __DIR__ . '/image/member/president';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        return ['', ['代表者写真保存フォルダを作成できませんでした。']];
    }

    $extension = $allowedTypes[$imageInfo[2]];
    $fileName = 'president_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        return ['', ['代表者写真を保存できませんでした。']];
    }

    return ['image/member/president/' . $fileName, []];
}

function apply_regular_president_image(array $data, string $uploadedImage): array
{
    if ($uploadedImage !== '') {
        $data['president_image'] = $uploadedImage;
    }

    return $data;
}

function handle_chairman_message_image_upload(): array
{
    if (empty($_FILES['image'])) {
        return ['', []];
    }

    $file = $_FILES['image'];
    $fileError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($fileError === UPLOAD_ERR_NO_FILE) {
        return ['', []];
    }

    if ($fileError !== UPLOAD_ERR_OK) {
        return ['', ['顔写真のアップロードに失敗しました。']];
    }

    $fileSize = (int)($file['size'] ?? 0);

    if ($fileSize > 5 * 1024 * 1024) {
        return ['', ['顔写真は5MB以内にしてください。']];
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    $imageInfo = $tmpName !== '' ? @getimagesize($tmpName) : false;
    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    if ($imageInfo === false || !isset($allowedTypes[$imageInfo[2]])) {
        return ['', ['顔写真にアップロードできる画像は JPG / PNG / GIF / WebP です。']];
    }

    $uploadDir = __DIR__ . '/image/chairman-message';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        return ['', ['顔写真保存フォルダを作成できませんでした。']];
    }

    $extension = $allowedTypes[$imageInfo[2]];
    $fileName = 'chairman_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        return ['', ['顔写真を保存できませんでした。']];
    }

    return ['image/chairman-message/' . $fileName, []];
}

function apply_chairman_message_image(array $data, string $uploadedImage): array
{
    if ($uploadedImage !== '') {
        $data['image'] = $uploadedImage;
    }

    return $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    require_csrf();

    $user = trim((string)($_POST['user'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($user === ADMIN_USER && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php?section=news');
        exit;
    }

    $errors[] = 'ユーザー名またはパスワードが正しくありません。';
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    require_csrf();

    $data = collect_news_form_data();
    [$uploadedImages, $uploadErrors] = handle_news_image_upload();
    $errors = array_merge($errors, $uploadErrors);

    $data = apply_news_uploaded_images($data, $uploadedImages);
    $errors = array_merge($errors, validate_news_form($data));

    if (!$errors) {
        $items = load_news(false);
        array_unshift($items, array_merge($data, [
            'id' => date('Ymd-His'),
        ]));

        if (save_news($items)) {
            redirect_admin('news', '最新情報を保存しました。');
        } else {
            $errors[] = '保存に失敗しました。data/news.json の書き込み権限を確認してください。';
        }
    }
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    require_csrf();

    $id = (string)($_POST['id'] ?? '');
    $data = collect_news_form_data();
    [$uploadedImages, $uploadErrors] = handle_news_image_upload();
    $errors = array_merge($errors, $uploadErrors);

    $data = apply_news_uploaded_images($data, $uploadedImages);
    $errors = array_merge($errors, validate_news_form($data));

    if ($id === '') {
        $errors[] = '編集する投稿が見つかりません。';
    }

    if (!$errors) {
        $items = load_news(false);
        $updated = false;

        foreach ($items as &$item) {
            if ((string)($item['id'] ?? '') === $id) {
                $item = array_merge($item, $data, ['id' => $id]);
                $updated = true;
                break;
            }
        }
        unset($item);

        if (!$updated) {
            $errors[] = '編集する投稿が見つかりません。';
        } elseif (save_news($items)) {
            redirect_admin('news', '投稿を更新しました。');
        } else {
            $errors[] = '更新に失敗しました。data/news.json の書き込み権限を確認してください。';
        }
    }
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    require_csrf();

    $id = (string)($_POST['id'] ?? '');
    $items = array_values(array_filter(load_news(false), function ($item) use ($id) {
        return (string)($item['id'] ?? '') !== $id;
    }));

    if (save_news($items)) {
        redirect_admin('news', '投稿を削除しました。');
    } else {
        $errors[] = '削除に失敗しました。';
    }
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'regular_create') {
    require_csrf();

    $data = collect_regular_member_form_data();
    [$uploadedPresidentImage, $presidentImageErrors] = handle_regular_president_image_upload();
    $errors = array_merge($errors, $presidentImageErrors);
    $data = apply_regular_president_image($data, $uploadedPresidentImage);
    $errors = array_merge($errors, validate_regular_member_form($data));

    if (!$errors) {
        $items = load_regular_members(false);
        array_unshift($items, array_merge($data, [
            'id' => 'regular_' . date('Ymd_His'),
        ]));

        if (save_regular_members($items)) {
            redirect_admin('regular_members', '正会員情報を保存しました。');
        } else {
            $errors[] = '正会員情報の保存に失敗しました。';
        }
    }
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'regular_update') {
    require_csrf();

    $id = (string)($_POST['id'] ?? '');
    $data = collect_regular_member_form_data();
    [$uploadedPresidentImage, $presidentImageErrors] = handle_regular_president_image_upload();
    $errors = array_merge($errors, $presidentImageErrors);
    $data = apply_regular_president_image($data, $uploadedPresidentImage);
    $errors = array_merge($errors, validate_regular_member_form($data));

    if ($id === '') {
        $errors[] = '編集する正会員情報が見つかりません。';
    }

    if (!$errors) {
        $items = load_regular_members(false);
        $updated = false;

        foreach ($items as &$item) {
            if ((string)($item['id'] ?? '') === $id) {
                $item = array_merge($item, $data, ['id' => $id]);
                $updated = true;
                break;
            }
        }
        unset($item);

        if (!$updated) {
            $errors[] = '編集する正会員情報が見つかりません。';
        } elseif (save_regular_members($items)) {
            redirect_admin('regular_members', '正会員情報を更新しました。');
        } else {
            $errors[] = '正会員情報の更新に失敗しました。';
        }
    }
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'regular_delete') {
    require_csrf();

    $id = (string)($_POST['id'] ?? '');
    $items = array_values(array_filter(load_regular_members(false), function ($item) use ($id) {
        return (string)($item['id'] ?? '') !== $id;
    }));

    if (save_regular_members($items)) {
        redirect_admin('regular_members', '正会員情報を削除しました。');
    } else {
        $errors[] = '正会員情報の削除に失敗しました。';
    }
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'support_create') {
    require_csrf();

    $data = collect_support_member_form_data();
    $errors = array_merge($errors, validate_support_member_form($data));

    if (!$errors) {
        $items = load_support_members(false);
        array_unshift($items, array_merge($data, [
            'id' => 'support_' . date('Ymd_His'),
        ]));

        if (save_support_members($items)) {
            redirect_admin('support_members', '賛助会員情報を保存しました。');
        } else {
            $errors[] = '賛助会員情報の保存に失敗しました。';
        }
    }
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'support_update') {
    require_csrf();

    $id = (string)($_POST['id'] ?? '');
    $data = collect_support_member_form_data();
    $errors = array_merge($errors, validate_support_member_form($data));

    if ($id === '') {
        $errors[] = '編集する賛助会員情報が見つかりません。';
    }

    if (!$errors) {
        $items = load_support_members(false);
        $updated = false;

        foreach ($items as &$item) {
            if ((string)($item['id'] ?? '') === $id) {
                $item = array_merge($item, $data, ['id' => $id]);
                $updated = true;
                break;
            }
        }
        unset($item);

        if (!$updated) {
            $errors[] = '編集する賛助会員情報が見つかりません。';
        } elseif (save_support_members($items)) {
            redirect_admin('support_members', '賛助会員情報を更新しました。');
        } else {
            $errors[] = '賛助会員情報の更新に失敗しました。';
        }
    }
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'support_delete') {
    require_csrf();

    $id = (string)($_POST['id'] ?? '');
    $items = array_values(array_filter(load_support_members(false), function ($item) use ($id) {
        return (string)($item['id'] ?? '') !== $id;
    }));

    if (save_support_members($items)) {
        redirect_admin('support_members', '賛助会員情報を削除しました。');
    } else {
        $errors[] = '賛助会員情報の削除に失敗しました。';
    }
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'chairman_message_create') {
    require_csrf();

    $data = collect_chairman_message_form_data();
    [$uploadedImage, $imageErrors] = handle_chairman_message_image_upload();
    $errors = array_merge($errors, $imageErrors);
    $data = apply_chairman_message_image($data, $uploadedImage);
    $errors = array_merge($errors, validate_chairman_message_form($data));

    if (!$errors) {
        $items = load_chairman_messages(false);
        array_unshift($items, array_merge($data, [
            'id' => 'chairman_' . date('Ymd_His'),
        ]));

        if (save_chairman_messages($items)) {
            redirect_admin('chairman_messages', '歴代会長の言葉を保存しました。');
        } else {
            $errors[] = '歴代会長の言葉の保存に失敗しました。';
        }
    }
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'chairman_message_update') {
    require_csrf();

    $id = (string)($_POST['id'] ?? '');
    $data = collect_chairman_message_form_data();
    [$uploadedImage, $imageErrors] = handle_chairman_message_image_upload();
    $errors = array_merge($errors, $imageErrors);
    $data = apply_chairman_message_image($data, $uploadedImage);
    $errors = array_merge($errors, validate_chairman_message_form($data));

    if ($id === '') {
        $errors[] = '編集する歴代会長の言葉が見つかりません。';
    }

    if (!$errors) {
        $items = load_chairman_messages(false);
        $updated = false;

        foreach ($items as &$item) {
            if ((string)($item['id'] ?? '') === $id) {
                $item = array_merge($item, $data, ['id' => $id]);
                $updated = true;
                break;
            }
        }
        unset($item);

        if (!$updated) {
            $errors[] = '編集する歴代会長の言葉が見つかりません。';
        } elseif (save_chairman_messages($items)) {
            redirect_admin('chairman_messages', '歴代会長の言葉を更新しました。');
        } else {
            $errors[] = '歴代会長の言葉の更新に失敗しました。';
        }
    }
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'chairman_message_delete') {
    require_csrf();

    $id = (string)($_POST['id'] ?? '');
    $items = array_values(array_filter(load_chairman_messages(false), function ($item) use ($id) {
        return (string)($item['id'] ?? '') !== $id;
    }));

    if (save_chairman_messages($items)) {
        redirect_admin('chairman_messages', '歴代会長の言葉を削除しました。');
    } else {
        $errors[] = '歴代会長の言葉の削除に失敗しました。';
    }
}

if (is_admin_logged_in() && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'settings_update') {
    require_csrf();

    $data = collect_settings_form_data();
    [$uploadedFvImages, $fvUploadErrors] = handle_fv_image_upload();
    $errors = array_merge($errors, $fvUploadErrors);
    [$uploadedHeaderLogo, $headerLogoErrors] = handle_site_logo_upload('header_logo', 'ヘッダーロゴ');
    [$uploadedFooterLogo, $footerLogoErrors] = handle_site_logo_upload('footer_logo', 'フッターロゴ');
    $errors = array_merge($errors, $headerLogoErrors, $footerLogoErrors);

    $data = apply_fv_uploaded_images($data, $uploadedFvImages);
    $data = apply_site_logo_uploads($data, $uploadedHeaderLogo, $uploadedFooterLogo);
    $errors = array_merge($errors, validate_settings_form($data));

    if (!$errors) {
        if (save_site_settings($data)) {
            redirect_admin('settings', 'サイト設定を更新しました。');
        } else {
            $errors[] = 'サイト設定の保存に失敗しました。';
        }
    }
}

$items = is_admin_logged_in() && $section === 'news' ? load_news(false) : [];
$editId = is_admin_logged_in() && $section === 'news' ? (string)($_GET['edit'] ?? '') : '';
$editingItem = null;
$regularMembers = is_admin_logged_in() && $section === 'regular_members' ? load_regular_members(false) : [];
$regularEditId = is_admin_logged_in() && $section === 'regular_members' ? (string)($_GET['edit'] ?? '') : '';
$editingRegularMember = null;
$supportMembersAdmin = is_admin_logged_in() && $section === 'support_members' ? load_support_members(false) : [];
$supportEditId = is_admin_logged_in() && $section === 'support_members' ? (string)($_GET['edit'] ?? '') : '';
$editingSupportMember = null;
$chairmanMessagesAdmin = is_admin_logged_in() && $section === 'chairman_messages' ? load_chairman_messages(false) : [];
$chairmanMessageEditId = is_admin_logged_in() && $section === 'chairman_messages' ? (string)($_GET['edit'] ?? '') : '';
$editingChairmanMessage = null;
$siteSettings = is_admin_logged_in() && $section === 'settings' ? load_site_settings() : default_site_settings();

if ($editId !== '') {
    foreach ($items as $item) {
        if ((string)($item['id'] ?? '') === $editId) {
            $editingItem = $item;
            break;
        }
    }

    if ($editingItem === null) {
        $errors[] = '編集する投稿が見つかりません。';
    }
}

$formItem = $editingItem ?? [];
$isEditing = $editingItem !== null;
$formDate = (string)($_POST['date'] ?? ($formItem['date'] ?? date('Y-m-d')));
$formTitle = (string)($_POST['title'] ?? ($formItem['title'] ?? ''));
$formBody = (string)($_POST['body'] ?? ($formItem['body'] ?? ''));
$formImage = (string)($_POST['image'] ?? '');
$formImageAlt = (string)($_POST['image_alt'] ?? ($formItem['image_alt'] ?? ''));
$formLink = (string)($_POST['link'] ?? ($formItem['link'] ?? ''));
$formImages = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? news_images([
        'images' => array_values(array_filter(array_map(
            function ($token) {
                $token = (string)$token;
                return starts_with($token, 'existing:') ? substr($token, strlen('existing:')) : '';
            },
            is_array($_POST['news_image_order'] ?? null) ? $_POST['news_image_order'] : []
        ))),
        'image' => $formImage,
        'image_alt' => $formImageAlt,
        'title' => $formTitle,
    ])
    : news_images($formItem);
$formPublished = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? !empty($_POST['published'])
    : ($isEditing ? !empty($formItem['published']) : false);

if ($regularEditId !== '') {
    foreach ($regularMembers as $member) {
        if ((string)($member['id'] ?? '') === $regularEditId) {
            $editingRegularMember = $member;
            break;
        }
    }

    if ($editingRegularMember === null) {
        $errors[] = '編集する正会員情報が見つかりません。';
    }
}

$regularFormItem = $editingRegularMember ?? [];
$isRegularEditing = $editingRegularMember !== null;
$regularFormParts = regular_member_parts($regularFormItem);
$regularFormRepresentativeParts = regular_member_representative_parts($regularFormItem);
$regularFormPrefecture = (string)($_POST['prefecture'] ?? ($regularFormItem['prefecture'] ?? ''));
$regularFormCompany = (string)($_POST['company'] ?? $regularFormParts['company']);
$regularFormStoreName = (string)($_POST['store_name'] ?? $regularFormParts['store_name']);
$regularFormPresidentRole = (string)($_POST['president_role'] ?? $regularFormRepresentativeParts['role']);
$regularFormPresidentLastName = (string)($_POST['president_last_name'] ?? $regularFormRepresentativeParts['last_name']);
$regularFormPresidentFirstName = (string)($_POST['president_first_name'] ?? $regularFormRepresentativeParts['first_name']);
$regularFormPresidentAlphabet = (string)($_POST['president_alphabet'] ?? $regularFormRepresentativeParts['alphabet']);
$regularFormPresident = trim($regularFormPresidentRole . ' ' . $regularFormPresidentLastName . ' ' . $regularFormPresidentFirstName);
$regularFormPresidentImage = (string)($_POST['existing_president_image'] ?? ($regularFormItem['president_image'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['action'] ?? ''), ['regular_create', 'regular_update'], true) && isset($data['president_image'])) {
    $regularFormPresidentImage = (string)$data['president_image'];
}
$regularFormAddress = (string)($_POST['address'] ?? ($regularFormItem['address'] ?? ''));
$regularFormTel = (string)($_POST['tel'] ?? ($regularFormItem['tel'] ?? ''));
$regularFormFax = (string)($_POST['fax'] ?? ($regularFormItem['fax'] ?? ''));
$regularFormUrl = (string)($_POST['url'] ?? ($regularFormItem['url'] ?? ''));
$regularFormMapTop = (string)($_POST['map_top'] ?? ($regularFormItem['map_top'] ?? ''));
$regularFormMapLeft = (string)($_POST['map_left'] ?? ($regularFormItem['map_left'] ?? ''));
$regularFormSortOrder = (string)($_POST['sort_order'] ?? ($regularFormItem['sort_order'] ?? (count($regularMembers) + 1)));
$regularFormPublished = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? !empty($_POST['published'])
    : ($isRegularEditing ? !empty($regularFormItem['published']) : true);

if ($supportEditId !== '') {
    foreach ($supportMembersAdmin as $member) {
        if ((string)($member['id'] ?? '') === $supportEditId) {
            $editingSupportMember = $member;
            break;
        }
    }

    if ($editingSupportMember === null) {
        $errors[] = '編集する賛助会員情報が見つかりません。';
    }
}

$supportFormItem = $editingSupportMember ?? [];
$isSupportEditing = $editingSupportMember !== null;
$supportFormCompany = (string)($_POST['company'] ?? ($supportFormItem['company'] ?? ''));
$supportFormPresident = (string)($_POST['president'] ?? ($supportFormItem['president'] ?? ''));
$supportFormAddress = (string)($_POST['address'] ?? ($supportFormItem['address'] ?? ''));
$supportFormUrl = (string)($_POST['url'] ?? ($supportFormItem['url'] ?? ''));
$supportFormSortOrder = (string)($_POST['sort_order'] ?? ($supportFormItem['sort_order'] ?? (count($supportMembersAdmin) + 1)));
$supportFormPublished = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? !empty($_POST['published'])
    : ($isSupportEditing ? !empty($supportFormItem['published']) : true);

if ($chairmanMessageEditId !== '') {
    foreach ($chairmanMessagesAdmin as $chairmanMessage) {
        if ((string)($chairmanMessage['id'] ?? '') === $chairmanMessageEditId) {
            $editingChairmanMessage = $chairmanMessage;
            break;
        }
    }

    if ($editingChairmanMessage === null) {
        $errors[] = '編集する歴代会長の言葉が見つかりません。';
    }
}

$chairmanMessageFormItem = $editingChairmanMessage ?? [];
$isChairmanMessageEditing = $editingChairmanMessage !== null;
$chairmanMessageFormTerm = (string)($_POST['term'] ?? ($chairmanMessageFormItem['term'] ?? ''));
$chairmanMessageFormCompany = (string)($_POST['company'] ?? ($chairmanMessageFormItem['company'] ?? ''));
$chairmanMessageFormLastName = (string)($_POST['last_name'] ?? ($chairmanMessageFormItem['last_name'] ?? ''));
$chairmanMessageFormFirstName = (string)($_POST['first_name'] ?? ($chairmanMessageFormItem['first_name'] ?? ''));
$chairmanMessageFormImage = (string)($_POST['existing_image'] ?? ($chairmanMessageFormItem['image'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['action'] ?? ''), ['chairman_message_create', 'chairman_message_update'], true) && isset($data['image'])) {
    $chairmanMessageFormImage = (string)$data['image'];
}
$chairmanMessageFormMessage = (string)($_POST['message'] ?? ($chairmanMessageFormItem['message'] ?? ''));
$chairmanMessageFormSortOrder = (string)($_POST['sort_order'] ?? ($chairmanMessageFormItem['sort_order'] ?? (count($chairmanMessagesAdmin) + 1)));
$chairmanMessageFormPublished = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? !empty($_POST['published'])
    : ($isChairmanMessageEditing ? !empty($chairmanMessageFormItem['published']) : true);

$settingsMapGroupDistance = (string)($_POST['map_group_distance'] ?? ($siteSettings['map_group_distance'] ?? MAP_GROUP_DISTANCE));
$settingsMapGroupDistanceMobile = (string)($_POST['map_group_distance_mobile'] ?? ($siteSettings['map_group_distance_mobile'] ?? MAP_GROUP_DISTANCE_MOBILE));
$settingsMapDotSize = (string)($_POST['map_dot_size'] ?? ($siteSettings['map_dot_size'] ?? MAP_DOT_SIZE));
$settingsMapDotSizeMobile = (string)($_POST['map_dot_size_mobile'] ?? ($siteSettings['map_dot_size_mobile'] ?? MAP_DOT_SIZE_MOBILE));
$settingsMapDotMultiSize = (string)($_POST['map_dot_multi_size'] ?? ($siteSettings['map_dot_multi_size'] ?? MAP_DOT_MULTI_SIZE));
$settingsMapDotMultiSizeMobile = (string)($_POST['map_dot_multi_size_mobile'] ?? ($siteSettings['map_dot_multi_size_mobile'] ?? MAP_DOT_MULTI_SIZE_MOBILE));
$settingsMapDotSpread = (string)($_POST['map_dot_spread'] ?? ($siteSettings['map_dot_spread'] ?? MAP_DOT_SPREAD));
$settingsMapDotSpreadMobile = (string)($_POST['map_dot_spread_mobile'] ?? ($siteSettings['map_dot_spread_mobile'] ?? MAP_DOT_SPREAD_MOBILE));
$settingsMapDotMultiSpread = (string)($_POST['map_dot_multi_spread'] ?? ($siteSettings['map_dot_multi_spread'] ?? MAP_DOT_MULTI_SPREAD));
$settingsMapDotMultiSpreadMobile = (string)($_POST['map_dot_multi_spread_mobile'] ?? ($siteSettings['map_dot_multi_spread_mobile'] ?? MAP_DOT_MULTI_SPREAD_MOBILE));
$settingsHeaderLogo = (string)($_POST['existing_header_logo'] ?? ($siteSettings['header_logo'] ?? 'image/logo/logo.png'));
$settingsHeaderLogoScale = (string)($_POST['header_logo_scale'] ?? ($siteSettings['header_logo_scale'] ?? 100));
$settingsFooterLogo = (string)($_POST['existing_footer_logo'] ?? ($siteSettings['footer_logo'] ?? 'image/logo/logo.png'));
$settingsFooterLogoScale = (string)($_POST['footer_logo_scale'] ?? ($siteSettings['footer_logo_scale'] ?? 100));
$settingsFvImages = site_fv_images();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'settings_update' && !empty($data['fv_images']) && is_array($data['fv_images'])) {
    $settingsFvImages = $data['fv_images'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'settings_update' && isset($data) && is_array($data)) {
    $settingsHeaderLogo = (string)($data['header_logo'] ?? $settingsHeaderLogo);
    $settingsHeaderLogoScale = (string)($data['header_logo_scale'] ?? $settingsHeaderLogoScale);
    $settingsFooterLogo = (string)($data['footer_logo'] ?? $settingsFooterLogo);
    $settingsFooterLogoScale = (string)($data['footer_logo_scale'] ?? $settingsFooterLogoScale);
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>サイト管理 | GKSチェーン協会</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    body {
      background: #f7f8fa;
    }

    .admin-page {
      max-width: 1100px;
      margin: 0 auto;
      padding: 48px 24px;
    }

    .admin-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
      margin-bottom: 28px;
    }

    .admin-brand {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .admin-brand img {
      width: 150px;
      height: auto;
    }

    .admin-header h1 {
      color: var(--main);
      font-size: 28px;
    }

    .admin-nav {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 28px;
    }

    .admin-nav a {
      display: inline-block;
      padding: 12px 18px;
      border-radius: 4px;
      background: #fff;
      color: var(--main);
      font-weight: 700;
      box-shadow: var(--shadow);
    }

    .admin-nav a.active {
      background: var(--main);
      color: #fff;
    }

    .admin-panel {
      background: #fff;
      border-radius: 8px;
      box-shadow: var(--shadow);
      padding: 28px;
      margin-bottom: 28px;
    }

    .admin-panel h2 {
      color: var(--main);
      font-size: 22px;
      margin-bottom: 18px;
    }

    .admin-form {
      display: grid;
      gap: 18px;
    }

    .admin-form label {
      display: grid;
      gap: 6px;
      font-weight: 700;
      color: var(--main);
    }

    .admin-form input,
    .admin-form textarea {
      width: 100%;
      padding: 12px 14px;
      border: 1px solid #d8dde8;
      border-radius: 6px;
      font-size: 16px;
      font-family: inherit;
      font-weight: 400;
      color: #333;
    }

    .admin-form textarea {
      min-height: 150px;
      resize: vertical;
    }

    .admin-help {
      color: #666;
      font-size: 13px;
      font-weight: 400;
    }

    .admin-image-preview {
      max-width: 280px;
      border-radius: 8px;
      box-shadow: var(--shadow);
    }

    .admin-logo-preview {
      display: block;
      max-width: 220px;
      max-height: 90px;
      object-fit: contain;
      padding: 10px;
      background: #fff;
    }

    .admin-news-images {
      display: grid;
      gap: 12px;
    }

    .admin-news-image-item {
      display: grid;
      grid-template-columns: 120px 1fr;
      gap: 14px;
      align-items: center;
      padding: 12px;
      border: 1px solid #d8dde8;
      border-radius: 8px;
      background: #f7f8fa;
    }

    .admin-news-image-item img {
      width: 120px;
      height: 78px;
      object-fit: cover;
      border-radius: 6px;
      background: #fff;
    }

    .admin-news-image-path {
      margin: 0 0 10px;
      color: #333;
      font-size: 13px;
      font-weight: 400;
      overflow-wrap: anywhere;
    }

    .admin-news-image-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .admin-news-image-actions button {
      width: auto;
      padding: 8px 12px;
      border-radius: 4px;
      font-size: 13px;
    }

    .admin-news-image-actions .remove {
      background: #9d2222;
    }

    .admin-upload-row {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .admin-upload-row input[type="file"] {
      flex: 1 1 320px;
    }

    .admin-upload-row button {
      width: auto;
      padding: 12px 18px;
      border-radius: 4px;
    }

    .admin-divider {
      margin: 28px 0;
      border: 0;
      border-top: 1px solid #d8dde8;
    }

    .admin-pending-images {
      display: none;
      gap: 12px;
      margin-top: 12px;
    }

    .admin-pending-images.is-visible {
      display: grid;
    }

    .admin-pending-image-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      gap: 10px;
    }

    .admin-pending-image-item {
      overflow: hidden;
      border: 1px solid #d8dde8;
      border-radius: 8px;
      background: #fff;
    }

    .admin-pending-image-item img {
      width: 100%;
      height: 82px;
      display: block;
      object-fit: cover;
    }

    .admin-pending-image-item span {
      display: block;
      padding: 8px;
      color: #333;
      font-size: 12px;
      font-weight: 400;
      overflow-wrap: anywhere;
    }

    .admin-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
    }

    .admin-representative-grid .representative-role {
      grid-column: 1;
      grid-row: 1;
    }

    .admin-representative-grid .representative-last-name {
      grid-column: 1;
      grid-row: 2;
    }

    .admin-representative-grid .representative-first-name {
      grid-column: 2;
      grid-row: 2;
    }

    .admin-representative-grid .representative-alphabet {
      grid-column: 1;
      grid-row: 3;
    }

    .admin-form label.admin-check {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      gap: 10px;
      width: fit-content;
      color: var(--main);
      font-weight: 700;
      cursor: pointer;
    }

    .admin-form label.admin-check input[type="checkbox"],
    .admin-form label.admin-check input[type="radio"] {
      width: 18px;
      height: 18px;
      margin: 0;
      flex: 0 0 auto;
    }

    .admin-status-options {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .admin-status-options label {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      width: auto;
      min-width: 0;
      padding: 9px 14px;
      border: 1px solid #d8dde8;
      border-radius: 999px;
      background: #f7f8fa;
      color: var(--main);
      line-height: 1;
      white-space: nowrap;
      cursor: pointer;
    }

    .admin-status-options label:has(input:checked) {
      border-color: var(--main);
      background: #eef4ff;
    }

    .admin-status-badge {
      display: inline-flex;
      align-items: center;
      min-width: 64px;
      justify-content: center;
      padding: 5px 10px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 700;
    }

    .admin-status-badge.published {
      background: #eaf4ee;
      color: #176236;
    }

    .admin-status-badge.draft {
      background: #fff3df;
      color: #8a4b00;
    }

    .admin-alert {
      padding: 12px 14px;
      border-radius: 6px;
      margin-bottom: 18px;
      font-weight: 700;
    }

    .admin-alert.success {
      background: #eaf6ee;
      color: #176536;
    }

    .admin-alert.error {
      background: #fdecec;
      color: #9d2222;
    }

    .admin-table {
      width: 100%;
      border-collapse: collapse;
    }

    .admin-table th,
    .admin-table td {
      padding: 12px;
      border-bottom: 1px solid #e5e5e5;
      text-align: left;
      vertical-align: top;
    }

    .admin-table th {
      color: var(--main);
      font-size: 14px;
    }

    .admin-delete-button {
      padding: 8px 14px;
      background: #9d2222;
    }

    .admin-edit-link {
      display: inline-block;
      padding: 8px 14px;
      border-radius: 4px;
      background: var(--main);
      color: #fff;
      font-size: 14px;
      font-weight: 700;
    }

    .admin-table-actions {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .admin-actions {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    @media (max-width: 700px) {
      .admin-header,
      .admin-grid {
        grid-template-columns: 1fr;
        display: grid;
      }

      .admin-news-image-item {
        grid-template-columns: 1fr;
      }

      .admin-representative-grid > label {
        grid-column: auto;
        grid-row: auto;
      }

      .admin-table {
        font-size: 14px;
      }
    }
  </style>
</head>

<body>
  <main class="admin-page">
    <div class="admin-header">
      <div class="admin-brand">
        <img src="image/logo/logo-admin.png" alt="GKSチェーン協会">
        <h1>サイト管理</h1>
      </div>
      <?php if (is_admin_logged_in()): ?>
        <div class="admin-actions">
          <a href="index.php" class="btn" target="_blank" rel="noopener">サイトを見る</a>
          <a href="logout.php" class="btn">ログアウト</a>
        </div>
      <?php endif; ?>
    </div>

    <?php if (is_admin_logged_in()): ?>
      <nav class="admin-nav">
        <a href="admin.php?section=news" class="<?= $section === 'news' ? 'active' : ''; ?>">最新情報管理</a>
        <a href="admin.php?section=regular_members" class="<?= $section === 'regular_members' ? 'active' : ''; ?>">正会員情報管理</a>
        <a href="admin.php?section=support_members" class="<?= $section === 'support_members' ? 'active' : ''; ?>">賛助会員情報管理</a>
        <a href="admin.php?section=chairman_messages" class="<?= $section === 'chairman_messages' ? 'active' : ''; ?>">歴代会長の言葉管理</a>
        <a href="admin.php?section=settings" class="<?= $section === 'settings' ? 'active' : ''; ?>">サイト設定</a>
      </nav>
    <?php endif; ?>

    <?php if ($message !== ''): ?>
      <div class="admin-alert success"><?= h($message); ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
      <div class="admin-alert error"><?= h($error); ?></div>
    <?php endforeach; ?>

    <?php if (!is_admin_logged_in()): ?>
      <section class="admin-panel">
        <h2>ログイン</h2>
        <form class="admin-form" method="POST">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']); ?>">
          <input type="hidden" name="action" value="login">
          <label>
            ユーザー名
            <input type="text" name="user" autocomplete="username" required>
          </label>
          <label>
            パスワード
            <input type="password" name="password" autocomplete="current-password" required>
          </label>
          <button type="submit">ログイン</button>
        </form>
      </section>
    <?php elseif ($section === 'news'): ?>
      <section class="admin-panel">
        <h2><?= $isEditing ? '投稿編集' : '新規投稿'; ?></h2>
        <form class="admin-form" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']); ?>">
          <input type="hidden" name="action" value="<?= $isEditing ? 'update' : 'create'; ?>">
          <?php if ($isEditing): ?>
            <input type="hidden" name="id" value="<?= h((string)($editingItem['id'] ?? '')); ?>">
          <?php endif; ?>

          <label>
            日付
            <input type="date" name="date" value="<?= h($formDate); ?>" required>
            <span class="admin-help">表示は自動で <?= h(str_replace('-', '/', $formDate)); ?> の形式になります。</span>
          </label>

          <label>
            タイトル
            <input type="text" name="title" value="<?= h($formTitle); ?>" required>
          </label>

          <label>
            本文
            <textarea name="body"><?= h($formBody); ?></textarea>
            <span class="admin-help">下書きは本文が空でも保存できます。公開する場合は本文を入力してください。</span>
          </label>

          <label>
            画像アップロード（複数選択可）
            <div class="admin-upload-row">
              <input type="file" id="newsImageInput" name="news_image[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
              <button type="button" id="newsImagePreviewButton">写真一覧に追加</button>
            </div>
            <span class="admin-help">JPG / PNG / GIF / WebP、1枚5MB以内。写真の説明文は投稿タイトルを自動で使います。</span>
          </label>

          <div class="admin-news-images" id="newsImageList">
            <p class="admin-help">登録済み・追加予定写真（上の写真がページ先頭の大きな写真になります）</p>
            <?php foreach ($formImages as $image): ?>
              <div class="admin-news-image-item">
                <img src="<?= h((string)$image['src']); ?>" alt="<?= h((string)($image['alt'] ?? $formTitle)); ?>">
                <div>
                  <p class="admin-news-image-path"><?= h((string)$image['src']); ?></p>
                  <input type="hidden" name="news_image_order[]" value="existing:<?= h((string)$image['src']); ?>">
                  <div class="admin-news-image-actions">
                    <button type="button" data-image-move="up">↑ 上へ</button>
                    <button type="button" data-image-move="down">↓ 下へ</button>
                    <button type="button" class="remove" data-image-remove>削除</button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div>
            <p class="admin-help">公開状態</p>
            <div class="admin-status-options">
              <label>
                <input type="radio" name="published" value="0" <?= !$formPublished ? 'checked' : ''; ?>>
                下書き保存
              </label>
              <label>
                <input type="radio" name="published" value="1" <?= $formPublished ? 'checked' : ''; ?>>
                公開して保存
              </label>
            </div>
          </div>

          <div class="admin-actions">
            <button type="submit"><?= $isEditing ? '更新する' : '保存する'; ?></button>
            <?php if ($isEditing): ?>
              <a href="admin.php" class="btn">新規投稿に戻る</a>
            <?php endif; ?>
          </div>
        </form>
      </section>

      <section class="admin-panel">
        <h2>投稿一覧</h2>
        <table class="admin-table">
          <thead>
            <tr>
              <th>日付</th>
              <th>タイトル</th>
              <th>状態</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?= h(news_display_date($item)); ?></td>
                <td><?= h((string)($item['title'] ?? '')); ?></td>
                <td>
                  <span class="admin-status-badge <?= !empty($item['published']) ? 'published' : 'draft'; ?>">
                    <?= !empty($item['published']) ? '公開' : '下書き'; ?>
                  </span>
                </td>
                <td>
                  <div class="admin-table-actions">
                    <a class="admin-edit-link" href="admin.php?edit=<?= h((string)($item['id'] ?? '')); ?>">編集</a>
                    <form method="POST" onsubmit="return confirm('この投稿を削除しますか？');">
                      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']); ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= h((string)($item['id'] ?? '')); ?>">
                      <button class="admin-delete-button" type="submit">削除</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    <?php elseif ($section === 'regular_members'): ?>
      <section class="admin-panel">
        <h2><?= $isRegularEditing ? '正会員情報編集' : '正会員情報 新規登録'; ?></h2>
        <form class="admin-form" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']); ?>">
          <input type="hidden" name="action" value="<?= $isRegularEditing ? 'regular_update' : 'regular_create'; ?>">
          <input type="hidden" name="existing_president_image" value="<?= h($regularFormPresidentImage); ?>">
          <?php if ($isRegularEditing): ?>
            <input type="hidden" name="id" value="<?= h((string)($editingRegularMember['id'] ?? '')); ?>">
          <?php endif; ?>

          <div class="admin-grid">
            <label>
              都道府県
              <input type="text" name="prefecture" value="<?= h($regularFormPrefecture); ?>" placeholder="例: 兵庫県" required>
            </label>
            <label>
              表示順
              <input type="number" name="sort_order" value="<?= h($regularFormSortOrder); ?>" min="1">
            </label>
          </div>

          <div class="admin-grid">
            <label>
              会社名
              <input type="text" name="company" value="<?= h($regularFormCompany); ?>" required>
            </label>
            <label>
              店舗名・屋号
              <input type="text" name="store_name" value="<?= h($regularFormStoreName); ?>">
            </label>
          </div>

          <div class="admin-grid admin-representative-grid">
            <label class="representative-role">
              代表者 役職
              <input type="text" name="president_role" value="<?= h($regularFormPresidentRole); ?>" placeholder="例: 代表取締役社長">
            </label>
            <label class="representative-last-name">
              代表者 姓
              <input type="text" name="president_last_name" value="<?= h($regularFormPresidentLastName); ?>" placeholder="例: 山田">
            </label>
            <label class="representative-first-name">
              代表者 名
              <input type="text" name="president_first_name" value="<?= h($regularFormPresidentFirstName); ?>" placeholder="例: 太郎">
            </label>
            <label class="representative-alphabet">
              代表者 アルファベット表記
              <input type="text" name="president_alphabet" value="<?= h($regularFormPresidentAlphabet); ?>" placeholder="例: TARO YAMADA">
            </label>
          </div>

          <label>
            代表者写真
            <?php if ($regularFormPresidentImage !== ''): ?>
              <img class="admin-image-preview" src="<?= h($regularFormPresidentImage); ?>" alt="<?= h($regularFormPresident !== '' ? $regularFormPresident : $regularFormCompany); ?>">
              <label class="admin-check">
                <input type="checkbox" name="remove_president_image" value="1">
                登録済み写真を削除する
              </label>
            <?php endif; ?>
            <input type="file" name="president_image" accept="image/jpeg,image/png,image/gif,image/webp">
            <span class="admin-help">JPG / PNG / GIF / WebP、5MB以内。新しい写真を選ぶと差し替わります。</span>
          </label>

          <label>
            住所
            <textarea name="address"><?= h($regularFormAddress); ?></textarea>
          </label>

          <div class="admin-grid">
            <label>
              TEL
              <input type="text" name="tel" value="<?= h($regularFormTel); ?>">
            </label>
            <label>
              FAX
              <input type="text" name="fax" value="<?= h($regularFormFax); ?>">
            </label>
          </div>

          <label>
            WebサイトURL
            <input type="url" name="url" value="<?= h($regularFormUrl); ?>" placeholder="https://example.com/">
          </label>

          <div class="admin-grid">
            <label>
              地図 上位置(%)
              <input type="number" name="map_top" value="<?= h($regularFormMapTop); ?>" min="0" max="100" step="0.1" placeholder="例: 61.6">
            </label>
            <label>
              地図 左位置(%)
              <input type="number" name="map_left" value="<?= h($regularFormMapLeft); ?>" min="0" max="100" step="0.1" placeholder="例: 63.5">
            </label>
          </div>

          <label class="admin-check">
            <input type="checkbox" name="published" value="1" <?= $regularFormPublished ? 'checked' : ''; ?>>
            公開する
          </label>

          <div class="admin-actions">
            <button type="submit"><?= $isRegularEditing ? '更新する' : '保存する'; ?></button>
            <?php if ($isRegularEditing): ?>
              <a href="admin.php?section=regular_members" class="btn">新規登録に戻る</a>
            <?php endif; ?>
          </div>
        </form>
      </section>

      <section class="admin-panel">
        <h2>正会員一覧</h2>
        <table class="admin-table">
          <thead>
            <tr>
              <th>順</th>
              <th>都道府県</th>
              <th>会社名</th>
              <th>代表者</th>
              <th>地図座標</th>
              <th>状態</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($regularMembers as $member): ?>
              <tr>
                <td><?= h((string)($member['sort_order'] ?? '')); ?></td>
                <td><?= h((string)($member['prefecture'] ?? '')); ?></td>
                <td><?= h(regular_member_display_name($member)); ?></td>
                <?php $adminRepresentativeParts = regular_member_representative_parts($member); ?>
                <td>
                  <?= h(regular_member_representative_display_name($member)); ?>
                  <?php if ($adminRepresentativeParts['alphabet'] !== ''): ?>
                    <br><small><?= h($adminRepresentativeParts['alphabet']); ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (($member['map_top'] ?? null) !== null && ($member['map_left'] ?? null) !== null): ?>
                    上 <?= h((string)$member['map_top']); ?>% / 左 <?= h((string)$member['map_left']); ?>%
                  <?php else: ?>
                    -
                  <?php endif; ?>
                </td>
                <td><?= !empty($member['published']) ? '公開' : '非公開'; ?></td>
                <td>
                  <div class="admin-table-actions">
                    <a class="admin-edit-link" href="admin.php?section=regular_members&edit=<?= h((string)($member['id'] ?? '')); ?>">編集</a>
                    <form method="POST" onsubmit="return confirm('この正会員情報を削除しますか？');">
                      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']); ?>">
                      <input type="hidden" name="action" value="regular_delete">
                      <input type="hidden" name="id" value="<?= h((string)($member['id'] ?? '')); ?>">
                      <button class="admin-delete-button" type="submit">削除</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    <?php elseif ($section === 'support_members'): ?>
      <section class="admin-panel">
        <h2><?= $isSupportEditing ? '賛助会員情報編集' : '賛助会員情報 新規登録'; ?></h2>
        <form class="admin-form" method="POST">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']); ?>">
          <input type="hidden" name="action" value="<?= $isSupportEditing ? 'support_update' : 'support_create'; ?>">
          <?php if ($isSupportEditing): ?>
            <input type="hidden" name="id" value="<?= h((string)($editingSupportMember['id'] ?? '')); ?>">
          <?php endif; ?>

          <div class="admin-grid">
            <label>
              会社名
              <input type="text" name="company" value="<?= h($supportFormCompany); ?>" required>
            </label>
            <label>
              表示順
              <input type="number" name="sort_order" value="<?= h($supportFormSortOrder); ?>" min="1">
            </label>
          </div>

          <label>
            代表者
            <input type="text" name="president" value="<?= h($supportFormPresident); ?>" placeholder="例: 代表取締役社長　山田 太郎">
          </label>

          <label>
            住所
            <textarea name="address"><?= h($supportFormAddress); ?></textarea>
          </label>

          <label>
            WebサイトURL
            <input type="url" name="url" value="<?= h($supportFormUrl); ?>" placeholder="https://example.com/">
          </label>

          <label class="admin-check">
            <input type="checkbox" name="published" value="1" <?= $supportFormPublished ? 'checked' : ''; ?>>
            公開する
          </label>

          <div class="admin-actions">
            <button type="submit"><?= $isSupportEditing ? '更新する' : '保存する'; ?></button>
            <?php if ($isSupportEditing): ?>
              <a href="admin.php?section=support_members" class="btn">新規登録に戻る</a>
            <?php endif; ?>
          </div>
        </form>
      </section>

      <section class="admin-panel">
        <h2>賛助会員一覧</h2>
        <table class="admin-table">
          <thead>
            <tr>
              <th>順</th>
              <th>会社名</th>
              <th>状態</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($supportMembersAdmin as $member): ?>
              <tr>
                <td><?= h((string)($member['sort_order'] ?? '')); ?></td>
                <td><?= h((string)($member['company'] ?? '')); ?></td>
                <td><?= !empty($member['published']) ? '公開' : '非公開'; ?></td>
                <td>
                  <div class="admin-table-actions">
                    <a class="admin-edit-link" href="admin.php?section=support_members&edit=<?= h((string)($member['id'] ?? '')); ?>">編集</a>
                    <form method="POST" onsubmit="return confirm('この賛助会員情報を削除しますか？');">
                      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']); ?>">
                      <input type="hidden" name="action" value="support_delete">
                      <input type="hidden" name="id" value="<?= h((string)($member['id'] ?? '')); ?>">
                      <button class="admin-delete-button" type="submit">削除</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    <?php elseif ($section === 'chairman_messages'): ?>
      <section class="admin-panel">
        <h2><?= $isChairmanMessageEditing ? '歴代会長の言葉 編集' : '歴代会長の言葉 新規登録'; ?></h2>
        <form class="admin-form" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']); ?>">
          <input type="hidden" name="action" value="<?= $isChairmanMessageEditing ? 'chairman_message_update' : 'chairman_message_create'; ?>">
          <input type="hidden" name="existing_image" value="<?= h($chairmanMessageFormImage); ?>">
          <?php if ($isChairmanMessageEditing): ?>
            <input type="hidden" name="id" value="<?= h((string)($editingChairmanMessage['id'] ?? '')); ?>">
          <?php endif; ?>

          <div class="admin-grid">
            <label>
              会長任期
              <input type="text" name="term" value="<?= h($chairmanMessageFormTerm); ?>" placeholder="例: 前会長 / 第5代会長 / 1998年〜2004年" required>
            </label>

            <label>
              表示順
              <input type="number" name="sort_order" value="<?= h($chairmanMessageFormSortOrder); ?>" min="1">
            </label>
          </div>

          <label>
            会社名
            <input type="text" name="company" value="<?= h($chairmanMessageFormCompany); ?>" placeholder="例: 三栄コーポレーション">
          </label>

          <div class="admin-representative-grid">
            <label class="representative-last-name">
              姓
              <input type="text" name="last_name" value="<?= h($chairmanMessageFormLastName); ?>" required>
            </label>

            <label class="representative-first-name">
              名
              <input type="text" name="first_name" value="<?= h($chairmanMessageFormFirstName); ?>" required>
            </label>
          </div>

          <label>
            顔写真
            <?php if ($chairmanMessageFormImage !== ''): ?>
              <img class="admin-image-preview" src="<?= h($chairmanMessageFormImage); ?>" alt="<?= h(trim($chairmanMessageFormLastName . ' ' . $chairmanMessageFormFirstName)); ?>">
              <label class="admin-check">
                <input type="checkbox" name="remove_image" value="1">
                登録済み写真を削除する
              </label>
            <?php endif; ?>
            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
            <span class="admin-help">JPG / PNG / GIF / WebP、5MB以内。新しい写真を選ぶと差し替わります。</span>
          </label>

          <label>
            メッセージ
            <textarea name="message" required><?= h($chairmanMessageFormMessage); ?></textarea>
          </label>

          <label class="admin-check">
            <input type="checkbox" name="published" value="1" <?= $chairmanMessageFormPublished ? 'checked' : ''; ?>>
            公開する
          </label>

          <div class="admin-actions">
            <button type="submit"><?= $isChairmanMessageEditing ? '更新する' : '保存する'; ?></button>
            <?php if ($isChairmanMessageEditing): ?>
              <a href="admin.php?section=chairman_messages" class="btn">新規登録に戻る</a>
            <?php endif; ?>
          </div>
        </form>
      </section>

      <section class="admin-panel">
        <h2>歴代会長の言葉一覧</h2>
        <table class="admin-table">
          <thead>
            <tr>
              <th>順</th>
              <th>任期</th>
              <th>氏名</th>
              <th>会社名</th>
              <th>状態</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($chairmanMessagesAdmin as $chairmanMessage): ?>
              <tr>
                <td><?= h((string)($chairmanMessage['sort_order'] ?? '')); ?></td>
                <td><?= h((string)($chairmanMessage['term'] ?? '')); ?></td>
                <td><?= h(chairman_message_name($chairmanMessage)); ?></td>
                <td><?= h((string)($chairmanMessage['company'] ?? '')); ?></td>
                <td><?= !empty($chairmanMessage['published']) ? '公開' : '非公開'; ?></td>
                <td>
                  <div class="admin-table-actions">
                    <a class="admin-edit-link" href="admin.php?section=chairman_messages&edit=<?= h((string)($chairmanMessage['id'] ?? '')); ?>">編集</a>
                    <form method="POST" onsubmit="return confirm('この歴代会長の言葉を削除しますか？');">
                      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']); ?>">
                      <input type="hidden" name="action" value="chairman_message_delete">
                      <input type="hidden" name="id" value="<?= h((string)($chairmanMessage['id'] ?? '')); ?>">
                      <button class="admin-delete-button" type="submit">削除</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    <?php elseif ($section === 'settings'): ?>
      <section class="admin-panel">
        <h2>サイト設定</h2>
        <form class="admin-form" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']); ?>">
          <input type="hidden" name="action" value="settings_update">

          <h3>ファーストビュー画像</h3>
          <label>
            画像アップロード（最大<?= FV_IMAGE_LIMIT; ?>枚）
            <div class="admin-upload-row">
              <input type="file" id="fvImageInput" name="fv_image[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
              <button type="button" id="fvImagePreviewButton">写真一覧に追加</button>
            </div>
            <span class="admin-help">JPG / PNG / GIF / WebP、1枚5MB以内。上の写真から順番にFVへ表示されます。</span>
          </label>

          <div class="admin-news-images" id="fvImageList" data-max-images="<?= FV_IMAGE_LIMIT; ?>">
            <p class="admin-help">登録済み・追加予定写真（最大<?= FV_IMAGE_LIMIT; ?>枚）</p>
            <?php foreach ($settingsFvImages as $image): ?>
              <div class="admin-news-image-item">
                <img src="<?= h((string)$image['src']); ?>" alt="<?= h((string)($image['alt'] ?? 'GKSチェーン協会のファーストビュー画像')); ?>">
                <div>
                  <p class="admin-news-image-path"><?= h((string)$image['src']); ?></p>
                  <input type="hidden" name="fv_image_order[]" value="existing:<?= h((string)$image['src']); ?>">
                  <div class="admin-news-image-actions">
                    <button type="button" data-image-move="up">↑ 上へ</button>
                    <button type="button" data-image-move="down">↓ 下へ</button>
                    <button type="button" class="remove" data-image-remove>削除</button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <hr class="admin-divider">

          <h3>サイトロゴ</h3>
          <div class="admin-grid">
            <label>
              ヘッダーロゴ
              <?php if ($settingsHeaderLogo !== ''): ?>
                <img class="admin-image-preview admin-logo-preview" src="<?= h($settingsHeaderLogo); ?>" alt="ヘッダーロゴ">
              <?php endif; ?>
              <input type="hidden" name="existing_header_logo" value="<?= h($settingsHeaderLogo); ?>">
              <input type="file" name="header_logo" accept="image/jpeg,image/png,image/gif,image/webp">
              <span class="admin-help">新しい画像を選ぶと差し替わります。</span>
            </label>
            <label>
              ヘッダーロゴ表示倍率（%）
              <input type="number" name="header_logo_scale" value="<?= h($settingsHeaderLogoScale); ?>" min="20" max="200" step="1" required>
              <span class="admin-help">100が標準サイズです。</span>
            </label>
          </div>

          <div class="admin-grid">
            <label>
              フッターロゴ
              <?php if ($settingsFooterLogo !== ''): ?>
                <img class="admin-image-preview admin-logo-preview" src="<?= h($settingsFooterLogo); ?>" alt="フッターロゴ">
              <?php endif; ?>
              <input type="hidden" name="existing_footer_logo" value="<?= h($settingsFooterLogo); ?>">
              <input type="file" name="footer_logo" accept="image/jpeg,image/png,image/gif,image/webp">
              <span class="admin-help">新しい画像を選ぶと差し替わります。</span>
            </label>
            <label>
              フッターロゴ表示倍率（%）
              <input type="number" name="footer_logo_scale" value="<?= h($settingsFooterLogoScale); ?>" min="20" max="200" step="1" required>
              <span class="admin-help">100が標準サイズです。</span>
            </label>
          </div>

          <hr class="admin-divider">

          <div class="admin-grid">
            <label>
              地図ピンまとめ距離（PC）
              <input type="number" name="map_group_distance" value="<?= h($settingsMapGroupDistance); ?>" min="0" max="20" step="0.1" required>
              <span class="admin-help">数値が大きいほど、近くの会員企業が1つのピンにまとまります。</span>
            </label>
            <label>
              地図ピンまとめ距離（スマホ）
              <input type="number" name="map_group_distance_mobile" value="<?= h($settingsMapGroupDistanceMobile); ?>" min="0" max="20" step="0.1" required>
              <span class="admin-help">スマホでは少し大きめにすると、ピンが密集しにくくなります。</span>
            </label>
          </div>

          <span class="admin-help">0にすると同じ座標だけがまとまります。</span>

          <div class="admin-grid">
            <label>
              座標の丸の大きさ（PC）
              <input type="number" name="map_dot_size" value="<?= h($settingsMapDotSize); ?>" min="1" max="40" step="0.5" required>
              <span class="admin-help">単独ピンの紺色の丸の直径です。</span>
            </label>
            <label>
              座標の丸の大きさ（スマホ）
              <input type="number" name="map_dot_size_mobile" value="<?= h($settingsMapDotSizeMobile); ?>" min="1" max="40" step="0.5" required>
              <span class="admin-help">スマホの単独ピンの丸です。</span>
            </label>
          </div>

          <div class="admin-grid">
            <label>
              まとめ丸の大きさ（PC）
              <input type="number" name="map_dot_multi_size" value="<?= h($settingsMapDotMultiSize); ?>" min="1" max="40" step="0.5" required>
              <span class="admin-help">数字が入るまとめピンの丸です。</span>
            </label>
            <label>
              まとめ丸の大きさ（スマホ）
              <input type="number" name="map_dot_multi_size_mobile" value="<?= h($settingsMapDotMultiSizeMobile); ?>" min="1" max="40" step="0.5" required>
              <span class="admin-help">スマホのまとめピンの丸です。</span>
            </label>
          </div>

          <div class="admin-grid">
            <label>
              座標の広がり（PC）
              <input type="number" name="map_dot_spread" value="<?= h($settingsMapDotSpread); ?>" min="0" max="40" step="0.5" required>
              <span class="admin-help">単独ピンのホワンホワンの半径です。</span>
            </label>
            <label>
              座標の広がり（スマホ）
              <input type="number" name="map_dot_spread_mobile" value="<?= h($settingsMapDotSpreadMobile); ?>" min="0" max="40" step="0.5" required>
              <span class="admin-help">スマホの単独ピンの広がりです。</span>
            </label>
          </div>

          <div class="admin-grid">
            <label>
              まとめ丸の広がり（PC）
              <input type="number" name="map_dot_multi_spread" value="<?= h($settingsMapDotMultiSpread); ?>" min="0" max="40" step="0.5" required>
              <span class="admin-help">まとめピンのホワンホワンの半径です。</span>
            </label>
            <label>
              まとめ丸の広がり（スマホ）
              <input type="number" name="map_dot_multi_spread_mobile" value="<?= h($settingsMapDotMultiSpreadMobile); ?>" min="0" max="40" step="0.5" required>
              <span class="admin-help">スマホのまとめピンの広がりです。</span>
            </label>
          </div>

          <div class="admin-actions">
            <button type="submit">保存する</button>
          </div>
        </form>
      </section>
    <?php endif; ?>
  </main>
  <script>
    const imageInput = document.getElementById('newsImageInput');
    const previewButton = document.getElementById('newsImagePreviewButton');
    const imageList = document.getElementById('newsImageList');
    const fvImageInput = document.getElementById('fvImageInput');
    const fvPreviewButton = document.getElementById('fvImagePreviewButton');
    const fvImageList = document.getElementById('fvImageList');
    let queuedUploadFiles = [];
    let stagedUploadFiles = [];
    let fvQueuedUploadFiles = [];
    let fvStagedUploadFiles = [];

    function syncUploadInputFiles() {
      if (!imageInput || typeof DataTransfer === 'undefined') {
        return;
      }

      const transfer = new DataTransfer();

      queuedUploadFiles.forEach(function (file) {
        transfer.items.add(file);
      });

      imageInput.files = transfer.files;
    }

    function createNewsImageItem(imageSrc, imageAlt, orderValue) {
      const item = document.createElement('div');
      const image = document.createElement('img');
      const body = document.createElement('div');
      const path = document.createElement('p');
      const input = document.createElement('input');
      const actions = document.createElement('div');
      const upButton = document.createElement('button');
      const downButton = document.createElement('button');
      const removeButton = document.createElement('button');

      item.className = 'admin-news-image-item';
      image.src = imageSrc;
      image.alt = imageAlt;
      path.className = 'admin-news-image-path';
      path.textContent = imageAlt;
      input.type = 'hidden';
      input.name = 'news_image_order[]';
      input.value = orderValue;
      actions.className = 'admin-news-image-actions';

      upButton.type = 'button';
      upButton.setAttribute('data-image-move', 'up');
      upButton.textContent = '↑ 上へ';

      downButton.type = 'button';
      downButton.setAttribute('data-image-move', 'down');
      downButton.textContent = '↓ 下へ';

      removeButton.type = 'button';
      removeButton.className = 'remove';
      removeButton.setAttribute('data-image-remove', '');
      removeButton.textContent = '削除';

      actions.appendChild(upButton);
      actions.appendChild(downButton);
      actions.appendChild(removeButton);
      body.appendChild(path);
      body.appendChild(input);
      body.appendChild(actions);
      item.appendChild(image);
      item.appendChild(body);

      return item;
    }

    function syncFvUploadInputFiles() {
      if (!fvImageInput || typeof DataTransfer === 'undefined') {
        return;
      }

      const transfer = new DataTransfer();

      fvQueuedUploadFiles.forEach(function (file) {
        transfer.items.add(file);
      });

      fvImageInput.files = transfer.files;
    }

    function createFvImageItem(imageSrc, imageAlt, orderValue) {
      const item = createNewsImageItem(imageSrc, imageAlt, orderValue);
      const input = item.querySelector('input[name="news_image_order[]"]');

      if (input) {
        input.name = 'fv_image_order[]';
      }

      return item;
    }

    function countFvImages() {
      return fvImageList ? fvImageList.querySelectorAll('.admin-news-image-item').length : 0;
    }

    if (imageInput) {
      imageInput.addEventListener('change', function () {
        stagedUploadFiles = Array.from(imageInput.files || []);
      });
    }

    if (previewButton && imageInput && imageList) {
      previewButton.addEventListener('click', function () {
        stagedUploadFiles.forEach(function (file) {
          const uploadIndex = queuedUploadFiles.length;
          const imageUrl = URL.createObjectURL(file);
          const item = createNewsImageItem(imageUrl, file.name, 'upload:' + uploadIndex);

          item.dataset.uploadIndex = String(uploadIndex);
          item.querySelector('img').addEventListener('load', function (event) {
            URL.revokeObjectURL(event.currentTarget.src);
          }, { once: true });

          queuedUploadFiles.push(file);
          imageList.appendChild(item);
        });

        stagedUploadFiles = [];
        syncUploadInputFiles();
      });
    }

    if (fvImageInput) {
      fvImageInput.addEventListener('change', function () {
        fvStagedUploadFiles = Array.from(fvImageInput.files || []);
      });
    }

    if (fvPreviewButton && fvImageInput && fvImageList) {
      fvPreviewButton.addEventListener('click', function () {
        const maxImages = Number(fvImageList.dataset.maxImages || '5');
        const remaining = maxImages - countFvImages();

        if (remaining <= 0) {
          alert('FV画像は最大' + maxImages + '枚までです。');
          return;
        }

        fvStagedUploadFiles.slice(0, remaining).forEach(function (file) {
          const uploadIndex = fvQueuedUploadFiles.length;
          const imageUrl = URL.createObjectURL(file);
          const item = createFvImageItem(imageUrl, file.name, 'upload:' + uploadIndex);

          item.dataset.fvUploadIndex = String(uploadIndex);
          item.querySelector('img').addEventListener('load', function (event) {
            URL.revokeObjectURL(event.currentTarget.src);
          }, { once: true });

          fvQueuedUploadFiles.push(file);
          fvImageList.appendChild(item);
        });

        if (fvStagedUploadFiles.length > remaining) {
          alert('FV画像は最大' + maxImages + '枚までです。超えた分は追加していません。');
        }

        fvStagedUploadFiles = [];
        syncFvUploadInputFiles();
      });
    }

    if (imageInput) {
      const imageForm = imageInput.closest('form');

      if (imageForm) {
        imageForm.addEventListener('submit', function () {
          if (stagedUploadFiles.length > 0) {
            stagedUploadFiles.forEach(function (file) {
              queuedUploadFiles.push(file);
            });
            stagedUploadFiles = [];
          }

          syncUploadInputFiles();
        });
      }
    }

    if (fvImageInput) {
      const fvImageForm = fvImageInput.closest('form');

      if (fvImageForm) {
        fvImageForm.addEventListener('submit', function () {
          syncFvUploadInputFiles();
        });
      }
    }

    document.addEventListener('click', function (event) {
      const moveButton = event.target.closest('[data-image-move]');
      const removeButton = event.target.closest('[data-image-remove]');

      if (moveButton) {
        const item = moveButton.closest('.admin-news-image-item');
        const direction = moveButton.getAttribute('data-image-move');

        if (!item) {
          return;
        }

        if (direction === 'up' && item.previousElementSibling && item.previousElementSibling.classList.contains('admin-news-image-item')) {
          item.parentNode.insertBefore(item, item.previousElementSibling);
        }

        if (direction === 'down' && item.nextElementSibling) {
          item.parentNode.insertBefore(item.nextElementSibling, item);
        }
      }

      if (removeButton) {
        const item = removeButton.closest('.admin-news-image-item');

        if (item && confirm('この写真を登録から外しますか？')) {
          if (item.dataset.fvUploadIndex !== undefined) {
            const uploadIndex = Number(item.dataset.fvUploadIndex);
            fvQueuedUploadFiles[uploadIndex] = null;
            fvQueuedUploadFiles = fvQueuedUploadFiles.filter(Boolean);
            item.remove();

            document.querySelectorAll('.admin-news-image-item[data-fv-upload-index]').forEach(function (uploadItem, index) {
              uploadItem.dataset.fvUploadIndex = String(index);
              const input = uploadItem.querySelector('input[name="fv_image_order[]"]');

              if (input) {
                input.value = 'upload:' + index;
              }
            });

            syncFvUploadInputFiles();
            return;
          }

          if (item.dataset.uploadIndex !== undefined) {
            const uploadIndex = Number(item.dataset.uploadIndex);
            queuedUploadFiles[uploadIndex] = null;
            queuedUploadFiles = queuedUploadFiles.filter(Boolean);
            item.remove();

            document.querySelectorAll('.admin-news-image-item[data-upload-index]').forEach(function (uploadItem, index) {
              uploadItem.dataset.uploadIndex = String(index);
              const input = uploadItem.querySelector('input[name="news_image_order[]"]');

              if (input) {
                input.value = 'upload:' + index;
              }
            });

            syncUploadInputFiles();
            return;
          }

          item.remove();
        }
      }
    });
  </script>
</body>

</html>
