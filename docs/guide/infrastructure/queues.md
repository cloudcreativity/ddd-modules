# Queues

As described in the [Asynchronous Processing chapter](../application/asynchronous-processing), our command bus allows
you to queue commands for asynchronous dispatch. To do this, you need to provide a queue adapter to the command bus.
This adapter handles pushing the command onto a queue, and dispatching the command when it is pulled from the queue.

We provide several queue adapters that you can use. These are designed to be simple to use and allow you to plug into
any PHP queue implementation that you choose to use. This chapter describes these queue implementations.

## Queue Port

To allow your application layer to queue commands, we need a driven port for the queue. Although there is a _generic_
queue interface, our bounded context needs to expose its specific queue interface.

We do this by defining an interface in our application's driven ports:

```php
namespace App\Modules\EventManagement\Application\Ports\Driven\Queue;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue\Queue as Port;

interface Queue extends Port
{
}
```

This port is injected into a command bus via a closure factory that ensures the instantiation of the queue adapter is
lazy. For example:

```php
$bus = new CommandBus(
    handlers: $handlers = new CommandHandlerContainer(),
    middleware: $middleware = new PipeContainer(),
    queue: fn() => $this->dependencies->getQueue(),
);
```

This allows the application layer to push commands onto the queue. When pulling commands from the queue, your queue
adapter will need to dispatch the command to the command bus.

We provide two concrete classes that allow you to push work onto a queue via your preferred PHP implementation. If
neither of these work for you, you can instead write a queue that implements the above interface.

### Internal Commands

The [asynchronous processing chapter](../application/asynchronous-processing) introduces the concept of _internal_
commands. These are commands that are not exposed as use cases of your bounded context. Instead they are used to split
long running or complex work up into smaller write operations (commands) that are sequenced via a workflow.

If you have an internal command bus, you will need to provide a separate queue port for these commands. This is because
the internal command bus will have its own queue, that dispatches internal command messages to the internal command bus.

In this scenario, define another driven port in your application layer:

```php
namespace App\Modules\EventManagement\Application\Ports\Driven\Queue;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue\Queue as Port;

interface InternalQueue extends Port
{
}
```

And then ensure the adapter of this internal port is injected into your internal command bus:

```php
$bus = new InternalCommandBus(
    handlers: $handlers = new CommandHandlerContainer(),
    middleware: $middleware = new PipeContainer(),
    queue: fn() => $this->dependencies->getInternalQueue(),
);
```

## Closure Queuing

Our `ClosureQueue` allows you to register closures that handle pushing work onto your queue. This is useful where
wiring in a queue implementation is extremely simple - as is the case with our Laravel example below.

Define a queue adapter by extending this class:

```php
namespace App\Modules\EventManagement\Infrastructure\Queue;

use App\Modules\EventManagement\Application\Ports\Driven\Queue\Queue;
use CloudCreativity\Modules\Infrastructure\Queue\ClosureQueue;

final class QueueAdapter extends ClosureQueue
    implements Queue
{
}
```

Then you can create the adapter by providing it with the default closure for queuing commands. For example:

```php
namespace App\Modules\EventManagement\Infrastructure\Queue;

use App\Modules\EventManagement\Application\Ports\Driven\Queue\Queue;
use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogPushedToQueue;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final class QueueAdapterProvider
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getQueue(): Queue
    {
        $adapter = new QueueAdapter(
            fn: function (Command $command): void {
                DispatchCommandJob::dispatch($command);    
            },
            middleware: $middleware = new PipeContainer(),
        );

        $middleware->bind(
            LogPushedToQueue::class,
            fn () => new LogPushedToQueue($this->logger),
        );
        
        $queue->through([LogPushedToQueue::class]);
        
        return $adapter;
    }
}
```

:::tip
As shown, the queue adapter can be configured with [queue middleware.](#middleware)
:::

This default closure will be used for all commands, unless you register closures for specific commands. For example:

```php
$queue = new QueueAdapter(
    fn: function (Command $command): void {
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

## Class-Based Queuing

Our `ComponentQueue` allows you to use define queuing logic on classes. Each class is an _enqueuer_ - a component that
handles pushing an item (in this case a command message) onto a queue.

This is useful in scenarios where you want to use constructor dependency injection when integrating with your PHP
queue implementation. Or alternatively, if wiring into your implementation is more complex than can be defined in a
simple closure.

Define a queue adapter by extending this class:

```php
namespace App\Modules\EventManagement\Infrastructure\Queue;

use App\Modules\EventManagement\Application\Ports\Driven\Queue\Queue;
use CloudCreativity\Modules\Infrastructure\Queue\ComponentQueue;

final class QueueAdapter extends ComponentQueue
    implements Queue
{
}
```

Then you can create the adapter by providing it with a default enqueuer for queuing commands. For example:

```php
namespace App\Modules\EventManagement\Infrastructure\Queue;

use App\Modules\EventManagement\Application\Ports\Driven\Queue\Queue;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogPushedToQueue;
use CloudCreativity\Modules\Infrastructure\Queue\EnqueuerContainer;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final class QueueAdapterProvider
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getQueue(): Queue
    {
        $queue = new QueueAdapter(
            enqueuers: new EnqueuerContainer(
                fn () => new DefaultEnqueuer(),
            ),
        );

        $middleware->bind(
            LogPushedToQueue::class,
            fn () => new LogPushedToQueue($this->logger),
        );
        
        $queue->through([LogPushedToQueue::class]);
        
        return $adapter;
    }
}
```

:::tip
As shown, the queue adapter can be configured with [queue middleware.](#middleware)
:::

The closure provided to the adapter's constructor is the default enqueuer factory that will be used for all work that is
being queued. You can bind alternative enqueuers for specific commands as follows:

```php
$queue = new QueueAdapter(
    enqueuers: $enqueuers = new EnqueuerContainer(
        fn () => new DefaultEnqueuer(),
    ),
);

