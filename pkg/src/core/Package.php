<?php

namespace Main;

class Package {
	public function __construct(string $directory, string $namespace = '', string $extension = 'php') {
		$this->setDirectory($directory);
		$this->setNamespace($namespace);
		$this->setExtension($extension);
	}

	protected string $directory;

	protected string $namespace;

	protected string $extension;

	public function getDirectory(): string {
		return $this->directory;
	}

	public function getNamespace(): string {
		return $this->namespace;
	}

	public function getExtension(): string {
		return $this->extension;
	}

	public function setDirectory(string $directory): self {
		$this->directory = self::realDirectory($directory);
		return $this;
	}

	public function setNamespace(string $namespace): self {
		$this->namespace = self::realNamespace($namespace);
		return $this;
	}

	public function setExtension(string $extension): self {
		$this->extension = self::realExtension($extension);
		return $this;
	}

	public static function realDirectory(string $directory): string {
		return realpath($directory) . DIRECTORY_SEPARATOR;
	}

	public static function realNamespace(string $namespace): string {
		return empty($namespace) ? $namespace : rtrim($namespace, '\\') . '\\';
	}

	public static function realExtension(string $extension): string {
		return empty($extension) ? $extension : '.' . ltrim($extension, '.');
	}

	public static function load(string $directory, string $namespace = '', string $extension = 'php'): self {
		$loader = new self($directory, $namespace, $extension);
		spl_autoload_register([$loader, 'add']);
		return $loader;
	}

	public static function unload(self $loader): self {
		spl_autoload_unregister([$loader, 'add']);
		return $loader;
	}

	public function add(string $name): bool {
		$name = str_replace('/', '\\', $name);
		if (preg_match('/^' . preg_quote($this->getNamespace(), '/') . '(?P<name>.*)$/', $name, $matches)) {
			$name = $matches['name'];
			if (false !== $this->import($name, true, true)) {
				if (class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false)) {
					return true;
				}
			}
		}
		return false;
	}

	protected array $modules = [];

	public function import(string $name, $once = false, bool $required = false, array $vars = []): mixed {
		if (isset($this->modules[$name])) {
			if ($once) {
				return !empty($vars);
			}
			return $required ? require $this->modules[$name] : include $this->modules[$name];
		}

		$file = $this->getDirectory() . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $name) . $this->getExtension();
		if (is_file($file)) {
			extract($vars);
			$this->modules[$name] = $file;
			return $required ? ($once ? require_once $file : require $file) : ($once ? include_once $file : include $file);
		}

		return false;
	}

	protected array $actions = [];

	public function call(string $name, mixed ...$args): mixed {
		if (isset($this->actions[$name])) {
			return $this->actions[$name](...$args);
		}

		if (is_callable($callback = $this->import($name, true)) || is_callable($callback = $this->getNamespace() . $name)) {
			$this->actions[$name] = $callback;
			return $callback(...$args);
		}

		return false;
	}

	protected string $name;

	public function as(string $name): self {
		$this->name = $name;
		return $this;
	}

	public function is(string $name): bool {
		return fnmatch($name, $this->name);
	}

	public function in(array $names): bool {
		foreach ($names as $name) {
			if ($this->is($name)) {
				return true;
			}
		}
		return false;
	}
}
