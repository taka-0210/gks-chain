<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Tokyo');

const ADMIN_USER = 'admin_gks';
const ADMIN_PASSWORD_HASH = '$2y$10$RellX.I9yZkd.9BVlum4B.g6Ps7FP2AgZvnThA5JR/guYbLDX2qcK';
const NEWS_DATA_FILE = __DIR__ . '/data/news.json';
const REGULAR_MEMBERS_DATA_FILE = __DIR__ . '/data/regular-members.json';
const SUPPORT_MEMBERS_DATA_FILE = __DIR__ . '/data/support-members.json';
const SETTINGS_DATA_FILE = __DIR__ . '/data/settings.json';
const MAP_GROUP_DISTANCE = 2.5;
const MAP_GROUP_DISTANCE_MOBILE = 4.0;
const MAP_DOT_SIZE = 12.0;
const MAP_DOT_SIZE_MOBILE = 12.0;
const MAP_DOT_MULTI_SIZE = 16.0;
const MAP_DOT_MULTI_SIZE_MOBILE = 16.0;
const MAP_DOT_SPREAD = 5.0;
const MAP_DOT_SPREAD_MOBILE = 5.0;
const MAP_DOT_MULTI_SPREAD = 5.0;
const MAP_DOT_MULTI_SPREAD_MOBILE = 5.0;
const FV_IMAGE_LIMIT = 5;

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function load_news(bool $publishedOnly = true): array
{
    if (!is_file(NEWS_DATA_FILE)) {
        return [];
    }

    $json = file_get_contents(NEWS_DATA_FILE);
    $items = json_decode($json ?: '[]', true);

    if (!is_array($items)) {
        return [];
    }

    if ($publishedOnly) {
        $items = array_values(array_filter($items, function ($item) {
            return !empty($item['published']);
        }));
    }

    usort($items, function ($a, $b) {
        return strcmp((string)($b['date'] ?? ''), (string)($a['date'] ?? ''));
    });

    return $items;
}

function save_news(array $items): bool
{
    usort($items, function ($a, $b) {
        return strcmp((string)($b['date'] ?? ''), (string)($a['date'] ?? ''));
    });

    $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return file_put_contents(NEWS_DATA_FILE, $json . PHP_EOL, LOCK_EX) !== false;
}

function news_display_date(array $item): string
{
    $date = (string)($item['date'] ?? '');
    return $date !== '' ? str_replace('-', '/', $date) : '';
}

function news_year(array $item): string
{
    $date = (string)($item['date'] ?? '');
    return preg_match('/^\d{4}/', $date, $matches) ? $matches[0] : 'その他';
}

function group_news_by_year(array $items): array
{
    $groups = [];

    foreach ($items as $item) {
        $year = news_year($item);
        $groups[$year][] = $item;
    }

    krsort($groups, SORT_STRING);

    return $groups;
}

function news_images(array $item): array
{
    $images = [];
    $defaultAlt = (string)($item['image_alt'] ?? $item['title'] ?? '');

    if (!empty($item['images']) && is_array($item['images'])) {
        foreach ($item['images'] as $image) {
            if (is_array($image)) {
                $src = trim((string)($image['src'] ?? ''));
                $alt = trim((string)($image['alt'] ?? $defaultAlt));
            } else {
                $src = trim((string)$image);
                $alt = $defaultAlt;
            }

            if ($src !== '') {
                $images[] = [
                    'src' => $src,
                    'alt' => $alt !== '' ? $alt : $defaultAlt,
                ];
            }
        }
    }

    if (!$images && !empty($item['image'])) {
        $images[] = [
            'src' => (string)$item['image'],
            'alt' => $defaultAlt,
        ];
    }

    return $images;
}

function news_main_image(array $item): ?array
{
    $images = news_images($item);
    return $images[0] ?? null;
}

function find_news_by_id(string $id, bool $publishedOnly = true): ?array
{
    foreach (load_news($publishedOnly) as $item) {
        if ((string)($item['id'] ?? '') === $id) {
            return $item;
        }
    }

    return null;
}

