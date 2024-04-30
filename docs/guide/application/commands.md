# Commands

A command is a message that indicates an intention to _change_ the state of the bounded context. It is a _request_ to
perform an action that will result in a change to the bounded context's state. For example, "create a new order",
"remove an attendee from an event", "update a customer's details".

Command messages define the data contract for the information that needs to be provided to perform the action. They are
dispatched to the _command bus_, and executed by _command handlers_.

## Command Messages

Command messages are defined by writing a class that implements the `CommandInterface`. The class should be named
according to the action it represents, and should contain properties that represent the data required to perform the
action. I.e. it defines the data contract for the action. Commands must be immutable.

For example:

```php
namespace App\Modules\EventManagement\Application\UseCases\Commands\CancelAttendeeTicket;

use CloudCreativity\Modules\Application\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;
use VendorName\EventManagement\Shared\Enums\CancellationReasonEnum;

final readonly class CancelAttendeeTicketCommand implements CommandInterface
{
    public function __construct(
        public IdentifierInterface $attendeeId,
        public IdentifierInterface $ticketId,
        public CancellationReasonEnum $reason,
    ) {
    }
}
```

:::tip
Some commands will only need to hold a few values to perform the action - such as in the example above, where the
action can be described by two identifiers and an enum.

Other commands might have significantly more complex data as their action input. It's good practice to make good use
of value objects and "write models" to describe the data required by the command.
:::

## Command Handlers

A command handler is a class that is responsible for performing the action described by a command. It is a _use case_ in
the application layer of the bounded context. The command handler is responsible for validating the command, performing
the action, and updating the state of the bounded context.

Start by expressing the use case as an interface. This defines that given a specific command as input, the handler will
return a specific result. This makes it clear what the use case is, and what it returns.

```php
namespace App\Modules\EventManagement\Application\UseCases\Commands\CancelAttendeeTicket;

use CloudCreativity\Modules\Toolkit\Results\ResultInterface;

interface CancelAttendeeTicketHandlerInterface
{
    /**
     * Cancel the specified attendee's ticket.
     *
     * @param CancelAttendeeTicketCommand $command
     * @return ResultInterface<null>
     */
    public function handle(CancelAttendeeTicketCommand $command): ResultInterface;
}
```

Then you can write the concrete implementation:

```php
namespace App\Modules\EventManagement\Application\UseCases\Commands\CancelAttendeeTicket;

use App\Modules\EventManagement\Application\Ports\Driven\Persistence\AttendeeRepositoryInterface;
use CloudCreativity\Modules\Application\Bus\Middleware\ExecuteInUnitOfWork;
use CloudCreativity\Modules\Application\Messages\DispatchThroughMiddleware;
use CloudCreativity\Modules\Toolkit\Results\Result;

final readonly class CancelAttendeeTicketHandler implements
    CancelAttendeeTicketHandlerInterface,
    DispatchThroughMiddleware
{
    public function __construct(
        private AttendeeRepositoryInterface $attendees,
    ) {
    }

    public function handle(CancelAttendeeTicketCommand $command): Result
    {
        $attendee = $this->attendees->findOrFail($command->attendeeId);

        if (!$attendee->hasTicket($command->ticketId)) {
            return Result::failed('The attendee does not have the specified ticket.');
        }

        $attendee->cancelTicket(
            $command->ticketId,
            $command->reason,
        );

        $this->attendees->update($attendee);

        return Result::ok();
    }

    public function middleware(): array
    {
        return [
            ExecuteInUnitOfWork::class,
        ];
    }
}
```

