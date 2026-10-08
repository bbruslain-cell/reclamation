<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicDemandRouteTest extends TestCase
{
    public function test_reclamations_url_redirects_to_the_public_form(): void
    {
        $this->get('/reclamations')
            ->assertRedirect('/reclamations/nouvelle');
    }
}
