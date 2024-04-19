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
  processes into asynchronously executed steps. We refer to this as _internal queuing_ via _queue jobs_.

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
allows this to be signalled by exposing a `queue()` method on the command bus. This allows the outside world to
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

For this to work, you must inject a factory closure into the command bus that creates a queue instance. For example:

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

As an example, say we needed to recalculate a sales report as a result of an attendee cancelling their ticket. It may
be acceptable for our domain's use case that this is not immediately recalculated - this is an _eventual consistency_
approach. We could push this internal work to a queue via a domain event listener:

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

## Queuing

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
2. dispatch queue jobs to the [queue bus](#queue-bus), as described below in this chapter.

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

## Queue Jobs

Queue jobs define the information that is required to execute a piece of work asynchronously. They are a concern of the
infrastructure layer, and are not exposed to the outside world. They are used to encapsulate asynchronous work that is 
internal to your bounded context.

As already mentioned, in the CQRS pattern queue jobs are technically _commands_ - they alter the state of your bounded 
context. Our implementation of queue jobs is therefore almost identical to that for 
[command messages.](../application/commands) When they are pulled from the queue, jobs are dispatched to a queue bus 
that ensures a job handler executes the work and returns a result object.

### Defining Jobs

Queue jobs are defined by writing a class that implements the `QueueJobInterface`. The class should be named according
to the work that it does, and should contain properties that define the information required to execute the work. 
They should be placed in the `Infrastructure\Queue` namespace of your bounded context. Queue jobs are immutable.

For example:

```php
namespace App\Modules\EventManagement\BoundedContext\Infrastructure\Queue\RecalculateSalesAtEvent;

use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Identifiers\IdentifierInterface;

final readonly class RecalculateSalesAtEventJob implements QueueJobInterface
{
    public function __construct(
        public IdentifierInterface $eventId,
    ) {
    }
}
```

### Job Handlers

A queue job handler is a class that executes the work defined by a queue job. It performs the action that the queue job
encapsulates, mutating the state of the bounded context in the process, and returns a result object that describes the 
outcome of the work.

Start by expressing the work of the handler as an interface:

```php
namespace App\Modules\EventManagement\BoundedContext\Infrastructure\Queue\RecalculateSalesAtEvent;

interface RecalculateSalesAtEventHandlerInterface
{
    /**
     * Recalculate sales at the specified event.
     *
     * @param RecalculateSalesAtEventJob $job
     * @return ResultInterface<null>
     */
    public function execute(RecalculateSalesAtEventJob $job): ResultInterface;
}
```

Then you can write the concrete implementation:

```php
namespace App\Modules\EventManagement\BoundedContext\Infrastructure\Queue\RecalculateSalesAtEvent;

use App\Modules\EventManagement\BoundedContext\Infrastructure\Persistence\AttendanceReportRepositoryInterface;
use CloudCreativity\Modules\Infrastructure\Queue\DispatchThroughMiddleware;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\ExecuteInUnitOfWork;
use CloudCreativity\Modules\Toolkit\Result\Result;

final readonly class RecalculateSalesAtEventHandler implements 
    RecalculateSalesAtEventHandlerInterface,
    DispatchThroughMiddleware
{
    public function __construct(private AttendanceReportRepositoryInterface $repository)
    {
    }

    public function execute(RecalculateSalesAtEventJob $job): Result
    {
        $report = $this->repository->findOrCreate($job->eventId);
        
        $report->recalculate();
        
        $this->repository->save($report);
        
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

:::warning
As queue jobs are a concern of the infrastructure layer, note that the `DispatchThroughMiddleware` interface and 
queue job middleware are in the `CloudCreativity\Modules\Infrastructure\Queue` namespace. This is different from
command interfaces and middleware.
:::

### Queue Bus

To allow queue jobs to be dispatched to a handler, you need to create a queue bus. Although there is a generic queue bus
interface, you should create a specific queue bus for your bounded context. This is because we need to dispatch jobs
specific to our bounded context to the queue for that context.

Extend the generic interface:

```php
namespace App\Modules\EventManagement\BoundedContext\Infrastructure\Queue;

use CloudCreativity\Modules\Infrastructure\Queue\QueueJobDispatcherInterface;

interface EventManagementQueueBusInterface extends QueueJobDispatcherInterface
{
}
```

Then create the concrete implementation:

```php
namespace App\Modules\EventManagement\BoundedContext\Infrastructure\Queue;

use CloudCreativity\Modules\Infrastructure\Queue\QueueJobDispatcher;

final class EventManagementQueueBus extends QueueJobDispatcher implements 
    EventManagementQueueBusInterface
{
}
```

### Creating a Queue Bus

Our queue dispatcher class that you extended in the example above allows you to build a queue bus specific to your 
domain. You do this by:

- binding queue job handler factories into the queue bus; and
- binding factories for any middleware used by your implementation; and
- optionally, attaching middleware that runs for all jobs dispatched through the queue bus.

Factories must always be lazy, so that the cost of instantiating job handlers or middleware only occurs if the handler 
or middleware are actually being used.

For example:

```php
namespace App\Modules\EventManagement\BoundedContext\Infrastructure\Queue;

use App\Modules\EventManagement\BoundedContext\Infrastructure\Queue\RecalculateSalesAtEvent\{
    RecalculateSalesAtEventJob,
    RecalculateSalesAtEventHandler,
    RecalculateSalesAtEventHandlerInterface,
};
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobHandlerContainer;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\ExecuteInUnitOfWork;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogJobDispatch;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final class EventManagementQueueDependencies
{
    // ...other methods e.g. constructor dependency injection

    public function getQueueBus(): EventManagementQueueBusInterface
    {
        $bus = new EventManagementQueueBus(
            handlers: $handlers = new QueueJobHandlerContainer(),
            middleware: $middleware = new PipeContainer(),
        );

        /** Bind jobs to handler factories */
        $handlers->bind(
            RecalculateSalesAtEventJob::class,
            fn(): RecalculateSalesAtEventHandlerInterface => new RecalculateSalesAtEventHandler(
                $this->repositories->getAttendanceReportRepository(),
            ),
        );

        /** Bind middleware factories */
        $middleware->bind(
            ExecuteInUnitOfWork::class,
            fn () => new ExecuteInUnitOfWork($this->getUnitOfWorkManager()),
        );

        $middleware->bind(
            LogJobDispatch::class,
            fn () => new LogJobDispatch(
                $this->dependencies->getLogger(),
            ),
        );

        /** Attach middleware that runs for all jobs */
        $bus->through([
            LogJobDispatch::class,
        ]);

        return $bus;
    }
}
```

You can now dispatch queue jobs to the queue bus, whenever your chosen PHP queue implementation pulls the job from the
queue. See the following Laravel examples that illustrate how an integration can be achieved.

## Laravel Example

Laravel provides a full-featured queue implementation, via queued jobs. For our bounded context to use this as the
implementation behind the queue abstraction, we would need two Laravel jobs:

- The first takes a command message and - when executed by the queue - dispatches this to the bounded context's command
  bus.
- The second takes a queue job and - when executed by the queue - dispatches this to the bounded context's queue bus.

### Default Integrations

For example, a default Laravel job for queuing and dispatching commands would be:

```php
namespace App\Modules\EventManagement\BoundedContext\Application\Queue;

use App\Modules\EventManagement\BoundedContext\Application\Commands\EventManagementCommandBusInterface;
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

:::tip
Note that this Laravel job is in our bounded context's application layer. That's because it is coordinating an
application concern (the command message and bus) with an infrastructure component (the Laravel queue).
:::

And to integrate a bounded context's queue job and queue bus:

```php
namespace App\Modules\EventManagement\BoundedContext\Infrastructure\Queue;

use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Result\FailedResultException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class DispatchQueueJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    
    public function __construct(
        public readonly QueueJobInterface $job
    ) {
    }
    
    public function handle(EventManagementQueueBusInterface $bus): void
    {
        $result = $bus->dispatch($this->job);
        
        if ($result->didFail()) {
            throw new FailedResultException($result);
        }
    }
}
```

:::tip
This Laravel job is purely an infrastructure concern, as it is coordinating the queue job and bus which are both in the
infrastructure layer of our bounded context. It is therefore in the infrastructure namespace.
:::

### Specific Integration

These default integrations may work for all of your asynchronous processing via command messages or queue jobs. However,
you may want to customise the queueing behaviour for specific commands or queue jobs. This is useful where we need to 
customise the queueing behaviour for a specific scenario - such as to prevent job overlapping, or customising how to 
handle failures when the command or queue job is dispatched.

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

class QueueRecalculateSalesAtEventJob implements ShouldQueue
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

The Laravel job becomes a purely infrastructure concern. There is no business logic in its `handle()` method, as
that is encapsulated in the command or queue job handler that will be executed via the relevant bus.

Instead, the Laravel `handle()` method and class structure simply needs to concern itself with _how_ the job should run 
on the Laravel queue, and what it should do if it receives a failure result.
:::

### Creating the Queue

We can now create our queue using the `ClosureQueue` described earlier in this chapter. For example, to create the
queue that is injected into our command bus:

```php
// default command queuing
$queue = new ClosureQueue(
    fn: function (CommandInterface $command): void {
        DispatchCommandJob::dispatch($command);    
    },
);

// specific command queuing
$queue->bind(
    RecalculateSalesAtEventCommand::class,
    function (RecalculateSalesAtEventCommand $command): void {
        QueueRecalculateSalesAtEventJob::dispatch($command);
    },
);
```

Or to create a queue for queue jobs:

```php
// default queuing
$queue = new ClosureQueue(
    fn: function (QueueJobInterface $job): void {
        DispatchQueueJob::dispatch($job);    
    },
);

// for specific queue jobs
$queue->bind(
    RecalculateSalesAtEventJob::class,
    function (RecalculateSalesAtEventJob $job): void {
        QueueRecalculateSalesAtEventJob::dispatch($job);
    },
);
```

Or (if you prefer - the choice is yours!) a queue that handles both internal and external queuing:

```php
// default command queuing
$queue = new ClosureQueue(
    fn: function (CommandInterface|QueueJobInterface $in): void {
        if ($in instanceof CommandInterface) {
            DispatchCommandJob::dispatch($in);
            return;
        }
        
        DispatchQueueJob::dispatch($in);
    },
);

// specific commands or queue jobs...
$queue->bind(
    RecalculateSalesAtEventJob::class,
    function (RecalculateSalesAtEventJob $command): void {
        QueueRecalculateSalesAtEventJob::dispatch($command);
    },
);
```

## Queue Middleware

Both our queuing implementations give you complete control over how to compose the queuing of command messages or queue 
jobs, via middleware. Middleware is a powerful way to add cross-cutting concerns to your queue, such as logging.

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
command message or queue job.

### Writing Middleware

You can write your own middleware to suit your specific needs. Middleware is a simple invokable class, with the
following signature:

```php
namespace App\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\QueueMiddlewareInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Messages\CommandInterface;

final class MyQueueMiddleware implements QueueMiddlewareInterface
{
    /**
     * Handle the command message or queue job being queued.
     *
     * @param CommandInterface|QueueJobInterface $queueable
     * @param Closure(CommandInterface|QueueJobInterface): void $next
     * @return void
     */
    public function __invoke(
        CommandInterface|QueueJobInterface $queueable, 
        Closure $next,
    ): void
    {
        // executes before the command is pushed to the queue.

        $next($command);

        // executes after the command is pushed to the queue.
    }
}
```

:::tip
If you're writing middleware that is only meant to be used for a specific command message or queue job, do not implement
the `QueueMiddlewareInterface`. Instead, use the same signature but change the type-hint for to the specific command
message or queue job your middleware is designed for.
:::

## Job Middleware

Our queue bus implementation gives you complete control over how to compose the handling of your queue jobs, via
middleware. This is identical to the 
[middleware implementation for the command bus.](../application/commands#middleware)

Middleware can be added either to the queue bus (so it runs for every job) or to individual job handlers.

To apply middleware to the queue bus, you can use the `through()` method on the bus - as shown in the example above.
Middleware is executed in the order it is added to the bus.

To apply middleware to a specific job handler, the handler must implement the `DispatchThroughMiddleware` interface,
as shown in the example handler above. The `middleware()` method should return an array of middleware to run, in the
order they should be executed. Handler middleware are always executed _after_ the bus middleware.

This package provides a number of queue job middleware, which are described below. Additionally, you can write your own
middleware to suit your specific needs.

:::warning
The middleware here has identical or very similar names to the middleware for the command bus. This is because both
command messages and queue jobs are _commands_ in the CQRS pattern. Make sure you are using the middleware from the
`CloudCreativity\Modules\Infrastructure\Queue\Middleware` namespace.
:::

### Setup and Teardown

Our `SetupBeforeDispatch` middleware allows setup work to be run before the job is dispatched, and optionally
teardown work when the job has completed.

This allows you to set up any state and guarantee that the state is cleaned up, regardless of the outcome of the
job. The primary use case for this is to boostrap [Domain Services](../domain/services) and to garbage collect any
singleton instances of dependencies.

For example:

```php
use App\Modules\EventManagement\BoundedContext\Domain\Services;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\SetupBeforeDispatch;

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
    LogJobDispatch::class,
    SetupBeforeDispatch::class,
]);
```

Here our setup middleware takes a setup closure as its only constructor argument. This setup closure can optionally
return a closure to do any teardown work. The teardown callback is guaranteed to always be executed, regardless of
whether a queue job succeeded or failed - and it also runs if an exception is thrown.

If you only need to do teardown work, use the `TeardownAfterDispatch` middleware instead. This takes a single teardown
closure as its only constructor argument:

```php
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\TeardownAfterDispatch;

$middleware->bind(
    TeardownAfterDispatch::class,
    fn () => new TeardownAfterDispatch(function (): Closure {
        // clean up a singleton instance of a unit of work manager.
        $this->unitOfWorkManager = null;
    }),
);

$bus->through([
    LogJobDispatch::class,
    TearDownAfterDispatch::class,
]);
```

### Unit of Work

Ideally queue job handlers should always be executed in a unit of work. We cover this in detail in the
[Units of Work chapter.](../infrastructure/units-of-work)

To execute a handler in a unit of work, you will need to use our `ExecuteInUnitOfWork` middleware. You should always
implement this as handler middleware - because typically you need it to be the final middleware that runs before a
handler is invoked. It also makes it clear to developers looking at the command handler that it is expected to run
in a unit of work. The example `RecalculateSalesAtEventHandler` above demonstrates this.

An example binding for this middleware is:

```php
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\ExecuteInUnitOfWork;

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
of the singleton instance once the job has executed.
:::

### Logging

Use our `LogJobDispatch` middleware to log the dispatch of a queue job, and the result. The middleware takes a
[PSR Logger](https://php-fig.org/psr/psr-3/).

```php
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogJobDispatch;

$middleware->bind(
    LogJobDispatch::class,
    fn (): LogJobDispatch => new LogJobDispatch(
        $this->dependencies->getLogger(),
    ),
);

$bus->through([LogJobDispatch::class]);
```

The use of this middleware is identical to that described in the [Commands chapter.](../application/commands#logging)
See those instructions for more information, such as configuring the log levels.

Additionally, if you need to customise the context that is logged for a queue job then implement the
`ContextProviderInterface` on your job. See the example in the 
[Commands chapter.](../application/commands#logging)

### Writing Middleware

You can write your own middleware to suit your specific needs. Middleware is a simple invokable class, with the
following signature:

```php
namespace App\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\JobMiddlewareInterface;
use CloudCreativity\Modules\Infrastructure\Queue\QueueJobInterface;
use CloudCreativity\Modules\Toolkit\Result\ResultInterface;

final class MyMiddleware implements JobMiddlewareInterface
{
    /**
     * Handle the queue job.
     *
     * @param QueueJobInterface $job
     * @param Closure(QueueJobInterface): ResultInterface<mixed> $next
     * @return ResultInterface<mixed>
     */
    public function __invoke(
        QueueJobInterface $job, 
        Closure $next,
    ): ResultInterface
    {
        // code here executes before the handler

        $result = $next($job);

        // code here executes after the handler

        return $result;
    }
}
```

:::tip
If you're writing middleware that is only meant to be used for a specific queue job, do not implement the
`JobMiddlewareInterface`. Instead, use the same signature but change the type-hint for the queue job to the job
class your middleware is designed to be used with.
:::