:::tip
You'll notice from the example above that our command handlers support [middleware](#middleware). In this example, we
are ensuring that the handler executes within a [unit of work](#unit-of-work) - i.e. the action is
performed within a single transaction.

Middleware is optional - if you do not need to use any middleware specific to the handler, your handler does not need to
implement the `DispatchThroughMiddleware` interface.
:::

### Results

We use a result object pattern for returning the _outcome_ of the command - see the
[Results chapter for information on using this object.](../toolkit/results)

This is a simple object that indicates whether the action was successful or not, and can contain additional information
about the outcome. This is a good practice because it makes it clear to the caller what the outcome of the action was,
and allows the caller to handle the outcome appropriately.

Commands must never return the _state of the system_. A command is a request to _change_ the state, and a query should
be used to _retrieve_ the state.

This means typically you should not return too much information in the result object - unless you need to return
something that is _specific to the action_ that was performed. For example, if the action was to create a new entity,
you would need to return the resulting identifier in the result. That would then allow the calling code to dispatch
a query to retrieve the state of the newly created entity, if desired.

## Command Bus

To allow the _outside world_ to execute commands, our bounded context must expose a _command bus_ as a driving port.
Although there is a _generic_ command bus interface, our bounded context needs to expose its _specific_ command bus.

We do this by defining an interface in our application's driving ports.

```php
namespace App\Modules\EventManagement\Application\Ports\Driving\CommandBus;

use CloudCreativity\Modules\Application\Ports\Driving\CommandBus\CommandDispatcher;

interface CommandBus extends CommandDispatcher
{
}
```

And then our adapter (the concrete implementation of the port) is as follows:

```php
namespace App\Modules\EventManagement\Application\Adapters\CommandBus;

use App\Modules\EventManagement\Application\Ports\Driving\CommandBus\CommandBusInterface;
use CloudCreativity\Modules\Application\Bus\CommandDispatcher;

final class CommandBusAdapter extends CommandDispatcher implements
    CommandBusInterface
{
}
```

### Creating a Command Bus

The command dispatcher class that your adapter extended (in the above example) allows you to build a command bus
specific to your domain. You do this by:

1. Binding command handler factories into the command dispatcher; and
2. Binding factories for any middleware used by your bounded context; and
3. Optionally, attaching middleware that runs for all commands dispatched through the command bus.

Factories must always be _lazy_, so that the cost of instantiating command handlers or middleware only occurs if the
handler or middleware are actually being used.

For example:

```php
namespace App\Modules\EventManagement\Application\Adapters\CommandBus;

use App\Modules\EventManagement\Application\UsesCases\Commands\{
    CancelAttendeeTicket\CancelAttendeeTicketCommand,
    CancelAttendeeTicket\CancelAttendeeTicketHandler,
    CancelAttendeeTicket\CancelAttendeeTicketHandlerInterface,
};
use App\Modules\EventManagement\Application\Ports\Driving\CommandBus\CommandBusInterface;
use App\Modules\EventManagement\Application\Ports\Driven\DependencyInjection\ExternalDependenciesInterface;
use CloudCreativity\Modules\Application\Bus\CommandHandlerContainer;
use CloudCreativity\Modules\Application\Bus\Middleware\ExecuteInUnitOfWork;
use CloudCreativity\Modules\Application\Bus\Middleware\LogMessageDispatch;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final class CommandBusAdapterProvider
{
    public function __construct(
        private readonly ExternalDependenciesInterface $dependencies,
    ) {
    }

    public function getCommandBus(): CommandBusInterface
    {
        $bus = new CommandBusAdapter(
            handlers: $handlers = new CommandHandlerContainer(),
            middleware: $middleware = new PipeContainer(),
        );

        /** Bind commands to handler factories */
        $handlers->bind(
            CancelAttendeeTicketCommand::class,
            fn(): CancelAttendeeTicketHandlerInterface => new CancelAttendeeTicketHandler(
                $this->dependencies->getAttendeeRepository(),
            ),
        );

        /** Bind middleware factories */
        $middleware->bind(
            ExecuteInUnitOfWork::class,
            fn () => new ExecuteInUnitOfWork($this->dependencies->getUnitOfWorkManager()),
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

As the presentation and delivery layer is the user of the driving ports, we can now bind the port and its adapter into a
service container. For example, in Laravel:

```php
namespace App\Providers;

use App\Modules\EventManagement\Application\{
    Adapters\CommandBus\CommandBusAdapterProvider,
    Ports\Driving\CommandBus\CommandBusInterface,
};
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class EventManagementServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            CommandBusInterface::class,
            static function (Container $app)  {
                $provider = $app->make(CommandBusAdapterProvider::class);
                return $provider->getCommandBus();
            },
        );
    }
}

