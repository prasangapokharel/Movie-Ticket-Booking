<?php
// helper/cachegenerate.php

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

require_once __DIR__ . '/../vendor/autoload.php';

class CacheGenerator
{
    private FilesystemAdapter $cache;
    private int $ttl;

    public function __construct(int $ttl = 3600)
    {
        $this->ttl = $ttl;
        $this->cache = new FilesystemAdapter('view_cache', $ttl, __DIR__ . '/../cache');
    }

    /**
     * Cache a specific view file and return its output (auto-render)
     *
     * @param string $viewPath Relative path to view file (e.g., '../view/index.php')
     * @param array $variables Variables to extract and pass into view
     * @return string Rendered output (cached or newly generated)
     */
    public function renderView(string $viewPath, array $variables = []): string
    {
        $key = 'render_' . md5(realpath($viewPath));

        return $this->cache->get($key, function (ItemInterface $item) use ($viewPath, $variables) {
            $item->expiresAfter($this->ttl);

            // Capture view output
            ob_start();
            extract($variables, EXTR_SKIP);
            include $viewPath;
            return ob_get_clean();
        });
    }

    /**
     * Clear all cached views
     */
    public function clear(): void
    {
        $this->cache->clear();
    }
}
