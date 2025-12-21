
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

```twig
{% set logs = this.logs %}
{% set isAllSelected = persistent_selection_is_selected_all("main_logs") %}
{% set isCurrentSelected = true %}
{% for log in logs %}
    {% set isCurrentSelected = isCurrentSelected and persistent_selection_is_selected("main_logs",log) %}
{% endfor %}

<div persistent_selection_stimulus_controller("main_logs", null,{
	selectAllClass:'btn-primary',
	unselectAllClass:'btn-outline-secondary',
},"default",true)>
    <div class="nav d-flex justify-content-between pb-1">
        <div class="d-flex flex-row">
        {# SELECT ROWS ON CURRENT PAGE #}
            <button
                    class="btn btn-{% if isCurrentSelected %}primary{% else %}outline-secondary{% endif %} btn-sm text-nowrap m-1"
                    data-action="{{ persistent_selection_stimulus_controller_name }}#selectCurrentPage"
            >
                <twig:ux:icon name="bi:check" width="20px" height="20px"/>
                {{ 'Select visible'|trans }}
            </button>
        {# SELECT ROWS ON ALL PAGES #}
            <button
                    class="btn btn-{% if isAllSelected %}primary{% else %}outline-secondary{% endif %} btn-sm text-nowrap  m-1"
                    data-action="{{ persistent_selection_stimulus_controller_name }}#selectAll"
            >
                <twig:ux:icon name="bi:check-all" width="20px" height="20px"/>
                {{ 'Select all'|trans }}
            </button>
        </div>
        
        {# PERFORM ACTION ON SELECTED ROWS #}
        <div>
            <button class="btn btn-outline-secondary btn-sm" aria-current="page">
                <twig:ux:icon name="game-icons:magic-broom" width="20px" height="20px"/>
                {{ 'Fix selected'|trans }}
            </button>
        </div>
    </div>
    <ul class="list-group">
        {% for log in logs %}
            <li>
                {{ persistent_selection_row_selector("main_logs",log,{class:"m-1 align-bottom"}) }}
                {{ log.name }}
            </li>
        {% endfor %}
    </ul>
</div>
```
```php
class MyController{
    public function __construct(
        private readonly SelectionManagerInterface $selectionManager,
        private readonly EntityManagerInterface $em,
    ) {}
    public function performAction(User $user, Product $product){
        
        $logsSelection = $this->selectionManager->getSelection($user,"main_logs");
        
        foreach($logsSelection->getSelectedIdentifiers() as $logId){
            $this->em->remove($this->em->getReference(Log::class,$logId));
        }
    }
    
}
```
Notes:

- The `context` argument accepts either a pre-resolved key like `user_15` or any object supported by your configured context resolvers.
- The `--manager` option selects which preference manager to use. It maps to the service id `persistent.manager.{name}` and defaults to `default` when omitted.
- The Storage line reflects the underlying storage: `session`, `doctrine`, or the short class name for custom storages.
- Non-scalar values are JSON-encoded for readability; `null` and booleans are rendered as `null`, `true`/`false`.