```

### Dispatching Commands

You can now dispatch command messages to your bounded context from the _outside world_. For example, if we were using
a single action controller to handle a HTTP request in a Laravel application, we might dispatch a command like this:

```php
namespace App\Http\Controllers\Api\Attendees;

use App\Modules\EventManagement\Application\{
    Ports\Driving\CommandBus\CommandBusInterface,
    UsesCases\Commands\CancelAttendeeTicket\CancelAttendeeTicketCommand,
};
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use Illuminate\Validation\Rule;
use VendorName\EventManagement\Shared\Enums\CancellationReasonEnum;

class CancellationController extends Controller
{
    public function __invoke(
        Request $request,
        CommandBusInterface $bus,
        string $attendeeId,
    ) {
        $validated = $request->validate([
            'ticket' => ['required', 'integer'],
            'reason' => ['required', Rule::enum(CancellationReasonEnum::class)]
        ]);

        $command = new CancelAttendeeTicketCommand(
            attendeeId: new IntegerId((int) $attendeeId),
            ticketId: new IntegerId((int) $validated['ticket']),
            reason: CancellationReasonEnum::from($request['reason']),
        );

        $result = $bus->dispatch($command);

        if ($result->didFail()) {
            // or throw here, if we are not expecting a failure to occur.
            return response()->json([
                'message' => 'The attendee ticket could not be cancelled.',
            ], 422);
        }

        return response()->noContent();
    }
}
```

:::tip
Here you can see that the event management bounded context is entirely [encapsulated.](../concepts/encapsulation)
The outside world uses the combination of the command message, with the driving port it needs to dispatch the message.
Everything else - how your bounded context processes and responds to the action - is hidden as an _internal
implementation detail_ of your domain.
:::

### Queuing Commands

The `dispatch()` method executes the bounded context's logic immediately via a command handler. Or in other words -
commands are dispatched synchronously. But what happens if the presentation and delivery layer does not need to wait
for the result of the command being dispatched, and instead wants the command to be handled in a non-blocking way?

In this scenario, the presentation and delivery layer can choose to queue the command for asynchronous processing. To
indicate that a command should be queued, the `queue()` method is used instead of the `dispatch()` method.

This can be used to execute a command in a non-blocking way. For example, our controller implementation could be
updated to return a `202 Accepted` response to indicate the command has been queued:

```php
namespace App\Http\Controllers\Api\Attendees;

use App\Modules\EventManagement\Application\{
    Ports\Driving\CommandBus\CommandBusInterface,
    UsesCases\Commands\CancelAttendeeTicket\CancelAttendeeTicketCommand,
};
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use Illuminate\Validation\Rule;
use VendorName\EventManagement\Shared\Enums\CancellationReasonEnum;

class CancellationController extends Controller
{
    public function __invoke(
        Request $request,
        CommandBusInterface $bus,
        string $attendeeId,
    ) {
        $validated = $request->validate([
            'ticket' => ['required', 'integer'],
            'reason' => ['required', Rule::enum(CancellationReasonEnum::class)]
        ]);

        $command = new CancelAttendeeTicketCommand(
            attendeeId: new IntegerId((int) $attendeeId),
            ticketId: new IntegerId((int) $validated['ticket']),
            reason: CancellationReasonEnum::from($request['reason']),
        );

        $bus->queue($command);

        return response()->noContent(status: 202);
    }
}
```

To allow commands to be queued, you **must** provide a queue factory to the command bus when creating it. This topic is
covered in the [Asynchronous Processing](../infrastructure/queues#external-queuing) chapter, with specific examples
in the _External Queuing_ section.

## Middleware

Our command bus implementation gives you complete control over how to compose the handling of your commands, via
middleware. Middleware is a powerful way to add cross-cutting concerns to your command handling, such as logging,
transaction management, and so on.

Middleware can be added either to the command bus (so it runs for every command) or to individual command handlers.

To apply middleware to the command bus, you can use the `through()` method on the bus - as shown in the example above.
Middleware is executed in the order it is added to the bus.

To apply middleware to a specific command handler, the handler must implement the `DispatchThroughMiddleware` interface,
as shown in the example handler above. The `middleware()` method should return an array of middleware to run, in the
order they should be executed. Handler middleware are always executed _after_ the bus middleware.

This package provides a number of command middleware, which are described below. Additionally, you can write your own
middleware to suit your specific needs.

### Setup and Teardown

Our `SetupBeforeDispatch` middleware allows setup work to be run before the command is dispatched, and optionally
teardown work when the command has completed.

This allows you to set up any state and guarantee that the state is cleaned up, regardless of the outcome of the
command. The primary use case for this is to boostrap [Domain Services](../domain/services) and to garbage collect any
singleton instances of dependencies.

For example:

```php
use App\Modules\EventManagement\Domain\Services;
use CloudCreativity\Modules\Application\Bus\Middleware\SetupBeforeDispatch;

