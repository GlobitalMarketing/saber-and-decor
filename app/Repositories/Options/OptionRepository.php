<?php
namespace App\Repositories\Options;

use App\Models\Option;
use Illuminate\Support\Str;
use App\Models\Installation;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;

class OptionRepository implements OptionRepositoryInterface
{
    /**
     * Store options and suboptions in the database.
     *
     * @param array $options
     * @param array $suboptions
     * @return void
     */
    public function store(array $options, $type, int $installationId)
    {        
        foreach ($options as $option) {
            $existing = Option::where('code', $option['OPTIONCODE'])->where('type', $type)->first();

            $parentOption = Option::updateOrCreate(
                ['code' => $option['OPTIONCODE']],
                [
                    'name' => $option['OPTIONNAME'],
                    'type' => $type,
                    'installation_id' => $installationId
                ]
            );
            
            
            $this->logoptionChange(
                $existing ? [$existing->only(['code', 'name', 'type'])] : [],
                [
                    [
                        'code' => $parentOption->code,
                        'name' => $parentOption->name,
                        'type' => $parentOption->type
                    ]
                ],
                $parentOption->id,
                $installationId
            );
        }
    }

    public function getSizesByInstallation($installationId){
        $installation = Installation::findIfActive($installationId)->first();
        if ($installation) {
            // Proceed with logic
            $sizes = Option::where('installation_id', $installationId)
                ->where('type', 'size')
                ->pluck('name', 'code')->toArray();

            return $sizes;
        } else {
            return [];
        }
    }

    public function getColorsByInstallation($installationId){
        $installation = Installation::findIfActive($installationId)->first();
        if ($installation) {
            // Proceed with logic
            $colors = Option::where('installation_id', $installationId)
                ->where('type', 'colour')
                ->pluck('name', 'code')->toArray();

            return $colors;
        } else {
            return [];
        }
    }



    /**
     * Get paginated options with optional eager loaded suboptions.
     *
     * @param int $perPage
     *
     * @throws \Exception on DB failure
     */
    public function options(int $perPage = 15): LengthAwarePaginator
    {
        try {
            return Option::with(['installation'])->paginate($perPage);
        } catch (\Exception $e) {
            // Log here if you want, or just rethrow
            throw new \Exception('Failed to fetch options: ' . $e->getMessage(), 0, $e);
        }
    }

    public function logoptionChange(array $oldData, array $newData, ?int $optionId = null, $installationId)
    {
        $changed = [];

        foreach ($newData as $newItem) {
            $oldItem = collect($oldData)->firstWhere('code', $newItem['code']);

            if (!$oldItem) {
                $changed[] = [
                    'old' => null,
                    'attributes' => $newItem,
                ];
            } elseif ($oldItem != $newItem) {
                $changed[] = [
                    'old' => $oldItem,
                    'attributes' => $newItem,
                ];
            }
        }

        if (!empty($changed)) {
            // Determine event type based on whether any old items existed
            $hasOld = collect($changed)->contains(fn ($item) => !is_null($item['old']));
            $eventType = $hasOld ? 'update' : 'create';

            try {
                Activity::create([
                    'log_name' => 'option',
                    'description' => 'options updated',
                    'subject_type' => \App\Models\Option::class,
                    'subject_id' => $optionId,
                    'causer_type' => 'user',
                    'causer_id' => $installationId,
                    'event' => $eventType,
                    'batch_uuid' => (string) Str::uuid(),
                    'properties' => ['changes' => $changed],
                ]);
            } catch (\Throwable $e) {
                \Log::error('Activity log failed: ' . $e->getMessage());
            }
        }
    }
}
