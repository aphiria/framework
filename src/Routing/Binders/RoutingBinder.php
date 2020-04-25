<?php

/**
 * Aphiria
 *
 * @link      https://www.aphiria.com
 * @copyright Copyright (C) 2020 David Young
 * @license   https://github.com/aphiria/aphiria/blob/master/LICENSE.md
 */

declare(strict_types=1);

namespace Aphiria\Framework\Routing\Binders;

use Aphiria\Configuration\GlobalConfiguration;
use Aphiria\Configuration\MissingConfigurationValueException;
use Aphiria\DependencyInjection\Binders\Binder;
use Aphiria\DependencyInjection\IContainer;
use Aphiria\Routing\Annotations\AnnotationRouteRegistrant;
use Aphiria\Routing\Caching\FileRouteCache;
use Aphiria\Routing\Caching\IRouteCache;
use Aphiria\Routing\Matchers\IRouteMatcher;
use Aphiria\Routing\Matchers\TrieRouteMatcher;
use Aphiria\Routing\RouteCollection;
use Aphiria\Routing\RouteRegistrantCollection;
use Aphiria\Routing\UriTemplates\AstRouteUriFactory;
use Aphiria\Routing\UriTemplates\Compilers\Tries\Caching\FileTrieCache;
use Aphiria\Routing\UriTemplates\Compilers\Tries\Caching\ITrieCache;
use Aphiria\Routing\UriTemplates\Compilers\Tries\TrieFactory;
use Aphiria\Routing\UriTemplates\IRouteUriFactory;
use Doctrine\Common\Annotations\AnnotationException;

/**
 * Defines the routing binder
 */
final class RoutingBinder extends Binder
{
    /**
     * @inheritdoc
     * @throws MissingConfigurationValueException Thrown if the config is missing values
     * @throws AnnotationException Thrown if PHP is not configured to handle scanning for annotations
     */
    public function bind(IContainer $container): void
    {
        $routes = new RouteCollection();
        $container->bindInstance(RouteCollection::class, $routes);
        $trieCache = new FileTrieCache(GlobalConfiguration::getString('aphiria.routing.trieCachePath'));
        $routeCache = new FileRouteCache(GlobalConfiguration::getString('aphiria.routing.routeCachePath'));
        $container->bindInstance(IRouteCache::class, $routeCache);
        $container->bindInstance(ITrieCache::class, $trieCache);

        if (getenv('APP_ENV') === 'production') {
            $routeRegistrants = new RouteRegistrantCollection($routeCache);
        } else {
            $routeRegistrants = new RouteRegistrantCollection();
        }

        $container->bindInstance(RouteRegistrantCollection::class, $routeRegistrants);

        // Bind as a factory so that our app builders can register all routes prior to the routes being built
        $container->bindFactory(
            [IRouteMatcher::class, TrieRouteMatcher::class],
            static function () use ($routes, $routeRegistrants, $trieCache) {
                $routeRegistrants->registerRoutes($routes);

                if (\getenv('APP_ENV') === 'production') {
                    $trieFactory = new TrieFactory($routes, $trieCache);
                } else {
                    $trieFactory = new TrieFactory($routes);
                }

                return new TrieRouteMatcher(($trieFactory)->createTrie());
            },
            true
        );

        $container->bindInstance(IRouteUriFactory::class, new AstRouteUriFactory($routes));

        // Register some route annotation dependencies
        $routeAnnotationRegistrant = new AnnotationRouteRegistrant(GlobalConfiguration::getArray('aphiria.routing.annotationPaths'));
        $container->bindInstance(AnnotationRouteRegistrant::class, $routeAnnotationRegistrant);
    }
}
