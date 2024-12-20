<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->ignoreErrorsOnPackage('friendsofsymfony/http-cache', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreUnknownClasses([\SensitiveParameter::class])
;
