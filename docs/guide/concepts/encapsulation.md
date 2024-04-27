---
outline: deep
---

# Encapsulation

When implementing a domain-centric module, one of the key patterns to follow is _encapsulation_. This ensures there are
defined entry- and exit-points to the module. None of its internal concerns or implementation semantics are exposed to
the outside world.

This enforces a clean architecture, ensuring that bounded contexts are loosely coupled. However, it is also important
because it lowers the cognitive load on developers working with the module.

Encapsulation means a module is easy to reason about, because the developer only needs to think about the module
_itself_, not the outside world.

## Messages

In DDD, a message indicates an intention of transmitting and/or processing information by the domain. The way the
domain responds and processes these messages is not important from the outside world's perspective. The message simply
defines the intent and information via a data contract.

As mentioned in the previous chapter, there are three _types_ of messages: commands, queries and integration events.

### Commands

A command is a message that indicates an intention to _change_ the state of the bounded context. It is a _request_ to
perform an action that will result in a change to the bounded context's state. For example, "create a new order",
"remove an attendee from an event", "update a customer's details".

Command messages define the data contract for the information that needs to be provided to perform the action. They are
dispatched to the _command bus_, which is an entry point to the application layer of the bounded context.

### Queries

A query is a message that indicates an intention to _read_ the state of the bounded context. It is a _request_ to
retrieve information from the bounded context. For example, "get the total number of attendees for an event",
"retrieve the details of a customer", "get the list of orders for a customer".

Query messages define the data contract for the information that is required to determine exactly what needs to be read
from the bounded context. They are dispatched to the _query bus_, which is an entry point to the application layer of
the bounded context.

### Integration Events

An integration event is a message that indicates an intention to _notify_ other bounded contexts of a change that has
occurred. It is a _notification_ that something has happened, and other bounded contexts may need to react to it. For
example, "an order has been paid", "a customer has been created", "an event has been cancelled".

Integration event messages define the data contract for the information that is being notified to other bounded
contexts. They are published to an _event bus_, which is the exit point from the application layer of the bounded
context.

Additionally, integration events from other bounded contexts are _subscribed_ to by the bounded context. The application
layer defines how the bounded context reacts to these events.

:::tip
Integration events are bi-directional. They are both _published_ by the bounded context, and _consumed_ by the
bounded context. This means we can refer to them in terms of their direction - specifically:

- **Outbound** integration events, are those _published_ by the bounded context.
- **Inbound** integration events, are those the bounded context _consumes_.
  :::

## Application Interface

The application layer of a bounded context defines its uses cases. These are the _commands_ and _queries_ that can be
dispatched to the bounded context, and the _integration events_ that the bounded context consumes.

We follow a hexagonal architecture, where the application layer defines the _driving ports_ that it exposes to the
outside world.

This means your bounded context's interface could be expressed as follows:

```php
namespace App\Modules\EventManagement\Application\Ports\Driving;

use App\Modules\EventManagement\Application\Ports\Driving\{
    CommandBus\CommandBusInterface,
    InboundEventBus\EventBusInterface,
    QueryBus\QueryBusInterface,
};

interface EventManagementApplicationInterface
{
    public function getCommandBus(): CommandBusInterface;

    public function getQueryBus(): QueryBusInterface;

    public function getEventBus(): EventBusInterface;
}
```

This is for illustrative purposes - you may not want to define an application interface like this as each driving port
can be consumed separately.

However, it illustrates how the bounded context is fully encapsulated and described by the three messages types it can
handle. Everything else - e.g. domain entities containing business logic, coordination with the infrastructure layer,
etc - is hidden as an internal implementation detail of your bounded context.

:::tip
In the above example interface, it is important to note that there is a specific interface for the event management's
command, query and event buses. This is intentional. Although there are _generic_ command, query and event bus
interfaces, the purpose of the event management application is to expose the _specific_ buses for the event management
bounded context. Therefore, there are _specific_ event management bus interfaces.
:::

## Coupling

Although bounded contexts are encapsulated, there are times when context-to-context communication is required. For
example, our "event management" bounded context may need to amend its attendee totals when a customer completes an
order. However, completing orders is a concern of the "ordering" bounded context.

Bounded contexts should have clear _boundaries_ that define how they communicate with other contexts. This is
achieved either by _loose_ or _direct_ coupling - with _loose_ coupling being the preferred approach.

### Loose Coupling

Bounded contexts are loosely coupled via integration events, as described above. These events allow loose coupling
because all a bounded context needs to do is publish the event to an event bus. What happens as a result of this
publishing is not the concern of the bounded context - it is the concern of the other bounded contexts that _consume_
that event by subscribing to it.

Additionally, communication via integration events is _asynchronous_. This means that the bounded context does not need
to wait for a response from the other bounded context. It simply publishes the event and continues with its own
execution. _"Fire and forget"_ is a good way of thinking of it!

### Direct Coupling

Direct coupling is when a bounded context directly calls another context, typically via some sort of infrastructure
implementation. The most vivid example of this is when a bounded context calls the microservice of another bounded
context, e.g. over HTTP or gRPC. It does this either to immediately invoke an action in that other bounded context,
or to retrieve current state from the other context.

Here, the invoking of an action in the other bounded context maps directly to sending a command message to that bounded
context. The retrieval of the current state of the other context maps directly to sending a query message to
that bounded context. Both happen immediately, i.e. are synchronous in nature. This means the calling bounded context
needs to wait for a response from the other context before it can continue with its own execution.

### Consumers

When a bounded context couples with another, we refer to it being a _consumer_ of the other context.

A bounded context that subscribes to an integration event from another _consumes_ information from that bounded context.
The consumption is loose, asynchronous in nature, and the data contract is defined on the integration event message.

A bounded context that directly calls another bounded context is also a _consumer_ of that context. The consumption
is direct, synchronous in nature, and the data contract is typically defined by a client interface - for example,
a Software Development Kit (SDK). That client interface defines the _immediate_ operations that can be called on
the other bounded context, with these operations mapping directly to commands and/or queries in that other bounded
context.

:::info
For example, if the client interface internally used HTTP to communicate with a microservice that represents the other
bounded context:

- A `GET` request would result in a query message being dispatched to the bounded context _within_ the microservice -
  with the resulting state being returned in the HTTP response.
- A `POST` request would result in a command message being dispatched to the bounded context _within_ in the
  microservice. It may also result in a query message if the API is returning the current state of the context in the
  HTTP response.
:::
