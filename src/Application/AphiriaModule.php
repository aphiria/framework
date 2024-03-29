<?php

/**
 * Aphiria
 *
 * @link      https://www.aphiria.com
 * @copyright Copyright (C) 2023 David Young
 * @license   https://github.com/aphiria/aphiria/blob/1.x/LICENSE.md
 */

declare(strict_types=1);

namespace Aphiria\Framework\Application;

use Aphiria\Application\IModule;

/**
 * Defines a base module for an Aphiria application
 */
abstract class AphiriaModule implements IModule
{
    use AphiriaComponents;
}
