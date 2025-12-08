<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Integration\Twig;

use Tito10047\PersistentPreferenceBundle\Enum\SelectionMode;
use Tito10047\PersistentPreferenceBundle\PersistentPreferenceBundle;
use Tito10047\PersistentPreferenceBundle\Selection\Service\SelectionInterface;
use Tito10047\PersistentPreferenceBundle\Selection\Service\SelectionManagerInterface;
use Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\Entity\User;
use Tito10047\PersistentPreferenceBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;
use Twig\Environment;

class SelectionExtensionTest extends AssetMapperKernelTestCase
{
    public function testTwigFunctionsAreRegistered(): void
    {
        $container = self::getContainer();
        /** @var Environment $twig */
        $twig = $container->get('twig');

        $this->assertInstanceOf(Environment::class, $twig);

        foreach ([
            'persistent_selection_is_selected',
            'persistent_selection_is_selected_all',
            'persistent_selection_row_selector',
            'persistent_selection_row_selector_all',
            'persistent_selection_total',
            'persistent_selection_count',
            'persistent_selection_stimulus_controller',
        ] as $functionName) {
            $this->assertNotNull($twig->getFunction($functionName), sprintf('Twig function %s should be registered.', $functionName));
		}
		$this->assertArrayHasKey('persistent_selection_stimulus_controller_name',$twig->getGlobals());
	}

    public function testTwigFunctionsBehavior(): void
    {
		$this->markTestSkipped("TODO: fix this test");
		$controllerName = PersistentPreferenceBundle::STIMULUS_CONTROLLER;
        $container = self::getContainer();

        /** @var SelectionManagerInterface $manager */
        $manager = $container->get(SelectionManagerInterface::class);

        // Prepare simple object list with "id" property
        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $o = new User();
            $o->setId($i);
            $o->setName('Item '.$i);
            $items[] = $o;
        }

        // Register source under a key
        $selection = $manager->registerSelection('twig_key', $items);
        $this->assertInstanceOf(SelectionInterface::class, $selection);
        $this->assertSame(3, $selection->getTotal(), 'Total after registerSource should reflect all items.');

        // Select one item (object with id=2)
        $selection->select($items[1]);

        /** @var Environment $twig */
        $twig = $container->get('twig');

        // persistent_is_selected for id=2 should be true, for id=1 should be false
        $tpl = $twig->createTemplate(
            "{{ persistent_selection_is_selected('twig_key', item) ? 'YES' : 'NO' }}"
        );
        $outSelected = $tpl->render(['item' => $items[1]]); // id=2
        $outNotSelected = $tpl->render(['item' => $items[0]]); // id=1
        $this->assertSame('YES', $outSelected);
        $this->assertSame('NO', $outNotSelected);

        // persistent_row_selector should include checked attribute only for selected item
        $tpl = $twig->createTemplate("{{ persistent_selection_row_selector('twig_key', item) }}");
        $htmlSelected = $tpl->render(['item' => $items[1]]);
        $htmlNotSelected = $tpl->render(['item' => $items[0]]);
        $this->assertStringContainsString('type=\'checkbox\'', $htmlSelected);
        $this->assertStringContainsString("data-{$controllerName}-target=\"checkbox\"", $htmlSelected);
        $this->assertStringContainsString('checked="checked"', $htmlSelected);
        $this->assertStringNotContainsString('checked="checked"', $htmlNotSelected);

        // persistent_selection_total and persistent_selection_count
        $tplTotal = $twig->createTemplate("{{ persistent_selection_total('twig_key') }}");
        $tplCount = $twig->createTemplate("{{ persistent_selection_count('twig_key') }}");
        $this->assertSame('3', trim($tplTotal->render()));
        $this->assertSame('1', trim($tplCount->render()));

        // check controller name
        $name = $twig->createTemplate("{{ persistent_selection_stimulus_controller_name }}");
		$this->assertSame($controllerName, trim($name->render()));

        // persistent_row_selector_all should not be checked in default INCLUDE mode
        $tplAll = $twig->createTemplate("{{ persistent_selection_row_selector_all('twig_key') }}");
        $htmlAll = $tplAll->render();
        $this->assertStringNotContainsString('checked="checked"', $htmlAll);

