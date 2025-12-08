<?php

namespace Tito10047\PersistentPreferenceBundle\Selection\Service;


interface SelectionInterface {

	public function destroy(): static;

	public function isSelected(mixed $item): bool;

	public function isSelectedAll():bool;

	public function select(mixed $item, null|array|object $metadata=null): static;
	public function update(mixed $item, null|array|object $metadata=null): static;

	public function unselect(mixed $item): static;


	/**
	 * Prepína stav položky (napríklad aktivovanie/deaktivovanie) s možnosťou pripojiť metadata.
	 *
	 * @param mixed             $item     Položka na prepnutie stavu (Entity, Objekt alebo ID).
	 * @param null|array|object $metadata Dodatočné informácie alebo nastavenia priradené k položke.
	 *
	 * @return bool Vráti nový stav.
	 */
	public function toggle(mixed $item, null|array|object $metadata=null): bool;

	/**
	 * Zvolí viacero položiek naraz, pričom každej môže priradiť špecifické metadata.
	 *
	 * @param array $items Zoznam položiek (Entity, Objekty alebo ID).
	 * @param array<int|string, null|array|object> $metadataMap
	 * Asociatívne pole, kde KĽÚČ je ID položky (získané normalizáciou)
	 * a HODNOTA sú metadata pre danú položku.
	 * Príklad: [101 => ['qty' => 5], 102 => ['qty' => 1]]
	 */
	public function selectMultiple(array $items, null|array $metadata=null):static;
	public function unselectMultiple(array $items):static;

	public function selectAll():static;

	public function unselectAll():static;

	/**
	 * @return array<string|int>
	 */
	public function getSelectedIdentifiers(): array;
	/**
	 * Vráti mapu vybraných položiek. Ak je zadaná $metadataClass, metadáta sa hydratujú.
	 *
	 * @return array<string|int, array|object>
	 * @template T of object
	 * @phpstan-param class-string<T>|null $metadataClass
	 * @phpstan-return array<string|int, T>|array<string|int, array|object>
	 */
	public function getSelected(): array;

	/**
	 * Vráti mapu vybraných položiek. Ak je zadaná $metadataClass, metadáta sa hydratujú.
	 *
	 * @return T|array|null
	 * @template T of object
	 * @phpstan-param class-string<T>|null $metadataClass
	 * @phpstan-return T|array
	 */
	public function getMetadata(mixed $item): null|array|object;

	public function getTotal():int;

}