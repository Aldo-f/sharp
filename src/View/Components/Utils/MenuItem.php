<?php

namespace Code16\Sharp\View\Components\Utils;

use Code16\Sharp\Utils\Menu\SharpMenuItem;

abstract class MenuItem
{
    public ?string $label;

    public static function buildFromItemClass(SharpMenuItem $item): ?MenuItem
    {
        if ($item->isSection()) {
            $menuItem = new MenuItemCategory($item);
        } elseif ($item->isEntity()) {
            $menuItem = new MenuItemEntity($item);
        } elseif (isset($config['url'])) {
            $menuItem = new MenuItemUrl($config);
        } elseif (isset($config['dashboard'])) {
            $menuItem = new MenuItemDashboard($config);
        } elseif (isset($config['separator'])) {
            $menuItem = new MenuItemSeparator($config);
        }

        return ($menuItem ?? false) && $menuItem->isValid()
            ? $menuItem
            : null;
    }

    public static function parse(array $config): ?MenuItem
    {
        if (isset($config['entities'])) {
            $menuItem = new MenuItemCategory($config);
        } elseif (isset($config['entity'])) {
            $menuItem = new MenuItemEntity($config);
        } elseif (isset($config['url'])) {
            $menuItem = new MenuItemUrl($config);
        } elseif (isset($config['dashboard'])) {
            $menuItem = new MenuItemDashboard($config);
        } elseif (isset($config['separator'])) {
            $menuItem = new MenuItemSeparator($config);
        }

        return ($menuItem ?? false) && $menuItem->isValid()
            ? $menuItem
            : null;
    }

    public function isMenuItemCategory(): bool
    {
        return false;
    }

    public function isMenuItemEntity(): bool
    {
        return false;
    }

    public function isMenuItemUrl(): bool
    {
        return false;
    }

    public function isMenuItemDashboard(): bool
    {
        return false;
    }

    abstract public function isValid(): bool;
}