function news_excerpt(string $body, int $length = 80): string
{
    $body = trim(strip_tags($body));

    if ($body === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($body, 'UTF-8') > $length
            ? mb_substr($body, 0, $length, 'UTF-8') . '...'
            : $body;
    }

    return strlen($body) > $length ? substr($body, 0, $length) . '...' : $body;
}

function default_site_settings(): array
{
    return [
        'fv_images' => [
            ['src' => 'image/fv/fv01.jpg', 'alt' => 'GKSチェーン協会の活動イメージ'],
            ['src' => 'image/fv/fv02.jpg', 'alt' => '全国の厨房設備会社が集まる様子'],
            ['src' => 'image/fv/fv03.jpg', 'alt' => '研修会や情報交換の様子'],
        ],
        'header_logo' => 'image/logo/logo.png',
        'header_logo_scale' => 100,
        'footer_logo' => 'image/logo/logo-footer.png',
        'footer_logo_scale' => 100,
        'map_group_distance' => MAP_GROUP_DISTANCE,
        'map_group_distance_mobile' => MAP_GROUP_DISTANCE_MOBILE,
        'map_dot_size' => MAP_DOT_SIZE,
        'map_dot_size_mobile' => MAP_DOT_SIZE_MOBILE,
        'map_dot_multi_size' => MAP_DOT_MULTI_SIZE,
        'map_dot_multi_size_mobile' => MAP_DOT_MULTI_SIZE_MOBILE,
        'map_dot_spread' => MAP_DOT_SPREAD,
        'map_dot_spread_mobile' => MAP_DOT_SPREAD_MOBILE,
        'map_dot_multi_spread' => MAP_DOT_MULTI_SPREAD,
        'map_dot_multi_spread_mobile' => MAP_DOT_MULTI_SPREAD_MOBILE,
    ];
}

function load_site_settings(): array
{
    $settings = default_site_settings();

    if (!is_file(SETTINGS_DATA_FILE)) {
        return $settings;
    }

    $json = file_get_contents(SETTINGS_DATA_FILE);
    $saved = json_decode($json ?: '[]', true);

    if (!is_array($saved)) {
        return $settings;
    }

    return array_merge($settings, $saved);
}

function save_site_settings(array $settings): bool
{
    $current = load_site_settings();
    $settings = array_merge($current, $settings);
    $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return file_put_contents(SETTINGS_DATA_FILE, $json . PHP_EOL, LOCK_EX) !== false;
}

function site_fv_images(): array
{
    $settings = load_site_settings();
    $images = [];

    foreach (($settings['fv_images'] ?? []) as $image) {
        if (is_array($image)) {
            $src = trim((string)($image['src'] ?? ''));
            $alt = trim((string)($image['alt'] ?? ''));
        } else {
            $src = trim((string)$image);
            $alt = '';
        }

        if ($src !== '') {
            $images[] = [
                'src' => $src,
                'alt' => $alt !== '' ? $alt : 'GKSチェーン協会のファーストビュー画像',
            ];
        }

        if (count($images) >= FV_IMAGE_LIMIT) {
            break;
        }
    }

    if (!$images) {
        $images = default_site_settings()['fv_images'];
    }

    return $images;
}

function site_header_logo(): array
{
    $settings = load_site_settings();
    $src = trim((string)($settings['header_logo'] ?? 'image/logo/logo.png'));
    $scale = (float)($settings['header_logo_scale'] ?? 100);

    return [
        'src' => $src !== '' ? $src : 'image/logo/logo.png',
        'scale' => $scale > 0 ? $scale : 100,
    ];
}

function site_footer_logo(): array
{
    $settings = load_site_settings();
    $src = trim((string)($settings['footer_logo'] ?? 'image/logo/logo-footer.png'));
    $scale = (float)($settings['footer_logo_scale'] ?? 100);

    return [
        'src' => $src !== '' ? $src : 'image/logo/logo-footer.png',
        'scale' => $scale > 0 ? $scale : 100,
    ];
}

function get_map_group_distance(): float
{
    $settings = load_site_settings();
    $distance = (float)($settings['map_group_distance'] ?? MAP_GROUP_DISTANCE);

    return $distance >= 0 ? $distance : MAP_GROUP_DISTANCE;
}

