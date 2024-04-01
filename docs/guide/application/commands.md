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
namespace App\Modules\EventManagement\BoundedContext\Application\Commands\CancelAttendeeTicket;

use App\Modules\EventManagement\Shared\Enums\CancellationReasonEnum;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

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

Start by expressing the use-case as an interface. This defines that given a specific command as input, the handler will
return a specific result. This makes it clear what the handler does, and what it returns.

```php
namespace App\Modules\EventManagement\BoundedContext\Application\Commands\CancelAttendeeTicket;

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
namespace App\Modules\EventManagement\BoundedContext\Application\Commands\CancelAttendeeTicket;

use App\Modules\EventManagement\BoundedContext\Infrastructure\Persistence\AttendeeRepositoryInterface;
use CloudCreativity\Modules\Bus\Middleware\ExecuteInUnitOfWork;
use CloudCreativity\Modules\Toolkit\Messages\DispatchThroughMiddleware;
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
performed within a single database transaction.

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

To allow the _outside world_ to execute commands, our bounded context must expose a _command bus_. Although there is
a _generic_ command bus interface, our bounded context needs to expose the _specific_ command bus for the bounded
context.

We do this by defining an interface, which is the interface we expose on our bounded context's
[application interface.](../concepts/encapsulation#application-interface)

```php
namespace App\Modules\EventManagement\BoundedContext\Application\Commands;

use CloudCreativity\Modules\Bus\CommandDispatcherInterface;

interface EventManagementCommandBusInterface extends CommandDispatcherInterface
{
}
```

And then a concrete implementation:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\Commands;

use CloudCreativity\Modules\Bus\CommandDispatcher;

final class EventManagementCommandBus extends CommandDispatcher implements
    EventManagementCommandBusInterface
{
}
```

### Creating a Command Bus

As our bounded context's application exposes a command bus, it will need to create an instance of the command bus.
Our command dispatcher class that you extended in the example above allows you to build a command bus
specific to your domain. You do this by:

1. Binding command handler factories into the command dispatcher; and
2. Binding factories for any middleware used by your bounded context; and
3. Optionally, attaching middleware that runs for all commands dispatched through the command bus.

Factories must always be _lazy_, so that the cost of instantiating command handlers or middleware only occurs if the
handler or middleware are actually being used.

For example:

```php
namespace App\Modules\EventManagement\BoundedContext\Application;

use App\Modules\EventManagement\BoundedContext\Application\Commands\{
    EventManagementCommandBus,
    EventManagementCommandBusInterface,
    CancelAttendeeTicket\CancelAttendeeTicketCommand,
    CancelAttendeeTicket\CancelAttendeeTicketHandler,
    CancelAttendeeTicket\CancelAttendeeTicketHandlerInterface,
};
use CloudCreativity\Modules\Bus\{
    CommandHandlerContainer,
    Middleware\ExecuteInUnitOfWork,
    Middleware\LogMessageDispatch,
};
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final class EventManagementApplication implements EventManagementApplicationInterface
{
    // ...other methods

    public function getCommandBus(): EventManagementCommandBusInterface
    {
        $bus = new EventManagementCommandBus(
            handlers: $handlers = new CommandHandlerContainer(),
            pipeline: $middleware = new PipeContainer(),
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
            fn () => new ExecuteInUnitOfWork($this->getUnitOfWorkManager()),
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

:::tip
As your bounded context grows, you may find that you have a lot of command handlers and middleware. In this scenario,
it may be best to move the creation of your command bus to a dedicated factory class.
:::

### Dispatching Commands

You can now dispatch command messages to your bounded context from the _outside world_. For example, if we were using
a single action controller to handle a HTTP request in a Laravel application, we might dispatch a command like this:

```php
namespace App\Http\Controllers\Api\Attendees;

use App\Modules\EventManagement\BoundedContext\Application\Commands\{
    CancelAttendeeTicket\CancelAttendeeTicketCommand,
    EventManagementCommandBusInterface,
};
use App\Modules\EventManagement\Shared\Enums\CancellationReasonEnum;
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use Illuminate\Validation\Rule;

