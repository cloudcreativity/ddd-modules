# Dependency Injection

In our hexagonal architecture, the application layer defines _driven ports_ for the external dependencies it needs to
interact with. The adapters of these ports - the concrete implementations - are found in the infrastructure layer.

Which raises the question - how does the application get access to the adapters from the infrastructure layer?

For example, when constructing a command bus (the adapter for a _driving port_), the application layer will have to
inject the infrastructure adapters into command handlers.

:::info
This chapter covers our approach to this dependency injection, to illustrate how you can solve this problem. However, it
is not the only approach - so feel free to take a different approach if you prefer.
:::

## Service Locators

Surely the solution is as simple as injecting a _service locator_, aka _service container_, into the application layer?

You could take this approach, but we choose not to.

While there is a whole discourse on whether or not service locators are anti-pattern, our rationale for not using a
service locator is essentially that it breaks the _encapsulation principle_.

By following the techniques described in this package, you will have constructed fully encapsulated domain and
application layers - with really clear boundaries defined by ports.

However, if we inject a service locator into our application layer, we arguably break that encapsulation. Why? Because
the service locator would allow our application layer to resolve _any_ service. This is particularly the case for a lot
of modern service locator implementations that use Reflection to build any requested service - e.g. the Laravel
container.

But our application layer should not rely on _any_ service - it can only depend on the _specific_ infrastructure
services, defined by ports. Therefore, we never expose a service locator to any of our bounded contexts.

## External Dependencies

Instead, we define the dependencies that the application needs via an external dependencies _driven port_.

By using a driven port, the application expects the infrastructure layer to provide an adapter for this port. So in
effect, we push the logic for creating driven port adapters into the infrastructure layer. This feels like the best
place for it as this is where the adapters live.

This external dependencies port in effect provides other driven ports. For example:

```php
namespace App\Modules\EventManagement\Application\Ports\Driven\DependencyInjection;

use App\Modules\EventManagement\Application\Ports\Driven\Persistence\AttendeeRepository;
use App\Modules\EventManagement\Application\Ports\Driven\Queue\Queue;
use Psr\Log\LoggerInterface;

interface ExternalDependencies
{
    public function getLogger(): LoggerInterface;
    
    public function getQueue(): Queue;
    
    public function getAttendeeRepository(): AttendeeRepository;
    
    // ...other methods
}
```

These external dependencies can then be type-hinted wherever the application layer needs to use them. For example, when
creating a command bus:

```php
namespace App\Modules\EventManagement\Application\Adapters\CommandBus;

use App\Modules\EventManagement\Application\UsesCases\Commands\{
    CancelAttendeeTicket\CancelAttendeeTicketCommand,
    CancelAttendeeTicket\CancelAttendeeTicketHandler,
};
use App\Modules\EventManagement\Application\Ports\Driving\CommandBus\CommandBus;
use App\Modules\EventManagement\Application\Ports\Driven\DependencyInjection\ExternalDependencies;
use CloudCreativity\Modules\Application\Bus\CommandHandlerContainer;
use CloudCreativity\Modules\Application\Bus\Middleware\ExecuteInUnitOfWork;
use CloudCreativity\Modules\Application\Bus\Middleware\LogMessageDispatch;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final class CommandBusAdapterProvider
{
    public function __construct(
        private readonly ExternalDependencies $dependencies,
    ) {
    }

    public function getCommandBus(): CommandBus
    {
        $bus = new CommandBusAdapter(
            handlers: $handlers = new CommandHandlerContainer(),
            middleware: $middleware = new PipeContainer(),
        );

        /** Bind commands to handler factories */
        $handlers->bind(
            CancelAttendeeTicketCommand::class,
            fn() => new CancelAttendeeTicketHandler(
                $this->dependencies->getAttendeeRepository(),
            ),
        );

        $middleware->bind(
            LogMessageDispatch::class,
            fn () => new LogMessageDispatch(
                $this->dependencies->getLogger(),
            ),
        );

        /** Attach middleware that runs for all commands */
        $bus->through([
            LogMessageDispatch::class,
        ]);

        return $bus;
    }
}
```

### Many Dependencies

If your application layer ends up with a lot of driven ports, then you will find that this external dependencies
interface gets very long. We handle this by grouping dependencies into several interfaces, accessed via the external
dependencies interface.

For example, you often end up with a lot of repositories as your bounded context grows in complexity. We would put these
on a `RepositoryProvider` interface, that can be accessed via the external dependencies interface:

```php
namespace App\Modules\EventManagement\Application\Ports\Driven\DependencyInjection;

use App\Modules\EventManagement\Application\Ports\Driven\Persistence\RepositoryProvider;
use App\Modules\EventManagement\Application\Ports\Driven\Queue\Queue;
use Psr\Log\LoggerInterface;

interface ExternalDependencies
{
    public function getLogger(): LoggerInterface;
    
    public function getQueue(): Queue;
    
    public function getRepositories(): RepositoryProvider;
}
```

Then in our persistence layer ports:

```php
namespace App\Modules\EventManagement\Application\Ports\Driven\Persistence;

interface RepositoryProvider
{
    public function getAttendees(): AttendeeRepository;
    
    public function getSalesReports(): SalesReportRepository;
    
    // ...other repositories
}
```

This means will still only need to inject the external dependencies port wherever the application needs to access
dependencies.

### Singleton Instances

Our approach is that the external dependencies interface always returns a new instance for whatever dependency is needed
by the application layer.

If the application layer needs a singleton instance of an external dependency, we always handle this in the application
layer. This is because the application layer _has knowledge_ that it needs a singleton instance. So it should handle the
lifetime of that instance - allowing it to set it up and tear it down as needed.

This also helps make the external dependencies port _predictable_. If some methods returned singletons and others did
not, how does the application layer know what it has been given - a singleton, or a new instance? Also, the application
layer would then not be able to tear down any singletons when it knew that they were no longer required, e.g. after
dispatching a command.