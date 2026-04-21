<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelEnso\DataImport\Models\Import;
use LaravelEnso\Forms\TestTraits\CreateForm;
use LaravelEnso\Forms\TestTraits\DestroyForm;
use LaravelEnso\Forms\TestTraits\EditForm;
use LaravelEnso\Helpers\Services\Obj;
use LaravelEnso\PackagingUnits\Imports\Importers\PackagingUnits as Importer;
use LaravelEnso\PackagingUnits\Models\PackagingUnit;
use LaravelEnso\Tables\Traits\Tests\Datatable;
use LaravelEnso\Users\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PackagingUnitsTest extends TestCase
{
    use CreateForm, Datatable, DestroyForm, EditForm, RefreshDatabase;

    private string $permissionGroup = 'administration.packagingUnits';
    private PackagingUnit $testModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed()
            ->actingAs(User::first());

        $this->testModel = PackagingUnit::factory()->make();
    }

    #[Test]
    public function can_store_packaging_unit(): void
    {
        $response = $this->post(
            route('administration.packagingUnits.store', [], false),
            $this->testModel->toArray()
        );

        $packagingUnit = PackagingUnit::query()
            ->whereName($this->testModel->name)
            ->first();

        $response->assertStatus(200)
            ->assertJsonStructure(['message'])
            ->assertJsonFragment([
                'redirect' => 'administration.packagingUnits.edit',
                'param' => ['packagingUnit' => $packagingUnit?->id],
            ]);

        $this->assertNotNull($packagingUnit);
    }

    #[Test]
    public function validates_required_name_on_store(): void
    {
        $this->post(route('administration.packagingUnits.store', [], false), [
            'name' => null,
            'description' => $this->testModel->description,
        ])->assertStatus(302)
            ->assertSessionHasErrors(['name']);
    }

    #[Test]
    public function validates_unique_name_on_store(): void
    {
        $this->testModel->save();

        $this->post(route('administration.packagingUnits.store', [], false), [
            'name' => $this->testModel->name,
            'description' => 'Duplicate',
        ])->assertStatus(302)
            ->assertSessionHasErrors(['name']);
    }

    #[Test]
    public function can_update_packaging_unit(): void
    {
        $this->testModel->save();
        $this->testModel->description = 'Updated description';

        $this->patch(
            route('administration.packagingUnits.update', $this->testModel->id, false),
            $this->testModel->toArray()
        )->assertStatus(200)
            ->assertJsonStructure(['message']);

        $this->assertSame('Updated description', $this->testModel->fresh()->description);
    }

    #[Test]
    public function get_option_list(): void
    {
        $this->testModel->save();

        $this->get(route('administration.packagingUnits.options', [
            'query' => $this->testModel->name,
            'limit' => 10,
        ], false))->assertStatus(200)
            ->assertJsonFragment(['name' => $this->testModel->name]);
    }

    #[Test]
    public function can_import_packaging_unit(): void
    {
        (new Importer())->run(new Obj([
            'name' => 'Imported Package',
            'description' => 'Imported description',
        ]), \Mockery::mock(Import::class));

        $this->assertDatabaseHas('packaging_units', [
            'name' => 'Imported Package',
            'description' => 'Imported description',
        ]);
    }
}
