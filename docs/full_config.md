```php
namespace App\Entity;
#[ORM\Entity(repositoryClass: UserPreferenceRepository::class)]
#[ORM\Table(name: 'user_preferences')]
#[ORM\UniqueConstraint(name: 'uniq_preference_context_key', columns: ['context', 'name'])]
class UserPreference extends BasePreference
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;


    public function getId(): ?Uuid
    {
        return $this->id;
    }
}

```


```yaml
services:
    app.persistent_state.users_resolver:
        class: Tito10047\PersistentStateBundle\Resolver\ObjectContextResolver
        arguments:
            $class: App\Entity\User\User
            $prefix: "user_"
    app.storage.doctrine:
        class: Tito10047\PersistentStateBundle\Preference\Storage\PreferenceEntityInterface
        arguments:
            - '@doctrine.orm.entity_manager'
            - App\Entity\User\UserPreference
persistent_state:
    preference:
        managers:
            default:
                storage: '@persistent_state.preference.storage.session'
            doctrine:
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

    private ?PreferenceInterface $storage = null;
    
    public function __construct(
        private readonly PreconfiguredPreferenceInterface $sessionPrefManager
        #[Autowire(service: 'persistent_state.preference.manager.doctrine')]
        private readonly PreferenceManagerInterface                 $doctrinePrefManager,
        private readonly PreferenceManagerInterface                 $sessionPrefManager,
        #[Autowire('persistent.selection.my_sel_manager')]
        private readonly SelectionManagerInterface $doctrinePrefManager,
        private readonly EntityManagerInterface $em
    ) {
    
        if ($user = $this->getUser()) {
            $this->storage = $doctrinePrefManager->getPreference($user);
        } else {
            $this->storage = $sessionPrefManager->getPreference("user");
        }
    }
    
    public function bar(User $user, Company $company, Product $product){
        
        $userPref = $this->storage;
        $companyPref = $this->doctrinePrefManager->getPreference($company);
        
        $cartSelection =  $selectionManager->getSelection("card", $this->getUser());
        $companySelection = $selectionManager->getSelection("products", $this->getUser());
        
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
