# Asynchronous Processing

Modern PHP frameworks provide implementations that allow you to queue work for asynchronous processing. This is
advantageous for a number of reasons, including:

1. allowing expensive operations to be executed separately in a non-blocking way, for example allowing an HTTP response
   to be returned to a client immediately.
2. providing retry and back-off capabilities when executing work that involves communication with external services -
   e.g. microservices in your architecture and/or third-party applications.

When composing the execution of your bounded context's domain, you should use asynchronous processing to improve both
the scalability and fault tolerance of your implementation. This package embraces this, by allowing commands to be
queued for asynchronous execution.

This is achieved via a queue abstraction, that allows you to plug the queuing of commands into any PHP queue
implementation you want to use.

:::tip
Of the three defined messages - commands, queries and integration events - only commands can be pushed to the queue.
This is because queued work will mutate the state of the bounded context, i.e. is a "command message" in the CQRS
pattern followed by this package.

If you want to asynchronously publish an integration event, an [Outbox pattern](./outbox-inbox.md) is the best approach.
:::

## Queuing Commands

There are two scenarios where commands can be queued:

- **Commands executed as an internal implementation of your bounded context.** A typical example is where a domain event
  listener queues work that needs to happen as a consequence of the domain event, but the execution of the work does not
  need to occur immediately. This is a good approach for decomposing potentially long-running or highly complex
  processes into asynchronously executed steps.
- **Commands dispatched by the presentation and delivery layer** - but where the presentation layer does not need to
  wait for the result of the command, i.e. prefers to return early. For example, where a HTTP controller action wants to
  dispatch a command but intends to return a `202 Accepted` response rather than waiting for the result. We refer to
  this as _external queueing_, as the request to queue the command comes from outside your bounded context.

### Internal Queuing

Use domain events to queue work for the bounded context to process asynchronously.

For example, if we needed to recalculate a sales report as a result of an attendee cancelling their ticket. The work is
a command message, because it will alter the state of the sales report within our bounded context.

A domain event listener would be used in this scenario:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\Listeners;

use App\Modules\EventManagement\BoundedContext\Application\Commands\RecalculateSalesAtEventCommand;
use App\Modules\EventManagement\BoundedContext\Domain\Events\AttendeeTicketWasCancelled;
use CloudCreativity\Modules\Infrastructure\Queue\QueueInterface;

final readonly class QueueTicketSalesReportRecalculation
{
    public function __construct(private QueueInterface $queue)
    {
    }

    public function handle(AttendeeTicketWasCancelled $event): void
    {
        $this->queue->push(new RecalculateSalesAtEventCommand(
            $event->eventId,
        ));
    }
}
```

:::tip
Notice the listener is in the application layer. This is because it is combining an application concern (the need to
alter the state of the bounded context via a command message) with an infrastructure component (the queue abstraction).
:::

We can also push multiple command messages onto the queue at once. This is useful where a listener may generate multiple
commands from a single domain event.

For example, imagine our bounded context allows tickets to be transferred between multiple in-person events. When a
ticket is transferred, we would need to recalculate the sales report for multiple events:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\Listeners;

use App\Modules\EventManagement\BoundedContext\Application\Commands\RecalculateSalesAtEventCommand;
use App\Modules\EventManagement\BoundedContext\Domain\Events\TicketsTransferred;
use CloudCreativity\Modules\Infrastructure\Queue\QueueInterface;
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;

final readonly class QueueReportRecalculationsOnTicketTransfer
{
    public function __construct(private QueueInterface $queue)
    {
    }

    public function handle(TicketsTransferred $event): void
    {
        $commands = array_map(
            fn (IntegerId $id) => new RecalculateSalesAtEventCommand($id),
            $event->affectedEventIds,
        );
        
        $this->queue->push($commands);
    }
}
```

### External Queuing

Commands define the use-cases of your module - specifically, the use-cases where there is an intent to change the state
of the bounded context. They are part of your bounded context's application interface, and therefore can be dispatched
by the presentation and delivery layer.

It is reasonable for there to be scenarios where the presentation and delivery later intends to change the state of the
bounded context via a command, but does not need to wait for the result of that change. Our command bus implementation
allows this to be signalled by exposing a `queue()` method on the command dispatcher. This allows the outside world to
signal an intent to alter the state of the bounded context asynchronously.

A common example of this is where an HTTP controller intends to return a `202 Accepted` response. This indicates
that the request has been accepted for processing, but the processing has not been completed - i.e. is occurring
asynchronously.

For example, an endpoint that triggers a recalculation of our sales report:

```php
namespace App\Http\Controllers\Api\AttendanceReport;

use App\Modules\EventManagement\BoundedContext\Application\Commands\{
    RecalculateSalesAtEvent\RecalculateSalesAtEventCommand,
    EventManagementCommandBusInterface,
};
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use Illuminate\Validation\Rule;

class ReportRecalculationController extends Controller
{
    public function __invoke(
        Request $request,
        EventManagementCommandBusInterface $bus,
        string $attendeeId,
    ) {
        $validated = $request->validate([
            'event' => ['required', 'integer'],
        ]);

        $command = new RecalculateSalesAtEventCommand(
            eventId: new IntegerId((int) $validated['event']),
        );

        $bus->queue($command);

        return response()->noContent(202);
    }
}
```

