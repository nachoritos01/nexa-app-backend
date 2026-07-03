<?php

namespace Database\Seeders;

use App\Models\Agency\AgencySetting;
use App\Models\Agency\Client;
use App\Models\Agency\Expense;
use App\Models\Agency\Invoice;
use App\Models\Agency\Project;
use App\Models\Agency\Quote;
use App\Models\Agency\Service;
use App\Models\Agency\Supplier;
use App\Models\Agency\TeamMember;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeds a small, cross-referenced agency dataset for the admin panel.
 * Idempotent: clears the tenant's agency rows before reseeding.
 */
class AgencyDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->first();
        if (! $tenant) {
            $this->command->warn('No tenant found; skipping AgencyDemoSeeder.');

            return;
        }
        $tid = $tenant->id;

        // Clear existing agency data for this tenant (idempotent reseed).
        foreach ([Project::class, Quote::class, Invoice::class, Expense::class, Client::class, Service::class, Supplier::class, TeamMember::class, AgencySetting::class] as $model) {
            $model::query()->where('tenant_id', $tid)->delete();
        }

        $today = now();
        $at = fn (int $days) => $today->copy()->addDays($days)->toDateString();

        // ---- Clients ----
        $clients = collect([
            ['name' => 'Grupo Financiero Aurora', 'contact_name' => 'Ricardo Delgado', 'email' => 'ricardo@aurora.mx', 'industry' => 'Servicios financieros', 'status' => 'Activo', 'pipeline_stage' => 'Ganado', 'origin' => 'Referido', 'potential_value' => 480000, 'phone' => '+52 55 1234 5678'],
            ['name' => 'TechNova Solutions', 'contact_name' => 'Alejandra Vega', 'email' => 'ale@technova.io', 'industry' => 'Tecnología', 'status' => 'Activo', 'pipeline_stage' => 'Negociación', 'origin' => 'Google', 'potential_value' => 220000],
            ['name' => 'Sabores del Valle', 'contact_name' => 'Manuel Ortega', 'email' => 'manuel@sabores.mx', 'industry' => 'Gastronomía', 'status' => 'Activo', 'pipeline_stage' => 'Propuesta enviada', 'origin' => 'Redes sociales', 'potential_value' => 90000],
            ['name' => 'Fundación Camino Verde', 'contact_name' => 'Carmen Reyes', 'email' => 'carmen@caminoverde.org', 'industry' => 'ONG', 'status' => 'Prospecto', 'pipeline_stage' => 'Lead', 'origin' => 'Evento', 'potential_value' => 60000],
        ])->map(fn ($c) => Client::create(array_merge($c, ['tenant_id' => $tid])));

        // ---- Team ----
        $team = collect([
            ['name' => 'Sofía Herrera', 'email' => 'sofia@nexadigital.studio', 'role' => 'manager', 'department' => 'Dirección', 'salary' => 45000],
            ['name' => 'Diego Ramírez', 'email' => 'diego@nexadigital.studio', 'role' => 'developer', 'department' => 'Desarrollo', 'salary' => 38000],
            ['name' => 'Valeria Cruz', 'email' => 'valeria@nexadigital.studio', 'role' => 'designer', 'department' => 'Diseño', 'salary' => 32000],
            ['name' => 'Andrés Soto', 'email' => 'andres@nexadigital.studio', 'role' => 'marketing', 'department' => 'Marketing', 'salary' => 30000],
        ])->map(fn ($m) => TeamMember::create(array_merge($m, ['tenant_id' => $tid, 'is_active' => true])));

        // ---- Services ----
        collect([
            ['name' => 'Desarrollo Web Corporativo', 'category' => 'web', 'base_price' => 65000, 'estimated_hours' => 160],
            ['name' => 'App Móvil', 'category' => 'mobile', 'base_price' => 120000, 'estimated_hours' => 320],
            ['name' => 'Identidad de Marca', 'category' => 'branding', 'base_price' => 45000, 'estimated_hours' => 80],
            ['name' => 'Campaña de Marketing Digital', 'category' => 'marketing', 'base_price' => 35000, 'estimated_hours' => 60],
            ['name' => 'Diseño UX/UI', 'category' => 'design', 'base_price' => 40000, 'estimated_hours' => 100],
        ])->each(fn ($s) => Service::create(array_merge($s, ['tenant_id' => $tid, 'is_active' => true, 'description' => 'Servicio de agencia.'])));

        // ---- Suppliers ----
        collect([
            ['name' => 'Hostinger Cloud', 'email' => 'billing@hostinger.com', 'category' => 'Hosting', 'contact_name' => 'Soporte'],
            ['name' => 'Adobe', 'email' => 'enterprise@adobe.com', 'category' => 'Licencias de software', 'contact_name' => 'Cuentas'],
            ['name' => 'Imprenta Offset MX', 'email' => 'ventas@offsetmx.com', 'category' => 'Imprenta', 'contact_name' => 'Luis Parra'],
        ])->each(fn ($s) => Supplier::create(array_merge($s, ['tenant_id' => $tid, 'is_active' => true])));

        // ---- Projects (reference clients + team) ----
        Project::create(['tenant_id' => $tid, 'name' => 'Portal Aurora 2.0', 'description' => 'Rediseño del portal de clientes.', 'client_id' => $clients[0]->id, 'type' => 'web_development', 'status' => 'in_progress', 'priority' => 'high', 'budget' => 180000, 'start_date' => $at(-30), 'end_date' => $at(20), 'team_members' => [$team[1]->id, $team[2]->id], 'tasks' => [['id' => 't1', 'name' => 'Wireframes', 'status' => 'completed', 'assignedTo' => [$team[2]->id], 'createdAt' => $at(-28)], ['id' => 't2', 'name' => 'Frontend', 'status' => 'in_progress', 'assignedTo' => [$team[1]->id], 'createdAt' => $at(-20)]], 'progress' => 55]);
        Project::create(['tenant_id' => $tid, 'name' => 'Rebranding TechNova', 'description' => 'Nueva identidad de marca.', 'client_id' => $clients[1]->id, 'type' => 'branding', 'status' => 'review', 'priority' => 'medium', 'budget' => 75000, 'start_date' => $at(-45), 'end_date' => $at(5), 'team_members' => [$team[2]->id, $team[3]->id], 'tasks' => [], 'progress' => 80]);
        Project::create(['tenant_id' => $tid, 'name' => 'Social Media Sabores', 'description' => 'Gestión de redes Q2.', 'client_id' => $clients[2]->id, 'type' => 'social_media', 'status' => 'in_progress', 'priority' => 'low', 'budget' => 30000, 'start_date' => $at(-15), 'end_date' => $at(45), 'team_members' => [$team[3]->id], 'tasks' => [], 'progress' => 35]);
        Project::create(['tenant_id' => $tid, 'name' => 'Campaña Aurora Verano', 'description' => 'Pauta y creatividades.', 'client_id' => $clients[0]->id, 'type' => 'marketing', 'status' => 'completed', 'priority' => 'medium', 'budget' => 60000, 'start_date' => $at(-120), 'end_date' => $at(-30), 'team_members' => [$team[3]->id], 'tasks' => [], 'progress' => 100]);

        // ---- Quotes ----
        $items = fn (array $rows) => collect($rows)->map(fn ($r, $i) => ['id' => "it-$i", 'description' => $r[0], 'quantity' => $r[1], 'unitPrice' => $r[2], 'total' => $r[1] * $r[2]])->all();
        Quote::create(['tenant_id' => $tid, 'number' => 'COT-000101', 'client_id' => $clients[1]->id, 'date' => $at(-10), 'valid_until' => $at(5), 'status' => 'sent', 'items' => $items([['Identidad de marca', 1, 45000], ['Sitio web', 1, 65000]]), 'discount' => 5, 'tax' => 16, 'subtotal' => 110000, 'total' => 121264, 'terms' => 'Pago 50/50.']);
        Quote::create(['tenant_id' => $tid, 'number' => 'COT-000102', 'client_id' => $clients[2]->id, 'date' => $at(-4), 'valid_until' => $at(11), 'status' => 'draft', 'items' => $items([['Campaña social media', 3, 12000]]), 'discount' => 0, 'tax' => 16, 'subtotal' => 36000, 'total' => 41760]);

        // ---- Invoices ----
        Invoice::create(['tenant_id' => $tid, 'number' => 'FAC-000201', 'client_id' => $clients[0]->id, 'date' => $at(-20), 'due_date' => $at(10), 'status' => 'sent', 'items' => $items([['Anticipo Portal 2.0', 1, 90000]]), 'subtotal' => 90000, 'tax' => 16, 'total' => 104400]);
        Invoice::create(['tenant_id' => $tid, 'number' => 'FAC-000202', 'client_id' => $clients[1]->id, 'date' => $at(-40), 'due_date' => $at(-10), 'status' => 'paid', 'items' => $items([['Rebranding TechNova', 1, 75000]]), 'subtotal' => 75000, 'tax' => 16, 'total' => 87000]);
        Invoice::create(['tenant_id' => $tid, 'number' => 'FAC-000203', 'client_id' => $clients[0]->id, 'date' => $at(-55), 'due_date' => $at(-25), 'status' => 'overdue', 'items' => $items([['Campaña Verano', 1, 60000]]), 'subtotal' => 60000, 'tax' => 16, 'total' => 69600]);

        // ---- Expenses ----
        collect([
            ['description' => 'Hosting mensual', 'amount' => 2500, 'category' => 'hosting', 'status' => 'paid', 'date' => $at(-5)],
            ['description' => 'Licencias Adobe CC', 'amount' => 4800, 'category' => 'software', 'status' => 'paid', 'date' => $at(-12)],
            ['description' => 'Pauta Meta Ads', 'amount' => 15000, 'category' => 'marketing', 'status' => 'pending', 'date' => $at(-3)],
            ['description' => 'Nómina quincena', 'amount' => 86500, 'category' => 'payroll', 'status' => 'paid', 'date' => $at(-15)],
        ])->each(fn ($e) => Expense::create(array_merge($e, ['tenant_id' => $tid])));

        // ---- Settings ----
        AgencySetting::create(['tenant_id' => $tid, 'data' => [
            'companyName' => 'NexaDigital Studio',
            'companyEmail' => 'contacto@nexadigital.studio',
            'companyPhone' => '+52 55 1234 5678',
            'currency' => 'MXN',
            'defaultTax' => 16,
            'invoicePrefix' => 'FAC',
            'quotePrefix' => 'COT',
            'theme' => 'system',
            'language' => 'es',
        ]]);

        $this->command->info('Agency demo data seeded.');
    }
}
