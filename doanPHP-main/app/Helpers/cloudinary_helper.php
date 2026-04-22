<?php

if (!function_exists('cloudinary_avatar')) {
    function cloudinary_avatar(string $cloudName, ?string $publicId, array $options = []): string
    {
        if ($cloudName === '') return '/public/img/default_avatar.jpg';

        $w      = $options['w'] ?? 120;
        $h      = $options['h'] ?? 120;
        $crop   = $options['crop'] ?? 'c_fill';
        $q      = $options['q'] ?? 'q_auto';
        $fmt    = $options['fmt'] ?? 'f_auto';
        $version = $options['version'] ?? null;

        $defaultPublicId = $options['default_public_id']
            ?? (env('CLOUDINARY_DEFAULT_AVATAR_PUBLIC_ID', 'qe9evl3wbbvjs8ekq21u'));

        $finalPublicId = $publicId ?: $defaultPublicId;
        $encoded = rawurlencode($finalPublicId);
        $verSeg = $version ? '/v' . (int)$version : '';

        return "https://res.cloudinary.com/{$cloudName}/image/upload/{$crop},w_{$w},h_{$h},{$q},{$fmt}{$verSeg}/{$encoded}.jpg";
    }
}

if (!function_exists('cloudinary_cover')) {
    function cloudinary_cover(string $cloudName, ?string $publicId, array $options = []): string
    {
        if ($cloudName === '') return '/public/img/default_cover.jpg';

        $w      = $options['w'] ?? 1200;
        $h      = $options['h'] ?? 380;
        $crop   = $options['crop'] ?? 'c_fill';
        $q      = $options['q'] ?? 'q_auto';
        $fmt    = $options['fmt'] ?? 'f_auto';
        $version = $options['version'] ?? null;

        $defaultPublicId = $options['default_public_id']
            ?? (env('CLOUDINARY_DEFAULT_COVER_PUBLIC_ID', 'iirjbjtmdsjmlmmdazjr'));

        if (!$version) {
            $version = env('CLOUDINARY_DEFAULT_COVER_VERSION', null);
        }

        $finalPublicId = $publicId ?: $defaultPublicId;
        $encoded = rawurlencode($finalPublicId);
        $verSeg = $version ? '/v' . (int)$version : '';

        return "https://res.cloudinary.com/{$cloudName}/image/upload/{$crop},w_{$w},h_{$h},{$q},{$fmt}{$verSeg}/{$encoded}.jpg";
    }
}