class CancellationController extends Controller
{
    public function __invoke(
        Request $request,
        EventManagementCommandBusInterface $bus,
        string $attendeeId,
    ) {
        $validated = $request->validate([
            'ticket' => ['required', 'integer'],
            'reason' => ['required', Rule::enum(CancellationReasonEnum::class)]
        ]);

        $command = new CancelAttendeeTicketCommand(
            attendeeId: new IntegerId((int) $attendeeId),
            ticketId: new IntegerId((int) $validated->ticket),
            reason: CancellationReasonEnum::from($request->reason),
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
The outside world uses the combination of the command message, with the bus interface it needs to dispatch the message
to. Everything else - how your bounded context processes and responds to the action - is hidden as an _internal
implementation detail_ of your domain.
:::

## Asynchronous Commands

Modern PHP frameworks provide implementations that allow you to queue work for asynchronous processing. This is
advantageous, as it allows expensive operations to be executed in a non-blocking way. It also gives developers
capabilities like retry and back-off - which typically increase the resilience implementations.

Our command bus provides an abstraction that allows the _outside world_ to queue commands for execution, rather than
dispatching them synchronously. However, the knowledge of how to queue this is encapsulated within the bounded context.

:::tip
Need to queue work that is an internal implementation detail of your bounded context? I.e. that will never be
dispatched by the _outside world_? Check out the [Asynchronous Processing chapter.](../infrastructure/queues)
:::

### Queuing Commands

To queue a command for asynchronous execution, use the `queue()` method on the command bus - instead of the `dispatch()`
method.

For example, the cancellation of an attendee's ticket could be performed asynchronously if we update the controller
implementation as follows:

```php
class CancellationController extends Controller
{
    public function __invoke(
        Request $request,
        EventManagementCommandBusInterface $bus,
        string $attendeeId,
    ) {
        $validated = $request->validate([
            'ticket' => ['required', 'integer'],
            'reason' => ['required', Rule::enum(CancellationReasonEnum::class)]
        ]);

        $command = new CancelAttendeeTicketCommand(
            attendeeId: new IntegerId((int) $attendeeId),
            ticketId: new IntegerId((int) $validated->ticket),
            reason: CancellationReasonEnum::from($request->reason),
        );

        $bus->queue($command);

        return response()->noContent();
    }
}
```

There is some additional setup required to get this working - we must provide the command bus with an _enqueuer_
component.

An _enqueuer_ is a component that is responsible for adding items to a queue. For our command bus, it is a provided
component that encapsulate the knowledge of how to push a command on to a queue. This is effectively an abstraction
that allows you to integrate our command bus with any queue implementation that you are using. To illustrate this, we
will use Laravel's queue as an example.

### Laravel Example

Laravel comes with a full-featured queue implementation. It is easy to use this with our command bus.

Firstly, create a Laravel queue job that can dispatch a command. Here is an example of such a job for the event
management bounded context:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\Commands;

use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class DispatchCommandJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    
    public function __construct(
        public readonly CommandInterface $command
    ) {
    }
    
    public function handle(EventManagementCommandBusInterface $bus): void
    {
        $result = $bus->dispatch($this->command);
        
        if ($result->didFail()) {
            throw new \RuntimeException(
                'Not expecting command to fail: ' . $result->error(),
            );
        }
    }
}
```

:::tip
Here the job is intentionally in our bounded context's application namespace, rather than Laravel's default location
of `App\Jobs`. How commands should be executed asynchronously is an internal implementation detail of our bounded
context - rather than something that is exposed to the outside world.

This makes sense when you think of the encapsulation that is key to the modular approach that this package takes.
Commands define what the outside world can dispatch, but there is no exposure of how a bounded context processes this
command. Therefore, only the bounded context can understand how to queue it. For example, if back-off or retry is
needed for a specific command, only the bounded context can understand that as it knows how the command is processed.
:::

We can then add this to our command bus when we create it:

```php
use App\Modules\EventManagement\BoundedContext\Application\Commands\{
    DispatchCommandJob,
    EventManagementCommandBus,
    EventManagementCommandBusInterface,
};
use CloudCreativity\Modules\Bus\{
    CommandHandlerContainer,
    Queue\CommandEnqueuerInterface,
};
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

final class EventManagementApplication implements EventManagementApplicationInterface
{
    // ...other methods

    public function getCommandBus(): EventManagementCommandBusInterface
    {
        $bus = new EventManagementCommandBus(
            handlers: $handlers = new CommandHandlerContainer(),
            pipeline: $middleware = new PipeContainer(),
            enqueuer: function (CommandInterface $command): void {
                DispatchCommandJob::dispatch($command);
            },
        );

        // ...command handler bindings.
        // ...middleware bindings

        return $bus;
    }
}
```

In this example, we provide the command bus with a closure that can be used as an enqueuer. This is the simplest way
of creating an enqueuer. You can customise this further using our `ClosureEnqueuer` class, or write a concrete class
yourself by implementing the enqueuer interface.

### Closure Enqueuer

As shown in the above example, you can provide a simple closure to our command bus, and it will be used to queue
commands. However, there may be scenarios where you want to customise this further. Our `ClosureEnqueuer` class
allows you to do this.

Create a closure enqueuer by providing it with the closure that will queue commands by default:

```php
use CloudCreativity\Modules\Bus\Queue\ClosureEnqueuer;

$enqueuer = new ClosureEnqueuer(
    function (CommandInterface $command): void {
        DispatchCommandJob::dispatch($command);
    },
);
```

If needed, you can configure additional closures that handle specific jobs. For example:

```php
use CloudCreativity\Modules\Bus\Queue\ClosureEnqueuer;

$enqueuer = new ClosureEnqueuer(
    function (CommandInterface $command): void {
        DispatchCommandJob::dispatch($command);
    },
);

$enqueuer->register(
    CancelAttendeeTicketCommand::class,
    function (CancelAttendeeTicketCommand $command): void {
        DispatchCommandJob::dispatch($command)->onQueue('cancellations');
    },
);
```

It is also possible to add middleware to the closure implementation. See the 
[Enqueuer Middleware section](#enqueuer-middleware) for more details.

### Writing an Enqueuer

Although the closure enqueuer implementation is pretty flexible, there may be times where it would be better for you
to write a concrete class instead. For example, if integrating with your queue implementation is too complex to
encapsulate in a simple closure.

Simply write a class that implements this interface, and provide it to the command bus:

```php
namespace CloudCreativity\Modules\Bus\Queue;

use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

interface CommandEnqueuerInterface
{
    /**
     * Add the command to the queue.
     *
     * @param CommandInterface $command
     * @return void
     */
    public function queue(CommandInterface $command): void;
}
```

:::tip
If you want to add middleware to your custom implementation, take a look at how that is implemented in our
`ClosureEnqueuer` class.
:::

## Dispatch Middleware

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

Our `SetupBeforeDispatch` middleware allows your to run setup work before the command is dispatched, and optionally
teardown work when the command has completed.

This allows you to set up any state and guarantee that the state is cleaned up, regardless of the outcome of the
command. The primary use case for this is to boostrap [Domain Services](../domain/services).

For example:

```php
use App\Modules\EventManagement\BoundedContext\Domain\Services;
use CloudCreativity\Modules\Bus\Middleware\SetupBeforeDispatch;

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
use CloudCreativity\Modules\Bus\Middleware\TeardownAfterDispatch;

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
[Units of Work chapter.](../infrastructure/units-of-work)

To execute a handler in a unit of work, you will need to use our `ExecuteInUnitOfWork` middleware. You should always
implement this as handler middleware - because typically you need it to be the final middleware that runs before a
handler is invoked. It also makes it clear to developers looking at the command handler that it is expected to run
in a unit of work. The example `CancelAttendeeTicketHandler` above demonstrates this.

An example binding for this middleware is:

```php
use CloudCreativity\Modules\Bus\Middleware\ExecuteInUnitOfWork;

$middleware->bind(
    ExecuteInUnitOfWork::class,
    fn () => new ExecuteInUnitOfWork($this->getUnitOfWorkManager()),
);
```

:::warning
If you're using a unit of work, you should be combining this with our "unit of work domain event dispatcher".
One really important thing to note is that you **must inject both the middleware and the domain event dispatcher with
exactly the same instance of the unit of work manager.**

I.e. use a singleton instance of the unit of work manager. Plus use the teardown middleware (described above) to dispose
of the singleton instance once the command has executed.
:::

### Logging

Use our `LogMessageDispatch` middleware to log the dispatch of a command, and the result. The middleware takes a
[PSR Logger](https://php-fig.org/psr/psr-3/).

```php
use CloudCreativity\Modules\Bus\Middleware\LogMessageDispatch;

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
namespace App\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

final class MyMiddleware
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
If you're writing middleware that is only meant to be used for a specific command, type-hint that command instead of
the generic `CommandInterface`.
:::

## Enqueuer Middleware

Our `ClosureEnqueuer` class can also be configured with middleware. Provide a pipe container as the second constructor
argument, which can be configured with middleware. Then use the `through()` method to define which middleware should
be run:

```php
use CloudCreativity\Modules\Bus\Queue\ClosureEnqueuer;
use CloudCreativity\Modules\Bus\Middleware\LogPushedToQueue;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

$enqueuer = new ClosureEnqueuer(
    fn: function (CommandInterface $command): void {
        DispatchCommandJob::dispatch($command);
    },
    middleware: $middleware = new PipeContainer(),
);

$enqueuer->through([LogPushedToQueue::class]);

$middleware->bind(
    LogPushedToQueue::class, 
    fn () => new LogPushedToQueue($this->dependencies->getLogger()), 
);
```

### Queue Logging

To log a command being pushed onto a queue, use the `LogPushedToQueue` middleware. This works in exactly the same way
as the `LogMessageDispatch` command dispatch middleware described above. For example:

```php
use CloudCreativity\Modules\Bus\Middleware\LogPushedToQueue;

$middleware->bind(
    LogPushedToQueue::class,
    fn (): LogPushedToQueue => new LogPushedToQueue(
        $this->dependencies->getLogger(),
    ),
);

$enqueuer->through([LogPushedToQueue::class]);
```

See the section above on the `LogMessageDispatch` for customising log levels. Additionally, the log context can be
customised by implementing the `ContextProviderInterface` on the command class.

### Writing Middleware

If you are writing a middleware for queuing commands via an enqueuer, the signature is as follows:

```php
namespace App\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

final class MyEnqueuerMiddleware
{
    /**
     * Execute the middleware.
     *
     * @param CommandInterface $command
     * @param Closure(CommandInterface): void $next
     * @return void
     */
    public function __invoke(CommandInterface $command, Closure $next): void
    {
        // executes before the enqueuer pushes command to the queue

        $next($command);

        // executes after the command is pushed to the queue.
    }
}
```