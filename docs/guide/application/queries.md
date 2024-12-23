# Queries

A query is a message that indicates an intention to _read_ the state of the bounded context. It is a _request_ to
retrieve information from the bounded context. For example, "get the total number of attendees for an event",
"retrieve the details of a customer", "get the list of orders for a customer".

Query messages define the data contract for the information that is required to determine exactly what needs to be read
from the bounded context. They are dispatched to the _query bus_, and executed by _query handlers_.

## Query Messages

Query messages are defined by writing a class that implements the `Query` interface. The class should be named
according to the request it represents, and should contain properties that represent the scope of the data requested.
I.e. it defines the data contract for the request.

For example:

```php
namespace App\Modules\EventManagement\Application\UseCases\Queries\GetAttendeeTickets;

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Query;

final readonly class GetAttendeeTicketsQuery implements Query
{
    public function __construct(
        public Identifier $attendeeId,
    ) {
    }
}
```

## Query Handlers

A query handler is a class that is responsible for performing the request described by a query. It is a _use case_ in
the application layer of the bounded context. The query handler is responsible for validating the query, performing
the data collection, and returning the result.

Your query handler defines the use case - by type-hinting the query input and the result output. For example:

```php
namespace App\Modules\EventManagement\Application\UseCases\Queries\GetAttendeeTickets;

use App\Modules\EventManagement\Application\Ports\Driven\Persistence\ReadModels\V1\TicketModelRepository;
use CloudCreativity\Modules\Toolkit\Results\Result;
use VendorName\EventManagement\Shared\ReadModels\V1\TicketModel;

final readonly class GetAttendeeTicketsHandler
{
    public function __construct(
        private TicketModelRepository $repository,
    ) {
    }

    /**
     * Execute the query.
     * 
     * @param GetAttendeeTicketsQuery $query
     * @return Result<list<TicketModel>>
     */
    public function handle(GetAttendeeTicketsQuery $query): Result
    {
        $models = $this->repository->findByAttendeeId($query->attendeeId);

        if (count($models) === 0) {
            return Result::failed('The provided attendee does not exist.');
        }

        return Result::ok($models);
    }
}
```

:::info
Notice we've used a ["read model"](#read-models) here. That's intentional - and is explained later in this chapter.
:::

As a reminder, queries must **never** alter the _state of the system_ - including never triggering any side effects
that alter the state. A query is a request to _read_ the state, and a command should be used to _change_ the state.

:::tip
You'll notice here that the example is very simple. The application layer hands off the request to the infrastructure
layer via a driven port, and returns the result. This is a common pattern for queries, as the logic is often very
simple.

