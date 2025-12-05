<?php

namespace Tito10047\PersistentPreferenceBundle\Storage;

/**
 * Toto je "obálka", ktorá drží dáta objektu a informáciu o jeho type,
 * aby mohol byť neskôr rekonštruovaný.
 * Je to čisté DTO (Data Transfer Object).
 */
final class StorableEnvelope
{
	public function __construct(
		/**
		 * Plný názov triedy (FQCN), napr. App\DTO\ThemeConfig
		 */
		public readonly string $className,

		/**
		 * Surové dáta objektu pripravené na serializáciu (napr. ['theme' => 'dark'])
		 */
		public readonly array|string|null|int|float $data
	) {}

	/**
	 * Pomocná metóda pre konverziu na pole (pre finálne úložisko)
	 */
	public function toArray(): array
	{
		return [
			'__class__' => $this->className,
			'data' => $this->data,
		];
	}

	/**
	 * Pomocná metóda pre vytvorenie z poľa (pri načítaní z úložiska)
	 */
	public static function fromArray(array $data): self
	{
		if (!isset($data['__class__']) || !isset($data['data'])) {
			throw new \InvalidArgumentException('Invalid storable structure definition.');
		}
		return new self($data['__class__'], $data['data']);
	}
}