<?php

namespace Synthetic;

use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Synthetic\Synthesizers\AnonymousSynth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Blade;

class SyntheticServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->injectJavaScript();
        $this->directives();
        $this->features();
        $this->routes();
    }

    function injectJavaScript()
    {
        app('events')->listen(RequestHandled::class, function ($handled) {
            if (! str($handled->response->headers->get('content-type'))->contains('text/html')) return;

            $html = $handled->response->getContent();

            if (str($html)->contains('</html>')) {
                $csrf = csrf_token();
                $replacement = <<<EOT
                    <script>window.__csrf = '{$csrf}'</script>
                </html>
                EOT;
                $html = str($html)->replaceLast('</html>', $replacement);
                $handled->response->setContent($html->__toString());
            } else {
                //
            }
        });
    }

    function directives()
    {
        Blade::directive('synthetic', function ($expression) {
            return sprintf(
                "synthetic(<?php echo \%s::from(app('livewire')->snapshot(%s))->toHtml() ?>)",
                \Illuminate\Support\Js::class, $expression
            );
        });
    }

    function features()
    {
        foreach ([
            \Synthetic\Features\SupportComputedProperties::class,
            \Synthetic\Features\SupportJsMethods::class,
        ] as $feature) {
            (new $feature)();
        }
    }

    function routes()
    {
        Route::get('/synthetic/synthetic.js', [JavaScriptAssets::class, 'source']);
        // Route::get('/synthetic/synthetic.js.map', [JavaScriptAssets::class, 'maps']);

        // Route::post('/synthetic/new', function () {
        //     $name = request('name');

        //     return app('livewire')->new($name);
        // });
    }
}