For this to work, you must provide a closure that creates a queue instance when creating the command bus. For example:

```php
namespace App\Modules\EventManagement\BoundedContext\Application;

use App\Modules\EventManagement\BoundedContext\Application\Commands\{
    EventManagementCommandBus,
    EventManagementCommandBusInterface,
};
use CloudCreativity\Modules\Bus\CommandHandlerContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final class EventManagementApplication implements EventManagementApplicationInterface
{
    // ...other methods

    public function getCommandBus(): EventManagementCommandBusInterface
    {
        $bus = new EventManagementCommandBus(
            handlers: $handlers = new CommandHandlerContainer(),
            pipeline: $middleware = new PipeContainer(),
            queue: fn () => $this->getQueue(),
        );

        // ...bind command handlers
        // ...bind middleware

        return $bus;
    }
    
    private function getQueue(): QueueInterface
    {
        // ...create a queue, more detail below.
    }
}
```

:::tip
The command bus requires a closure factory for the queue, so that the queue instantiation is _lazy_. I.e. the cost
of creating the queue is avoided if the `queue()` method is not invoked on the command bus.
:::

## Queue

This package provides a queue abstraction that allows you to use any PHP queue implementation. The interface is:

```php
namespace CloudCreativity\Modules\Infrastructure\Queue;

use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

interface QueueInterface
{
    /**
     * Push a command or commands onto the queue.
     *
     * @param CommandInterface|iterable<CommandInterface> $command
     * @return void
     */
    public function push(CommandInterface|iterable $queueable): void;
}
```

No other functionality is required. As all queued messages are commands, when your PHP implementation pulls them out of
the queue it simply needs to dispatch them to your bounded context's command bus.

This abstraction will work with any PHP queue implementation you wish to use. Below there is a Laravel example.

We provide two concrete classes that allow you to wire a queue together with your PHP implementation. If neither of
these work for you, you can instead write a queue that implements the above interface.

### Closure Queuing

Our `ClosureQueue` allows you to register closures that handle pushing commands onto your queue. This is useful where
wiring in a queue implementation is extremely simple - as is the case with our Laravel example below.

Create a closure-based queue by providing it with the default closure for queuing commands. For example:

```php
use App\Modules\EventManagement\BoundedContext\Application\Queue\DispatchCommandJob;
use CloudCreativity\Modules\Infrastructure\Queue\ClosureQueue;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

$queue = new ClosureQueue(
    fn: function (CommandInterface $command): void {
        DispatchCommandJob::dispatch($command);    
    },
);
```

This closure will be used for all commands, unless you register closures for a specific command. For example:

```php
$queue = new ClosureQueue(
    fn: function (CommandInterface $command): void {
        DispatchCommandJob::dispatch($command);    
    },
);

$queue->bind(
    RecalculateSalesAtEventCommand::class,
    function (RecalculateSalesAtEventCommand $command): void {
        DispatchCommandJob::dispatch($command)
            ->onQueue('reporting');    
    },
);
```

