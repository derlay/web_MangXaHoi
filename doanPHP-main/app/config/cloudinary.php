<?php
// app/config/cloudinary.php

require_once dirname(__DIR__, 2) . '/app/config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env'); // nạp .env 

$cloud_name  = getenv('CLOUDINARY_CLOUD_NAME');
$api_key     = getenv('CLOUDINARY_API_KEY');
$api_secret  = getenv('CLOUDINARY_API_SECRET');
$unsigned_preset = getenv('CLOUDINARY_UNSIGNED_PRESET');

// Có thể kiểm tra nếu thiếu:
if (!$cloud_name) {
    error_log('Cloudinary config missing CLOUDINARY_CLOUD_NAME');
}

return [
    'cloud_name' => $cloud_name,
    'api_key' => $api_key,
    'api_secret' => $api_secret,
    'unsigned_preset' => $unsigned_preset,
    'default_avatar_public_id'   => env('CLOUDINARY_DEFAULT_AVATAR_PUBLIC_ID', 'qe9evl3wbbvjs8ekq21u'),
    'default_cover_public_id'    => env('CLOUDINARY_DEFAULT_COVER_PUBLIC_ID', 'iirjbjtmdsjmlmmdazjr'),
    'default_cover_version'      => env('CLOUDINARY_DEFAULT_COVER_VERSION', null),
];
