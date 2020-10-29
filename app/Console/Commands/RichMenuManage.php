<?php

namespace App\Console\Commands;

use App\Services\RichMenuService;
use Illuminate\Console\Command;

class RichMenuManage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'linebot:richmenu {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '管理 Linebot Rich Menu';

    protected $richMenuService;

    /**
     * Create a new command instance.
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
        $handleActions = ['create', 'delete'];
        $action        = $this->argument('action');

        if (!in_array($action, $handleActions)) {
            $this->error('參數錯誤');
            exit;
        }

        // call 對應 function
        $this->richMenuService->$action();
        $this->info('執行完成');
    }
}
