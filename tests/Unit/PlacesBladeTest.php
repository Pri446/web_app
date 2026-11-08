<?php

namespace Tests\Unit;

use Collective\Html\FormFacade as Form;
use Tests\TestCase;

class PlacesBladeTest extends TestCase
{
    public function test_place_form_has_the_fields_a_user_needs(): void
    {
        $html = view('places.partial-form', [
            'types' => ['Government Office', 'Hospital'],
            'submitButtonText' => 'Save',
            'geom' => '',
        ])->render();

        $this->assertStringContainsString('name="unique_reference_id"', $html);
        $this->assertStringContainsString('name="type"', $html);
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="ward"', $html);
        $this->assertStringContainsString('name="geom"', $html);
        $this->assertStringContainsString('value="Save"', $html);
    }

    public function test_edit_form_shows_saved_values_and_an_update_button(): void
    {
        $place = (object) [
            'id' => 12,
            'unique_reference_id' => 'PLACE-012',
            'type' => 'Hospital',
            'name' => 'Municipal Hospital',
            'ward' => 6,
        ];

        Form::model($place);

        try {
            $html = view('places.partial-form', [
                'types' => ['Government Office', 'Hospital'],
                'submitButtonText' => 'Update',
                'geom' => 'POINT(81.6333 28.6000)',
                'place' => $place,
            ])->render();
        } finally {
            Form::close();
        }

        $this->assertStringContainsString('value="PLACE-012"', $html);
        $this->assertStringContainsString('value="Municipal Hospital"', $html);
        $this->assertStringContainsString('value="POINT(81.6333 28.6000)"', $html);
        $this->assertStringContainsString('value="Update"', $html);
    }
}
