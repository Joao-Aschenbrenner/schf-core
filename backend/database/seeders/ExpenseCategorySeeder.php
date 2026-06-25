<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Medicamento', 'code' => 'MED'],
            ['name' => 'Material Médico', 'code' => 'MAT_MED'],
            ['name' => 'Médicos', 'code' => 'MEDICOS'],
            ['name' => 'Funcionários', 'code' => 'FUNC'],
            ['name' => 'Impostos', 'code' => 'IMP'],
            ['name' => 'Serviços', 'code' => 'SERV'],
            ['name' => 'Equipamentos', 'code' => 'EQUIP'],
            ['name' => 'Laboratório', 'code' => 'LAB'],
            ['name' => 'Diagnóstico', 'code' => 'DIAG'],
            ['name' => 'Manutenção', 'code' => 'MANUT'],
            ['name' => 'Informática', 'code' => 'INFO'],
            ['name' => 'Limpeza', 'code' => 'LIMP'],
            ['name' => 'Nutrição', 'code' => 'NUTR'],
            ['name' => 'Gases Medicinais', 'code' => 'GASES'],
            ['name' => 'Outros', 'code' => 'OUTROS'],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::create($cat);
        }
    }
}
