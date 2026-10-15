<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class DeployController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $provided = (string) ($request->query('secret')
            ?? $request->input('secret')
            ?? $request->header('X-Deploy-Secret', ''));
        $expected = (string) config('reapersventory.deploy_secret', '');

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response(
                "Forbidden. Set DEPLOY_SECRET in hosting .env, then open:\n".
                "https://reapersventory.com/deploy?secret=YOUR_SECRET\n",
                403
            )->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        $log = [];
        $log[] = '[deploy] started '.gmdate('c');
        $log[] = '[deploy] path '.base_path();

        chdir(base_path());

        if ($request->boolean('pull', true)) {
            $output = [];
            $code = 0;
            exec('git pull origin main 2>&1', $output, $code);
            $log[] = '[deploy] git pull exit='.$code;
            foreach ($output as $line) {
                $log[] = '  '.$line;
            }
        }

        foreach ([
            'config:clear',
            'cache:clear',
            'route:clear',
            'view:clear',
            'reapersventory:verify-all-users',
        ] as $command) {
            try {
                Artisan::call($command);
                $log[] = '[deploy] OK '.$command;
                $out = trim(Artisan::output());
                if ($out !== '') {
                    $log[] = '  '.$out;
                }
            } catch (Throwable $e) {
                $log[] = '[deploy] FAIL '.$command.' — '.$e->getMessage();
            }
        }

        $log[] = '[deploy] finished '.gmdate('c');

        return response(implode("\n", $log)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
