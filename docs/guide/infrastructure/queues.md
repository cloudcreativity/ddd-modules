# Asynchronous Processing

Modern PHP frameworks provide implementations that allow you to queue work for asynchronous processing. This is
advantageous for a number of reasons, including:

1. allowing expensive and/or long running operations to be executed separately in a non-blocking way, for example
   allowing an HTTP response to be returned to a client immediately.
2. providing retry and back-off capabilities when executing work that involves communication with external services -
   e.g. microservices in your architecture and/or third-party applications.

When composing the execution of your bounded context's domain, you should use asynchronous processing to improve both
the scalability and fault tolerance of your implementation. This package embraces this, by providing abstractions that
allow:

- command messages to be dispatched by the presentation and delivery layer in a non-blocking way; and
- work that is internal to the bounded context to be executed asynchronously as queue jobs.

Both use cases are implemented via a queue abstraction, that allows you to plug your bounded context into any PHP queue
implementation you want to use.

## Scenarios

There are two scenarios where a bounded context's work can be queued:

- **Commands dispatched by the presentation and delivery layer** - but where the presentation layer does not need to
  wait for the result of the command, i.e. prefers to return early. For example, where a HTTP controller action wants to
  dispatch a command but intends to return a `202 Accepted` response rather than waiting for the result. We refer to
  this as _external queueing_, as the request to queue the command comes from _outside_ your bounded context.
- **Work executed as an internal implementation of your bounded context.** A typical example is where a domain event
  listener queues work that needs to happen as a consequence of the domain event, but the execution of the work does not
  need to occur immediately. This is a good approach for decomposing potentially long-running or highly complex
  processes into asynchronously executed steps. We refer to these as _queue jobs_.

:::tip
In the CQRS pattern that this package follows, queue jobs are technically _commands_ - because they alter the state of
your bounded context. However, they are not _command messages_. That's because command messages are concerns of your
application layer, which means they can be dispatched by the presentation and delivery layer.

Queue jobs in comparison are used for work that is internal to your bounded context, and therefore cannot be
dispatched by the outside world. They are a concern of your infrastructure layer, and are therefore not _command
messages_.
:::

### External Queuing

Commands define the use-cases of your module - specifically, the use-cases where there is an intent to change the state
of the bounded context. They are part of your bounded context's application interface, and therefore can be dispatched
by the presentation and delivery layer.

It is reasonable for there to be scenarios where the presentation and delivery intends to change the state of the
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
            middleware: $middleware = new PipeContainer(),
            queue: fn () => $this->getCommandQueue(),
        );

        // ...bind command handlers
        // ...bind middleware

        return $bus;
    }
    
    private function getCommandQueue(): QueueInterface
    {
        // ...create a queue, more detail below.
    }
}
```

:::tip
The command bus requires a closure factory for the queue, so that the queue instantiation is _lazy_. I.e. the cost
of creating the queue is avoided if the `queue()` method is not invoked on the command bus.
:::

### Internal Queuing

There are many scenarios where it can be advantageous for your bounded context to queue internal work for asynchronous
processing. We refer to these pieces of work as _queue jobs_. In the CQRS pattern, they are commands as they alter the
state of your bounded context. But they are a concern of the infrastructure layer that is not exposed to the outside
world - unlike _command messages_ in your application layer.

Some examples of where a bounded context might need to push internal work to a queue include:

1. Work that needs to occur as a result of a domain event - but does not need to happen in the same unit of work in
   which that event was emitted and is advantageous to occur separately (e.g. with back-off and retry capabilities).
2. Splitting expensive or long-running processes into multiple asynchronous jobs that are more memory efficient or
   individually run for shorter periods of time.
3. Implementing parallel processing of a task - for example, by splitting a task into multiple jobs that can run
   concurrently.

Or anything else that fits with the specific use-case of your bounded context!

As an example, say we needed to recalculate a sales report as a result of an attendee cancelling their ticket. If it
did not need to happen immediately (i.e. within the same unit of work as the event occurs) then we could push it to a
queue via a domain event listener:

```php
namespace App\Modules\EventManagement\BoundedContext\Infrastructure\DomainEventListeners;

use App\Modules\EventManagement\BoundedContext\Domain\Events\AttendeeTicketWasCancelled;
use App\Modules\EventManagement\BoundedContext\Infrastructure\Queue\{
    RecalculateSalesAtEvent\RecalculateSalesAtEventJob
};
use CloudCreativity\Modules\Infrastructure\Queue\QueueInterface;

