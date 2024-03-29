<?php

/**
 * Aphiria
 *
 * @link      https://www.aphiria.com
 * @copyright Copyright (C) 2023 David Young
 * @license   https://github.com/aphiria/aphiria/blob/1.x/LICENSE.md
 */

declare(strict_types=1);

namespace Aphiria\Framework\Routing\Components;

use Aphiria\Api\ApiGateway;
use Aphiria\Api\Router;
use Aphiria\Application\IComponent;
use Aphiria\DependencyInjection\IContainer;
use Aphiria\DependencyInjection\ResolutionException;
use Aphiria\DependencyInjection\TargetedContext;
use Aphiria\Net\Http\IRequestHandler;
use Aphiria\Routing\Attributes\AttributeRouteRegistrant;
use Aphiria\Routing\IRouteRegistrant;
use Aphiria\Routing\RouteCollection;
use Aphiria\Routing\RouteCollectionBuilder;
use Aphiria\Routing\RouteCollectionBuilderRouteRegistrant;
use Aphiria\Routing\RouteRegistrantCollection;
use Closure;
use RuntimeException;

/**
 * Defines the router component
 */
class RouterComponent implements IComponent
{
    /** @var bool Whether or not attributes are enabled */
    private bool $attributesEnabled = false;
    /** @var list<Closure(RouteCollectionBuilder): void> The list of callbacks that can register route builders */
    private array $callbacks = [];

    /**
     * @param IContainer $container The DI container
     */
    public function __construct(private readonly IContainer $container)
    {
    }

    /**
     * @inheritdoc
     * @throws ResolutionException Thrown if any dependencies could not be resolved
     */
    public function build(): void
    {
        $routeRegistrants = $this->container->resolve(RouteRegistrantCollection::class);

        if ($this->attributesEnabled) {
            /** @var IRouteRegistrant $attributeRouteRegistrant */
            $attributeRouteRegistrant = null;

            if (!$this->container->tryResolve(AttributeRouteRegistrant::class, $attributeRouteRegistrant)) {
                throw new RuntimeException(AttributeRouteRegistrant::class . ' cannot be null if using attributes');
            }

            $routeRegistrants->add($attributeRouteRegistrant);
        }

        $routeRegistrants->add(new RouteCollectionBuilderRouteRegistrant($this->callbacks));
        $routeRegistrants->registerRoutes($this->container->resolve(RouteCollection::class));
        $this->container->for(
            new TargetedContext(ApiGateway::class),
            fn (IContainer $container) => $container->bindFactory(IRequestHandler::class, fn (): IRequestHandler => $this->container->resolve(Router::class))
        );
    }

    /**
     * Enables route attributes
     *
     * @return static For chaining
     */
    public function withAttributes(): static
    {
        $this->attributesEnabled = true;

        return $this;
    }

    /**
     * Adds routes to the registry
     *
     * @param Closure(RouteCollectionBuilder): void $callback The callback that takes in an instance of RouteCollectionBuilder
     * @return static For chaining
     */
    public function withRoutes(Closure $callback): static
    {
        $this->callbacks[] = $callback;

        return $this;
    }
}
