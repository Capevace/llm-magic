# Examples

## Experimentation

The following is just syntax exploration and might not work with the current version yet/anymore.

```php
<?php
use Mateffy\Magic;
use Mateffy\Magic\Tools\Prebuilt\EloquentTools;

Magic::chat()
    ->tools([
        EloquentTools::crud(\App\Models\Product::class),
        EloquentTools::crud(
            model: \App\Models\Product::class,
            update: false,
            delete: false,
        ),
        FilamentTools::crud(\App\Filament\Resources\ProductResource::class),
    ])
```

```php
use Mateffy\Magic;
use Mateffy\Magic\Tools\Prebuilt\EloquentTools;

Magic::chat()
    ->tools([
        FilamentTools::crud(\App\Filament\Resources\ProductResource::class),
    ])
```

```php
use Mateffy\Magic;
use Mateffy\Magic\Tools\Prebuilt\EloquentTools;

class LivewireChat extends Component implements HasChat
{
    use InteractsWithChat;

    public function getMagic()
    {
        return static::makeMagic()
            ->model('google/gemini-2.0-flash-lite')
            ->system('You are a Leuphana University assistant. You know a lot...');
    }

    /**
     * @throws \ReflectionException
     */
    protected static function getTools(): array
    {
        return [
            Tool::make('findFileInDownloads')
                ->widget(ClosureToolWidget::make(fn () => '<p>You just made a sandwich!</p>'))
                ->callback(function (string $search) {
                    $files = Storage::disk('downloads')->allFiles();

                    return [
                        'files' => $files
                    ];
                }),

            Tool::make('makeSandwich')
                ->callback(function (string $bread, string $filling) {
                    return [
                        'sandwich' => "A sandwich with {$bread} and {$filling}."
                    ];
                }),
        ];
    }
}
```