final readonly class QueueTicketSalesReportRecalculation
{
    public function __construct(private QueueInterface $queue)
    {
    }

    public function handle(AttendeeTicketWasCancelled $event): void
    {
        $this->queue->push(new RecalculateSalesAtEventJob(
            $event->eventId,
        ));
    }
}
```

## Queue

This package provides a queue abstraction that allows you to use any PHP queue implementation when queuing command
messages or queue jobs. The interface is:

```php
namespace CloudCreativity\Modules\Infrastructure\Queue;

use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;

interface QueueInterface
{
    /**
     * Push a command or queue job onto thw queue.
     *
     * @param CommandInterface|QueueJobInterface $queueable
     * @return void
     */
    public function push(CommandInterface|QueueJobInterface $queueable): void;
}
```

This abstraction allows you to push command messages or queue jobs onto a queue. When pulling them from the queue
for processing, you should either:

1. dispatch command messages to your bounded context's application command bus - as described in
   the [Commands chapter](../application/commands); or
2. dispatch queue jobs to the queue dispatcher, as described below in this chapter.

This abstraction will work with any PHP queue implementation you wish to use. Both of these dispatching patterns
are shown later in this chapter in a Laravel example.

We provide two concrete classes that allow you to push work onto a queue via your preferred PHP implementation. If 
neither of these work for you, you can instead write a queue that implements the above interface.

### Closure Queuing

Our `ClosureQueue` allows you to register closures that handle pushing work onto your queue. This is useful where
wiring in a queue implementation is extremely simple - as is the case with our Laravel example below.

Create a closure-based queue by providing it with the default closure for queuing commands. For example:

```php
use App\Modules\EventManagement\BoundedContext\Application\Queue\DispatchCommandJob;
use App\Modules\EventManagement\BoundedContext\Infrastructure\Queue\DispatchQueueJob;
use CloudCreativity\Modules\Infrastructure\Queue\ClosureQueue;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

// for the queue injected into your command bus
$queue = new ClosureQueue(
    fn: function (CommandInterface $command): void {
        DispatchCommandJob::dispatch($command);    
    },
);

// or a queue for queue jobs
$queue = new ClosureQueue(
    fn: function (QueueJobInterface $job): void {
        DispatchQueueJob::dispatch($job);    
    },
);
```

This default closure will be used for all commands or queue jobs, unless you register closures for a specific command
or job. For example:

```php
$queue = new ClosureQueue(
    fn: function (QueueJobInterface $job): void {
        DispatchQueueJob::dispatch($job);    
    },
);

$queue->bind(
    RecalculateSalesAtEventJob::class,
    function (RecalculateSalesAtEventJob $job): void {
        DispatchQueueJob::dispatch($job)
            ->onQueue('reporting');    
    },
);
```

The closure-based queue can also be configured with [queue middleware](#queue-middleware) as described later in the
chapter. For example:

```php
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
handles pushing an item (in this case a command message or queue job) onto a queue.

This is useful in scenarios where you want to use constructor dependency injection when integrating with your PHP
queue implementation. Or alternatively, if wiring into your implementation is more complex than can be defined in a
simple closure.

Create this queue as follows:

```php
use CloudCreativity\Modules\Infrastructure\Queue\ComponentQueue;
use CloudCreativity\Modules\Infrastructure\Queue\Enqueuers\EnqueuerContainer;

$queue = new ComponentQueue(
    enqueuers: new EnqueuerContainer(
        fn () => new DefaultEnqueuer(),
    ),
);
```

The closure provided to the `EnqueuerContainer` constructor is the default enqueuer factory that will be used for all
work that is being queued. You can bind alternative enqueuers for specific commands or queue jobs as follows:

```php
$queue = new ComponentQueue(
    enqueuers: $enqueuers = new EnqueuerContainer(
        fn () => new DefaultEnqueuer(),
    ),
);

$enqueuers->bind(
    RecalculateSalesAtEventJob::class,
    fn () => new ReportingEnqueuer(),
);
```

The enqueuer class can be implemented as you need, but must have a `push()` method that queues the given command
or queue job. For example:

```php
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;

class DefaultEnqueuer
{
    public function push(CommandInterface|QueueJobInterface $queueable): void
    {
        // ...implementation
    }
}

class ReportingEnqueuer
{
    public function push(RecalculateSalesAtEventJob $job): void
    {
        // ...implementation
    }
}
```

:::tip
Enqueuers that are type-hinting specific command messages must be in the application layer. This is because they are
coordinating an application concern (the command message) with an infrastructure component (your PHP queue
implementation).

Enqueuers that type-hint specific queue jobs can be in the infrastructure layer, because queue jobs are an
infrastructure concern (internal to your bounded context).
:::

The class-based queue can also be configured with [queue middleware](#queue-middleware) as described later in the
chapter. For example:

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

## Queue Bus

### Creating a Queue Bus

### Dispatching Queue Jobs

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

## Queue Middleware

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

## Job Middleware