There may be times when your query handlers need to do a lot more work. For instance, there is an example in the
[domain services chapter](../domain/services#query-handlers) that shows a query handler executing business logic and
returning a result representing the outcome of that logic.
:::

### Results

Just like commands, queries handlers return a result object - which contains the resulting data as its value.
See the [Results chapter for information on using this object.](../toolkit/results)

Unlike command results, query results can contain complex data structures as their return value. It is best to define
these data structures - which is why our recommended pattern is to return [read models.](#read-models)

## Query Bus

To allow the _outside world_ to execute queries, our bounded context must expose a _query bus_ as a driving port.
Although there is a _generic_ query bus interface, our bounded context needs to expose its _specific_ query bus.

We do this by defining an interface in our application's driving ports:

```php
namespace App\Modules\EventManagement\Application\Ports\Driving;

use CloudCreativity\Modules\Contracts\Application\Ports\Driving\QueryDispatcher;

interface QueryBus extends QueryDispatcher
{
}
```

And then our implementation is as follows:

```php
namespace App\Modules\EventManagement\Application\Bus;

use App\Modules\EventManagement\Application\Ports\Driving\QueryBus as Port;
use CloudCreativity\Modules\Application\Bus\QueryDispatcher;

final class QueryBus extends QueryDispatcher implements Port
{
}
```

### Creating a Query Bus

The query dispatcher class that your implementation extends (in the above example) allows you to build a query bus 
specific to your domain. You do this by:

1. Binding query handler factories into the query dispatcher; and
2. Binding factories for any middleware used by your bounded context; and
3. Optionally, attaching middleware that runs for all queries dispatched through the query bus.

Factories must always be _lazy_, so that the cost of instantiating command handlers or middleware only occurs if the
handler or middleware are actually being used.

For example:

```php
namespace App\Modules\EventManagement\Application\Bus;

use App\Modules\EventManagement\Application\UseCases\Queries\{
    GetAttendeeTickets\GetAttendeeTicketsQuery,
    GetAttendeeTickets\GetAttendeeTicketsHandler,
};
use App\Modules\EventManagement\Application\Ports\Driving\QueryBus as QueryBusPort;
use App\Modules\EventManagement\Application\Ports\Driven\DependencyInjection\ExternalDependencies;
use CloudCreativity\Modules\Application\Bus\QueryHandlerContainer;
use CloudCreativity\Modules\Application\Bus\Middleware\LogMessageDispatch;
use CloudCreativity\Modules\Toolkit\Pipeline\PipeContainer;

final class QueryBusProvider
{
    public function __construct(
        private readonly ExternalDependencies $dependencies,
    ) {
    }

    public function getQueryBus(): QueryBusPort
    {
        $bus = new QueryBus(
            handlers: $handlers = new QueryHandlerContainer(),
            middleware: $middleware = new PipeContainer(),
        );

        /** Bind queries to handler factories */
        $handlers->bind(
            GetAttendeeTicketsQuery::class,
            fn() => new GetAttendeeTicketsHandler(
                $this->dependencies->getTicketModelRepository(),
            ),
        );

        /** Bind middleware factories */
        $middleware->bind(
            LogMessageDispatch::class,
            fn () => new LogMessageDispatch(
                $this->dependencies->getLogger(),
            ),
        );

        /** Attach middleware that runs for all queries */
        $bus->through([
            LogMessageDispatch::class,
        ]);

        return $bus;
    }
}
```

Adapters in the presentation and delivery layer will use the driving ports. Typically this means we need to bind the port into a service container. For example, in Laravel:

```php
namespace App\Providers;

use App\Modules\EventManagement\Application\{
    Bus\QueryBusProvider,
    Ports\Driving\QueryBus,
};
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

final class EventManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            QueryBus::class,
            static function (Container $app)  {
                $provider = $app->make(QueryBusProvider::class);
                return $provider->getQueryBus();
            },
        );
    }
}
```

### Dispatching Queries

You can now dispatch query messages to your bounded context from the _outside world_. For example, if we were using
a single action controller to handle a HTTP request in a Laravel application, we might dispatch a query like this:

```php
namespace App\Http\Controllers\Api\Attendees;

use App\Modules\EventManagement\Application\{
    Ports\Driving\QueryBus\QueryBus,
    UsesCases\Queries\GetAttendeeTickets\GetAttendeeTicketsQuery,
};
use App\Http\Resources\Attendees\TicketsResource;
use CloudCreativity\Modules\Toolkit\Identifiers\IntegerId;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;
use Illuminate\Validation\Rule;
use VendorName\EventManagement\Shared\ReadModels\V1\TicketModel;

class TicketsController extends Controller
{
    public function __invoke(
        QueryBus $bus,
        string $attendeeId,
    ): TicketsResource {
        $query = new GetAttendeeTicketsQuery(
            attendeeId: new IntegerId((int) $attendeeId),
        );

        /** @var Result<list<TicketModel>> $result */
        $result = $bus->dispatch($query);

        return new TicketsResource($result->value());
    }
}
```

:::tip
Here you can see that the event management bounded context is entirely [encapsulated.](../concepts/encapsulation)
The outside world uses the combination of the query message, with the driving port it needs to dispatch the message.
Everything else - how your bounded context processes and responds to the query - is hidden as an _internal
implementation detail_ of your domain.
:::

## Read Models

Read models are a way of describing the data that is returned from a query. They are a _model_ of data that
represents some current state of the bounded context. They are _read-only_ i.e. follow the immutability principle.

For example, our model returned by our "get attendee tickets" query might look like this:

```php
namespace VendorName\EventManagement\Shared\ReadModels\V1;

use CloudCreativity\Modules\Contracts\Toolkit\Identifiers\Identifier;

final readonly class TicketModel
{
    /**
     * TicketModel constructor.
     *
     * @param Identifier $id
     * @param Identifier $attendeeId
     * @param list<ActivitiesModel> $attending
     */
    public function __construct(
        public Identifier $id,
        public Identifier $attendeeId,
        public array $attending,
    ) {
    }
}
```

A read model can contain other models, and also value objects and enums. Collectively they are all _read-only_
so can be freely passed around without fear of the data being altered.

One thing to note here is that the ticket model is different from the ticket entity that exists in the domain. In the
domain layer the ticket entity is part of the attendee aggregate root, so does not need an attendee id property.

This is not unusual - and in fact, it is actually good design to have different data structures for read and write
operations. This gives a clear separation of concerns.

Aggregate roots and entities represent the data structure that is required to determine _if_ the state of the domain can
be
changed, and _what_ to change it to - plus what domain events should be emitted as a result. In our example domain, the
attendee aggregate root controls changes to its tickets - therefore the tickets are always contained within the attendee
aggregate root.

Read models represent the answer to a question posed by a query, and are structured in a way that we can understand the
state of the domain. In our example, it makes sense for tickets to be retrieved independently of the attendee - e.g. if
we wanted to display a list of all tickets. The ticket model can therefore exist in isolation, and can be linked to the
attendee via an attendee identifier property.

:::info
This is one of the big advantages of using the Command Query Responsibility Separation (CQRS) pattern. It allows you
to structure the data required for write operations (commands) in a completely separate way to the data required to
represent the current state of the system in response to read operations (queries). Each is modelled for their specific
purpose.

When you start writing a bounded context, you may find these data structures are very similar. However, it is inevitable
that as your domain scales, the data structures required for read and write operations will diverge. Sometimes
significantly. This is why it is important to start with a clear separation of concerns from the beginning.
:::

### Versioning

Read models may be consumed by other bounded contexts. For example, by a client that returns the read model it receives
by calling your bounded context's microservice.

This mean you cannot make breaking changes to the data contract without updating every single consumer to use the new
contract.

In large systems, this can be a significant challenge. To mitigate this, you can version your read models. This
allows you to introduce breaking changes to the data contract, while still supporting older versions. For
example, our read models could be in `ReadModels\V1` and `ReadModels\V2` namespaces.

This allows you to introduce a new version of the model, while retaining the model name. Retaining the model name is
important because it is an expression of your domain, using the ubiquitous language of your bounded context. If you do
not version your read models, you'll be forced to rename the model just to introduce a new data contract. Whereas
the priority should be to keep the language of the domain.

This means that when you introduce a new version of the model, the originating bounded context can define both v1 and v2
queries, which return the specific version of the model. You can then introduce a new versioned API endpoint and add
this version to your client interface in your consumer package. Over time you can migrate all consumers to the new
version, and then remove the old version.

## Middleware

Our query bus implementation gives you complete control over how to compose the handling of your queries, via
middleware. Middleware is a powerful way to add cross-cutting concerns to your command handling, such as logging.

Middleware can be added either to the query bus (so it runs for every query) or to individual query handlers.

To apply middleware to the query bus, you can use the `through()` method on the bus - as shown in the example above.
Middleware is executed in the order it is added to the bus.

To apply middleware to a specific query handler, the handler must implement the `DispatchThroughMiddleware` interface.
The `middleware()` method should then return an array of middleware to run, in the order they should be executed.
Handler middleware are always executed _after_ the bus middleware.

This package provides several useful middleware, which are described below. Additionally, you can write your own
middleware to suit your specific needs.

### Setup and Teardown

If you need to do any setup and/or teardown work around dispatching a query, use our `SetupAndTeardown` or
`TeardownAfterDispatch` middleware. These are described [here in the Commands chapter.](./commands#setup-and-teardown)
Their use is identical for queries.

### Logging

Use our `LogMessageDispatch` middleware to log the dispatch of a query, and the result. The middleware takes a
[PSR Logger](https://php-fig.org/psr/psr-3/).

```php
use CloudCreativity\Modules\Application\Bus\Middleware\LogMessageDispatch;

$middleware->bind(
    LogMessageDispatch::class,
    fn (): LogMessageDispatch => new LogMessageDispatch(
        $this->dependencies->getLogger(),
    ),
);
```

The use of this middleware is identical to that described in the [Commands chapter.](./commands#logging)
See those instructions for more information, such as configuring the log levels.

Additionally, if you need to customise the context that is logged for a query then implement the
`ContextProvider` interface on your query message. See the example in the [Commands chapter.](./commands#logging)

### Writing Middleware

You can write your own middleware to suit your specific needs. Middleware is a simple invokable class, with the
following signature:

```php
namespace App\Modules\EventManagement\Application\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\Bus\QueryMiddleware;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

final class MyMiddleware implements QueryMiddleware
{
    /**
     * Execute the middleware.
     *
     * @param Query $query
     * @param Closure(Query): Result<mixed> $next
     * @return Result<mixed>
     */
    public function __invoke(
        Query $query,
        Closure $next,
    ): Result
    {
        // code here executes before the handler

        $result = $next($command);

        // code here executes after the handler

        return $result;
    }
}
```

:::tip
If you're writing middleware that is only meant to be used for a specific query, do not implement the
`QueryMiddleware` interface. Instead, use the same signature but change the type-hint for the query to the query
class your middleware is designed to be used with.
:::

If you want to write middleware that can be used with both commands and queries, implement the `BusMiddleware` interface
instead:

```php
namespace App\Modules\EventManagement\Application\Bus\Middleware;

use Closure;
use CloudCreativity\Modules\Contracts\Application\Bus\BusMiddleware;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Command;
use CloudCreativity\Modules\Contracts\Toolkit\Messages\Query;
use CloudCreativity\Modules\Contracts\Toolkit\Result\Result;

class MyBusMiddleware implements BusMiddleware
{
    /**
     * Handle the command or query.
     *
     * @param Command|Query $message
     * @param Closure(Command|Query): Result<mixed> $next
     * @return Result<mixed>
     */
    public function __invoke(
        Command|Query $message, 
        Closure $next,
    ): Result
    {
        // code here executes before the handler

        $result = $next($command);

        // code here executes after the handler

        return $result;
    }
}
```
