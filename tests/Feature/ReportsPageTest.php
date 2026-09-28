<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_report_renders_and_downloads_as_csv(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_MANAGER]));

        $this->get(route('reports.index'))->assertOk()->assertSee('Profit &amp; Loss', false);

        foreach (ReportController::REPORTS as $key => $meta) {
            $this->get(route('reports.show', $key))->assertOk()->assertSee('Print / PDF')->assertSee('Download CSV');

            $csv = $this->get(route('reports.csv', $key));
            $csv->assertOk()->assertDownload('jesco-'.$key.'-'.now()->format('Y-m-d').'.csv');
            $this->assertNotEmpty($csv->streamedContent());
        }

        $this->get('/reports/not-a-report')->assertNotFound();
    }

    public function test_sales_role_cannot_open_reports(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SALES]));

        $this->get(route('reports.index'))->assertForbidden();
        $this->get(route('reports.csv', 'sales'))->assertForbidden();
    }
}
