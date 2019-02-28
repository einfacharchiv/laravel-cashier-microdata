# Add Microdata to your Laravel Cashier invoices (supporting JSON-LD)

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This package provides an easy way to add Microdata (in JSON-LD format) to your Laravel Cashier invoices.

## Requirements
PHP 7.0 and later.

## Installation
You can install this package via [Composer](http://getcomposer.org). Run the following command:

```bash
composer require einfacharchiv/laravel-cashier-microdata
```

## Usage
Add the following snippet to your invoice notification `toMail` method and adjust the values to your environment. `$this->invoice` is your `\Laravel\Cashier\Invoice` (or alternatively `\Stripe\Invoice`). The methods `setSeller`, `setBuyer`, and `setUrl` are optional.

```php
// ...

public function toMail($notifiable)
{
    \einfachArchiv\LaravelCashierMicrodata\Invoice::getInstance()
        ->setInvoice($this->invoice)
        ->setSeller([
            'company' => config('app.vendor.company'),
            'first_name' => config('app.vendor.first_name'),
            'last_name' => config('app.vendor.last_name'),
            'street_address' => config('app.vendor.street_address'),
            'city' => config('app.vendor.city'),
            'zip' => config('app.vendor.zip'),
            'state' => config('app.vendor.state'),
            'country' => config('app.vendor.country'),
            'vat_id' => config('app.vendor.vat_id'),
            'email' => config('app.vendor.email'),
            'website' => config('app.vendor.website'),
        ])
        ->setBuyer([
            'company' => $notifiable->billing_company,
            'first_name' => $notifiable->billing_first_name,
            'last_name' => $notifiable->billing_last_name,
            'street_address' => $notifiable->billing_street_address,
            'city' => $notifiable->billing_city,
            'zip' => $notifiable->billing_zip,
            'state' => $notifiable->billing_state,
            'country' => $notifiable->billing_country,
            'vat_id' => $notifiable->vat_id,
            'email' => $notifiable->email,
            'website' => $notifiable->website,
        ])
        ->setUrl(route('invoices.show', $this->invoice->id));

    // return (new MailMessage())...
}

// ...
```

And add the following snippet to your `resources/views/vendor/mail/html/layout.blade.php` before the `</body>` tag.

```php
    <!-- ... -->

    {!! \einfachArchiv\LaravelCashierMicrodata\Invoice::getInstance() !!}
</body>
</html>
```

## Contributing
Contributions are **welcome**.

We accept contributions via Pull Requests on [Github](https://github.com/einfachArchiv/laravel-cashier-microdata).

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improvement? Feel free to [create an issue on GitHub](https://github.com/einfachArchiv/laravel-cashier-microdata/issues), we'll try to address it as soon as possible.

If you've found a security issue, please email [support@einfacharchiv.com](mailto:support@einfacharchiv.com) instead of using the issue tracker.

**Happy coding**!

## Credits
- [Philip Günther](https://github.com/Pag-Man)
- [All Contributors](https://github.com/einfachArchiv/laravel-cashier-microdata/contributors)

## License
The MIT License (MIT). Please see [License File](LICENSE) for more information.
