# Laravel namecheap client

## Description

This project is built using Laravel with the Filament admin panel. It offers the following features:

- Connect Namecheap accounts using Namecheap login and API key
- View the balance of your Namecheap account, list of domains (with pagination), and perform domain search within the list
- Purchase domains by checking domain availability and using funds in the account
- Modify DNS records settings for each domain
- Update contact information for each domain in the account

The application provides a user-friendly interface to manage Namecheap domains and account settings efficiently.

## Requirements

- PHP 8.1+
- Composer
- MySQL (or other)
- Namecheap account or accounts (sandbox works too)

## Deploy instructions

1. Clone the project
```shell
git clone git@github.com:rnr1721/laravel-namecheap-client.git
```

2. Install composer dependencies

```shell
composer install
```

3. Copy env file from example

```shell
cp .env.example .env
```

4. Need to setup database settings and namecheap settings in .env file

Important! Your laravel server IP need to be in whitelist in Namecheap API account

```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=namecheapclient
DB_USERNAME=namecheapclient
DB_PASSWORD=1234567

NAMECHEAP_CLIENT_IP=XXX.XXX.XXX.XXX
NAMECHEAP_SANDBOX=true
```

5. Generate application key

```shell
php artisan key:generate
```

6. Make database migrations

```shell
php artisan migrate
```

7. Make filament user

```shell
php artisan make:filament-user
```

8. Setup your web server to serve /publid directory or run command below

```
php artisan serve
```

9. Thats all!
