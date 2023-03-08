<?php

namespace Sitepilot\Framework;

class Vite extends Module
{
    protected function init(): void
    {
        if (!$this->enabled()) {
            return;
        }

        $this->app->action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        $this->app->filter('script_loader_tag', [$this, 'filter_script_loader_tag'], 10, 3);
    }

    public function enabled(): bool
    {
        return file_exists($this->manifest_file());
    }

    public function hot_file(): string
    {
        return $this->app->path('public/hot');
    }

    public function manifest_file(): string
    {
        return $this->app->path('public/build/manifest.json');
    }

    public function manifest(): array
    {
        return (array)json_decode(file_get_contents($this->manifest_file()));
    }

    public function hot(): bool
    {
        return file_exists($this->hot_file());
    }

    public function url(string $path): string
    {
        if ($this->hot()) {
            return file_get_contents($this->hot_file()) . ($path ? '/' . $path : $path);
        }

        return $this->app->url($path);
    }

    public function enqueue_scripts(): void
    {
        foreach ($this->manifest() as $asset) {
            $file = $this->hot() ? $asset->src : 'public/build/' . $asset->file;
            $name = $this->app->name() . '-' . strtok(basename($asset->src), '.');

            if (str_ends_with($asset->file, '.css')) {
                wp_enqueue_style($name, $this->url($file), [], null);
            } else {
                wp_enqueue_script($name, $this->url($file), [], null, true);
            }
        }

        if ($this->hot()) {
            wp_enqueue_script('vite', $this->url('@vite/client'), [], null);
        }
    }

    public function filter_script_loader_tag($tag, $handle, $src): string
    {
        if ('vite' !== $handle) {
            return $tag;
        }

        return '<script type="module" src="' . esc_url($src) . '"></script>';
    }
}
