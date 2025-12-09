<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Composer\InstalledVersions;

return static function (ContainerConfigurator $containerConfigurator): void {

	$configurations = [
		'dbal' => [
			'url'                         => '%env(resolve:DATABASE_URL)%',
			// 'server_version' => '16', // Odkomentuj ak potrebuješ
			'profiling_collect_backtrace' => '%kernel.debug%',
		],
		'orm'  => [
			'naming_strategy'              => 'doctrine.orm.naming_strategy.underscore_number_aware',
			'auto_mapping'                 => true,
			'mappings'                     => [
				'App' => [
					'type'      => 'attribute',
					'is_bundle' => false,
					'dir'       => '%kernel.project_dir%/tests/App/AssetMapper/Src/Entity',
					'prefix'    => 'Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Entity',
					'alias'     => 'App',
				],
			],
		],
	];

	$testConnectionConfig = [
		'dbname_suffix' => '_test%env(default::TEST_TOKEN)%',
	];

	// Tu je tá kľúčová logika pre kompatibilitu:
	// Kontrolujeme, či je nainštalovaný DBAL a či je verzia nižšia ako 4.0.0
	// Composer\InstalledVersions je dostupný automaticky v modernom PHP
	if (class_exists(InstalledVersions::class) &&
		InstalledVersions::isInstalled('doctrine/dbal') &&
		version_compare(InstalledVersions::getVersion('doctrine/dbal'), '4.0.0', '<')
	) {
		$testConnectionConfig['use_savepoints'] = true;
		$configurations['orm']['report_fields_where_declared']=true;
	}
	// ----------------------------------------------------------------------
	// 1. GLOBAL KONFIGURÁCIA (zodpovedá hornej časti tvojho yaml)
	// ----------------------------------------------------------------------
	$containerConfigurator->extension('doctrine', $configurations);

	// ----------------------------------------------------------------------
	// 2. TEST ENVIRONMENT OVERRIDE (zodpovedá when@test)
	// ----------------------------------------------------------------------
	if ($containerConfigurator->env() === 'test') {

		// Aplikujeme override konfiguráciu pre testy
		$containerConfigurator->extension('doctrine', [
			'dbal' => [
				'connections' => [
					'default' => $testConnectionConfig,
				],
			],
		]);
	}
};