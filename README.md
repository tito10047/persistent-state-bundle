![Tests](https://github.com//tito10047/persistent-preference-bundle/actions/workflows/symfony.yml/badge.svg)

# 🛒 Persistent Preference Bundle

```yaml
persistent_preference:
    managers:
        default:
            storage: 'persistent_preference.storage.session'
        my_pref_manager:
            storage: 'app.persistent_preference.storage.doctrine'
    storage:
        doctrine:
            id: 'app.persistent_preference.storage.doctrine'
            enabled: true
            preference_class: App\Entity\UserPreference
            entity_manager: 'default'

    context_providers:
        users:
            class: App\Entity\User
            prefix: 'user'
        companies:
            class: App\Entity\Company
            prefix: 'company'
            identifier_method: 'getUuid'
```

```php
namespace ;

use \Symfony\Component\DependencyInjection\Attribute\Autowire;
use \App\Entity\User;
use \App\Entity\Company;

class Foo{

    public function __construct(
        private readonly PreferenceManagerInterface $sessionPrefManager
        #[Autowire('persistent_preference.manager.my_pref_manager')]
        private readonly PreferenceManagerInterface $doctrinePrefManager,
        private readonly EntityManagerInterface $em
    ) {}
    
    public function bar(User $user, Company $company){
        
        $userPref = $this->sessionPrefManager->getPreference($user);
        $companyPref = $this->doctrinePrefManager->getPreference($company);
    
        $userPref->set('foo', 'bar');
        $userPref->set('baz', [1,2,3]);
        
        $companyPref->set('foo2', 'bar');
        $companyPref->set('baz2', [1,2,3]);
        
        $em->flush();
        
        $foo = $userPref->get('foo');
        $baz = $userPref->get('baz');
        
        $foo2 = $companyPref->get('foo2');
        $baz2 = $companyPref->get('baz2');
    }

}
```

```twig
<div>
    User Foo: {{ preference(user, 'foo') }}<br>
    Company pref: {{ company|pref('foo2') }}
</div>
```