$middleware->bind(
    SetupBeforeDispatch::class,
    fn () => new SetupBeforeDispatch(function (): Closure {
        // setup domain services
        Services::setEvents(fn() => $this->getDomainEventDispatcher());
        return function (): void {
            // clean up a singleton instance of a unit of work manager.
            $this->unitOfWorkManager = null;
            // teardown the domain services
            Services::tearDown();
        };
    }),
);

$bus->through([
    LogMessageDispatch::class,
    SetupBeforeDispatch::class,
]);
```

Here our setup middleware takes a setup closure as its only constructor argument. This setup closure can optionally
return a closure to do any teardown work. The teardown callback is guaranteed to always be executed, regardless of
whether a command succeeded or failed - and it also runs if an exception is thrown.

If you only need to do teardown work, use the `TeardownAfterDispatch` middleware instead. This takes a single teardown
closure as its only constructor argument:

```php
use CloudCreativity\Modules\Application\Bus\Middleware\TeardownAfterDispatch;

$middleware->bind(
    TeardownAfterDispatch::class,
    fn () => new TeardownAfterDispatch(function (): Closure {
        // clean up a singleton instance of a unit of work manager.
        $this->unitOfWorkManager = null;
    }),
);

$bus->through([
    LogMessageDispatch::class,
    TearDownAfterDispatch::class,
]);
```

### Unit of Work

Ideally command handlers should always be executed in a unit of work. We cover this in detail in the
[Units of Work chapter.](units-of-work.md)

To execute a handler in a unit of work, you will need to use our `ExecuteInUnitOfWork` middleware. You should always
implement this as handler middleware - because typically you need it to be the final middleware that runs before a
handler is invoked. It also makes it clear to developers looking at the command handler that it is expected to run
in a unit of work. The example `CancelAttendeeTicketHandler` above demonstrates this.

An example binding for this middleware is:

```php
use CloudCreativity\Modules\Application\Bus\Middleware\ExecuteInUnitOfWork;

$middleware->bind(
    ExecuteInUnitOfWork::class,
    fn () => new ExecuteInUnitOfWork(
        $this->dependencies->getUnitOfWorkManager(),
    ),
);
```

:::warning
If you're using a unit of work, you should be combining this with our "unit of work domain event dispatcher".
One really important thing to note is that you **must inject both the middleware and the domain event dispatcher with
exactly the same instance of the unit of work manager.**

I.e. use a singleton instance of the unit of work manager. Plus use the teardown middleware (described above) to dispose
of the singleton instance once the command has executed.
:::

### Flushing Deferred Events

If you are not using a unit of work, you will most likely be using our deferred domain event dispatcher. This is covered
in the [Domain Events chapter.](./domain-events)

When using this dispatcher, you will need to use our `FlushDeferredEvents` middleware. You should always
implement this as handler middleware - because typically you need it to be the final middleware that runs before a
handler is invoked. I.e. this is an equivalent middleware to the unit of work middleware.

An example binding for this middleware is:

```php
use CloudCreativity\Modules\Application\Bus\Middleware\FlushDeferredEvents;

