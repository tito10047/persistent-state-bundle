![Tests](https://github.com/tito10047/persistent-state-bundle/actions/workflows/symfony.yml/badge.svg)

# Persistent State Bundle

<p align="center">
<img src="docs/image/promo_small.png"><br>
</p>

This Symfony bundle provides a unified and robust solution for managing persistent user interface state. It handles two key areas:
1. **Selections**: Efficient handling of bulk operations (e.g., "Select All" across multiple pages of a paginated list).
2. **Preferences**: Storing key-value settings for various contexts (e.g., user, company).

It features a flexible architecture based on context resolvers and support for various storage backends (Session, Redis, Doctrine), allowing you to elegantly solve UI state persistence without boilerplate code in your controllers.

## Features

- **Selection Management**: Track selected items (IDs) across pagination.
- **Preference Management**: Store and retrieve arbitrary user/context settings.
- **Context Resolvers**: Automatically resolve context (e.g., current user) for storage.
- **Twig Integration**: Easy-to-use Twig functions and filters.
- **Stimulus Integration**: Ready-to-use Stimulus controller for interactive selections.
- **Multiple Storages**: Support for Session (default), Doctrine, and easily extensible for others (e.g., Redis).
- **CLI Support**: Debug preferences directly from the terminal.

## Requirements

- **PHP**: ^8.1
- **Symfony**: ^6.4 | ^7.4 | ^8.0

## Installation

Install the bundle via Composer:

```bash
composer require tito10047/persistent-state-bundle
```

If you are using Symfony Flex, the bundle will be registered automatically. Otherwise, add it to your `config/bundles.php`:

```php
return [
    // ...
    Tito10047\PersistentStateBundle\PersistentStateBundle::class => ['all' => true],
];
```

For Stimulus integration, ensure you have [Symfony UX Stimulus](https://symfony.com/bundles/StimulusBundle/current/index.html) installed and configured.

## Usage Scenarios

### 1. Simple Selection (e.g., Shopping Cart)

```php
use Tito10047\PersistentStateBundle\Selection\Service\SelectionManagerInterface;

class CartController
{
    public function __construct(
        private readonly SelectionManagerInterface $selectionManager,
    ) {}

    public function add(User $user, Product $product)
    {
        $cartSelection = $this->selectionManager->getSelection($user, 'cart');
        $cartSelection->select($product, [
            'quantity' => 1,
            'added_at' => new \DateTime()
        ]);
    }

    public function clear(User $user)
    {
        $this->selectionManager->getSelection($user, 'cart')->destroy();
    }
}
```

### 2. User Preferences

```php
use Tito10047\PersistentStateBundle\Preference\Service\PreferenceManagerInterface;

class SettingsController
{
    public function __construct(
        private readonly PreferenceManagerInterface $prefManager,
    ) {}

    public function updateTheme(User $user, string $theme)
    {
        $preferences = $this->prefManager->getPreference($user);
        $preferences->set('theme', $theme);
    }
}
```

In Twig:
```twig
<body class="theme-{{ preference(app.user, 'theme', 'light') }}">
    Company Setting: {{ company|pref('some_setting') }}
</body>
```

### 3. Bulk Actions with Stimulus

This bundle provides a Stimulus controller to handle "Select All" and individual row selections.

```twig
<div {{ persistent_selection_stimulus_controller("main_logs", null, {
    selectAllClass: 'btn-primary',
    unselectAllClass: 'btn-outline-secondary',
}) }}>
    <div class="mb-2">
        <button class="btn btn-sm" data-action="tito10047--persistent-state-bundle--selection#selectCurrentPage">
            Select Visible
        </button>
        <button class="btn btn-sm" data-action="tito10047--persistent-state-bundle--selection#selectAll">
            Select All
        </button>
    </div>

    <ul class="list-group">
        {% for log in logs %}
            <li class="list-group-item">
                {{ persistent_selection_row_selector("main_logs", log) }}
                {{ log.name }}
            </li>
        {% endfor %}
    </ul>
</div>
```

In your controller to perform the action:
```php
public function deleteSelected(User $user)
{
    $selection = $this->selectionManager->getSelection($user, 'main_logs');
    $ids = $selection->getSelectedIdentifiers();
    
    // ... perform deletion
}
```

## Configuration

Default configuration (optional to override in `config/packages/persistent_state.yaml`):

```yaml
persistent_state:
    preference:
        managers:
            default:
                storage: '@persistent_state.preference.storage.session'
    selection:
        managers:
            default:
                storage: '@persistent_state.selection.storage.session'
```

See [docs/full_config.md](docs/full_config.md) for more advanced configuration, including Doctrine storage setup.

## Console Commands

### `debug:preference`
Inspect stored preferences for a specific context.

```bash
php bin/console debug:preference "user_15" --manager=default
```

## Scripts

The following scripts are available via Composer:

- `composer test`: Runs the PHPUnit test suite.

## Project Structure

- `assets/`: Stimulus controllers and frontend assets.
- `config/`: Bundle configuration and service definitions.
- `docs/`: Additional documentation and images.
- `src/`: Core bundle logic.
    - `Command/`: CLI commands.
    - `Controller/`: Ajax endpoints for state updates.
    - `Preference/`: Preference management logic.
    - `Selection/`: Selection management logic.
    - `Storage/`: Storage implementations (Session, Doctrine).
    - `Twig/`: Twig extensions and runtimes.
- `templates/`: Default templates and Twig components.
- `tests/`: Integration and unit tests.

## Testing

Run tests using PHPUnit:

```bash
composer test
```

## License

This bundle is released under the [MIT License](LICENSE).
