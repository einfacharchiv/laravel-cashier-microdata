<?php

namespace einfachArchiv\LaravelCashierMicrodata;

use Carbon\Carbon;
use Spatie\SchemaOrg\PaymentStatusType;
use Spatie\SchemaOrg\Schema;

class Invoice
{
    /**
     * The instance.
     *
     * @var Invoice
     */
    protected static $instance = null;

    /**
     * The invoice.
     *
     * @var \Laravel\Cashier\Invoice|\Stripe\Invoice|null
     */
    protected $invoice;

    /**
     * The seller.
     *
     * @var array
     */
    protected $seller = [];

    /**
     * The buyer.
     *
     * @var array
     */
    protected $buyer = [];

    /**
     * The URL.
     *
     * @var string|null
     */
    protected $url;

    /**
     * Returns the instance via lazy initialization (created on first usage).
     *
     * @return Invoice
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * It's not allowed to call from outside to prevent multiple instances from being created.
     * To use the singleton, you have to obtain the instance from Invoice::getInstance() instead.
     */
    protected function __construct()
    {
    }

    /**
     * Prevent the instance from being cloned (which would create a second instance of it).
     */
    protected function __clone()
    {
    }

    /**
     * Sets the invoice.
     *
     * @param \Laravel\Cashier\Invoice|\Stripe\Invoice
     *
     * @return self
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;

        return $this;
    }

    /**
     * Sets the seller.
     *
     * @param array $details
     *
     * @return self
     */
    public function setSeller(array $details)
    {
        return $this->setParty('seller', $details);
    }

    /**
     * Sets the buyer.
     *
     * @param array $details
     *
     * @return self
     */
    public function setBuyer(array $details)
    {
        return $this->setParty('buyer', $details);
    }

    /**
     * Sets the party.
     *
     * @param string $type
     * @param array  $details
     *
     * @return self
     */
    protected function setParty(string $type, array $details)
    {
        $this->{$type} = array_merge([
            'company' => null,
            'first_name' => null,
            'last_name' => null,
            'street_address' => null,
            'city' => null,
            'zip' => null,
            'state' => null,
            'country' => null,
            'vat_id' => null,
            'email' => null,
            'website' => null,
        ], $details);

        return $this;
    }

    /**
     * Returns the schema for the seller.
     *
     * @return \Spatie\SchemaOrg\Organization|\Spatie\SchemaOrg\Person
     */
    public function getSeller()
    {
        return $this->getParty('seller');
    }

    /**
     * Returns the schema for the buyer.
     *
     * @return \Spatie\SchemaOrg\Organization|\Spatie\SchemaOrg\Person
     */
    public function getBuyer()
    {
        return $this->getParty('buyer');
    }

    /**
     * Returns the schema for the party.
     *
     * @param string $type
     *
     * @return \Spatie\SchemaOrg\Organization|\Spatie\SchemaOrg\Person
     */
    protected function getParty(string $type)
    {
        if (isset($this->{$type}['company'])) {
            $party = Schema::organization()
                ->name($this->{$type}['company']);
        } else {
            $party = Schema::person()
                ->if(isset($this->{$type}['first_name']) || isset($this->{$type}['last_name']), function ($schema) use ($type) {
                    $schema->name($this->{$type}['first_name'].' '.$this->{$type}['last_name']);
                });
        }

        $postalAddress = $this->getPostalAddress($type);

        $party
            ->if(count($postalAddress->getProperties()), function ($schema) use ($postalAddress) {
                $schema->address($postalAddress);
            })
            ->if(isset($this->{$type}['vat_id']), function ($schema) use ($type) {
                $schema->vatID($this->{$type}['vat_id']);
            })
            ->if(isset($this->{$type}['email']), function ($schema) use ($type) {
                $schema->email($this->{$type}['email']);
            })
            ->if(isset($this->{$type}['website']), function ($schema) use ($type) {
                $schema->url($this->{$type}['website']);
            });

        return $party;
    }

    /**
     * Returns the schema for the postal address.
     *
     * @param string $type
     *
     * @return \Spatie\SchemaOrg\PostalAddress
     */
    public function getPostalAddress(string $type)
    {
        return Schema::postalAddress()
            ->if(isset($this->{$type}['street_address']), function ($schema) use ($type) {
                $schema->streetAddress($this->{$type}['street_address']);
            })
            ->if(isset($this->{$type}['city']), function ($schema) use ($type) {
                $schema->addressLocality($this->{$type}['city']);
            })
            ->if(isset($this->{$type}['zip']), function ($schema) use ($type) {
                $schema->postalCode($this->{$type}['zip']);
            })
            ->if(isset($this->{$type}['state']), function ($schema) use ($type) {
                $schema->addressRegion($this->{$type}['state']);
            })
            ->if(isset($this->{$type}['country']), function ($schema) use ($type) {
                $schema->addressCountry($this->{$type}['country']);
            });
    }

    /**
     * Sets the URL of the invoice.
     *
     * @param string $url
     *
     * @return self
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Returns the schema.
     *
     * @return \Spatie\SchemaOrg\Invoice|null
     */
    public function getSchema()
    {
        if (is_null($this->invoice)) {
            return;
        }

        $seller = $this->getSeller();
        $buyer = $this->getBuyer();

        return Schema::invoice()
            ->identifier($this->invoice->number)
            ->name($this->invoice->number)
            ->billingPeriod(Carbon::createFromFormat('U', $this->invoice->period_start)->toDateString().'/'.Carbon::createFromFormat('U', $this->invoice->period_start)->diffAsCarbonInterval(Carbon::createFromFormat('U', $this->invoice->period_end))->spec())
            ->if(count($buyer->getProperties()), function ($schema) use ($buyer) {
                $schema->customer($buyer);
            })
            ->if(isset($this->invoice->due_date), function ($schema) {
                $schema->paymentDueDate(Carbon::createFromFormat('U', $this->invoice->due_date));
            })
            ->paymentStatus(PaymentStatusType::PaymentAutomaticallyApplied)
            ->if(count($seller->getProperties()), function ($schema) use ($seller) {
                $schema->provider($seller);
            })
            ->if(isset($this->invoice->next_payment_attempt), function ($schema) {
                $schema->scheduledPaymentDate(Carbon::createFromFormat('U', $this->invoice->next_payment_attempt));
            })
            ->totalPaymentDue(Schema::priceSpecification()
                ->price($this->invoice->total / 100)
                ->priceCurrency(strtoupper($this->invoice->currency)))
            ->if(isset($this->url), function ($schema) {
                $schema->url($this->url);
            });
    }

    /**
     * Returns the JSON-LD script tag.
     *
     * @return string|null
     */
    public function getScript()
    {
        $schema = $this->getSchema();

        if (is_null($schema)) {
            return;
        }

        return $schema->toScript();
    }

    /**
     * Returns the JSON-LD script tag.
     *
     * @return string
     */
    public function __toString()
    {
        $script = $this->getScript();

        if (is_null($script)) {
            return '';
        }

        return $script;
    }
}
