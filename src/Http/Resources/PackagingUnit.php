<?php

namespace LaravelEnso\PackagingUnits\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PackagingUnit extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