$middleware->bind(
    FlushDeferredEvents::class,
    fn () => new FlushDeferredEvents(
        $this->eventDispatcher,
    ),
);
```

:::warning
When using this middleware, it is important that you inject it with a singleton instance of the deferred event
dispatcher. This must be the same instance that is exposed to your domain layer as a service.
:::

### Logging

Use our `LogMessageDispatch` middleware to log the dispatch of a command, and the result. The middleware takes a
[PSR Logger](https://php-fig.org/psr/psr-3/).

```php
use CloudCreativity\Modules\Application\Bus\Middleware\LogMessageDispatch;

$middleware->bind(
    LogMessageDispatch::class,
    fn (): LogMessageDispatch => new LogMessageDispatch(
        $this->dependencies->getLogger(),
    ),
);

$bus->through([LogMessageDispatch::class]);
```

The middleware will log a message before executing the command, with a log level of _debug_. It will then log a message
after the command has executed, with a log level of _info_.

You can adjust the log levels via constructor arguments. For example, if we wanted the _before dispatch_ message to be
_info_, and the _dispatched_ message to be _notice_:

```php
use Psr\Log\LogLevel;

$middleware->bind(
    LogMessageDispatch::class,
    fn (): LogMessageDispatch => new LogMessageDispatch(
        logger: $this->dependencies->getLogger(),
        dispatchLevel: LogLevel::INFO,
        dispatchedLevel: LogLevel::NOTICE,
    ),
);

$bus->through([LogMessageDispatch::class]);
```

#### Log Context

When logging that the command is being dispatched, we log all the public properties of the command message as log
context. This is useful for debugging, as it allows you to see the data that was provided.

However, there can be scenarios where you want to control what context is logged. A good example is a command message
that has sensitive customer data on it that you do not want to end up in your logs. To control the log context,
implement the `ContextProviderInterface` on your command message:

```php
use CloudCreativity\Modules\Toolkit\Loggable\ContextProviderInterface;

final readonly class CancelAttendeeTicketCommand implements
  CommandInterface,
  ContextProviderInterface
{
    public function __construct(
        public IdentifierInterface $attendeeId,
        public IdentifierInterface $ticketId,
        public CancellationReasonEnum $reason,
    ) {
    }

    public function context(): array
    {
        return [
            'attendeeId' => $this->attendeeId,
        ];
    }
}
```

### Writing Middleware

You can write your own middleware to suit your specific needs. Middleware is a simple invokable class, with the
following signature:

```php
namespace App\Modules\EventManagement\Application\Adapters\Middleware;

use Closure;
use CloudCreativity\Modules\Application\Bus\Middleware\CommandMiddlewareInterface;
use CloudCreativity\Modules\Application\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

final class MyMiddleware implements CommandMiddlewareInterface
{
    /**
     * Execute the middleware.
     *
     * @param CommandInterface $command
     * @param Closure(CommandInterface): ResultInterface<mixed> $next
     * @return ResultInterface<mixed>
     */
    public function __invoke(
        CommandInterface $command, 
        Closure $next,
    ): ResultInterface
    {
        // code here executes before the handler

        $result = $next($command);

        // code here executes after the handler

        return $result;
    }
}
```

:::tip
If you're writing middleware that is only meant to be used for a specific command, do not implement the
`CommandMiddlewareInterface`. Instead, use the same signature but change the type-hint for the command to the command
class your middleware is designed to be used with.
:::

If you want to write middleware that can be used with both commands and queries, implement the `BusMiddlewareInterface`
instead:

```php
namespace App\Modules\EventManagement\Application\Adapters\Middleware;

use Closure;
use CloudCreativity\Modules\Application\Bus\Middleware\BusMiddlewareInterface;
use CloudCreativity\Modules\Application\Messages\CommandInterface;
use CloudCreativity\Modules\Application\Messages\QueryInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

class MyBusMiddleware implements BusMiddlewareInterface
{
    /**
     * Handle the command or query.
     *
     * @param CommandInterface|QueryInterface $message
     * @param Closure(CommandInterface|QueryInterface): ResultInterface<mixed> $next
     * @return ResultInterface<mixed>
     */
    public function __invoke(
        CommandInterface|QueryInterface $message, 
        Closure $next,
    ): ResultInterface
    {
        // code here executes before the handler

        $result = $next($command);

        // code here executes after the handler

        return $result;
    }
}
```
