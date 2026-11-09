<?php

// Run by `composer run setup` before Artisan starts. No account prompts are needed.
require __DIR__.'/vendor/autoload.php';

try {
    $envPath = __DIR__.'/.env';
    if (! file_exists($envPath) && ! copy(__DIR__.'/.env.example', $envPath)) {
        throw new RuntimeException('Unable to create backend/.env.');
    }
    $contents = file_get_contents($envPath);
    if ($contents === false) {
        throw new RuntimeException('Unable to read backend/.env.');
    }
    $values = Dotenv\Dotenv::parse($contents);
    $environment = getenv('APP_ENV') ?: ($values['APP_ENV'] ?? 'production');
    if ($environment !== 'local') {
        throw new RuntimeException('This demo setup requires APP_ENV=local. Existing configuration was preserved.');
    }
    if (empty($values['APP_KEY'])) {
        $keyLine = 'APP_KEY=base64:'.base64_encode(random_bytes(32));
        $contents = preg_match('/^APP_KEY=/m', $contents)
            ? preg_replace('/^APP_KEY=.*$/m', $keyLine, $contents)
            : $contents.PHP_EOL.$keyLine.PHP_EOL;
        if (file_put_contents($envPath, $contents) === false) {
            throw new RuntimeException('Unable to save the application key.');
        }
    }
    // Only prepare the bundled default database; retain custom database settings.
    if (($values['DB_CONNECTION'] ?? 'sqlite') === 'sqlite' && empty($values['DB_DATABASE'])) {
        $database = __DIR__.'/database/database.sqlite';
        if (! file_exists($database) && ! touch($database)) {
            throw new RuntimeException('Unable to create the local SQLite database.');
        }
    }
    foreach (['bootstrap/cache', 'storage/logs', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views'] as $directory) {
        $path = __DIR__.'/'.$directory;
        if (! is_dir($path) && ! mkdir($path, 0775, true) && ! is_dir($path)) {
            throw new RuntimeException('Unable to create '.$directory.'.');
        }
    }
    echo "Local configuration ready. Existing application keys and database records are preserved.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}
