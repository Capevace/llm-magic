# `@mateffy/llm-magic`

> [!NOTE]
> This project is still in development and not yet publicly released. API will change!

A PHP SDK to do magical things with LLMs using just a few lines of code.
Also enables developer-friendly extraction of structured and validated JSON data from files such as PDFs, Word documents, and images.

```php
use Mateffy\Magic;
use Mateffy\Magic\Chat\Messages\Step;
use Mateffy\Magic\Chat\Tool;

$answer = Magic::ask('What is the capital of France?');
// -> "The capital of France is Paris."

$messages = Magic::chat()
    ->model('google/gemini-2.0-flash-lite')
    ->temperature(0.5)
    ->messages([
        Step::user([
            Step\Text::make('What is in this picture and where was it taken?'),
            Step\Image::make('https://example.com/eiffel-tower.jpg'),
        ]),
        Step::assistant([
            Step\Text::make('The picture shows the Eiffel Tower, which is located in Paris, France.'),
        ]),
        Step::user('How much is a flight to Paris?'),
    ])
    ->tools([
        Tool::make('search_flight')
            ->description('Search for flights to a given destination. Pass the departure airport code and the destination airport code in the ISO 3166-1 alpha-3 format.')
            ->callback(fn (string $from_airport_code, string $to_airport_code) {
                return app(FlightService::class)
                    ->search($from_airport_code, $to_airport_code)
                    ->toArray();
            }),
    ]);
])

$answer = Magic::chat()
    ->model('google/gemini-2.0-flash-lite')
    ->prompt('What is the capital of France?')
    ->stream()
    ->text();
    
$data = Magic::extract()
    ->schema([
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer'],
        ],
        'required' => ['name', 'age'],
    ])
    ->artifacts([$artifact])
    ->send();
```


### Copyright and License

This project is made by [Lukas Mateffy](https://mateffy.me) and is licensed under the [GNU Affero General Public License v3.0 (AGPL-3.0)](https://choosealicense.com/licenses/agpl-3.0/).

For commercial licensing, please drop me an email at [hey@mateffy.me](mailto:hey@mateffy.me).

### Contributing

At the moment, this project is not yet open for contributions, as I am in the process of writing a thesis about it. After that is done, and the published version is tagged, I may open it up for contributions, if there is interest.

However, if you have ideas, bugs or suggestions, feel free to open an issue or start a discussion anyway! Feedback is always welcome.