function get_map_group_distance_mobile(): float
{
    $settings = load_site_settings();
    $distance = (float)($settings['map_group_distance_mobile'] ?? MAP_GROUP_DISTANCE_MOBILE);

    return $distance >= 0 ? $distance : MAP_GROUP_DISTANCE_MOBILE;
}

function get_map_dot_size(): float
{
    $settings = load_site_settings();
    $size = (float)($settings['map_dot_size'] ?? MAP_DOT_SIZE);

    return $size > 0 ? $size : MAP_DOT_SIZE;
}

function get_map_dot_size_mobile(): float
{
    $settings = load_site_settings();
    $size = (float)($settings['map_dot_size_mobile'] ?? MAP_DOT_SIZE_MOBILE);

    return $size > 0 ? $size : MAP_DOT_SIZE_MOBILE;
}

function get_map_dot_multi_size(): float
{
    $settings = load_site_settings();
    $size = (float)($settings['map_dot_multi_size'] ?? MAP_DOT_MULTI_SIZE);

    return $size > 0 ? $size : MAP_DOT_MULTI_SIZE;
}

function get_map_dot_multi_size_mobile(): float
{
    $settings = load_site_settings();
    $size = (float)($settings['map_dot_multi_size_mobile'] ?? MAP_DOT_MULTI_SIZE_MOBILE);

    return $size > 0 ? $size : MAP_DOT_MULTI_SIZE_MOBILE;
}

function get_map_dot_spread(): float
{
    $settings = load_site_settings();
    $spread = (float)($settings['map_dot_spread'] ?? MAP_DOT_SPREAD);

    return $spread >= 0 ? $spread : MAP_DOT_SPREAD;
}

function get_map_dot_spread_mobile(): float
{
    $settings = load_site_settings();
    $spread = (float)($settings['map_dot_spread_mobile'] ?? MAP_DOT_SPREAD_MOBILE);

    return $spread >= 0 ? $spread : MAP_DOT_SPREAD_MOBILE;
}

function get_map_dot_multi_spread(): float
{
    $settings = load_site_settings();
    $spread = (float)($settings['map_dot_multi_spread'] ?? MAP_DOT_MULTI_SPREAD);

    return $spread >= 0 ? $spread : MAP_DOT_MULTI_SPREAD;
}

function get_map_dot_multi_spread_mobile(): float
{
    $settings = load_site_settings();
    $spread = (float)($settings['map_dot_multi_spread_mobile'] ?? MAP_DOT_MULTI_SPREAD_MOBILE);

    return $spread >= 0 ? $spread : MAP_DOT_MULTI_SPREAD_MOBILE;
}

function load_regular_members(bool $publishedOnly = true): array
{
    if (!is_file(REGULAR_MEMBERS_DATA_FILE)) {
        return [];
    }

    $json = file_get_contents(REGULAR_MEMBERS_DATA_FILE);
    $items = json_decode($json ?: '[]', true);

    if (!is_array($items)) {
        return [];
    }

    if ($publishedOnly) {
        $items = array_values(array_filter($items, function ($item) {
            return !empty($item['published']);
        }));
    }

    usort($items, function ($a, $b) {
        return ((int)($a['sort_order'] ?? 9999)) <=> ((int)($b['sort_order'] ?? 9999));
    });

    return $items;
}

function save_regular_members(array $items): bool
{
    usort($items, function ($a, $b) {
        return ((int)($a['sort_order'] ?? 9999)) <=> ((int)($b['sort_order'] ?? 9999));
    });

    $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return file_put_contents(REGULAR_MEMBERS_DATA_FILE, $json . PHP_EOL, LOCK_EX) !== false;
}

function regular_member_parts(array $member): array
{
    $company = trim((string)($member['company'] ?? ''));
    $storeName = trim((string)($member['store_name'] ?? ''));

    if ($storeName === '' && preg_match('/^(.*?)\s*[（(]([^（）()]+)[）)]\s*$/u', $company, $matches)) {
        $company = trim($matches[1]);
        $storeName = trim($matches[2]);
    }

    return [
        'company' => $company,
        'store_name' => $storeName,
    ];
}

function regular_member_display_name(array $member): string
{
    $parts = regular_member_parts($member);

    if ($parts['store_name'] === '') {
        return $parts['company'];
    }

    return $parts['company'] . '（' . $parts['store_name'] . '）';
}

