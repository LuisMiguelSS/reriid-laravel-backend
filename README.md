<p align="center">
    <img alt="Reriid"
        src="public/static/logo-transparent.png"
        style="vertical-align: middle; width: 80%; height: auto; max-width: 400px;"
    />
</p>

<h2 align="center">BACKEND</h2>

<p align="center">
    <!-- Laravel -->
    <a href="https://laravel.com/docs/7.x/readme" alt="Laravel Framework">
        <img src="https://img.shields.io/badge/laravel-7.7.1-red"/>
    </a>
    <!-- Passport -->
    <a href="https://laravel.com/docs/master/passport" alt="Passport Package">
        <img src="https://img.shields.io/badge/passport-8.4.4-yellow"/>
    </a>
    <!-- Php -->
    <a href="https://www.php.net/releases/7_4_1.php">
        <img src="https://img.shields.io/badge/php-7.4.1-blue">
    </a>
    <!-- Npm -->
    <a href="https://www.npmjs.com/package/node/v/6.14.4">
        <img src="https://img.shields.io/badge/npm-6.14.4-orange"/>
    </a>
</p>

## Index
1. [About this Backend](#headerThisBackend)
    * [OAuth 2.0](#headerOAuth2)
    * [Testing](#headerTesting)
    * [Deployment](#headerDeployment)
2. [Laravel](#headerLaravel)
3. [Npm](#headerNpm)
4. [License](#headerLicense)


## <a name="headerThisBackend"></a> 1. About this Backend
This backend provides an API for dealing with the database information. It uses [Passport][], which is an [OAuth2][] and authentication package.

### <a name="headerOAuth2"></a> OAuth 2.0

The OAuth 2.0 Authorization Framework, defined in the [RFC-6749][], provides authorization control for all kinds of applications, wether they may be web or desktop based.

### <a name="headerTesting"></a> Testing

In order to test locally firstly you'll need to have a working web server and database.
1. Configuring the **.env** file.
You may use the same configuration from [Deployment](#headerDeployment) but you **must** change the following variables as follows:

```
APP_ENV=development
APP_DEBUG=true
...
```
2. Install [Passport][].
```php
php artisan passport:install
```
3. If after the install you don't have any keys stored in the **.env** file under the ```APP_KEY``` variable, you must run:
```php
php artisan key:generate
```
4. Run the migrations.
```php
php artisan migrate
```
5. Running the live server.
- Use the command ```php artisan serve```
- On a new terminal execute ```php artisan queue:work --queue=email```

### <a name="headerDeployment"></a> Deployment

This guide takes into account that the web server has been previously configured.
To successfully deploy this backend, you must:
1. Properly configure the **.env** file.
```
APP_ENV=production
APP_DEBUG=false
...
APP_URL=https://...
...
DB_CONNECTION=... <- wether it is mysql or other DB
DB_HOST=... <- host (IP) in charge of the DB
DB_PORT=...
DB_DATABASE=... <- name of the DB
DB_USERNAME=...
DB_PASSWORD=...
...
QUEUE_CONNECTION=database
...
MAIL_DRIVER=smtp
MAIL_HOST=...
MAIL_PORT=...
MAIL_USERNAME=... <- email account
MAIL_PASSWORD=...
MAIL_ENCRYPTION=... <- SSL/TLS/STARTTLS...
MAIL_FROM_ADDRESS=... <- email account
MAIL_FROM_NAME="${APP_NAME}"
...
```
2. Generate application keys.
```php
php artisan key:generate
```
3. Install [Passport][].
```php
php artisan passport:install
```
4. Migrate the database.
```php
php artisan migrate --force
```
5. Optimize autoloader.
```php
composer install --optimize-autoloader --no-dev
```
6. Optimize configurations.
```php
php artisan config:cache
```
7. Optimize API routes.
```php
php artisan route:cache
```
8. Precompile views.
```php
php artisan view:cache
```
9. Install & configure [supervisor](https://laravel.com/docs/5.1/queues#supervisor-configuration).
This will take care of the queued jobs such as email sending and the websocket for the realtime chat.
10. Ready to go!

### <a name="headerLaravel"></a> 2. About Laravel

Laravel is a web application framework with expressive, elegant syntax. The authors believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects.

More info: [Laravel website][].

### <a name="headerNpm"></a> 3. About Npm
npm is the package manager for the Node JavaScript platform. It puts modules in place so that node can find them, and manages dependency conflicts intelligently.

It is extremely configurable to support a wide variety of use cases. Most commonly, it is used to publish, discover, install, and develop node programs.

Discover more at the [NPM site][].

## License - ©Copyright

This work is copyrighted by [Luis Miguel Soto Sánchez][] and thus, cannot be distributed or copied in any way unless strictly told by the author.

<!-- Links -->
[Passport]: https://github.com/laravel/passport
[Laravel website]: https://laravel.com/
[OAuth2]: https://oauth.net/2/
[RFC-6749]: https://tools.ietf.org/html/rfc6749
[NPM site]: https://www.npmjs.com/
[Luis Miguel Soto Sánchez]: https://github.com/luismiguelss