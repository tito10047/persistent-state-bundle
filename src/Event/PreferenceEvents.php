<?php

namespace Tito10047\PersistentStateBundle\Event;

final class PreferenceEvents
{
	/**
	 * Vyvolaný PREDtým, než sa dáta pošlú do serializéra a storage.
	 * Umožňuje zmeniť hodnotu alebo zastaviť proces (stopPropagation).
	 *
	 * @Event("App\PersistentStateBundle\Event\PreferenceEvent")
	 */
	public const PRE_SET = 'persistent.pre_set';

	/**
	 * Vyvolaný PO tom, čo boli dáta úspešne odovzdané do storage.
	 * Pozor: Ak je storage Doctrine s auto_flush=false, dáta ešte nemusia byť v DB, len v pamäti.
	 *
	 * @Event("App\PersistentStateBundle\Event\PreferenceEvent")
	 */
	public const POST_SET = 'persistent.post_set';
}