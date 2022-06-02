<?php

namespace Main;

class Library implements \ArrayAccess, \IteratorAggregate, \Countable {
	public function __construct(array $packages = []) {
		$this->setPackages($packages);
	}

	protected array $packages = [];

	public function setPackages(array $packages) {
		$this->resetPackages();
		$this->addPackages($packages);
		return $this;
	}

	public function resetPackages() {
		$this->packages = [];
		return $this;
	}

	public function addPackages(array $packages) {
		foreach ($packages as $package) {
			$this->addPackage($package);
		}
		return $this;
	}

	public function addPackage(Package $package, null|int|string $key = null) {
		if (is_null($key)) {
			$this->packages[] = $package;
		} else {
			$this->packages[$key] = $package;
		}
		return $this;
	}

	public function setPackage(string $directory, string $namespace = '', string $extension = 'php', null|int|string $key = null): self {
		return $this->addPackage(new Package($directory, $namespace, $extension), $key);
	}

	public function getPackages(array $names = []): array {
		if (empty($names)) {
			return $this->packages;
		}

		$packages = [];
		foreach ($this->packages as $key => $package) {
			if ($package->in($names) || in_array($key, $names, true)) {
				$packages[] = $package;
			}
		}
		return $packages;
	}

	public function getPackage(int|string $name = 0): ?Package {
		if (is_int($name)) {
			return $this->packages[$name] ?? null;
		}

		foreach ($this->getPackages() as $package) {
			if ($package->is($name)) {
				return $package;
			}
		}
		return null;

	}

	public function hasPackages(array $names = []): bool {
		return !empty($this->getPackages($names));
	}

	public function hasPackage(int|string $name = 0): bool {
		return !empty($this->getPackage($name));
	}

	public function removePackage(int|string $name = 0): self {
		if (is_int($name)) {
			unset($this->packages[$name]);
			return $this;
		}

		foreach ($this->packages as $key => $package) {
			if ($package->is($name)) {
				array_splice($this->packages, $key, 1);
				return $this;
			}
		}
		return $this;
	}

	public function removePackages(array $names = []): self {
		foreach ($this->getPackages($names) as $package) {
			$this->removePackage($package);
		}
		return $this;
	}

	public function getIterator(array $names = []): \ArrayIterator {
		return new \ArrayIterator($this->getPackages($names));
	}

	public function count(array $names = []): int {
		return count($this->getPackages($names));
	}

	public function offsetExists(mixed $offset): bool {
		return $this->hasPackage($offset);
	}

	public function offsetGet(mixed $offset): mixed {
		return $this->getPackage($offset);
	}

	public function offsetSet(mixed $offset, mixed $value): void {
		$this->addPackage($value, $offset);
	}

	public function offsetUnset(mixed $offset): void {
		$this->removePackage($offset);
	}
}
