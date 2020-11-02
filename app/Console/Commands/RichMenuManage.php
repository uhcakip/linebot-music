<?php

namespace App\Console\Commands;

use App\Exceptions\CustomException;
use App\Services\RichMenuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RichMenuManage extends Command
{
    protected $signature = 'linebot:richmenu {action}';
    protected $description = '管理 Linebot Rich Menu';

    protected $richMenuService;

    /**
     * RichMenuManage constructor.
     *
     * @param RichMenuService $richMenuService
     */
    public function __construct(RichMenuService $richMenuService)
    {
        parent::__construct();

        // 注入
        $this->richMenuService = $richMenuService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            switch ($this->argument('action')) {
                case 'create':
                    $this->richMenuService->create();
                    break;

                case 'delete':
                    $this->richMenuService->delete();
                    break;

                default:
                    $this->error('參數錯誤');
                    exit;
            }

        } catch (Throwable $t) {
            Log::error($t);
            $this->error($t instanceof CustomException ? $t->getMessage() : '執行失敗');
        }
    }
}
