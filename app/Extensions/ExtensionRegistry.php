<?php

namespace App\Extensions;

class ExtensionRegistry
{
    /** @var array<string, mixed> */
    protected array $permissions = [];

    /** @var array<string, mixed> */
    protected array $serverPermissions = [];

    /** @var array<string, mixed> */
    protected array $serverPageRestrictions = [];

    /** @var array<string, mixed> */
    protected array $navigationItems = [];

    /** @var array<string, mixed> */
    protected array $userMenuItems = [];

    /** @var array<string, mixed> */
    protected array $renderHooks = [];

    /**
     * Register custom admin/role permissions.
     *
     * @param  array  $permissions  Array of model => actions
     */
    public function permissions(array $permissions): void
    {
        $this->permissions = array_merge($this->permissions, $permissions);
    }

    /**
     * Register custom server panel (subuser) permissions.
     *
     * @param  string  $extensionId  Extension identifier (e.g., 'example-extension')
     * @param  array  $permissionData  Array with structure:
     *                                 [
     *                                 'name' => 'extension_name',
     *                                 'icon' => 'tabler-icon',
     *                                 'permissions' => ['action1', 'action2'],
     *                                 'descriptions' => [
     *                                 'desc' => 'Category description',
     *                                 'action1' => 'Action 1 description',
     *                                 'action2' => 'Action 2 description',
     *                                 ],
     *                                 'egg_tags' => ['minecraft', 'java'] // Optional: restrict to specific egg tags
     *                                 ]
     */
    public function serverPermissions(string $extensionId, array $permissionData): void
    {
        $this->serverPermissions[$extensionId] = $permissionData;
    }

    /**
     * Register server page restrictions for specific egg tags.
     * This allows extensions to specify which server types (eggs) their pages should be available for.
     *
     * @param  string  $extensionId  Extension identifier (e.g., 'example-extension')
     * @param  string  $pageClass  Fully qualified page class name
     * @param  array  $eggTags  Array of egg tags (e.g., ['minecraft', 'java'])
     */
    public function serverPageRestriction(string $extensionId, string $pageClass, array $eggTags): void
    {
        $this->serverPageRestrictions[$pageClass] = [
            'extension_id' => $extensionId,
            'egg_tags' => $eggTags,
        ];
    }

    /**
     * Register navigation items for specific panels.
     * Note: App panel does not support navigation items (navigation is disabled).
     *
     * @param  string  $itemId  Unique item identifier
     * @param  string|callable  $label  Display label (or callable returning label)
     * @param  array  $config  Configuration:
     *                         - 'url': string|callable - URL for the navigation item
     *                         - 'icon': string - Icon name
     *                         - 'sort': int - Sort order
     *                         - 'group': string|null - Navigation group (admin panel only)
     *                         - 'visible': callable|bool - Visibility condition
     *                         - 'panels': array - Which panels to register on ['admin' => true, 'server' => true]
     */
    public function navigationItem(
        string $itemId,
        string|callable $label,
        array $config
    ): void {
        // Remove 'app' panel from config if present (app panel has navigation disabled)
        if (isset($config['panels']['app'])) {
            unset($config['panels']['app']);
        }

        $this->navigationItems[$itemId] = array_merge([
            'label' => $label,
            'panels' => ['admin' => false, 'server' => false],
        ], $config);
    }

    /**
     * Register user menu items for specific panels.
     *
     * @param  string  $itemId  Unique item identifier
     * @param  string|callable  $label  Display label (or callable returning label)
     * @param  array  $config  Configuration:
     *                         - 'url': string|callable - URL for the menu item
     *                         - 'icon': string - Icon name
     *                         - 'sort': int - Sort order
     *                         - 'visible': callable|bool - Visibility condition
     *                         - 'panels': array - Which panels to register on ['admin' => true, 'server' => true, 'app' => true]
     */
    public function userMenuItem(
        string $itemId,
        string|callable $label,
        array $config
    ): void {
        $this->userMenuItems[$itemId] = array_merge([
            'label' => $label,
            'panels' => ['admin' => false, 'server' => false, 'app' => false],
        ], $config);
    }

    /**
     * Register a render hook.
     *
     * @param  string  $hook  The hook name (use PanelsRenderHook constants)
     * @param  callable  $callback  Callback to render content
     * @param  array  $options  Additional options
     */
    public function renderHook(
        string $hook,
        callable $callback,
        array $options = []
    ): void {
        $this->renderHooks[$hook][] = [
            'callback' => $callback,
            'options' => $options,
        ];
    }

    /**
     * Get all registered admin permissions.
     *
     * @return array<string, mixed>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Get all registered server permissions.
     *
     * @return array<string, mixed>
     */
    public function getServerPermissions(): array
    {
        return $this->serverPermissions;
    }

    /**
     * Get all registered navigation items.
     *
     * @return array<string, mixed>
     */
    public function getNavigationItems(): array
    {
        return $this->navigationItems;
    }

    /**
     * Get all registered user menu items.
     *
     * @return array<string, mixed>
     */
    public function getUserMenuItems(): array
    {
        return $this->userMenuItems;
    }

    /**
     * Get all registered render hooks.
     *
     * @return array<string, mixed>
     */
    public function getRenderHooks(): array
    {
        return $this->renderHooks;
    }

    /**
     * Get all registered server page restrictions.
     *
     * @return array<string, mixed>
     */
    public function getServerPageRestrictions(): array
    {
        return $this->serverPageRestrictions;
    }

    /**
     * Check if a server page is allowed for a specific server (based on egg tags).
     *
     * @param  string  $pageClass  Fully qualified page class name
     * @param  \App\Models\Server  $server  The server instance
     * @return bool True if allowed, false if restricted
     */
    public function isServerPageAllowed(string $pageClass, \App\Models\Server $server): bool
    {
        // If no restriction is registered for this page, it's allowed by default
        if (!isset($this->serverPageRestrictions[$pageClass])) {
            return true;
        }

        $restriction = $this->serverPageRestrictions[$pageClass];
        $requiredTags = $restriction['egg_tags'];

        // Get server's egg tags
        $serverEggTags = $server->egg->tags ?? [];

        // Check if any required tag matches any server egg tag
        foreach ($requiredTags as $requiredTag) {
            if (in_array($requiredTag, $serverEggTags)) {
                return true;
            }
        }

        return false;
    }
}
