<?php

namespace App\Extensions;

class ExtensionRegistry
{
    protected array $permissions = [];
    protected array $serverPermissions = [];
    protected array $navigationItems = [];
    protected array $userMenuItems = [];
    protected array $renderHooks = [];

    /**
     * Register custom admin/role permissions.
     *
     * @param array $permissions Array of model => actions
     */
    public function permissions(array $permissions): void
    {
        $this->permissions = array_merge($this->permissions, $permissions);
    }

    /**
     * Register custom server panel (subuser) permissions.
     *
     * @param string $extensionId Extension identifier (e.g., 'example-extension')
     * @param array $permissionData Array with structure:
     *   [
     *     'name' => 'extension_name',
     *     'icon' => 'tabler-icon',
     *     'permissions' => ['action1', 'action2'],
     *     'descriptions' => [
     *       'desc' => 'Category description',
     *       'action1' => 'Action 1 description',
     *       'action2' => 'Action 2 description',
     *     ]
     *   ]
     */
    public function serverPermissions(string $extensionId, array $permissionData): void
    {
        $this->serverPermissions[$extensionId] = $permissionData;
    }

    /**
     * Register navigation items for specific panels.
     * Note: App panel does not support navigation items (navigation is disabled).
     *
     * @param string $itemId Unique item identifier
     * @param string $label Display label (or callable returning label)
     * @param array $config Configuration:
     *   - 'url': string|callable - URL for the navigation item
     *   - 'icon': string - Icon name
     *   - 'sort': int - Sort order
     *   - 'group': string|null - Navigation group (admin panel only)
     *   - 'visible': callable|bool - Visibility condition
     *   - 'panels': array - Which panels to register on ['admin' => true, 'server' => true]
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
     * @param string $itemId Unique item identifier
     * @param string $label Display label (or callable returning label)
     * @param array $config Configuration:
     *   - 'url': string|callable - URL for the menu item
     *   - 'icon': string - Icon name
     *   - 'sort': int - Sort order
     *   - 'visible': callable|bool - Visibility condition
     *   - 'panels': array - Which panels to register on ['admin' => true, 'server' => true, 'app' => true]
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
     * @param string $hook The hook name (use PanelsRenderHook constants)
     * @param callable $callback Callback to render content
     * @param array $options Additional options
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
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Get all registered server permissions.
     */
    public function getServerPermissions(): array
    {
        return $this->serverPermissions;
    }

    /**
     * Get all registered navigation items.
     */
    public function getNavigationItems(): array
    {
        return $this->navigationItems;
    }

    /**
     * Get all registered user menu items.
     */
    public function getUserMenuItems(): array
    {
        return $this->userMenuItems;
    }

    /**
     * Get all registered render hooks.
     */
    public function getRenderHooks(): array
    {
        return $this->renderHooks;
    }
}
