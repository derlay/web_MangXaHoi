<?php
if (!function_exists('loadEnv')) {
    function loadEnv(string $filepath, bool $override = false): void
    {
        if (!is_file($filepath)) return;
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') continue;
            if (stripos($line, 'export ') === 0) $line = trim(substr($line, 7));
            if (($pos = strpos($line, ' #')) !== false) $line = substr($line, 0, $pos);
            $eq = strpos($line, '=');
            if ($eq === false) continue;
            $name  = trim(substr($line, 0, $eq));
            $value = trim(substr($line, $eq + 1));
            if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }
            if (!$override && (array_key_exists($name, $_ENV) || getenv($name) !== false)) continue;
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return ($v === false || $v === null || $v === '') ? $default : $v;
    }
}
