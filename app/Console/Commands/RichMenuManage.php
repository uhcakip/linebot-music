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
    protected $description = '管理 Rich Menu';

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
        switch ($this->argument('action')) {
            case 'create':
                $this->richMenuService->create();
                break;
            case 'delete':
                $all = $this->choice('Delete all ?', ['Y', 'N']);
                if ($all === 'Y') $this->richMenuService->deleteAll();
                else $this->richMenuService->delete('');
                break;
        }

        $this->info('執行完成');
    }
}
