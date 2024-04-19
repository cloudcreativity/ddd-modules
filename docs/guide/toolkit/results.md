# Results

This package provides a result object that can be used to represent the result of an operation that may fail. This is a
"result pattern", which is an alternative to throwing and catching exceptions. Returning a result object gives calling
code better control over how to handle failures, and typically makes the code easier to reason about.

The [Commands](../application/commands) and [Queries](../application/queries) that you define in your application layer
both use the result object to represent their outcome. You can also use the result object anywhere else that makes
sense in your code. For example, you might use it to represent the result of executing business logic via a
[domain service](../domain/services).

## Result Object

### Basic Usage

The simplest way of returning a success or failure result is as follows:

```php
use CloudCreativity\Modules\Toolkit\Result\Result;

// successful
return Result::ok();

// failure
return Result::failed('Something went wrong.');
```

Calling code can check whether the result object represents a success or failure using the `didSucceed()` or
`didFail()` methods:

```php
$result = $bus->dispatch($command);

if ($result->didFail()) {
    throw new \RuntimeException('Not expecting command to fail.');
}
```

Alternatively, call the `abort()` method to throw a [failed result exception](#exception) if the result is a failure.
The previous example can be rewritten as follows:

```php
$result = $bus->dispatch($command);
$result->abort();
// here result is definitely a success.
```

### Success Values

To return a successful result with a value, pass the value to the `ok()` method:

```php
$model = $this->readModelRepository->find($id);

return Result::ok($model);
```

Calling code can then access the value using the `value()` method:

```php
$result = $bus->dispatch($query);

if ($result->didSucceed()) {
    return $result->value();
}
```

If you use the `value()` method without checking whether the result is a success, it will throw an exception when you
attempt to access the value from a failed result. For example:

```php
$result = $bus->dispatch($query);
// throws an exception if the result is a failure
return $result->value();
```

There can sometimes be scenarios where calling code does not care if the result succeeds or fails. In this case, use
the `safe()` method to access the value. This does not throw an exception if the result is a failure, but instead
returns `null`:

```php
$result = $bus->dispatch($query);
// returns null if the result is a failure
return $result->safe();
```

### Failures

When creating a failed result, you must provide something about the error. This can either be a message, an error code
(expressed as a backed enum), or an [error object or list of errors](#errors).

If you provide a string message, the failed result will be created with a single error object with its message set.
For example:

```php
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;

Result::failed('Something went wrong.');
// is a short-hand for:
Result::failed(new ListOfErrors(
    new Error(message: 'Something went wrong.'),
));
```

If you provide an enum, the failed result will be created with a single error object with its code set. For
example:

```php
$code = CancelAttendeeTicketError::AlreadyCancelled;

Result::failed($code);
// is a short-hand for:
Result::failed(new ListOfErrors(
    new Error(code: $code),
));
```

Alternatively, you can provide the `failed()` method with an error object or a list of errors that you have constructed.

## Errors

### Error Properties

The error object provided by this package has the following properties:

- `message`: a string message that describes the error.
- `code`: a backed enum that identifies error and can be used to programmatically detect and handle specific errors. It
  is intentionally an enum, because if you need to detect specific errors, the codes need to be a defined list of
  values - which is an enum.
- `key`: optionally, a key for the error. This can be used to group errors by keys, for example to group errors by
   properties that exist on a command message.

Error objects _must_ be instantiated with either a code or a message. They are immutable, so you cannot change their
properties after they have been created.

Use named parameters when constructing the error:

```php
use CloudCreativity\Modules\Toolkit\Result\Error;

$error = new Error(
    code: CancelAttendeeTicketError::AlreadyCancelled,
    message: 'The ticket has already been cancelled.',
    key: 'ticketId',
);
```

### Error Lists

The `ListOfErrors` class is a collection of error objects. It is immutable, but does provide some methods to help you
build up a list of errors.

```php
use CloudCreativity\Modules\Toolkit\Result\Error;
use CloudCreativity\Modules\Toolkit\Result\ListOfErrors;

$errors = new ListOfErrors(
    new Error(message: 'Problem 1'),
    new Error(message: 'Problem 2'),
);
```

Use the `push()` method to return a new error list with an additional error added to the end:

```php
$errors = $errors->push(new Error(message: 'Problem 3'));
```

You can also merge two list of errors into a new list of errors:

```php
$errors3 = $errors1->merge($errors2);
```

:::tip
The error list class is iterable, countable and has `isEmpty()` and `isNotEmpty()` helper methods.
:::

### Keyed Error Sets

If you are using the `key` property on error objects, we provide a keyed set of errors class that groups errors by their
key.

To create one, provide error objects to its constructor:

```php
use CloudCreativity\Modules\Toolkit\Result\KeyedSetOfErrors;

$keyedErrors = new KeyedSetOfErrors(
    new Error(message: 'Problem 1', key: 'a'),
    new Error(message: 'Problem 2', key: 'b'),
    new Error(message: 'Problem 3', key: 'a'),
);
```

Or if you already have the errors as a list of errors, use the static `from()` method:

```php
$keyedErrors = KeyedSetOfErrors::from($errors);
```

:::tip
There's a number of helper methods on the keyed set class - see the class for more details.
:::

### Result Errors

Errors can be accessed on a result object via its `errors()` method:

```php
$errors = $result->errors();
```

On a successful result, the list of errors will be empty. On a failed result, there will always be at least one error.

The result object also has a helper `error()` method. This returns the first error message in the list of errors, or
`null` if there are no errors with messages. This is useful when you want to provide a default error message when
handling a failed result:

```php
if ($result->didFail()) {
    $message = $result->error() ?? 'unknown error';
    throw new \RuntimeException(
      'Not expecting command to fail: ' . $message,
    );
}
```

### Detecting Errors

If calling code needs to programmatically detect specific errors, you should use an enum to define the error codes that
a particular operation can return. Then you can use the `contains()` method on the list of errors to check whether a
specific error is present:

```php
$result = $bus->dispatch($command);
$errors = $result->errors();

if ($errors->contains(CancelAttendeeTicketError::AlreadyCancelled)) {
    // handle the specific error
}
```

## Exception

We provide a `FailedResultException` that you can use to throw a result object:

```php
use CloudCreativity\Modules\Toolkit\Result\FailedResultException;

$result = $bus->dispatch($command);

if ($result->didFail()) {
    throw new FailedResultException($result);
}
```

The exception message will be set to the first error message in the result, if there is one. Any code that catches
this exception can access the result object using the `getResult()` method.

When you call the `abort()` method on a result, the result object will throw a `FailedResultException` if it is a
failure.