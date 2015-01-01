WellRESTed
==========

[![Build Status](https://travis-ci.org/pjdietz/wellrested.svg?branch=master)](https://travis-ci.org/pjdietz/wellrested)

WellRESTed is a micro-framework for creating RESTful APIs in PHP. It provides a lightweight yet powerful routing system and classes to make working with HTTP requests and responses clean and easy.

Version 2
---------

It's more RESTed than ever!

Version 2 brings a lot improvements over 1.x, but it is **not backwards compatible**. See [Changes from Version 1](https://github.com/pjdietz/wellrested/wiki/Changes-from-Version-1) if you are migrating from a previous 1.x version of WellRESTed.

Requirements
------------

- PHP 5.3
- [PHP cURL](http://php.net/manual/en/book.curl.php) for making requests with the [`Client`](src/pjdietz/WellRESTed/Client.php) class (Optional)


Install
-------

Add an entry for "pjdietz/wellrested" to your composer.json file's `require` property. If you are not already using Composer, create a file in your project called "composer.json" with the following content:

```json
{
    "require": {
        "pjdietz/wellrested": "2.*"
    }
}
```

Use Composer to download and install WellRESTed. Run these commands from the directory containing the **composer.json** file.

```bash
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
```

You can now use WellRESTed by including the `vendor/autoload.php` file generated by Composer.


Examples
--------

### Routing

WellRESTed's primary goal is to facilitate mapping of URIs to classes that will provide or accept representations. To do this, create a [`Router`](src/pjdietz/WellRESTed/Router.php) instance and load it up with some routes. Each route is simply a mapping of a path or path pattern to a class name. The class name represents the "handler" (any class implementing [`HandlerInterface`](src/pjdietz/WellRESTed/Interfaces/HandlerInterface.php) ) which the router will dispatch when it receives a request for the given URI. **The handlers are never instantiated or loaded unless they are needed.**

```php
// Build the router.
$myRouter = new Router();
$myRouter->addRoutes(array(
    new StaticRoute("/", "\\myapi\\Handlers\\RootHandler"),
    new StaticRoute("/cats/", "\\myapi\\Handlers\\CatCollectionHandler"),
    new TemplateRoute("/cats/{id}/", "\\myapi\\Handlers\\CatItemHandler")
));
$myRouter->respond();
```

See [Routes](https://github.com/pjdietz/wellrested/wiki/Routes) to learn about the various route classes.


### Handlers

Any class that implements [`HandlerInterface`](src/pjdietz/WellRESTed/Interfaces/HandlerInterface.php) may be the handler for a route. This could be a class that builds the actual response, or it could be another [`Router`](src/pjdietz/WellRESTed/Router.php).

For most cases, you'll want to use a subclass of the [`Handler`](src/pjdietz/WellRESTed/Handler.php) class, which provides methods for responding based on HTTP method. When you create your [`Handler`](src/pjdietz/WellRESTed/Handler.php) subclass, you will implement a method for each HTTP verb you would like the endpoint to support. For example, if `/cats/` should support `GET`, you would override the `get()` method. For `POST`, `post()`, etc.

Here's a simple Handler that allows `GET` and `POST`.

```php
class CatsCollectionHandler extends \pjdietz\WellRESTed\Handler
{
    protected function get()
    {
        // Read some cats from the database, cache, whatever.
        // ...read these an array as the variable $cats.

        // Set the values for the instance's response member. This is what the
        // Router will eventually output to the client.
        $this->response->setStatusCode(200);
        $this->response->setHeader("Content-Type", "application/json");
        $this->response->setBody(json_encode($cats));
    }

    protected function post()
    {
        // Read from the instance's request member and store a new cat.
        $cat = json_decode($this->request->getBody());
        // ...store $cat to the database...

        // Build a response to send to the client.
        $this->response->setStatusCode(201);
        $this->response->setBody(json_encode($cat));
    }
}
```

See [Handlers](https://github.com/pjdietz/wellrested/wiki/Handlers) to learn about the subclassing the [`Handler`](src/pjdietz/WellRESTed/Handler.php) class.
See [HandlerInteface](https://github.com/pjdietz/wellrested/wiki/HandlerInterface) to learn about more ways build completely custom classes.

### Responses

You've already seen a [`Response`](src/pjdietz/WellRESTed/Response.php) used inside a [`Handler`](src/pjdietz/WellRESTed/Handler.php) in the examples above. You can also create a [`Response`](src/pjdietz/WellRESTed/Response.php) outside of [`Handler`](src/pjdietz/WellRESTed/Handler.php). Let's take a look at creating a new [`Response`](src/pjdietz/WellRESTed/Response.php), setting a header, supplying the body, and outputting.

```php
$resp = new \pjdietz\WellRESTed\Response();
$resp->setStatusCode(200);
$resp->setHeader("Content-type", "text/plain");
$resp->setBody("Hello world!");
$resp->respond();
exit;
```

This will output nice response, complete with status code, headers, body.

### Requests

From outside the context of a [`Handler`](src/pjdietz/WellRESTed/Handler.php), you can also use the [`Request`](src/pjdietz/WellRESTed/Request.php) class to read info for the request sent to the server by using the static method `Request::getRequest()`.

```php
// Call the static method Request::getRequest() to get the request made to the server.
$rqst = \pjdietz\WellRESTed\Request::getRequest();

if ($rqst->getMethod() === 'PUT') {
    $obj = json_decode($rqst->getBody());
    // Do something with the JSON sent as the message body.
    // ...
}
```

### HTTP Client

The [`Client`](src/pjdietz/WellRESTed/Client.php) class allows you to make an HTTP request using cURL.

(This feature requires [PHP cURL](http://php.net/manual/en/book.curl.php).)

```php
// Prepare a request.
$rqst = new \pjdietz\WellRESTed\Request();
$rqst->setUri('http://my.api.local/resources/');
$rqst->setMethod('POST');
$rqst->setBody(json_encode($newResource));

// Use a Client to get a Response.
$client = new Client();
$resp = $client->request($rqst);

// Read the response.
if ($resp->getStatusCode() === 201) {
    // The new resource was created.
    $createdResource = json_decode($resp->getBody());
}
```

### Building Routes with JSON

WellRESTed also provides a class to construct routes for you based on a JSON description. Here's an example:

```php
$json = <<<'JSON'
{
    "handlerNamespace": "\\myapi\\Handlers",
    "routes": [
        {
            "path": "/",
            "handler": "RootHandler"
        },
        {
            "path": "/cats/",
            "handler": "CatCollectionHandler"
        },

        {
            "tempalte": "/cats/{id}",
            "handler": "CatItemHandler"
        }
    ]
}
JSON;

$builder = new RouteBuilder();
$routes = $builder->buildRoutes($json);

$router = new Router();
$router->addRoutes($routes);
$router->respond();
```

When you build routes through JSON, you can provide a `handlerNamespace` to be affixed to the front of every handler.


Copyright and License
---------------------
Copyright © 2015 by PJ Dietz
Licensed under the [MIT license](http://opensource.org/licenses/MIT)