$enqueuers->bind(
    RecalculateSalesAtEventCommand::class,
    fn () => new ReportingEnqueuer(),
);
```

The enqueuer class can be implemented as you need. All it needs is a `push()` method that queues the given command. For
example:

```php
namespace App\Modules\EventManagement\Infrastructure\Queue;

use CloudCreativity\Modules\Contracts\Application\Messages\Command;

final class DefaultEnqueuer
{
    public function push(Command $command): void
    {
        // ...implementation
    }
}

final class ReportingEnqueuer
{
    public function push(RecalculateSalesAtEventCommand $command): void
    {
        // ...implementation
    }
}
```

## Writing a Queue

If neither of these two queue adapters work for you, you can write your own queue adapter. This is a simple class that
implements the port interface that is extended in your application layer:

```php
namespace CloudCreativity\Modules\Application\Ports\Driven\Queue;

use CloudCreativity\Modules\Contracts\Application\Messages\Command;

interface Queue
{
    /**
     * Push a command on to the queue.
     *
     * @param Command $command
     * @return void
     */
    public function push(Command $command): void;
}
```

:::tip
If you want your custom queue to use middleware, take a look at either of our two implementations to see how that works.
:::

## Laravel Example

Laravel provides a full-featured queue implementation, via queue jobs. For our bounded context to use this as the queue
adapter, we would need a Laravel job that takes a command message and dispatches it to the command bus.

:::tip
If we also have an internal command bus, we would need another Laravel job that takes an internal command message and
dispatches it to the internal command bus.
:::

### Default Queue Job

For example, a default Laravel job for queuing and dispatching commands would be:

```php
namespace App\Modules\EventManagement\Infrastructure\Queue;

use App\Modules\EventManagement\Application\Ports\Driving\Commands\CommandBus;
use CloudCreativity\Modules\Contracts\Application\Messages\Command;
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
        public readonly Command $command
    ) {
    }
    
    public function handle(CommandBus $bus): void
    {
        $result = $bus->dispatch($this->command);
        
        if ($result->didFail()) {
            throw new FailedResultException($result);
        }
    }
}
```

### Specific Queue Job

This default queue job may work for all of your asynchronous processing via command messages. However, you may want to
customise the behaviour for specific commands. For example, to prevent job overlapping, or customise how failures are
handled.

An example of a queue job for a specific command might be:

```php
namespace App\Modules\EventManagement\Infrastructure\Queue;

use App\Modules\EventManagement\Application\Ports\Driving\Commands\CommandBus;
use App\Modules\EventManagement\Application\UseCases\Commands\{
    RecalculateSalesAtEvent\ErrorCodeEnum,
    RecalculateSalesAtEvent\RecalculateSalesAtEventCommand,
};
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
    
    public function handle(CommandBus $bus): void
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

The Laravel job is purely an infrastructure concern. There is no business logic in its `handle()` method, as
that is encapsulated in the command handler that will be executed via the command bus.

Instead, the Laravel `handle()` method and class structure simply needs to concern itself with _how_ the job should run
on the Laravel queue, and what it should do if it receives a failure result.
:::

### Creating the Queue

We can now create our queue adapter. This will extend the closure queue adapter described earlier in this chapter. For
example, to create the queue that is injected into our command bus:

```php
// default command queuing
$queue = new QueueAdapter(
    fn: function (Command $command): void {
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

## Middleware

Both our queue adapters give you complete control over how to compose the queuing of command messages, via middleware.
Middleware is a powerful way to add cross-cutting concerns to your queue, such as logging.

To apply middleware to the queue, use the `through()` method - as shown in the example below. Middleware is executed in
the order it is added to the queue.

```php
use CloudCreativity\Modules\Infrastructure\Queue\ClosureQueue;
use CloudCreativity\Modules\Infrastructure\Queue\Middleware\LogPushedToQueue;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

$queue = new ClosureQueue(
    fn: function (Command $command): void {
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
    fn: function (Command $command): void {
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
namespace App\Modules\Shared\Infrastructure\Queue\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\Messages\Command;
use CloudCreativity\Modules\Contracts\Infrastructure\Queue\QueueMiddleware;

final class MyQueueMiddleware implements QueueMiddleware
{
    /**
     * Handle the command message being queued.
     *
     * @param Command $command
     * @param Closure(Command): void $next
     * @return void
     */
    public function __invoke(
        Command $command, 
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
the `QueueMiddleware` interface. Instead, use the same signature but change the type-hint for to the specific command
message your middleware is designed for.
:::
