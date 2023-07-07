<?php

use Rector\Symfony\Set\SymfonySetList;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
	$rectorConfig->symfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml');

	$rectorConfig->sets([
		SymfonySetList::SYMFONY_53,
		SymfonySetList::SYMFONY_CODE_QUALITY,
		SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
	]);
};
