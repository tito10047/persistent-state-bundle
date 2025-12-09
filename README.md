![Tests](https://github.com//tito10047/persistent-state-bundle/actions/workflows/symfony.yml/badge.svg)

# Persistent State Bundle

<p align="center">
<img src="docs/image/promo_small.png"><br>
</p>

This bundle provides a unified and robust solution for managing persistent user interface state in Symfony applications. It covers two key areas: Selections for efficient
handling of bulk operations (e.g., "Select All" across pagination) and Preferences for storing key-value settings. With a flexible architecture based on context
resolvers (e.g., binding to a user or company) and support for various storage backends (Session, Redis, Doctrine), it allows you to elegantly solve UI state persistence
without cluttering your controllers with boilerplate code.

```yaml
services:
    app.users_resolver:
        class: Tito10047\PersistentStateBundle\Resolver\ObjectContextResolver
        arguments:
            $targetClass: App\Entity\User
            $identifierMethod: 'getName'
    app.companies_resolver:
        class: Tito10047\PersistentStateBundle\Resolver\ObjectContextResolver
        arguments:
            $targetClass: App\Entity\Company
    app.storage.doctrine:
        class: Tito10047\PersistentStateBundle\Storage\DoctrinePreferenceStorage
        arguments:
            - '@doctrine.orm.entity_manager'
            - Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Entity\UserPreference

persistent:
    preference:
        managers:
            default:
                storage: '@persistent.preference.storage.session'
            my_pref_manager:
                storage: '@app.storage.doctrine'
    selection:
        managers:
            default:
                storage: 'persistent.selection.storage.session'
            simple:
                storage: 'persistent.selection.storage.doctrine'
```

```php
namespace ;

use \Symfony\Component\DependencyInjection\Attribute\Autowire;
use \App\Entity\User;
use \App\Entity\Company;
use \App\Entity\Product;

class Foo{

    public function __construct(
        private readonly PreconfiguredPreferenceInterface $sessionPrefManager
        #[Autowire('persistent.preference.my_pref_manager')]
        private readonly PreconfiguredPreferenceInterface $doctrinePrefManager,
        private readonly PreconfiguredSelectionInterface $sessionPrefManager,
        #[Autowire('persistent.selection.my_sel_manager')]
        private readonly PreconfiguredSelectionInterface $doctrinePrefManager,
        private readonly EntityManagerInterface $em
    ) {}
    
    public function bar(User $user, Company $company, Product $product){
        
        $userPref = $this->sessionPrefManager->getPreference($user);
        $companyPref = $this->doctrinePrefManager->getPreference($company);
        
        $cartSelection = $this->sessionPrefManager->getSelection($user,"cart");
        $companySelection = $this->doctrinePrefManager->getSelection($company, "products");
        
        $cartSelection->select($product, [
            'quantity' => $request->get('qty', 1),
            'added_at' => new \DateTime()
        ]);
        
        $companySelection->select($product);
    
        $userPref->set('foo', 'bar');
        $userPref->set('baz', [1,2,3]);
        
        $companyPref->set('foo2', 'bar');
        $companyPref->set('baz2', [1,2,3]);
        
        $em->flush();
        
        $foo = $userPref->get('foo');
        $baz = $userPref->get('baz');
        
        $foo2 = $companyPref->get('foo2');
        $baz2 = $companyPref->get('baz2');
        
        $selectedItems = $cartSelection->getSelectedObjects(); 
        $selectedProducts = $companySelection->getSelectedObjects(); 
        
        
        $cart->destroy();
    }
}
```

```twig
<div>
    User Foo: {{ preference(user, 'foo') }}<br>
    Company pref: {{ company|pref('foo2') }}
</div>
```

## Console command: debug:preference

Inspect stored preferences for a specific context directly from CLI.

Usage:

```
php bin/console debug:preference "user_15" --manager=my_pref_manager
```

Output example:

```
Context: user_15
Storage: doctrine

+-------+-------+
| Key   | Value |
+-------+-------+
| theme | dark  |
| limit | 50    |
+-------+-------+
```

Notes:

- The `context` argument accepts either a pre-resolved key like `user_15` or any object supported by your configured context resolvers.
- The `--manager` option selects which preference manager to use. It maps to the service id `persistent.manager.{name}` and defaults to `default` when omitted.
- The Storage line reflects the underlying storage: `session`, `doctrine`, or the short class name for custom storages.
- Non-scalar values are JSON-encoded for readability; `null` and booleans are rendered as `null`, `true`/`false`.