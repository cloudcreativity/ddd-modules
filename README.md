# Domain Driven Design Modules

**Write highly encapsulated and loosely coupled modules, for domain-centric architecture.**

## What is this?

You've decided to use domain-driven design (DDD) as your architectural approach. You've engaged with business experts,
maybe even held some Event Storming sessions, and can now talk fluently in the ubiquitous language of your domain. Now
you need to start writing the domain code.

But how?! :thinking:

How should your code be structured? How do you ensure that the bounded context you're writing is entirely
encapsulated? How do you enforce architectural boundaries between bounded contexts, and ensure that they are loosely
coupled?

:melting_face:

Don't worry, we've got you! :saluting_face:

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

[You can read the documentation here.](https://cloudcreativity.github.io/ddd-modules)

## Installation

```bash
composer require cloudcreativity/ddd-modules
```

## License

DDD Modules is open-source software licensed under the [MIT License](./LICENSE).
