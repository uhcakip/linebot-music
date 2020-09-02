<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class CustomException extends Exception
{
    public function report()
    {
        Log::error($this);
    }

    public function render()
    {
        return response()->json(['message' => $this->getMessage()], 200);
    }
}
