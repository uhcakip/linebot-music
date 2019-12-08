<?php

namespace App\Http\Controllers;

use App\Services\LineBotService;
use Illuminate\Http\Request;
use LINE\LINEBot\Constant\HTTPHeader;

class LineBotController extends Controller
{
    protected $lineBotService;

    public function __construct(LineBotService $lineBotService)
    {
        $this->lineBotService = $lineBotService;
    }

    public function index(Request $request)
    {
        // 取得訊息相關資訊
        $response = $request->getContent();
        $signature = $request->header(HTTPHeader::LINE_SIGNATURE);
        $this->lineBotService->handle($response, $signature);
    }

    public function createRichMenu()
    {
        $this->lineBotService->createRichMenu(true);
    }
}
