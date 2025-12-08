<?php

namespace Tito10047\PersistentPreferenceBundle\Twig;

use InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tito10047\PersistentPreferenceBundle\PersistentPreferenceBundle;
use Tito10047\PersistentPreferenceBundle\Selection\Service\SelectionManagerInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class SelectionRuntime implements RuntimeExtensionInterface {

	private string $controllerName = PersistentPreferenceBundle::STIMULUS_CONTROLLER;

	/**
	 * @param iterable<SelectionManagerInterface> $selectionManagers
	 */
	public function __construct(
		private readonly iterable              $selectionManagers,
		private readonly UrlGeneratorInterface $router
	) {
	}

	public function getStimulusController(string $key, ?string $controller = null, array $variables = [], string $manager = 'default', bool $asArray = false): string|array {
		$toggleUrl    = $this->router->generate('persistent_selection_toggle');
		$selectAllUrl = $this->router->generate('persistent_selection_select_all');
		$selectRange  = $this->router->generate('persistent_selection_select_range');


		$myAttributes = [
			"data-controller" => $this->controllerName,
		];
		$variables    = $variables + [
				"urlToggle"      => $toggleUrl,
				"urlSelectAll"   => $selectAllUrl,
				"urlSelectRange" => $selectRange,
				"key"            => $key,
				"manager"        => $manager
			];
		foreach ($variables as $name => $value) {
			$name                                                     = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $name));
			$attributes["data-{$this->controllerName}-{$name}-value"] = $value;
		}
		if ($controller){
			$attributes["data-controller"]=$controller;
		}
		$attributes = $this->mergeAttributes($myAttributes, $attributes);
		if ($asArray) {
			return $attributes;
		}
		return $this->renderAttributes($attributes);
	}

	public function isSelected(string $key, mixed $item, string $manager = 'default'): bool {
		$manager   = $this->getRowsSelector($manager);
		$selection = $manager->getSelection($key);
		return $selection->isSelected($item);
	}

	public function isSelectedAll(string $key, string $manager = 'default'): bool {
		$manager   = $this->getRowsSelector($manager);
		$selection = $manager->getSelection($key);
		return $selection->isSelectedAll();
	}

	public function rowSelector(string $key, mixed $item, array $attributes = [], string $manager = 'default'): string {
		$selected  = "";
		$manager   = $this->getRowsSelector($manager);
		$selection = $manager->getSelection($key);

		$myAttributes = [
			"name"  => "row-selector[]",
			"class" => "row-selector"
		];

		if ($selection->isSelected($item)) {
			$myAttributes["checked"] = 'checked';
		}

		$attributes = $this->renderAttributes($this->mergeAttributes($myAttributes, $attributes));

		return "<input type='checkbox' {$attributes} data-{$this->controllerName}-target=\"checkbox\" data-action='{$this->controllerName}#toggle' data-{$this->controllerName}-id-param='{$id}' value='{$id}'>";
	}

	public function mergeAttributes(array $default, array $custom): array {

		// Merge default and custom attributes with simple rules:
		// - Start from defaults
		// - "class" values are concatenated (default first, then custom) and de-duplicated
		// - Boolean-like false/null removes the attribute
		// - For other keys, custom overrides default
		$attrs = $default;

		foreach ($custom as $key => $value) {
			if ($value === false || $value === null) {
				unset($attrs[$key]);
				continue;
			}

			// Merge token-list attributes: class and data-controller
			if ($key === 'class' || $key === 'data-controller') {
				$existing = isset($attrs[$key]) ? trim((string) $attrs[$key]) : '';
				$incoming = trim((string) $value);

				$tokens = array_merge(
					$existing !== '' ? preg_split('/\s+/', $existing, -1, PREG_SPLIT_NO_EMPTY) : [],
					$incoming !== '' ? preg_split('/\s+/', $incoming, -1, PREG_SPLIT_NO_EMPTY) : []
				);

				$attrs[$key] = implode(' ', array_unique($tokens));
				continue;
			}

			$attrs[$key] = $value;
		}
		return $attrs;
	}

	public function renderAttributes(array $attributes): string {
		// Render attributes: for boolean-like true use key="key", for strings escape
		foreach ($attributes as $key => $value) {
			if ($value === true || ($key === 'checked' && $value === 'checked')) {
				$out[] = sprintf('%s="%s"', $key, $key);
				continue;
			}
			if ($value === '') {
				$out[] = sprintf('%s=""', $key);
				continue;
			}
			$out[] = sprintf('%s="%s"', $key, htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
		}

		return implode(' ', $out);
	}

	public function rowSelectorAll(string $key, array $attributes = [], string $manager = 'default'): string {
		$selected = "";

		$manager   = $this->getRowsSelector($manager);
		$selection = $manager->getSelection($key);

		$myAttributes = [
			"name"  => "row-selector-all",
			"class" => "row-selector"
		];

		if ($selection->isSelectedAll()) {
			$myAttributes["checked"] = 'checked';
		}

		$attributes = $this->renderAttributes($this->mergeAttributes($myAttributes, $attributes));

		return "<input type='checkbox' {$attributes} data-{$this->controllerName}-target=\"selectAll\" data-action='{$this->controllerName}#selectAll'>";
	}

	public function getTotal(string $key, string $manager = 'default'): int {
		$manager   = $this->getRowsSelector($manager);
		$selection = $manager->getSelection($key);
		return $selection->getTotal();
	}

	public function getSelectedCount(string $key, string $manager = 'default'): int {
		$manager   = $this->getRowsSelector($manager);
		$selection = $manager->getSelection($key);
		return count($selection->getSelectedIdentifiers());
	}

	private function getRowsSelector(string $manager): SelectionManagerInterface {
		foreach ($this->selectionManagers as $id => $selectionManager) {
			if ($id === $manager) {
				return $selectionManager;
			}
		}
		throw new InvalidArgumentException(sprintf('No selection manager found for manager "%s".', $manager));
	}
}