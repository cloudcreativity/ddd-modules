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
bounded context via a command, but does not need to wait for the result of that change. We provide a _command queuer_
implementation that allows commands to be dispatched in a non-blocking way, allowing the outside world to alter the
state of the domain asynchronously.

See the [Commands chapter](./commands.md#command-queuer) for details on how to define a command queuer port and
implementation.

### Example

A common example of this is where an HTTP controller intends to return a `202 Accepted` response. This indicates
that the request has been accepted for processing, but the processing has not been completed - i.e. is occurring
asynchronously.

For example, an endpoint that triggers a recalculation of our sales report:

```php
namespace App\Http\Controllers\Api\AttendanceReport;

use App\Modules\EventManagement\Application\{
    Ports\Driving\CommandQueuer,
    UsesCases\Commands\RecalculateSalesAtEvent\RecalculateSalesAtEventCommand,
};
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use Illuminate\Validation\Rule;

class ReportRecalculationController extends Controller
{
    public function __invoke(
        Request $request,
        CommandQueuer $bus,
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

Our approach is to define this work as _internal_ command messages. These are queued by a specific _internal_ queue, and
dispatched by a specific _internal_ command bus. This segregates them from the command bus that implements the command
bus driving port.

This segregation is important, because it means that internal commands cannot be dispatched by the outside world. And it
means internal commands are not exposed as use cases of our module - making them an internal implementation detail of
the application layer.

:::tip
If you have a command that can be queued by both the outside world and internally, you should define this as a use case
of your bounded context, i.e. a public command. When queuing internally within the application layer, the command can be
pushed directly onto the queue via the queue driven port. I.e. you do not need to go via the public command queuer.
:::

### Example

As an example, say we needed to recalculate a sales report as a result of an attendee cancelling their ticket. It may be
acceptable to our business logic that this is not immediately recalculated. This is an _eventual consistency_ approach,
i.e. derived data can be out-of-date for a short amount of time, as long as it is guaranteed to be updated.

We could push this internal work to a queue via a domain event listener:

```php
namespace App\Modules\EventManagement\Application\Internal\DomainEvents\Listeners;

use App\Modules\EventManagement\Application\Ports\Driven\Queue\{
    InternalQueue,
    Commands\RecalculateSalesAtEventCommand,
};
use App\Modules\EventManagement\Domain\Events\AttendeeTicketWasCancelled;

final readonly class QueueTicketSalesReportRecalculation
{
    public function __construct(private InternalQueue $queue)
    {
    }

    public function handle(AttendeeTicketWasCancelled $event): void
    {
        $this->queue->queue(new RecalculateSalesAtEventCommand(
            $event->eventId,
        ));
    }
}
```

:::tip
Notice that as this is an internal command, the command class is defined in the queue driven port namespace. This is to
ensure that the command is not exposed to the outside world.
:::

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

If you are implementing internal commands, you will need an internal command bus that is separate from your _driving_
port command bus.

We deal with this by defining the internal command bus as a _driven_ port. This is technically correct, as commands
cannot be queued unless we have infrastructure to support queuing messages. Therefore, the internal command bus works
nicely as a driven port.

Define the internal command bus as follows:

```php
namespace App\Modules\EventManagement\Application\Ports\Driven\Queue;

use CloudCreativity\Modules\Application\Ports\Driving\CommandBus\CommandDispatcher;

interface InternalCommandBus extends CommandDispatcher
{
}
```

And then our port adapter is as follows:

```php
namespace App\Modules\EventManagement\Application\Bus;

use App\Modules\EventManagement\Application\Ports\Driven\Queue\InternalCommandBus;
use CloudCreativity\Modules\Application\Bus\CommandDispatcher;

final class InternalCommandBusAdapter extends CommandDispatcher implements
    InternalCommandBus
{
}
```

:::info
See the [commands chapter](./commands) for details on how to create the adapter. This covers binding command handlers
and middleware into the command bus.
:::

You will also need a queue driven port that allows you to queue these internal commands. This means there must also be a
queue adapter in the infrastructure layer that implements this port. Queue adapters are covered by
the [queues chapter in the infrastructure section.](../infrastructure/queues)

One approach is to define a port specifically for queuing internal commands - rather than reusing the queue port for
public commands. I.e.:

```php
namespace App\Modules\EventManagement\Application\Ports\Driven\Queue;

use CloudCreativity\Modules\Contracts\Application\Ports\Driven\Queue as Port;

// injected into the command queuer for queuing public commands
interface Queue extends Port
{
}

// used by the application layer to queue internal commands
interface InternalQueue extends Port
{
}
```

This separation is useful because it allows each queue adapter to know exactly which command bus - the public or
internal bus - to dispatch the command to when it is pulled from the queue.

If you prefer, it is acceptable to define a single queue driven port. This simplifies the implementation by having a
single queue that deals with both. However, you might find it gets complicated knowing whether to dispatch queued
commands to either the public or internal command bus.