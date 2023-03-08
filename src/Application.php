<?php

namespace Sitepilot\Framework;

abstract class Application
{
    protected static ?self $app = null;

    protected string $name;

    protected string $file;

    protected string $type;

    protected array $meta;

    protected string $base_url;

    protected string $base_path;

    public Vite $vite;

    public function __construct(string $file)
    {
        $this->file = $file;

        $this->set_type();

        $this->set_paths();

        $this->vite = new Vite($this);

        $this->init();
    }

    abstract function init(): void;

    public static function make(string $file): static
    {
        if (!static::$app) {
            static::$app = new static($file);
        }

        return static::$app;
    }

    public function version(): string
    {
        return $this->meta['version'];
    }

    public function script_version(): string
    {
        $version = $this->version();

        if ($this->is_dev()) {
            $version = time();
        }

        return $version;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function file(): string
    {
        return $this->file;
    }

    public function url(string $path = ''): string
    {
        return $this->base_url . ($path ? '/' . $path : $path);
    }

    public function path(string $path = ''): string
    {
        return $this->base_path . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    public function is_dev(): bool
    {
        return str_ends_with($this->version(), '-dev');
    }

    public function is_theme(): bool
    {
        return $this->type === 'theme';
    }

    public function is_plugin(): bool
    {
        return $this->type === 'plugin';
    }

    public function template(string $template, string $name = null, array $args = array()): string
    {
        if ($this->is_theme()) {
            ob_start();
            get_template_part($template, $name, array_merge(['app' => $this], $args));
            $template = ob_get_contents();
            ob_end_clean();

            return $template;
        } else {
            #toDo
            return '';
        }
    }

    public function action(string $hook_name, $callback, int $priority = 10, int $accepted_args = 1): void
    {
        if (is_string($callback)) {
            $callback = [$this, $callback];
        }

        add_action($hook_name, $callback, $priority, $accepted_args);
    }

    public function filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): void
    {
        if (is_string($callback)) {
            $callback = [$this, $callback];
        }

        add_filter($hook_name, $callback, $priority, $accepted_args);
    }

    private function set_type(): void
    {
        $stylesheet = dirname($this->file) . '/style.css';

        if (file_exists($stylesheet)) {
            $meta = get_file_data($stylesheet, [
                'name' => 'Theme Name',
                'version' => 'Version',
                'template' => 'Template'
            ], 'theme');

            if (!empty($meta['name'])) {
                $this->type = 'theme';
                $this->meta = $meta;
                $this->name = get_stylesheet();
            }
        } else {
            $meta = get_file_data($this->file, [
                'name' => 'Plugin Name',
                'version' => 'Version'
            ], 'plugin');

            $this->type = 'plugin';
            $this->meta = $meta;
            $this->name = plugin_basename($this->file);
        }
    }

    private function set_paths(): void
    {
        if ($this->is_theme()) {
            if (!empty($this->meta['template'])) {
                $this->base_path = get_stylesheet_directory();
                $this->base_url = get_stylesheet_directory_uri();
            } else {
                $this->base_path = get_template_directory();
                $this->base_url = get_template_directory_uri();
            }
        } else {
            $this->base_path = plugin_dir_path($this->file);
            $this->base_url = plugin_dir_url($this->file);
        }
    }
}
