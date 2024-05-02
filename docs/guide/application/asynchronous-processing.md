# Asynchronous Processing

Modern PHP frameworks provide implementations that allow you to queue work for asynchronous processing. This is
advantageous for a number of reasons, including:

1. allowing expensive and/or long running operations to be executed separately in a non-blocking way, for example
   allowing an HTTP response to be returned to a client immediately.
2. providing retry and back-off capabilities when executing work that involves communication with external services -
   e.g. microservices in your architecture and/or third-party applications.

When composing the execution of your bounded context's domain, you should use asynchronous processing to improve both
the scalability and fault tolerance of your implementation. This package embraces this, by providing abstractions that
allow command messages to be queued for asynchronous dispatch.

## Scenarios

There are two scenarios where a bounded context's work could be queued:

- **Commands dispatched by the presentation and delivery layer** - but where the presentation layer does not need to
  wait for the result of the command, i.e. prefers to return early. We refer to this as **external queueing**, as the
  request to queue the command comes from _outside_ your bounded context.
- **Work executed as an internal implementation of your application layer.** A typical example is where a domain event
  listener queues work that needs to happen as a consequence of the domain event, but the execution of the work does not
  need to occur immediately. This is a good approach for decomposing potentially long-running or highly complex
  processes into an asynchronously executed workflow. We refer to this as **internal queuing**.

## External Queuing

Commands define the use-cases of your module - specifically, the use-cases where there is an intent to change the state
of the bounded context. They are exposed by your application layer as a driving port, and therefore can be dispatched
by the presentation and delivery layer.

It is reasonable for there to be scenarios where the presentation and delivery layer intends to change the state of the
bounded context via a command, but does not need to wait for the result of that change. Our command bus implementation
allows this to be signalled by exposing a `queue()` method on the command bus. This allows the outside world to
signal a desire to alter the state of the domain asynchronously.

### Example

A common example of this is where an HTTP controller intends to return a `202 Accepted` response. This indicates
that the request has been accepted for processing, but the processing has not been completed - i.e. is occurring
asynchronously.

For example, an endpoint that triggers a recalculation of our sales report:

```php
namespace App\Http\Controllers\Api\AttendanceReport;

use App\Modules\EventManagement\Application\{
    Ports\Driving\Commands\CommandBus,
    UsesCases\Commands\RecalculateSalesAtEvent\RecalculateSalesAtEventCommand,
};
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use Illuminate\Validation\Rule;

class ReportRecalculationController extends Controller
{
    public function __invoke(
        Request $request,
        CommandBusInterface $bus,
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

For this to work, you must have a driven port that can queue commands, along with a queue adapter in the infrastructure
layer that implements this port. This is covered by
the [queues chapter in the infrastructure section.](../infrastructure/queues) The queue adapter is then injected into
the command bus. The queue chapter contains an example.

## Internal Queuing

There are many scenarios where it can be advantageous for your bounded context to queue internal work for asynchronous
processing. Some examples of where your application layer might need to push internal work to a queue include:

1. Work that needs to occur as a result of a domain event - but does not need to happen in the same unit of work in
   which that event was emitted and is advantageous to occur separately (e.g. with back-off and retry capabilities).
2. Splitting expensive or long-running processes into multiple asynchronous jobs that are more memory efficient or
   individually run for shorter periods of time.
3. Implementing parallel processing of a task - for example, by splitting a task into multiple jobs that can run
   concurrently.

Or anything else that fits with the specific use case of your bounded context!

Our approach is to define this work as _internal_ command messages. These are queued and dispatched by a specific
_internal_ command bus - separating them from the command bus that is an adapter of the driving port in the application
layer.

This means internal commands are not exposed as use cases of our module - making them an internal implementation detail
of the application layer.

:::tip
If you have a command that can be dispatched both by the outside world and internally, you should define this as a
public command. The internal dispatching would use the public command bus rather than the internal command bus to queue
the command.
:::

### Example

As an example, say we needed to recalculate a sales report as a result of an attendee cancelling their ticket. It may be
acceptable to our business logic that this is not immediately recalculated. This is an _eventual consistency_ approach,
i.e. derived data can be out-of-date for a short amount of time, as long as it is guaranteed to be updated.

We could push this internal work to a queue via a domain event listener:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents\Listeners;

use App\Modules\EventManagement\Application\Internal\Commands\{
    InternalCommandBusInterface,
    RecalculateSalesAtEvent\RecalculateSalesAtEventCommand,
};
use App\Modules\EventManagement\Domain\Events\AttendeeTicketWasCancelled;

final readonly class QueueTicketSalesReportRecalculation
{
    public function __construct(private InternalCommandBusInterface $bus)
    {
    }

    public function handle(AttendeeTicketWasCancelled $bus): void
    {
        $this->bus->queue(new RecalculateSalesAtEventCommand(
            $event->eventId,
        ));
    }
}
```

