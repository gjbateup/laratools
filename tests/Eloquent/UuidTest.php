<?php

namespace Laratools\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use PHPUnit_Framework_TestCase;
use Laratools\Eloquent\Uuid;
use Ramsey\Uuid\Uuid as UuidGenerator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class UuidTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $db = new DB();

        $db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        Eloquent::setEventDispatcher(new \Illuminate\Events\Dispatcher());

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();

        UuidInvoice::flushEventListeners();
        UuidInvoice::boot();

        RenamedUuidColumnProject::flushEventListeners();
        RenamedUuidColumnProject::boot();
    }

    /**
     * Setup database schema
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('invoices', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('uuid', 36);
        });

        $this->schema()->create('projects', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('guid', 36);
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('invoices');
        $this->schema()->drop('projects');
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    public function test_it_should_automatically_generate_a_uuid_when_creating_a_model()
    {
        $invoice = UuidInvoice::create();

        $this->assertNotNull($invoice->uuid);
    }

    public function test_it_should_not_generate_a_uuid_if_one_has_already_been_set()
    {
        $uuid = (string) UuidGenerator::uuid4();

        $invoice = UuidInvoice::create(['uuid' => $uuid]);

        $this->assertSame($uuid, $invoice->uuid);
    }

    public function test_it_should_fetch_a_model_using_the_uuid_scope()
    {
        $a = UuidInvoice::create();
        $b = UuidInvoice::create();
        $c = UuidInvoice::create();

        $invoice = UuidInvoice::uuid($b->uuid)->first();

        $this->assertInstanceOf(UuidInvoice::class, $invoice);
        $this->assertSame($b->uuid, $invoice->uuid);
        $this->assertSame($b->uuid_text, $invoice->uuid_text);
    }

    public function test_it_should_detect_a_custom_column()
    {
        $invoice = RenamedUuidColumnProject::create();

        $this->assertSame('guid', $invoice->getUuidColumn());
    }

    public function test_it_should_automatically_generate_a_uuid_when_creating_a_model_with_a_custom_column()
    {
        $invoice = RenamedUuidColumnProject::create();

        $this->assertNotNull($invoice->guid);
    }

    public function test_it_should_not_automatically_generate_a_uuid_if_one_has_already_been_set_with_a_custom_column()
    {
        $uuid = (string) UuidGenerator::uuid4();

        $invoice = RenamedUuidColumnProject::create(['guid' => $uuid]);

        $this->assertSame($uuid, $invoice->guid);
    }

    public function test_it_should_fetch_a_model_using_the_uuid_scope_when_using_a_custom_column()
    {
        $a = RenamedUuidColumnProject::create();
        $b = RenamedUuidColumnProject::create();
        $c = RenamedUuidColumnProject::create();

        $project = RenamedUuidColumnProject::guid($b->guid)->first();

        $this->assertInstanceOf(RenamedUuidColumnProject::class, $project);
        $this->assertSame($b->guid, $project->guid);
    }
}

/**
 * Models
 */
class UuidInvoice extends Eloquent
{
    use Uuid;

    public $timestamps = false;

    protected $table = 'invoices';

    protected $guarded = [];
}

class RenamedUuidColumnProject extends Eloquent
{
    use Uuid;

    const UUID_COLUMN = 'guid';

    public $timestamps = false;

    protected $table = 'projects';

    protected $guarded = [];

    public function scopeGuid(Builder $query, string $uuid)
    {
        return $this->scopeUuid($query, $uuid);
    }
}
