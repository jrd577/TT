<?php
// tiktok.php - upload this to your website's server (needs PHP with cURL).
header('Content-Type: application/json; charset=utf-8');

$username = ltrim(trim($_GET['username'] ?? ''), '@');

if (!preg_match('/^[A-Za-z0-9._]{1,24}$/', $username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid username.']);
    exit;
}

$ch = curl_init("https://www.tiktok.com/@" . $username);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_ENCODING       => '',
    CURLOPT_HTTPHEADER     => [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
        'Accept-Language: en-US,en;q=0.9',
    ],
]);
$html = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code === 404) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found.']);
    exit;
}

if (!$html || !preg_match('#<script id="__UNIVERSAL_DATA_FOR_REHYDRATION__"[^>]*>(.*?)</script>#s', $html, $m)) {
    http_response_code(502);
    echo json_encode(['error' => 'Could not read TikTok data. Try again later.']);
    exit;
}

$data = json_decode($m[1], true);
$info = $data['__DEFAULT_SCOPE__']['webapp.user-detail']['userInfo'] ?? null;

if (!$info) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found.']);
    exit;
}

echo json_encode([
    'username'  => $info['user']['uniqueId'] ?? $username,
    'nickname'  => $info['user']['nickname'] ?? '',
    'avatar'    => $info['user']['avatarLarger'] ?? ($info['user']['avatarMedium'] ?? ''),
    'followers' => $info['stats']['followerCount'] ?? 0,
    'following' => $info['stats']['followingCount'] ?? 0,
]);