The closure-based queue can also be configured with [middleware](#middleware) as described later in the chapter. For
example:

```php
use App\Modules\EventManagement\BoundedContext\Application\Queue\DispatchCommandJob;
use CloudCreativity\Modules\Infrastructure\Queue\ClosureQueue;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogPushedToQueue;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

$queue = new ClosureQueue(
    fn: function (CommandInterface $command): void {
        DispatchCommandJob::dispatch($command);    
    },
    middleware: $middleware = new PipeContainer(),
);

$middleware->bind(
    LogPushedToQueue::class,
    fn () => new LogPushedToQueue($this->dependencies->getLogger()),
 );

$queue->through([LogPushedToQueue::class]);
```

### Class-Based Queuing

Our `ComponentQueue` allows you to use define queuing logic on classes. Each class is an _enqueuer_ - a component that
handles pushing an item (in this case a command message) onto a queue.

This is useful in scenarios where you want to use constructor dependency injection when integrating with your PHP
queue implementation. Or alternatively, if wiring into your implementation is more complex than can be defined in a
simple closure.

Create this queue as follows:

```php
use CloudCreativity\Modules\Infrastructure\Queue\ComponentQueue;
use CloudCreativity\Modules\Infrastructure\Queue\EnqueuerContainer;

$queue = new ComponentQueue(
    enqueuers: new EnqueuerContainer(
        fn () => new DefaultEnqueuer(),
    ),
);
```

The closure provided to the `EnqueuerContainer` constructor is the default enqueuer factory that will be used for all
commands. You can bind alternative enqueuers for specific commands as follows:

```php
$queue = new ComponentQueue(
    enqueuers: $enqueuers = new EnqueuerContainer(
        fn () => new DefaultEnqueuer(),
    ),
);

$enqueuers->bind(
    RecalculateSalesAtEventCommand::class,
    fn () => new ReportingEnqueuer(),
);
```

The enqueuer class can be implemented as you need, but must have a `push()` method that queues the given command. For
example:

```php
use App\Modules\EventManagement\BoundedContext\Application\Queue;

class DefaultEnqueuer
{
    public function push(CommandInterface $command): void
    {
        // ...implementation
    }
}

class ReportingEnqueuer
{
    public function push(RecalculateSalesAtEventCommand $command): void
    {
        // ...implementation
    }
}
```

:::tip
Enqueuers are intentionally in the application layer. This is because they are coordinating an application concern
(the command message) with an infrastructure component (your PHP queue implementation).
:::

The closure-based queue can also be configured with [middleware](#middleware) as described later in the chapter. For
example:

```php
$queue = new ComponentQueue(
    enqueuers: $enqueuers = new EnqueuerContainer(
        fn () => new DefaultEnqueuer(),
    ),
    middleware: $middleware = new PipeContainer(),
);

$middleware->bind(
    LogPushedToQueue::class,
    fn () => new LogPushedToQueue($this->dependencies->getLogger()),
 );

$queue->through([LogPushedToQueue::class]);
```

## Laravel Example

Laravel provides a full-featured queue implementation, via queued jobs. For our bounded context to use this as the
implementation behind the queue abstraction, we would need a job that pushes a command onto the queue, then dispatches
it to the command bus when the queued job is executed.

### Default Queue Job

For example, a default queue job would be:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\Queue;

use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Result\FailedResultException;
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
            throw new FailedResultException($result);
        }
    }
}
```

### Specific Queue Job

Or we could create one for a specific command. This is useful where we need to customise the queueing behaviour for a
specific command - such as to prevent job overlapping, or customising how to handle failures when the command is
dispatched.

For example:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\Queue;

use App\Modules\EventManagement\BoundedContext\Application\Commands\{
    RecalculateSalesAtEvent\RecalculateSalesAtEventCommand,
    RecalculateSalesAtEvent\ErrorCodeEnum
};
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Toolkit\Result\FailedResultException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class RecalculateSalesAtEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    
    public function __construct(
        public readonly RecalculateSalesAtEventCommand $command
    ) {
    }
    
    public function handle(RecalculateSalesAtEventCommand $bus): void
    {
        $result = $bus->dispatch($this->command);
        $errors = $result->errors();
        
        if ($errors->contains(ErrorCodeEnum::TemporaryFailure)) {
            $this->release(now()->addSeconds(30));
            return;
        }
        
        if ($result->didFail()) {
            throw new FailedResultException($result);
        }
    }
    
    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->command->eventId->value),
        ];
    }
}
```

:::tip
Hopefully you can see from these examples that this queue implementation works well with the encapsulation of our
business logic.

The Laravel queue job becomes a purely infrastructure concern. There is no business logic in its `handle()` method, as
that is encapsulated in the command handler that will be executed when the command is dispatched to the bus.

Instead, the `handle()` method and class structure simply needs to concern itself with how the job should run on the
queue, and what the queue should do if the command fails.
:::

### Creating the Queue

We can now create our queue using the `ClosureQueue` described earlier in this chapter:

```php
$queue = new ClosureQueue(
    fn: function (CommandInterface $command): void {
        DispatchCommandJob::dispatch($command);    
    },
);

$queue->bind(
    RecalculateSalesAtEventCommand::class,
    function (RecalculateSalesAtEventCommand $command): void {
        RecalculateSalesAtEventJob::dispatch($command);
    },
);
```

## Middleware

Our queue implementations give you complete control over how to compose the queuing of commands, via middleware.
Middleware is a powerful way to add cross-cutting concerns to your queue, such as logging.

To apply middleware to the queue, use the `through()` method - as shown in the example below. Middleware is executed in
the order it is added to the queue.

```php
use CloudCreativity\Modules\Infrastructure\Queue\ClosureQueue;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogPushedToQueue;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

$queue = new ClosureQueue(
    fn: function (CommandInterface $command): void {
        DispatchCommandJob::dispatch($command);
    },
    middleware: $middleware = new PipeContainer(),
);

$queue->through([LogPushedToQueue::class]);
```

### Queue Logging

Use our `LogPushedToQueue` middleware to log a command being pushed into the queue. The middleware takes a
[PSR Logger](https://php-fig.org/psr/psr-3/).

```php
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogPushedToQueue;

$queue = new ClosureQueue(
    fn: function (CommandInterface $command): void {
        DispatchCommandJob::dispatch($command);
    },
    middleware: $middleware = new PipeContainer(),
);

$middleware->bind(
    LogPushedToQueue::class,
    fn (): LogPushedToQueue => new LogPushedToQueue(
        $this->dependencies->getLogger(),
    ),
);

$queue->through([LogPushedToQueue::class]);
```

The use of this middleware is identical to that described in the [Commands chapter.](../application/commands#logging)
See those instructions for more information, such as configuring the log levels and customising the log context for the
command.

### Writing Middleware

You can write your own middleware to suit your specific needs. Middleware is a simple invokable class, with the
following signature:

```php
namespace App\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

final class MyQueueMiddleware
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
        // executes before the command is pushed to the queue.

        $next($command);

        // executes after the command is pushed to the queue.
    }
}
```