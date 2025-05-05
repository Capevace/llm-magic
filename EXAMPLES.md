# Examples

## Experimentation

The following is just syntax exploration and might not work with the current version yet/anymore.

```php
use Mateffy\Magic;
use Mateffy\Magic\Tools\Prebuilt\EloquentTools;

Magic::chat()
    ->tools([
        EloquentTools::crud(\App\Models\Product::class),
        EloquentTools::crud(
            model: \App\Models\Product::class,
            update: false,
            delete: false,
        )
    ])
```