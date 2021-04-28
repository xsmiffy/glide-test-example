<?php

namespace App\Console\Commands;

use \App\Models\Area;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateCalorificValues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'populate:calorific-values';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import calorific values from National Grid';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $gasCalorificValues = $this->getDataFromNationalGrid();
        
        $areas = Area::all();

        foreach ($gasCalorificValues as $gasCalorificValueData) {

            $matches = [];

            preg_match('/Calorific Value, LDZ\((\w+)\)/', $gasCalorificValueData['Data Item'], $matches);

            if (!isset($matches[1])) {
                continue;
            }

            $areaCode = $matches[1];

            // Attempt to find an Area match, otherwise create it
            $correspondingArea = $areas->first(fn ($area) => $area->name === $areaCode, false);

            if (!$correspondingArea) {
                $correspondingArea = new \App\Models\Area;
                $correspondingArea->name = $areaCode;
                $correspondingArea->save();

                $areas->push($correspondingArea);
            }

            $applicableFor = \DateTime::createFromFormat('d/m/Y', $gasCalorificValueData['Applicable For'])->format('Y-m-d');

            $existingCalorificValue = DB::selectOne('
                SELECT id, applicable_for, value, area_id
                FROM gas_calorific_values
                WHERE applicable_for = ? AND area_id = ?
            ', [$applicableFor, $correspondingArea->id]);

            if (!$existingCalorificValue) {
                DB::insert('
                    INSERT INTO gas_calorific_values (applicable_for, value, area_id, created_at, updated_at)
                    VALUES (?, ?, ?, NOW(), NOW())
                ', [$applicableFor, $gasCalorificValueData['Value'], $correspondingArea->id]);
            }

            if ($existingCalorificValue && $existingCalorificValue->value !== (double)$gasCalorificValueData['Value']) {
                // Update
                DB::update('
                    UPDATE gas_calorific_values
                    SET value = ?
                    WHERE id = ?
                ', [$gasCalorificValueData['Value'], $existingCalorificValue->id]);
            }

        }
    }

    private function getDataFromNationalGrid()
    {
        $url = 'http://mip-prd-web.azurewebsites.net/DataItemViewer/DownloadFile';

        $requestParams = [
            'LatestValue' => 'value',
            'PublicationObjectIds' => '408:5328,408:5320,408:5291,408:5366,408:5312,408:5346,408:5324,408:5316,408:5308,408:5336,408:5333,408:5342,408:5354',
            'Applicable' => 'ApplicableAt',
            'FromUtcDatetime' => '2021-01-01T00:00:00',
            // 'FromUtcDatetime' => '2021-04-27T00:00:00',
            'ToUtcDateTime' => '2021-04-27T23:00:00',
            'FileType' => 'Csv'
        ];

        $contents = file_get_contents($url . '?' . http_build_query($requestParams));

        $rows = explode("\r\n", $contents);

        $headers = str_getcsv(array_shift($rows));

        $gasCalorificValues = [];

        foreach ($rows as $key => $row) {
            $parsedData = [];
            
            if ($row === '') {
                continue;
            }

            $data = str_getcsv($row);

            foreach ($data as $key => $value) {
                $parsedData[$headers[$key]] = $value;
            }
            
            $gasCalorificValues[] = $parsedData;
        }

        return $gasCalorificValues;
    }

}
