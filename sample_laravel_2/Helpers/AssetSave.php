<?php

namespace App\Helpers;

use App\ConferenceAsset;
use Carbon\Carbon;

trait AssetSave
{

    /**
     * Proceed conference asset save
     *
     * @param $file
     * @param $assetType
     *
     * @return bool|mixed
     */
    protected function saveFile($file, $assetType = 'default') {
        if (!$file) {
            return false;
        }
        try {

            if (!$this->conference) {
                \Log::info('AssetSave@savePhoto Conference not found', [$this->conference]);

                return false;
            }

            $fileExt = explode('/', explode(':', substr($file['file'], 0, strpos($file['file'], ';')))[1])[1];
            if ($fileExt !== 'jpeg') {
                $fileNameParts = explode('.', $file['name']);
                $fileExt = $fileNameParts[count($fileNameParts) - 1];
            }
            $photoName = Carbon::now()->timestamp . uniqid() . '.' . $fileExt;
            $photoPath = storage_path('app/public/cms/' . $this->conference->url_slug . '/' . $assetType);

            $base64_str = substr($file['file'], strpos($file['file'], ",") + 1);

            if (!is_dir(storage_path('app/public/cms/' . $this->conference->url_slug))) {
                mkdir(storage_path('app/public/cms/' . $this->conference->url_slug));
            }

            if (!is_dir($photoPath)) {
                mkdir($photoPath);
            }

            file_put_contents($photoPath . '/' . $photoName, base64_decode($base64_str));

            $fileData = getimagesize($photoPath . '/' . $photoName);

            if (!$fileData) {
                $fileData = [
                    0      => null,
                    1      => null,
                    'bits' => filesize($photoPath . '/' . $photoName),
                ];
            }

            $asset = new ConferenceAsset();

            $asset->filename = $file['name'] ?: $photoName;
            $asset->size = $fileData['bits'];
            $asset->width = $fileData[0];
            $asset->height = $fileData[1];
            $asset->path = '/storage/cms/' . $this->conference->url_slug . '/' . $assetType . '/' . $photoName;
            $asset->type = $assetType;
            $asset->conference_id = $this->conference->id;

            $asset->save();

            return $asset->id;
        } catch (\Exception $e) {
            \Log::info('AssetSave@savePhoto Exception', [$e->getMessage(), $e->getFile(), $e->getCode()]);

            return false;
        }
    }

}
