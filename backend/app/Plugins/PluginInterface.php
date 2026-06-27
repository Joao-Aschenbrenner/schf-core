<?php

namespace App\Plugins;

interface PluginInterface
{
    public function getName(): string;

    public function getVersion(): string;

    public function getDescription(): string;

    public function getAuthor(): string;

    public function getMinCoreVersion(): string;

    public function getMaxCoreVersion(): string;

    public function getDependencies(): array;

    public function isCompatible(string $coreVersion): bool;

    public function install(): bool;

    public function uninstall(): bool;

    public function enable(): bool;

    public function disable(): bool;

    public function isEnabled(): bool;
}