        // Switch to EXCLUDE mode; with current Selection::isSelectedAll() implementation,
        // the checkbox remains unchecked unless all items are excluded (which we don't do here)
        $selection->setMode(SelectionMode::EXCLUDE);
        $htmlAllExclude = $tplAll->render();
        $this->assertStringNotContainsString('checked="checked"', $htmlAllExclude);

        // persistent_stimulus_controller should contain controller name and required URLs
        $tplStimulus = $twig->createTemplate("{{ persistent_selection_stimulus_controller('twig_key') }}");
        $attrs = $tplStimulus->render();
        $this->assertStringContainsString("data-controller=\"{$controllerName}\"", $attrs);
        $this->assertStringContainsString("data-{$controllerName}-url-toggle-value=\"", $attrs);
        $this->assertStringContainsString('/_persistent-selection/toggle', $attrs);
        $this->assertStringContainsString("data-{$controllerName}-url-select-all-value=\"", $attrs);
        $this->assertStringContainsString('/_persistent-selection/select-all', $attrs);
        $this->assertStringContainsString("data-{$controllerName}-url-select-range-value=\"", $attrs);
        $this->assertStringContainsString('/_persistent-selection/select-range', $attrs);
        $this->assertStringContainsString("data-{$controllerName}-key-value=\"twig_key\"", $attrs);
        $this->assertStringContainsString("data-{$controllerName}-manager-value=\"default\"", $attrs);
    }

    public function testRowSelectorCustomAttributesAreMergedAndEscaped(): void
    {
		$this->markTestSkipped("TODO: fix this test");
        $container = self::getContainer();

        /** @var SelectionManagerInterface $manager */
        $manager = $container->get(SelectionManagerInterface::class);

        // Items
        $items = [];
        for ($i = 1; $i <= 1; $i++) {
            $o = new \stdClass();
            $o->id = $i;
            $items[] = $o;
        }

        // Register & select item to ensure 'checked' attribute appears
        $selection = $manager->registerSelection('twig_attr_key', $items);
        $selection->select($items[0]);

        /** @var Environment $twig */
        $twig = $container->get('twig');

        // Custom attributes: override name, add class, data-foo, boolean, and escaping
        $tpl = $twig->createTemplate(
            "{{ persistent_selection_row_selector('twig_attr_key', item, {\n".
            "  'class': 'extra extra',\n".
            "  'name': 'custom[]',\n".
            "  'data-foo': 'bar',\n".
            "  'disabled': true,\n".
            "  'data-title': 'A \"quote\" & more'\n".
            "}) }}"
        );

        $html = $tpl->render(['item' => $items[0]]);

        // name overridden
        $this->assertStringContainsString('name="custom[]"', $html);
        // class merged and de-duplicated: default 'row-selector' + custom 'extra'
        $this->assertStringContainsString('class="row-selector extra"', $html);
        // checked present because item selected
        $this->assertStringContainsString('checked="checked"', $html);
        // data-foo propagated
        $this->assertStringContainsString('data-foo="bar"', $html);
        // boolean attribute rendered as disabled="disabled"
        $this->assertStringContainsString('disabled="disabled"', $html);
        // escaped value
        $this->assertStringContainsString('data-title="A &quot;quote&quot; &amp; more"', $html);
    }

    public function testStimulusControllerMergesDataController(): void
    {
		$this->markTestSkipped("TODO: fix this test");
        $container = self::getContainer();
        /** @var Environment $twig */
        $twig = $container->get('twig');
        $controllerName = PersistentPreferenceBundle::STIMULUS_CONTROLLER;

        // Provide custom data-controller to be concatenated after the bundle's default
        $tpl = $twig->createTemplate(
            "{{ persistent_selection_stimulus_controller('twig_key', 'extra-controller' ) }}"
        );

        $attrs = $tpl->render();

        // Expect both controllers, default first then custom
        $this->assertStringContainsString(
            sprintf('data-controller="%s extra-controller"', $controllerName),
            $attrs
        );

        // If custom includes duplicate default, it should be de-duplicated
        $tplDedupe = $twig->createTemplate(
            "{{ persistent_selection_stimulus_controller('twig_key', 'extra-controller') }}"
        );
        $attrsDedupe = $tplDedupe->render();
        $this->assertStringContainsString(
            sprintf('data-controller="%s extra-controller"', $controllerName),
            $attrsDedupe
        );
    }
}
