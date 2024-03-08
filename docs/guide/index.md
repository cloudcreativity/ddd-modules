# What is DDD Modules?

You've decided to use domain-driven design (DDD) as your architectural approach. You've engaged with business experts,
maybe even held some Event Storming sessions, and can now talk fluently in the ubiquitous language of your domain. Now
you need to start writing the domain code.

But how? How should your code be structured? How do you ensure that the bounded context you're writing is entirely
encapsulated? How do you enforce architectural boundaries between bounded contexts, and ensure that they are loosely
coupled?

This package provides a conceptual approach and a set of tooling to help you write loosely coupled and highly
encapsulated bounded contexts - or modules - in PHP.

Whether you are on a journey to split a monolith into a modular monolith, or building out a microservices architecture,
this package will help you keep your bounded contexts clean, highly unit-testable, and easy to reason about.
It also ensures you have code consistency across all bounded contexts, while allowing plenty of flexibility for
each context to be tailored to its specific needs.

## Where to Start?

This package provides a common PHP toolset for writing domain-centric modules.

**It can be tempting to dive straight into the code - you'll need to resist that temptation!**

The key to achieving highly encapsulated and loosely coupled modules is _how you use the tools in this package_. That is
described in the documentation - so ensure you read the docs before using these tools.

[You can start reading the core concepts here.](./concepts/)

## Inspiration

The tools provided by this package have emerged out of a real-life project -
[dancecloud.com](https://www.dancecloud.com).

DanceCloud followed a fairly typical journey of development - evolving from a prototype of an idea, to an application
with a high level of complexity, and lots of interdependent and highly-coupled features. Like many projects, it had
become a complex monolith.

Looking at a long list of additional features to add, a better architecture and a domain-centric approach was
desperately needed. Although microservices would in theory be desirable, they would add complexity to the
infrastructure which would be unnecessary for the size of the project at that time.

The solution was to incrementally transition to a modular monolith - but doing this in a way which would provide a clear
path to microservices in the future. I.e. an evolution along these lines:

1. Monolith
2. Monolith + some modules
3. Modular Monolith
4. Modular Monolith + some modules extracted to microservices
5. Microservices

This package emerged out of the journey from a monolith to a modular monolith. It provided a toolset to write each
bounded context as its own module within the monolith, while also guaranteeing that each module was loosely coupled
and highly encapsulated. This guarantee is needed to ensure modules can be extracted to microservices as needed in the
future.
