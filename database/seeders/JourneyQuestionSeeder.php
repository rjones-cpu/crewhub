<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\JourneyQuestion;
use App\Support\JourneyQuestionLibrary;
use Illuminate\Database\Seeder;

/**
 * Journey questions are configuration rather than operational data, so every company
 * starts with the standard assessment set. Companies stay free to edit or deactivate
 * them; re-running the seeder will not overwrite their wording.
 */
class JourneyQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $templates = JourneyQuestionLibrary::templates();

        Company::query()->each(function (Company $company) use ($templates): void {
            foreach ($templates as $position => $template) {
                JourneyQuestion::query()->firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'question' => $template['question'],
                    ],
                    [
                        'type' => $template['type'],
                        'description' => $template['description'],
                        'options' => $template['options'],
                        'risk_key' => $template['risk_key'],
                        'risk_weight' => $template['risk_weight'],
                        'is_required' => $template['is_required'],
                        'is_active' => true,
                        'sort_order' => $position + 1,
                    ],
                );
            }
        });
    }
}
