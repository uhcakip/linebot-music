<?php

namespace App\Http\Controllers;

use App\Services\RecordService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecordController extends Controller
{
    protected $recordService;

    public function __construct(RecordService $recordService)
    {
        // 注入
        $this->recordService = $recordService;
    }

    public function handle(Request $request)
    {
        try {
            $events = $request->input('events.0');
            $this->recordService->handle($events);
        } catch (Exception $ex) {
            Log::error($ex);
        }

    }
}
