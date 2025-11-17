<?php
namespace App\Repositories\Manufacturers;

use Illuminate\Support\Str;
use App\Models\Installation;
use App\Models\Manufacturer;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;

class ManufacturerRepository implements ManufacturerRepositoryInterface
{
    /**
     * Store manufacturers and submanufacturers in the database.
     *
     * @param array $manufacturers
     * @param array $submanufacturers
     * @return void
     */
    public function store(array $manufacturers, int $installationId)
    {
        foreach ($manufacturers as $manufecturer) {
            $existing = Manufacturer::where('code', $manufecturer['MANUFACTURERCODE'])->first();

            $parentManufacturer = Manufacturer::updateOrCreate(
                ['code' => $manufecturer['MANUFACTURERCODE']],
                [
                    'name' => $manufecturer['MANUFACTURERNAME'],
                    'installation_id' => $installationId
                ]
            );


            $this->logManufacturerChange(
                $existing ? [$existing->only(['code', 'name'])] : [],
                [
                    [
                        'code' => $parentManufacturer->code,
                        'name' => $parentManufacturer->name
                    ]
                ],
                $parentManufacturer->id,
                $installationId
            );
        }
    }

    public function getManufacturersByInstallation($installationId){
        $installation = Installation::findIfActive($installationId)->first();
        if ($installation) {
            // Proceed with logic
            $manufacturers = Manufacturer::where('installation_id', $installationId)
                ->pluck('name', 'code')->toArray();

            return $manufacturers;
        } else {
            return [];
        }
    }



    /**
     * Get paginated manufacturers with optional eager loaded submanufacturers.
     *
     * @param int $perPage
     *
     * @throws \Exception on DB failure
     */
    public function manufacturers(int $perPage = 15): LengthAwarePaginator
    {
        try {
            return Manufacturer::with(['installation'])->paginate($perPage);
        } catch (\Exception $e) {
            // Log here if you want, or just rethrow
            throw new \Exception('Failed to fetch manufacturers: ' . $e->getMessage(), 0, $e);
        }
    }

    public function logManufacturerChange(array $oldData, array $newData, ?int $menufecturerId = null, $installationId)
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
                    'log_name' => 'manufacturer',
                    'description' => 'manufacturers updated',
                    'subject_type' => \App\Models\Manufacturer::class,
                    'subject_id' => $menufecturerId,
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