### Workflow Orchestration

When you have a complex process that needs to be executed asynchronously, you can define a workflow that orchestrates
the execution of multiple internal commands. This is a powerful way to decompose a complex process into smaller, more
manageable parts that can be executed asynchronously.

The simplest implementation of this is for each step in the workflow to be implemented as an internal command message.
When this is dispatched:

1. The command handler triggers a state mutation on the relevant aggregate root in your domain.
2. The aggregate root emits a domain event that signals the completion of the state mutation.
3. An application listener that subscribes to the domain event queues the next internal command in the workflow.

This is a simple approach, because there is no tracking of the progress of the workflow. Also, the domain events are not
_specific_ to the workflow - the workflow is being inferred from an aggregate's domain events.

There may be scenarios where actually you want to track progress, for example if you wanted to expose whether a workflow
has completed successfully or failed. Or where you need to disambiguate domain events so that subsequent internal work
is only queued if the domain event is _definitely_ a consequence of previous internal work.

In this case, you would implement a specific aggregate root that represents the state of the workflow in your domain.
This aggregate root would be responsible for tracking the progress of the workflow via state mutations, and emitting
domain events that signal the completion of each step in the workflow. These domain events are now _specific_ to the
workflow, because they are emitted by the workflow aggregate root.

This would allow you to implement a workflow that can be queried for its progress. Or have additional features - e.g.
commands that could cancel or retry the workflow.

### Internal Command Bus

If you are implementing internal commands, you will need an internal command bus that is separate from your public
command bus.

Technically, our internal command bus is a driving port of the application layer. This is because when the internal
command is queued by an infrastructure adapter, it has left the application layer. When that adapter pulls it from the
queue for processing, it needs to re-enter the application layer via a driving port.

However, by defining this as a separate port to our public command bus, we can ensure that internal commands are only
dispatched by the internal queue adapter.

Define the port as follows:

```php
namespace App\Modules\EventManagement\Application\Ports\Driving\CommandBus;

use CloudCreativity\Modules\Application\Ports\Driving\CommandBus\CommandDispatcher;

interface InternalCommandBus extends CommandDispatcher
{
}
```

And then our adapter (the concrete implementation of the port) is as follows:

```php
namespace App\Modules\EventManagement\Application\Adapters\CommandBus;

use App\Modules\EventManagement\Application\Ports\Driving\CommandBus\InternalCommandBusInterface;
use CloudCreativity\Modules\Application\Bus\CommandDispatcher;

final class InternalCommandBusAdapter extends CommandDispatcher implements
    InternalCommandBusInterface
{
}
```

:::info
See the [commands chapter](./commands) for details on how to create the adapter. This covers binding command handlers
and middleware into the command bus.
:::

To allow this bus to queue commands, it requires a driven port that can queue commands. This means there must also be a
queue adapter in the infrastructure layer that implements this port. Queue adapters are covered by
the [queues chapter in the infrastructure section.](../infrastructure/queues) The queue adapter is then injected into
the command bus.

Our approach is to define a port specifically for internal commands - rather than reusing a queue port for public
commands. I.e.:

```php
namespace App\Modules\EventManagement\Application\Ports\Driven\Queue;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue\Queue as Port;

// queues public commands
interface Queue extends Port
{
}

// queues internal commands
interface InternalQueue extends Port
{
}
```

This separation is useful because it allows each queue adapter to know exactly which command bus - the public or
internal bus - to dispatch the command to when it is pulled from the queue.