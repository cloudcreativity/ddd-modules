# Bounded Contexts

Bounded contexts are a key concept in domain-driven design - and this package fully embraces them as a way of
writing highly-encapsulated and easy to reason about code. But what are they? :thinking:

## What is a Bounded Context?

A bounded context is a conceptual boundary around a domain model. Each bounded context has its own domain model - which
is a set of concepts, rules, and relationships that are relevant to the domain within that context.

What's neat about this concept is it allows you to reduce complexity. It does this by dividing a large domain model
into smaller, more manageable parts. This makes each individual part easier to understand and reason about.

### Example

In a large e-commerce application, the term _Order_ might mean different things in different contexts. In the context
of the warehouse, an order might be a set of items that need to be picked, packed and shipped. In the context of the
customer, an order might be a set of items that they have purchased.

In a monolith, these two concepts would be mixed together, leading to confusion and complexity. Your _Order_ entity
would end up being potentially thousands of lines long, with no clear clarity of whether it is being consumed in the
context of the warehouse or the customer.

By splitting your domain into bounded contexts, you can create a clear separation of concerns. Each bounded context
can have its own _Order_ aggregate root, but it can be modelled exactly for the specific use case of that context.

## Modules

This package is called _DDD Modules_ because it originated from a need to split down a complex monolith into a
_modular monolith_. This [Modularisation process is covered in a later chapter.](./modularisation)

**The important thing to note is that there is a one-to-one relationship between a bounded context and a module. Each
module encompasses a singular bounded context.**

In a modular monolith, this allows you to decompose your domain into multiple fully encapsulated modules that each
represent a single bounded context.

In a microservices architecture, a microservice might encompass a single bounded context - therefore it would use this
package to implement a single module. However, it gives you the option of having a microservice that encompasses
a few bounded contexts - in which case you would have a few modules within that microservice.

:::warning
If your microservice encompasses too many bounded contexts, you loose some of the benefits of a microservices
architecture. For example, it is important to keep your microservices small and focused, so they can be scaled
independently.
:::