function representative_parts(string $representative): array
{
    $tokens = preg_split('/\s+/u', trim($representative));

    if (!is_array($tokens) || count($tokens) < 2) {
        return [
            'role' => trim($representative),
            'name' => '',
        ];
    }

    if (count($tokens) === 2) {
        return [
            'role' => $tokens[0],
            'name' => $tokens[1],
        ];
    }

    return [
        'role' => implode(' ', array_slice($tokens, 0, -2)),
        'name' => implode(' ', array_slice($tokens, -2)),
    ];
}

function regular_member_representative_parts(array $member): array
{
    $role = trim((string)($member['president_role'] ?? ''));
    $lastName = trim((string)($member['president_last_name'] ?? ''));
    $firstName = trim((string)($member['president_first_name'] ?? ''));
    $alphabet = trim((string)($member['president_alphabet'] ?? ''));

    if ($role === '' && $lastName === '' && $firstName === '') {
        $parts = representative_parts((string)($member['president'] ?? ''));
        $role = $parts['role'];
        [$lastName, $firstName] = array_pad(preg_split('/\s+/u', $parts['name']) ?: [], 2, '');
    }

    return [
        'role' => $role,
        'last_name' => $lastName,
        'first_name' => $firstName,
        'name' => trim($lastName . ' ' . $firstName),
        'alphabet' => $alphabet,
    ];
}

function regular_member_representative_display_name(array $member): string
{
    $parts = regular_member_representative_parts($member);

    return trim($parts['role'] . ' ' . $parts['name']);
}

function group_regular_members_by_prefecture(array $items): array
{
    $groups = [];

    foreach ($items as $item) {
        $prefecture = (string)($item['prefecture'] ?? 'その他');

        if ($prefecture === '') {
            $prefecture = 'その他';
        }

        $groups[$prefecture][] = $item;
    }

    return $groups;
}

function group_regular_members_by_map_position(array $items, float $distance = MAP_GROUP_DISTANCE): array
{
    $groups = [];

    foreach ($items as $item) {
        if (($item['map_top'] ?? null) === null || ($item['map_left'] ?? null) === null) {
            continue;
        }

        $top = (float)$item['map_top'];
        $left = (float)$item['map_left'];
        $targetIndex = null;

        foreach ($groups as $index => $group) {
            $topDiff = $top - (float)$group['top'];
            $leftDiff = $left - (float)$group['left'];
            $pointDistance = sqrt(($topDiff * $topDiff) + ($leftDiff * $leftDiff));

            if ($pointDistance <= $distance) {
                $targetIndex = $index;
                break;
            }
        }

        if ($targetIndex === null) {
            $groups[] = [
                'top' => $top,
                'left' => $left,
                'members' => [],
                'prefectures' => [],
            ];

            $targetIndex = array_key_last($groups);
        }

        $groups[$targetIndex]['members'][] = $item;

        $prefecture = (string)($item['prefecture'] ?? '');
        if ($prefecture !== '' && !in_array($prefecture, $groups[$targetIndex]['prefectures'], true)) {
            $groups[$targetIndex]['prefectures'][] = $prefecture;
        }
    }

    return $groups;
}

function load_support_members(bool $publishedOnly = true): array
{
    if (!is_file(SUPPORT_MEMBERS_DATA_FILE)) {
        return [];
    }

    $json = file_get_contents(SUPPORT_MEMBERS_DATA_FILE);
    $items = json_decode($json ?: '[]', true);

    if (!is_array($items)) {
        return [];
    }

    if ($publishedOnly) {
        $items = array_values(array_filter($items, function ($item) {
            return !empty($item['published']);
        }));
    }

    usort($items, function ($a, $b) {
        return ((int)($a['sort_order'] ?? 9999)) <=> ((int)($b['sort_order'] ?? 9999));
    });

    return $items;
}

function save_support_members(array $items): bool
{
    usort($items, function ($a, $b) {
        return ((int)($a['sort_order'] ?? 9999)) <=> ((int)($b['sort_order'] ?? 9999));
    });

    $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return file_put_contents(SUPPORT_MEMBERS_DATA_FILE, $json . PHP_EOL, LOCK_EX) !== false;
}
